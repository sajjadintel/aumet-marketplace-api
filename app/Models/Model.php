<?php

namespace App\Models;

use Base;
use DB\Cortex;
use DB\CortexCollection;

abstract class Model extends Cortex
{
    public function __construct()
    {
        $this->db = $this->db ?? Base::instance()->get('dbConnectionMain');
        parent::__construct($this->db, $this->table);
    }

    public function pluck(CortexCollection $collection, $column)
    {
        $pluckedValues = [];
        if (strstr($column, '.')) {
            $column = explode('.', $column);
        }

        foreach ($collection as $item) {
            $value = null;
            if (is_array($column)) {
                foreach ($column as $field) {
                    $value = $item->$field;
                }
            } else {
                $value = $item->$column;
            }

            $pluckedValues[] = $value;
        }

        return $pluckedValues;
    }
}
