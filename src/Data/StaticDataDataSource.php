<?php

namespace ErickComp\LivewireDataTable\Data;

interface StaticDataDataSource extends DataSource
{
    public function getStaticData(): iterable;
}
