<?php
namespace tests;
use LazyRecord\Schema;

class WineCategorySchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128);
    }
}
