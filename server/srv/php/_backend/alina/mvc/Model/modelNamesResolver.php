<?php

namespace alina\mvc\Model;
class modelNamesResolver
{
    static public $vocTableToClassName = [
        'user'     => '\alina\mvc\model\user',
        'timezone' => '\alina\mvc\model\timezone',
    ];

    /**
     * @param $describer
     * @return \alina\mvc\Model\_BaseAlinaModel
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
            return new $describer(['table' => \alina\Utils\Resolver::shortClassName($describer)]);
        }
        //$clarifiedDescriber = '\alina\mvc\Model\\'.$describer;
        try {
            $clarifiedDescriber = [
                AlinaCfg('appNamespace'),
                AlinaCfg('mvc/structure/Model'),
                $describer,
            ];
            $mClassName         = \alina\Utils\Resolver::buildClassNameFromBlocks($clarifiedDescriber);
            if (class_exists($mClassName)) {
                return new $mClassName();
            }
            else {
                $clarifiedDescriber = [
                    AlinaCfgDefault('appNamespace'),
                    AlinaCfgDefault('mvc/structure/Model'),
                    $describer,
                ];
                $mClassName         = \alina\Utils\Resolver::buildClassNameFromBlocks($clarifiedDescriber);
                if (class_exists($mClassName)) {
                    return new $mClassName();
                }
            }
        } catch (\Exception $e) {
            //Nothing is to do here :-)
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
