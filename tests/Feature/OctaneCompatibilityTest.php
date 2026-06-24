<?php

use ErickComp\LivewireDataTable\Data\DataSourcePaginationType;
use ErickComp\LivewireDataTable\Data\EloquentDataSource;
use ErickComp\LivewireDataTable\Livewire\Preset;
use Illuminate\Database\Eloquent\Model;

class OctaneModelA extends Model
{
    protected $table = 'octane_table_a';
    protected $perPage = 10;
}

class OctaneModelB extends Model
{
    protected $table = 'octane_table_b';
    protected $perPage = 50;
}

// --- Preset cache ---

it('serves correct preset data from cache on repeated loads', function () {
    $preset1 = Preset::loadFromName('vanilla');
    $preset2 = Preset::loadFromName('vanilla');

    expect($preset1)->toBe($preset2)
        ->and($preset1->get('table.class'))->toBe(['lw-dt-table']);
});

it('caches different presets independently', function () {
    $empty = Preset::loadFromName('empty');
    $vanilla = Preset::loadFromName('vanilla');

    expect($empty)->not->toBe($vanilla)
        ->and($empty->get('table.class'))->toBeNull()
        ->and($vanilla->get('table.class'))->toBe(['lw-dt-table']);
});

it('throws on invalid preset name without polluting cache', function () {
    $threw = false;

    try {
        Preset::loadFromName('nonexistent_preset_name');
    } catch (\InvalidArgumentException $e) {
        $threw = true;
    }

    expect($threw)->toBeTrue();

    // Valid presets still work after a failed load
    $vanilla = Preset::loadFromName('vanilla');
    expect($vanilla->get('table.class'))->toBe(['lw-dt-table']);
});

// --- EloquentDataSource instance isolation ---

it('isolates model instances across sequential EloquentDataSource creation', function () {
    $sourceA = new EloquentDataSource(OctaneModelA::class, DataSourcePaginationType::LengthAware);
    $perPageA = $sourceA->modelPerPage();

    $sourceB = new EloquentDataSource(OctaneModelB::class, DataSourcePaginationType::LengthAware);
    $perPageB = $sourceB->modelPerPage();

    // Verify A wasn't contaminated by B
    expect($perPageA)->toBe(10)
        ->and($perPageB)->toBe(50)
        ->and($sourceA->modelPerPage())->toBe(10);
});

it('isolates model instances when sources are created and queried interleaved', function () {
    $sourceA = new EloquentDataSource(OctaneModelA::class, DataSourcePaginationType::LengthAware);
    $sourceB = new EloquentDataSource(OctaneModelB::class, DataSourcePaginationType::LengthAware);

    // Interleaved access - simulates two data tables on the same page
    expect($sourceA->modelPerPage())->toBe(10)
        ->and($sourceB->modelPerPage())->toBe(50)
        ->and($sourceA->modelPerPage())->toBe(10)
        ->and($sourceB->modelPerPage())->toBe(50);
});

// --- Repeated instantiation (simulates multiple Octane requests) ---

it('produces correct results across simulated sequential requests', function () {
    // Simulate request 1
    $source1 = new EloquentDataSource(OctaneModelA::class, DataSourcePaginationType::LengthAware);
    expect($source1->modelPerPage())->toBe(10);

    // Simulate request 2 with different model
    $source2 = new EloquentDataSource(OctaneModelB::class, DataSourcePaginationType::LengthAware);
    expect($source2->modelPerPage())->toBe(50);

    // Simulate request 3 back to first model
    $source3 = new EloquentDataSource(OctaneModelA::class, DataSourcePaginationType::LengthAware);
    expect($source3->modelPerPage())->toBe(10);
});
