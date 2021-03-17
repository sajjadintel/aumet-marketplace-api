<?php

namespace App\Models;

use Base;
use DB\Cortex;

abstract class Model extends Cortex
{
    public function __construct()
    {
        $this->db = $this->db ?? Base::instance()->get('dbConnectionMain');
        parent::__construct($this->db, $this->table);
    }
}
