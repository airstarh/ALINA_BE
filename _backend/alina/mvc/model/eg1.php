<?php

namespace alina\mvc\model;

class eg1 extends _BaseAlinaModel {
	public $table  = 'eg1';
	public $pkName = 'id';

	public function fields() {
		return [
			'id'  => [],
			'val' => [],
		];
	}

	public function uniqueKeys() {
		return [];
	}
}