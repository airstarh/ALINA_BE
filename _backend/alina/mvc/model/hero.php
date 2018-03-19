<?php
namespace alina\mvc\model;

class hero extends _BaseAlinaModel
{
    public $table='hero';
    public $pkName='id';

    public function fields() {
        return [
        	'id' => [],
        	'name' => [],
        ];
    }

    public function uniqueKeys() {
        return [];
    }
}