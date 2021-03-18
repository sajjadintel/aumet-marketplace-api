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

    /**
     * Retrieve value of column in collection, dot notation is supported
     *
     * @param CortexCollection $collection
     * @param string $column
     * @return array
     */
    public function pluck(CortexCollection $collection, $column)
    {
        $pluckedValues = [];
        $parameters = explode('.', $column);
        $parameters = $parameters === false ? $column : $parameters;

        foreach ($collection as $item) {
            // get dot notation value
            $pluckedValues[] = array_reduce(
                $parameters, 
                function ($object, $parameter) { 
                        return $object->$parameter; 
                    }, 
                $item
            );
        }

        return $pluckedValues;
    }
}
