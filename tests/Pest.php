<?php

use Tests\BrowserTestCase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(BrowserTestCase::class)
    ->in('Browser');
