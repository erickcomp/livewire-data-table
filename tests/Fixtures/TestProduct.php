<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestProduct extends Model
{
    protected $table = 'test_products';
    protected $guarded = [];
}
