<?php

namespace ErickComp\LivewireDataTable\Concerns;

use ErickComp\LivewireDataTable\Support\ServerCallable;

/**
 * What bulk actions and row actions have in common: a confirmation, a gate ability and, for some, a PHP handler
 */
trait DefinesAction
{
    /** Whether the user has to confirm before the action runs */
    public bool $requiresConfirmation = false;

    /** The question asked before running the action, null for the default one */
    public ?string $confirm = null;
    public ?string $confirmTitle = null;

    /** Gate ability the user must have to run the action */
    public ?string $can = null;

    public function confirmTitle(): string
    {
        return $this->confirmTitle ?? __('erickcomp_lw_data_table::messages.confirm_default_title');
    }

    /**
     * A bare "confirm" (or a boolean) asks the default question; a text is the question itself
     */
    protected function setupConfirmation(bool|string|null $confirm, ?string $confirmTitle): void
    {
        $confirmFlag = \is_string($confirm) ? \filter_var(\trim($confirm), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : $confirm;

        $this->requiresConfirmation = $confirmFlag !== false && $confirm !== null;
        $this->confirm = $this->requiresConfirmation && $confirmFlag === null ? \trim($confirm) : null;
        $this->confirmTitle = static::blankToNull($confirmTitle);
    }

    /**
     * @param string $actionDescription How error messages refer to the action, e.g. "the bulk action [delete]"
     */
    protected static function parseHandler(mixed $handler, string $actionDescription): ?ServerCallable
    {
        $handler = \is_string($handler) ? \trim($handler) : $handler;

        if ($handler === null || $handler === '') {
            return null;
        }

        try {
            return new ServerCallable($handler);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException("Invalid handler for $actionDescription: {$e->getMessage()}", previous: $e);
        }
    }

    protected static function blankToNull(?string $value): ?string
    {
        $value = \trim($value ?? '');

        return $value === '' ? null : $value;
    }
}
