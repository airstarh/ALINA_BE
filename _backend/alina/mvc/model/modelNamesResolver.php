<?php

namespace alina\mvc\model;

class modelNamesResolver
{
    static public $vocTableToClassName = [
        'user'     => '\alina\mvc\model\user',
        'timezone' => '\alina\mvc\model\timezone',
    ];

    /**
     * @param $describer
     * @return \alina\mvc\model\_baseAlinaEloquentModel
     * @throws \ErrorException
     */
    static public function getModelObject($describer)
    {
        $message[0] = 'Unresolvable Value of Model!';

        if (is_a($describer, '\alina\mvc\model\_baseAlinaEloquentModel')) {
            return $describer;
        }

        if (!is_string($describer)) {
            throw new \ErrorException($message[0]);
        }

        if (class_exists($describer)) {
            return new $describer;
        }

        $clarifiedDescriber = '\alina\mvc\model\\'.$describer;
        if (class_exists($clarifiedDescriber)) {
            return new $clarifiedDescriber();
        }
        
        //ToDo: Implement Class Scanner.
        $voc = static::$vocTableToClassName;
        if (array_key_exists($describer, $voc)) {
            if (class_exists($voc[$describer])) {
                return new $voc[$describer]();
            }
        }

        //Finally try...
        try {
            $m        = new _baseAlinaEloquentModel();
            $m->table = $m->alias = $describer;

            return $m;
        } catch (\Exception $e) {
            $message[0] = "There is no table {$describer}";
        }

        throw new \ErrorException($message[0]);
    }
}