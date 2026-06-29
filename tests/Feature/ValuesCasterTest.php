<?php

use ErickComp\LivewireDataTable\Data\ValuesCaster;
use ErickComp\LivewireDataTable\DataTable\Filter;

it('returns raw value when number parsing fails', function () {
    $result = ValuesCaster::castValueToFilterType('not-a-number', Filter::TYPE_NUMBER);

    expect($result)->toBe('not-a-number');
});
