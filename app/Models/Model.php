<?php

namespace App\Models;

use Base;
use Constants;
use DB\Cortex;
use DB\CortexCollection;

abstract class Model extends Cortex
{
    use \Validate;
    public $response = ['statusCode' => Constants::HTTP_OK, 'message' => 'success'];
    protected $hasErrors = false;
    public function __construct()
    {
        $this->db = $this->db ?? Base::instance()->get('dbConnectionMain');
        parent::__construct($this->db, $this->table);
    }

    public function getRules()
    {
        return [];
    }

    /**
     * Due to a bug in the validation package, we are required to not have
     * Any missing keys in the input
     *
     * @param array $data
     * @return array
     */
    protected function initializeMissingKeys($data)
    {
        $differences = array_diff_key($this->getRules(), $data);
        if (!empty($differences)) {
            foreach ($differences as $key => $difference) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    public function hasErrors()
    {
        $this->hasErrors = count($this->errors) > 0;
        return $this->hasErrors;
    }

    public function create($data)
    {
        $data = $this->initializeMissingKeys($data);
        if ($this->check($data) !== true) {
            $this->response['statusCode'] = Constants::HTTP_BAD_REQUEST;
            $this->response['message'] = 'Validation Failed';
            return $this;
        }

        foreach ($data as $parameter => $value) {
            $this->$parameter = $value;
        }

        return $this->save();
    }

    /**
     * Retrieve value of column in collection, dot notation is supported
     * Passing the last 2 parameters will activate a condition which will
     * conditionally pluck based on the parameter and the expected value of it
     *
     * @param CortexCollection $collection
     * @param string $column
     * @param null|string $conditionParameter
     * @param null|mixed $conditionValue
     * 
     * @return array
     */
    public function pluck(CortexCollection $collection, $column, $conditionParameter = null, $conditionValue = null)
    {
        $pluckedItems = [];
        $parameters = explode('.', $column);
        $parameters = $parameters === false ? $column : $parameters;

        foreach ($collection as $item) {
            // get dot notation value
            $value = null;
            foreach ($parameters as $parameter) {
                $value = $item->$parameter;
            }

            if ($conditionParameter && $conditionValue) {
                if ($value->$conditionParameter === $conditionValue) {
                    $pluckedItems[] = $value;
                }

                continue;
            }

            $pluckedItems[] = $value;
        }

        return $pluckedItems;
    }
}
