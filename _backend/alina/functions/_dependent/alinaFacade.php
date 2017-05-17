<?php
function alinaApp() {
    return \alina\app::get();
}

function getAlinaConfig($path)
{
    return \alina\app::getConfig($path);
}