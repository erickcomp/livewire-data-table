<?php

namespace ErickComp\LivewireDataTable\DataTable;

use ErickComp\LivewireDataTable\Concerns\FillsComponentAttributeBags;
use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\View\ComponentAttributeBag;

/**
 * The bulk actions of a data table (<x-data-table.bulk-actions>): the "Actions" dropdown shown above the table.
 *
 * Its attributes go to the <select>, except "placeholder", the text of the dropdown's empty option.
 */
class BulkActions
{
    use FillsComponentAttributeBags;

    public ComponentAttributeBag $selectAttributes;

    /** @var array<string, BulkAction> The actions keyed by name, in the order they were declared */
    public array $actions = [];

    public function __construct(ComponentAttributeBag $componentAttributes)
    {
        $this->fillComponentAttributeBags($componentAttributes);
    }

    public function add(BulkAction $action): void
    {
        if (\array_key_exists($action->name, $this->actions)) {
            throw new \InvalidArgumentException("There is more than one bulk action named [{$action->name}]: the names must be unique");
        }

        $this->actions[$action->name] = $action;
    }

    public function find(string $name): ?BulkAction
    {
        return $this->actions[$name] ?? null;
    }

    public function isEmpty(): bool
    {
        return empty($this->actions);
    }

    public function placeholder(): string
    {
        $placeholder = \trim((string) $this->selectAttributes->get('placeholder', ''));

        return $placeholder !== '' ? $placeholder : __('erickcomp_lw_data_table::messages.bulk_actions_label');
    }

    public function selectAttributes(Preset $preset): ComponentAttributeBag
    {
        return $this->selectAttributes
            ->except(['placeholder'])
            ->class($preset->get('actions.bulk-actions-and-per-page.bulk-actions-select.class', []));
    }

    protected function getAttributeBagsMappings(): array
    {
        return [
            0 => 'selectAttributes', //default
        ];
    }
}
