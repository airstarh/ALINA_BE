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
     * @return \alina\mvc\model\_BaseAlinaModel
     * @throws \ErrorException
     */
    static public function getModelObject($describer)
    {
        $message[0] = 'Unresolvable Value of Model!';

        if (is_a($describer, '\alina\mvc\model\_BaseAlinaModel')) {
            return $describer;
        }

        if (!is_string($describer)) {
            throw new \ErrorException($message[0]);
        }

        if (class_exists($describer)) {
            return new $describer(['table' => shortClassName($describer)]);
        }

        //$clarifiedDescriber = '\alina\mvc\model\\'.$describer;
        try {
            $clarifiedDescriber = [
                \alina\app::getConfig('appNamespace'),
                \alina\app::getConfig('mvc\structure\model'),
                $describer
            ];
            if (class_exists(implode('\\', $clarifiedDescriber))) {
                return new $clarifiedDescriber();
            }
        } catch (\Exception $e) {
            try {
                $clarifiedDescriber = [
                    \alina\app::getConfigDefault('appNamespace'),
                    \alina\app::getConfigDefault('mvc\structure\model'),
                    $describer
                ];
                if (class_exists(implode('\\', $clarifiedDescriber))) {
                    return new $clarifiedDescriber();
                }
            } catch (\Exception $e) {
                //Nothing is to do here :-)
            }
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
            $m        = new _BaseAlinaModel();
            $m->table = $m->alias = $describer;

            return $m;
        } catch (\Exception $e) {
            $message[0] = "There is no table {$describer}";
        }

        throw new \ErrorException($message[0]);
    }
}