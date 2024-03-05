<?php
if (!function_exists('borgForm')) {
    function borgForm()
    {
        return BorgTestData::randFormData();
    }
}

if (!function_exists('borgTypes')) {
    function borgTypes()
    {
        return BorgTestData::dataTypes();
    }
}