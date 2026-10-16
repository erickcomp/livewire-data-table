<?php

namespace ErickComp\LivewireDataTable\Support;

use ErickComp\LivewireDataTable\Exceptions\ErrorMessageForUserException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Livewire\Component as LivewireComponent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns what an action handler returned into what the user gets:
 *
 * - null (or any value not listed below) shows the default message;
 * - a string shows it as a success message, and an ActionResult shows its message;
 * - a redirect (redirect()->to(...), redirect()->route(...), a RedirectResponse) redirects the page;
 * - a download (response()->download(...), response()->streamDownload(...), Storage::download(...)) or a Responsable
 *   that makes one is handed over to Livewire, which sends the file to the browser.
 *
 * An ErrorMessageForUserException thrown by the handler shows its message as an error (see run()).
 */
class ActionResultHandler
{
    /** The message to show, if any */
    public ?ActionResult $message = null;

    /** What the Livewire action returns: the download, if any */
    public mixed $response = null;

    final public function __construct() {}

    /**
     * @param array<string, string> $replace Placeholders replaced in the messages, e.g. [':count' => '3']
     */
    public static function handle(mixed $result, LivewireComponent $component, string $defaultMessage, array $replace = []): static
    {
        $handled = new static();

        match (true) {
            $result instanceof ActionResult => $handled->message = $result,
            \is_string($result) && \trim($result) !== '' => $handled->message = ActionResult::success($result),
            $result instanceof RedirectResponse => $component->redirect($result->getTargetUrl()),
            $result instanceof Redirector => null,
            $result instanceof BinaryFileResponse, $result instanceof StreamedResponse, $result instanceof Responsable => $handled->response = $result,
            $result instanceof SymfonyResponse => throw new \LogicException('An action can only return a redirect or a download, not a [' . $result::class . ']'),
            default => $handled->message = ActionResult::success($defaultMessage),
        };

        if ($handled->message !== null && !empty($replace)) {
            $handled->message = new ActionResult($handled->message->type, \strtr($handled->message->message, $replace));
        }

        return $handled;
    }

    /**
     * Calls the handler and handles its result. An ErrorMessageForUserException becomes an error message.
     *
     * @param array<string, mixed> $parameters The parameters the handler may ask for, by name or by class
     * @param array<string, string> $replace Placeholders replaced in the messages, e.g. [':count' => '3']
     */
    public static function run(ServerCallable $handler, array $parameters, LivewireComponent $component, string $defaultMessage, array $replace = []): static
    {
        try {
            $result = $handler->call($parameters);
        } catch (ErrorMessageForUserException $e) {
            $result = ActionResult::error($e->getMessage());
        }

        return static::handle($result, $component, $defaultMessage, $replace);
    }

    public function failed(): bool
    {
        return $this->message?->isError() ?? false;
    }
}
