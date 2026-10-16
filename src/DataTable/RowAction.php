<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\CreatesFromComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\DefinesAction;
use ErickComp\LivewireDataTable\Support\ServerCallable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * An action on a single row (<x-data-table.row-action>), shown in the row actions column. It either leads somewhere,
 * like a link (url or route, with any HTTP method), or runs on the server, like a bulk action (handler or event).
 *
 * Kept in the data table definition, which goes into the file cache, so it holds no closures.
 */
class RowAction
{
    use CreatesFromComponentAttributeBag;
    use DefinesAction;

    public const METHOD_GET = 'get';
    public const METHODS = [self::METHOD_GET, 'post', 'put', 'patch', 'delete'];

    /** "{field}" in a URL or in a question: the value of that field of the row */
    protected const PLACEHOLDER_PATTERN = '/\{([\w.\-]+)\}/';

    public string $name;
    public string $label;

    /** A URL with "{field}" placeholders, filled with the row's values (URL-encoded) */
    public ?string $url = null;

    public ?string $route = null;

    /** @var array<string, string> The route parameters, each filled with the value of a field of the row */
    public array $routeParams = [];

    public ?ServerCallable $handler = null;
    public ?string $event = null;

    /** The HTTP method of a url or route action: anything but GET submits a form, with the CSRF token */
    public string $method = self::METHOD_GET;

    /** The target of a url or route action, e.g. "_blank" */
    public ?string $target = null;

    public function __construct(
        ?string $name = null,
        ?string $label = null,
        ?string $url = null,
        ?string $route = null,
        array|string $routeParams = [],
        mixed $handler = null,
        ?string $event = null,
        ?string $method = null,
        ?string $target = null,
        bool|string|null $confirm = null,
        ?string $confirmTitle = null,
        ?string $can = null,
    ) {
        // Replaced by the attributes left over when made from an <x-data-table.row-action>: they go to its element
        $this->attributes = new ComponentAttributeBag();

        $this->name = \trim($name ?? '');

        if ($this->name === '') {
            throw new \InvalidArgumentException('The [name] attribute of an [x-data-table.row-action] is required');
        }

        $this->label = static::blankToNull($label) ?? Str::headline($this->name);

        $this->url = static::blankToNull($url);
        $this->route = static::blankToNull($route);
        $this->handler = static::parseHandler($handler, "the row action [{$this->name}]");
        $this->event = static::blankToNull($event);

        $destinations = \array_filter([$this->url, $this->route, $this->handler, $this->event], fn($destination) => $destination !== null);

        if (\count($destinations) !== 1) {
            throw new \InvalidArgumentException("The row action [{$this->name}] must have one (and only one) of the [url], [route], [handler] and [event] attributes");
        }

        if ($this->url !== null) {
            static::validateUrl($this->url, "the row action [{$this->name}]");
        }

        if ($this->route !== null) {
            $this->setupRoute($routeParams);
        } elseif ($routeParams !== [] && $routeParams !== '') {
            throw new \InvalidArgumentException("The [route-params] attribute of the row action [{$this->name}] needs a [route]");
        }

        $this->setupMethodAndTarget($method, $target);
        $this->setupConfirmation($confirm, $confirmTitle);
        $this->can = static::blankToNull($can);
    }

    /**
     * Whether the action leads somewhere (url or route), rather than running on the server (handler or event)
     */
    public function isLink(): bool
    {
        return $this->url !== null || $this->route !== null;
    }

    public function submitsForm(): bool
    {
        return $this->isLink() && $this->method !== static::METHOD_GET;
    }

    /**
     * Where a url or route action leads for the given row
     */
    public function href(mixed $row): string
    {
        if ($this->route !== null) {
            $parameters = [];

            foreach ($this->routeParams as $parameter => $field) {
                $parameters[$parameter] = \data_get($row, $field);
            }

            return route($this->route, $parameters);
        }

        if ($this->url !== null) {
            return static::fillUrl($this->url, $row, "the row action [{$this->name}]");
        }

        throw new \LogicException("The row action [{$this->name}] has no url or route");
    }

    /**
     * The question asked before running the action, with the row's values in its "{field}" placeholders
     */
    public function confirmMessage(mixed $row = null): string
    {
        if ($this->confirm === null) {
            return __('erickcomp_lw_data_table::messages.confirm_row_action_default_message');
        }

        return \preg_replace_callback(static::PLACEHOLDER_PATTERN, fn(array $match) => static::rowValue($row, $match[1]), $this->confirm);
    }

    /**
     * Only http(s) and relative URLs are accepted: a "javascript:" URL, for one, would run code when clicked. Browsers
     * ignore whitespace and control characters in a URL's scheme, so they are ignored here too.
     *
     * @param string $owner How error messages refer to the URL's owner, e.g. "the row action [edit]"
     */
    public static function validateUrl(string $url, string $owner): void
    {
        $compactUrl = \preg_replace('/[\x00-\x20\x7F]+/', '', $url);

        if (\preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compactUrl, $match) && !\in_array(\strtolower($match[1]), ['http', 'https'], true)) {
            throw new \InvalidArgumentException("Invalid URL for $owner: [$url]. Only http(s) and relative URLs are allowed");
        }
    }

    /**
     * Fills the URL's "{field}" placeholders with the row's values, URL-encoded
     *
     * @param string $owner How error messages refer to the URL's owner, e.g. "the row action [edit]"
     */
    public static function fillUrl(string $url, mixed $row, string $owner): string
    {
        $filledUrl = \preg_replace_callback(static::PLACEHOLDER_PATTERN, fn(array $match) => \rawurlencode(static::rowValue($row, $match[1])), $url);

        // Empty values must not turn the URL into another kind of URL, e.g. "java{x}script:..."
        static::validateUrl($filledUrl, $owner);

        return $filledUrl;
    }

    protected static function rowValue(mixed $row, string $field): string
    {
        $value = \data_get($row, $field);

        return match (true) {
            $value instanceof \BackedEnum => (string) $value->value,
            \is_scalar($value), $value instanceof \Stringable => (string) $value,
            default => '',
        };
    }

    protected function setupRoute(array|string $routeParams): void
    {
        if (!Route::has($this->route)) {
            throw new \InvalidArgumentException("The route [{$this->route}] of the row action [{$this->name}] does not exist");
        }

        if (\is_string($routeParams)) {
            throw new \InvalidArgumentException("The route parameters of the row action [{$this->name}] must be an array of parameters and row fields: use [:route-params=\"['user' => 'id']\"]");
        }

        foreach ($routeParams as $parameter => $field) {
            $this->routeParams[(string) $parameter] = (string) $field;
        }
    }

    protected function setupMethodAndTarget(?string $method, ?string $target): void
    {
        $method = static::blankToNull($method);
        $this->target = static::blankToNull($target);

        if (!$this->isLink() && ($method !== null || $this->target !== null)) {
            throw new \InvalidArgumentException("The [method] and [target] attributes of the row action [{$this->name}] need a [url] or a [route]");
        }

        if ($method === null) {
            return;
        }

        $this->method = \strtolower($method);

        if (!\in_array($this->method, static::METHODS, true)) {
            throw new \InvalidArgumentException("Invalid method for the row action [{$this->name}]: [$method]. The valid ones are: " . \implode(', ', static::METHODS));
        }
    }
}
