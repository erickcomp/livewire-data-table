<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\CreatesFromComponentAttributeBag;
use ErickComp\LivewireDataTable\Concerns\DefinesAction;
use ErickComp\LivewireDataTable\Support\ServerCallable;
use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * An action run on the selected rows (<x-data-table.bulk-action>), by a PHP handler or by dispatching a Livewire event.
 *
 * Kept in the data table definition, which goes into the file cache, so it holds no closures: the handler is
 * referenced by name, like a callable data source.
 */
class BulkAction
{
    use CreatesFromComponentAttributeBag;
    use DefinesAction;

    public string $name;
    public string $label;
    public ?ServerCallable $handler = null;
    public ?string $event = null;

    /** @var array<string, string> Sub-actions: the option values handed over to the handler, with their labels */
    public array $options = [];

    /** Whether the rows stay selected after the action runs */
    public bool $keepSelection = false;

    public function __construct(
        ?string $name = null,
        ?string $label = null,
        mixed $handler = null,
        ?string $event = null,
        array|string $options = [],
        bool|string|null $confirm = null,
        ?string $confirmTitle = null,
        ?string $can = null,
        bool|string $keepSelection = false,
    ) {
        // Replaced by the attributes left over when made from an <x-data-table.bulk-action>: they go to its <option>
        $this->attributes = new ComponentAttributeBag();

        $this->name = \trim($name ?? '');

        if ($this->name === '') {
            throw new \InvalidArgumentException('The [name] attribute of an [x-data-table.bulk-action] is required');
        }

        $this->label = static::blankToNull($label) ?? Str::headline($this->name);

        $this->handler = static::parseHandler($handler, "the bulk action [{$this->name}]");
        $this->event = static::blankToNull($event);

        if (($this->handler === null) === ($this->event === null)) {
            throw new \InvalidArgumentException("The bulk action [{$this->name}] must have either a [handler] or an [event] attribute (and only one of them)");
        }

        if (\is_string($options)) {
            throw new \InvalidArgumentException("The options of the bulk action [{$this->name}] must be an array of values and labels: use [:options=\"['value' => 'Label']\"]");
        }

        foreach ($options as $optionValue => $optionLabel) {
            $this->options[(string) $optionValue] = (string) $optionLabel;
        }

        // ":count" in the question becomes the number of selected rows
        $this->setupConfirmation($confirm, $confirmTitle);
        $this->can = static::blankToNull($can);
        $this->keepSelection = \is_bool($keepSelection)
            ? $keepSelection
            : (\filter_var($keepSelection, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true);
    }

    public function confirmMessage(): string
    {
        return $this->confirm ?? __('erickcomp_lw_data_table::messages.confirm_default_message');
    }

    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    public function hasOption(?string $option): bool
    {
        return $option !== null && \array_key_exists($option, $this->options);
    }
}
