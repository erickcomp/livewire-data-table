<?php

namespace ErickComp\LivewireDataTable\Export;

use ErickComp\LivewireDataTable\DataTable\Export;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Where finished exports wait for the browser to download them: a directory of a filesystem disk ("export.disk" and
 * "export.directory" in the config).
 *
 * Each file is downloaded once, through a signed URL that expires after "export.download-ttl" minutes and only works
 * in the session that made the export. The file is streamed to the browser, never held whole in memory, and deleted
 * once sent. Files never downloaded are deleted by the next export, once their URL has expired.
 */
class ExportFiles
{
    public const ROUTE_NAME = 'lw-data-table.export';

    /** Extra time before an expired file is deleted, so that a URL never outlives its file */
    protected const PRUNE_MARGIN_SECONDS = 60;

    /**
     * Moves a written export to the disk and returns the URL that downloads it, relative to the site's root (the
     * browser resolves it against the page, whatever the scheme and host the server sees)
     *
     * @param string $localPath The written file, left in place
     * @param string $downloadName The name the browser saves the file with
     */
    public function store(string $localPath, string $downloadName): string
    {
        $this->prune();

        $file = Str::random(40);

        if ($this->disk()->putFileAs($this->directory(), $localPath, $file) === false) {
            throw new \RuntimeException('Could not store the export on the disk [' . $this->diskName() . ']');
        }

        $url = URL::temporarySignedRoute(
            static::ROUTE_NAME,
            \now()->addMinutes($this->ttlMinutes()),
            ['file' => $file, 'name' => $downloadName, 'owner' => $this->owner()],
            absolute: false,
        );

        // A site served from a subdirectory: the relative URL starts at the site's root, not the domain's
        return \request()->getBaseUrl() . $url;
    }

    /**
     * Streams a stored export, then deletes it. The route has already checked the signature.
     */
    public function download(Request $request, string $file): Response
    {
        $owner = $request->query('owner');

        if (!\is_string($owner) || !\hash_equals($this->owner(), $owner)) {
            \abort(403);
        }

        $path = $this->path($file);
        $disk = $this->disk();

        if (!$disk->exists($path)) {
            \abort(404);
        }

        $name = (string) $request->query('name');

        $headers = [
            'Content-Type' => Export::CONTENT_TYPES[\strtolower(\pathinfo($name, PATHINFO_EXTENSION))] ?? 'application/octet-stream',
            'Content-Length' => (string) $disk->size($path),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, \str_replace('%', '', Str::ascii($name))),
            'Cache-Control' => 'no-store, private',
        ];

        // A HEAD request (some download managers send one first) must not use up the download
        if ($request->isMethod('HEAD')) {
            return new Response('', 200, $headers);
        }

        return new StreamedResponse(function () use ($disk, $path) {
            $stream = $disk->readStream($path);

            // Downloaded by another request meanwhile
            if (!\is_resource($stream)) {
                return;
            }

            try {
                \fpassthru($stream);
            } finally {
                \fclose($stream);
                $disk->delete($path);
            }
        }, 200, $headers);
    }

    /**
     * Deletes the files whose URL has expired, never downloaded
     */
    public function prune(): void
    {
        $disk = $this->disk();
        $expiredBefore = \now()->subMinutes($this->ttlMinutes())->subSeconds(static::PRUNE_MARGIN_SECONDS)->getTimestamp();

        foreach ($disk->files($this->directory()) as $path) {
            try {
                if ($disk->lastModified($path) < $expiredBefore) {
                    $disk->delete($path);
                }
            } catch (\Throwable) {
                // Downloaded, or deleted by another export, meanwhile: the cleanup is done as it can be
            }
        }
    }

    /**
     * Ties a download to the session that made the export. The session id itself never goes into the URL.
     */
    protected function owner(): string
    {
        return \hash('sha256', \session()->getId());
    }

    protected function path(string $file): string
    {
        return $this->directory() . '/' . $file;
    }

    protected function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    protected function diskName(): string
    {
        return (string) (\config('erickcomp-livewire-data-table.export.disk') ?? 'local');
    }

    protected function directory(): string
    {
        return \trim((string) \config('erickcomp-livewire-data-table.export.directory', 'lw-data-table-exports'), '/');
    }

    protected function ttlMinutes(): int
    {
        return \max((int) \config('erickcomp-livewire-data-table.export.download-ttl', 5), 1);
    }
}
