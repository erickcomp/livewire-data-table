<?php

namespace ErickComp\LivewireDataTable\Livewire;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class Preset
 *
 * Represents a preset configuration for Livewire Data Table.
 * Presets can be used to define default styles, behaviors, and configurations for the data table.
 * 
 * @property-read Preset $parent
 */
class Preset
{
    public const LW_DT_PRESETS = [
        'empty',
        'vanilla',
    ];
    public const DEFAULT_PRESET = 'vanilla';

    protected static array $presetCache = [];

    /**
     * Memo of get(): values already resolved (here or up the parent chain), by key. A key may resolve to
     * null, so it is looked up with array_key_exists().
     *
     * @var array<string, mixed>
     */
    protected array $resolvedValues = [];

    /**
     * Memo of get(): keys found neither here nor up the parent chain. The default is per call, so only
     * the absence is kept.
     *
     * @var array<string, true>
     */
    protected array $missingKeys = [];

    protected ?Preset $loadedParent = null;
    protected bool $isParentLoaded = false;

    /**
     * @param array {
     *     extends: ?string,
     *     main-container-class: array<int, string>|array<string, bool>,
     *     actions-container-class: array<int, string>|array<string, bool>,
     *     actions-row-class: array<int, string>|array<string, bool>,
     *     search: array{
     *         container-class: array<int, string>|array<string, bool>,
     *         input-class: array<int, string>|array<string, bool>,
     *         button-class: array<int, string>|array<string, bool>,
     *         button-use-default-icon: bool
     *     },
     *     filters: array{
     *         collapsible: bool,
     *         toggle-button-use-default-icon: bool,
     *         toggle-button-class: array<int, string>|array<string, bool>,
     *         apply-button-use-default-icon: bool,
     *         apply-button-container-class: array<int, string>|array<string, bool>,
     *         apply-button-class: array<int, string>|array<string, bool>,
     *         item-class: array<int, string>|array<string, bool>,
     *         content-class: array<int, string>|array<string, bool>,
     *         range-class: array<int, string>|array<string, bool>,
     *         select-class: array<int, string>|array<string, bool>,
     *         input-text-class: array<int, string>|array<string, bool>,
     *         input-date-class: array<int, string>|array<string, bool>,
     *         input-datetime-local-class: array<int, string>|array<string, bool>,
     *         input-number-class: array<int, string>|array<string, bool>
     *     },
     *     applied-filters: array{
     *         container-class: array<int, string>|array<string, bool>,
     *         label-class: array<int, string>|array<string, bool>,
     *         applied-filter-item-class: array<int, string>|array<string, bool>,
     *         button-remove-applied-filter-item-class: array<int, string>|array<string, bool>
     *     },
     *     columns-search: array{
     *         debounce-ms: int,
     *         columns: mixed // TODO: Add columns configuration
     *     },
     *     sorting: array{
     *         default-sorting-indicators: bool,
     *         indicator-class: array<int, string>|array<string, bool>,
     *         indicator-asc-class: array<int, string>|array<string, bool>,
     *         indicator-desc-class: array<int, string>|array<string, bool>
     *     },
     *     table: array<string, mixed>, // TODO: Add table configuration
     *     pagination: array{
     *         view: string,
     *         simple-view: string,
     *         default-style-for-pagination: bool
     *     }
     * }
     * $presetInfo
     */
    public function __construct(
        protected string $name,
        protected array $presetInfo,
    ) {}

    public function __get(string $key): mixed
    {
        if ($key === 'parent') {
            return $this->loadParent();
        }

        throw new \BadMethodCallException("Undefined property: " . static::class . "::$key for preset [{$this->name}]");
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->presetInfo;
        }

        if (\array_key_exists($key, $this->resolvedValues)) {
            return $this->resolvedValues[$key];
        }

        if (isset($this->missingKeys[$key])) {
            return $default;
        }

        $val = Arr::get($this->presetInfo, $key, static::noDataFound());

        if ($val === static::noDataFound()) {
            $parent = $this->loadParent();

            // The sentinel only goes to the parent as its default and is never memoized: it is an instance
            // of an anonymous class, which cannot be serialized
            $val = $parent instanceof static
                ? $parent->get($key, static::noDataFound())
                : static::noDataFound();
        }

        if ($val === static::noDataFound()) {
            $this->missingKeys[$key] = true;

            return $default;
        }

        return $this->resolvedValues[$key] = $val;
    }

    /**
     * Only the name and the preset info are serialized, under the very keys PHP uses by default for these
     * two protected properties: the serialized form is the same as before the memo existed and does not
     * depend on what get() has resolved. DataTable keeps its preset, and the DataTable file cache is named
     * after the md5 of the serialized DataTable.
     */
    public function __serialize(): array
    {
        return [
            "\0*\0name" => $this->name,
            "\0*\0presetInfo" => $this->presetInfo,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data["\0*\0name"];
        $this->presetInfo = $data["\0*\0presetInfo"];
    }

    /**
     * The preset named by "extends", loaded once.
     *
     * "extends" is read from this preset's own info: looking it up with get() would climb to the parent
     * when missing, which requires the parent in the first place.
     */
    protected function loadParent(): ?Preset
    {
        if (!$this->isParentLoaded) {
            $parentName = $this->presetInfo['extends'] ?? '';
            $this->loadedParent = empty($parentName) ? null : static::loadFromName($parentName);
            $this->isParentLoaded = true;
        }

        return $this->loadedParent;
    }

    public static function loadFromName(string $name): static
    {
        if (\array_key_exists($name, self::$presetCache)) {
            return self::$presetCache[$name];
        }

        $presetInfo = \config("app.lw_dt_presets.$name", null)
            ?? \config("erickcomp-livewire-data-table.presets.$name", null);

        if (empty($presetInfo)) {
            throw new \InvalidArgumentException("Preset '$name' not found in config: app.lw_dt_presets.$name or erickcomp-livewire-data-table.presets.$name");
        }

        self::$presetCache[$name] = new static($name, $presetInfo);

        return self::$presetCache[$name];
    }

    protected static final function noDataFound()
    {
        static $noDataFound = null;

        if ($noDataFound === null) {
            $noDataFound = new class {};
        }

        return $noDataFound;
    }
}
