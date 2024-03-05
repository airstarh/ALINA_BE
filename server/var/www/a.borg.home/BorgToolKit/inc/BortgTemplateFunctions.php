<?php
if (!function_exists('borgTemplate')) {
    function borgTemplate($path, $data)
    {
        return BorgTemplate::run($path, $data);
    }
}
