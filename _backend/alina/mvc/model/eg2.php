<?php

namespace alina\mvc\model;

class eg2 extends _baseAlinaEloquentModel {
	public $table  = 'eg2';
	public $pkName = 'id';

	public function fields() {
		return [
			'id'     => [],
			'val'    => [],
			'eg1_id' => [],
		];
	}

	public function uniqueKeys() {
		return [];
	}
}