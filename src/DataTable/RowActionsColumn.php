<?php

namespace ErickComp\LivewireDataTable\DataTable;

/**
 * The column with each row's actions (<x-data-table.row-actions>), placed where it is declared among the columns. It
 * is neither sortable nor searchable, and takes the "th-" and "td-" attributes like any other column.
 */
class RowActionsColumn extends Column
{
    public const DISPLAY_DROPDOWN = 'dropdown';
    public const DISPLAY_LINKS = 'links';

    /** How the actions show up: a dropdown, or the actions side by side */
    public string $display;

    /** @var array<string, RowAction> The actions keyed by name, in the order they were declared */
    public array $actions = [];

    public function __construct(?string $title = null, ?string $display = null)
    {
        parent::__construct(\trim($title ?? '') !== '' ? \trim($title) : __('erickcomp_lw_data_table::messages.row_actions_title'));

        $display = \strtolower(\trim($display ?? ''));
        $this->display = $display === '' ? static::DISPLAY_DROPDOWN : $display;

        if (!\in_array($this->display, [static::DISPLAY_DROPDOWN, static::DISPLAY_LINKS], true)) {
            throw new \InvalidArgumentException("Invalid display for the row actions: [$display]. The valid ones are: dropdown and links");
        }
    }

    public function add(RowAction $action): void
    {
        if (\array_key_exists($action->name, $this->actions)) {
            throw new \InvalidArgumentException("There is more than one row action named [{$action->name}]: the names must be unique");
        }

        $this->actions[$action->name] = $action;
    }

    public function find(string $name): ?RowAction
    {
        return $this->actions[$name] ?? null;
    }

    public function isDropdown(): bool
    {
        return $this->display === static::DISPLAY_DROPDOWN;
    }
}
