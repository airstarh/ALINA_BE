<?php
function alinaApp() {
    return \alina\app::get();
}

function getAlinaConfig($path)
{
    return \alina\app::getConfig($path);
}

function getCurrentUserId() {
	$u = [
		'first_name' => 'HardCodedFirstName',
		'last_name' => 'HardCodedFirstName',
		'email' => 'HardCodedFirstName',
		'username' => 'HardCodedFirstName',
	];

	return toObject($u);

}

define('DT_FORMAT_DB','Y-m-d H:i:s');
function getNow() {
	if (defined('DM_REQUEST_TIME'))
		return date(DT_FORMAT_DB, DM_REQUEST_TIME);
	else
		return date(DT_FORMAT_DB);
}
