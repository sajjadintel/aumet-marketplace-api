<?php

namespace App\Validators;

class CompositeUnique extends Validator
{
    const RULE = 'composite_unique';
    const MESSAGE = 'Record already exists';

    public static function validate($value, $ruleConfigs)
    {
        $condition = '';
        $count = 0;
        foreach (array_keys($value) as $column) {
            if ($count > 0) {
                $condition .= "AND {$column} = ? ";
            } else {
                $condition .= "{$column} = ? ";
            }
            $count++;
        }

        $condition = [$condition];
        $condition = array_merge($condition, array_values($value));

        $model = new \GenericModel(\Base::instance()->get('dbConnectionMain'), $ruleConfigs[0]);
        $exists = $model->findone($condition);
        return $exists === false;
    }
}