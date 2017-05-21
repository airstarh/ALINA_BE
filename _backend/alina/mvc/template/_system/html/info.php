<?php

// ToDo: Delete later
if (ALINA_MODE !== 'PROD') {
    print_r('<h1>FROM INDEX:::</h1>');
    echo '<pre>';
    print_r(\alina\app::get()->router);
    echo '</pre>';

    echo '<pre>';
    print_r('<h1>Config</h1>');
    print_r(\alina\app::get()->config);
    echo '</pre>';

    echo '<pre>';
    print_r('<h1>Config Default</h1>');
    print_r(\alina\app::get()->configDefault);
    echo '</pre>';

    $alinaTimeSpent = microtime(TRUE) - ALINA_MICROTIME;
    print_r("<h2>Time spent: $alinaTimeSpent</h2>");
}


?>
