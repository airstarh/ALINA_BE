<?php

use alina\utils\Sys;

?>
<?php if (AlinaAccessIfAdmin()) { ?>
    <div class="ck-content">
        <?php
        $h1 = 'Alina Details';
        print_r("<h1>{$h1}</h1>");
        echo '<pre>';
        print_r(Sys::SUPER_DEBUG_INFO());
        echo '</pre>';
        print_r(\alina\utils\Sys::reportSpentTime());
        ?>
    </div>
<?php } ?>

