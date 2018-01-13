<?php

function fetchBytesFromFile($file) {           # функция возвращает генератор, который считывает данные разной длины из файла
	$length = yield;                                          # в начале установим длину
	$f = fopen($file, 'r');
	while (!feof($f)) {                                        # проверка на конец файла
		$length = yield fread($f, $length);       # выбрасываем блок данных
	}
	yield false;
}
function processBytesInBatch(Generator $byteGenerator) {
	$buffer = '';
	$bytesNeeded = 1000;
	while ($buffer .= $byteGenerator->send($bytesNeeded)) {           # всегда считываем порцию разного размера
		// проверяем, достаточно ли данных в буфере
		list($lengthOfRecord) = unpack('N', $buffer);
		if (strlen($buffer) < $lengthOfRecord) {
			$bytesNeeded = $lengthOfRecord - strlen($buffer);
			continue;
		}
		yield substr($buffer, 1, $lengthOfRecord);
		$buffer = substr($buffer, 0, $lengthOfRecord + 1);
		$bytesNeeded = 1000 - strlen($buffer);
	}
}
$gen = processBytesInBatch(fetchBytesFromFile($file));
foreach ($gen as $record) {
	doSomethingWithRecord($record);
}