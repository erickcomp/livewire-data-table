<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\CreatesFromComponentAttributeBag;
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

    public string $name;
    public string $label;
    public ?ServerCallable $handler = null;
    public ?string $event = null;

    /** @var array<string, string> Sub-actions: the option values handed over to the handler, with their labels */
    public array $options = [];

    /** Whether the user has to confirm before the action runs */
    public bool $requiresConfirmation = false;

    /** The question asked before running the action (":count" becomes the number of selected rows), null for the default one */
    public ?string $confirm = null;
    public ?string $confirmTitle = null;

    /** Gate ability the user must have to run the action */
    public ?string $can = null;

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

        $this->label = \trim($label ?? '') !== '' ? \trim($label) : Str::headline($this->name);

        $handler = \is_string($handler) ? \trim($handler) : $handler;
        $hasHandler = $handler !== null && $handler !== '';
        $event = \trim($event ?? '');

        if ($hasHandler === ($event !== '')) {
            throw new \InvalidArgumentException("The bulk action [{$this->name}] must have either a [handler] or an [event] attribute (and only one of them)");
        }

        if ($hasHandler) {
            try {
                $this->handler = new ServerCallable($handler);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException("Invalid handler for the bulk action [{$this->name}]: {$e->getMessage()}", previous: $e);
            }
        } else {
            $this->event = $event;
        }

        if (\is_string($options)) {
            throw new \InvalidArgumentException("The options of the bulk action [{$this->name}] must be an array of values and labels: use [:options=\"['value' => 'Label']\"]");
        }

        foreach ($options as $optionValue => $optionLabel) {
            $this->options[(string) $optionValue] = (string) $optionLabel;
        }

        // A bare "confirm" (or a boolean) asks the default question; a text is the question itself
        $confirmFlag = \is_string($confirm) ? \filter_var(\trim($confirm), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : $confirm;
        $this->requiresConfirmation = $confirmFlag !== false && $confirm !== null;
        $this->confirm = $this->requiresConfirmation && $confirmFlag === null ? \trim($confirm) : null;

        $this->confirmTitle = \trim($confirmTitle ?? '') !== '' ? \trim($confirmTitle) : null;
        $this->can = \trim($can ?? '') !== '' ? \trim($can) : null;
        $this->keepSelection = \is_bool($keepSelection)
            ? $keepSelection
            : (\filter_var($keepSelection, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true);
    }

    public function confirmMessage(): string
    {
        return $this->confirm ?? __('erickcomp_lw_data_table::messages.confirm_default_message');
    }

    public function confirmTitle(): string
    {
        return $this->confirmTitle ?? __('erickcomp_lw_data_table::messages.confirm_default_title');
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
