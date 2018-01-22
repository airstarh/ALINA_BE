<?php

function isIterable($subject) {
	return (is_array($subject) || is_object($subject));
}

function toArray($v) {
	if (is_array($v))
		return $v;

	if (isIterable($v)) {
		// ToDo: Make less heavy
		$array = json_decode(json_encode($v), true);
	} else {
		$array = [$v];
	}
	return $array;

}

/**
 * @param $v
 * @return mixed
 * @throws Exception
 */
function toObject($v) {
	if (is_object($v))
		return $v;

	if (isIterable($v)) {
		// ToDo: Make less heavy
		$object = json_decode(json_encode($v), false);
	} else {
		throw new \Exception('Unconvertable value');
	}
	return $object;
}

/**
 * Transforms input data to 'ASC' or 'DESC' string.
 * @param string|int|boolean $dir
 * @return string 'ASC' or 'DESC'
 */
function getSqlDirection($dir) {
	if (is_string($dir)) {
		$dir = trim(strtoupper($dir));
		if ($dir === 'ASC' || $dir === 'DESC') {
			return $dir;
		}
	}

	$dir = filter_var($dir, FILTER_VALIDATE_BOOLEAN)
		? 'ASC'
		: 'DESC';

	return $dir;
}

function utf8ize($d) {
	if (is_array($d) || is_object($d)) {
		foreach ($d as &$v) $v = utf8ize($v);
	} else {
		$enc   = mb_detect_encoding($d);
		$value = iconv($enc, 'UTF-8', $d);
		return $value;
	}

	return $d;
}