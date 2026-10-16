<?php

namespace ErickComp\LivewireDataTable\Support;

/**
 * The message an action handler returns to be shown above the table:
 *
 *     return ActionResult::success(':count products deleted');
 *     return ActionResult::error('Some products could not be deleted');
 *
 * An error leaves the rows selected, so the user can try again.
 */
class ActionResult
{
    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR = 'error';
    public const TYPE_INFO = 'info';

    final public function __construct(
        public readonly string $type,
        public readonly string $message,
    ) {
        if (!\in_array($type, [static::TYPE_SUCCESS, static::TYPE_ERROR, static::TYPE_INFO], true)) {
            throw new \ValueError("Invalid action result type [$type]. The valid ones are: success, error and info");
        }
    }

    public static function success(string $message): static
    {
        return new static(static::TYPE_SUCCESS, $message);
    }

    public static function error(string $message): static
    {
        return new static(static::TYPE_ERROR, $message);
    }

    public static function info(string $message): static
    {
        return new static(static::TYPE_INFO, $message);
    }

    public function isError(): bool
    {
        return $this->type === static::TYPE_ERROR;
    }

    /**
     * @return array{type: string, message: string}
     */
    public function toArray(): array
    {
        return ['type' => $this->type, 'message' => $this->message];
    }
}
