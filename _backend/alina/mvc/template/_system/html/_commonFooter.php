<?php

use alina\mvc\model\CurrentUser;
use alina\utils\Sys;

?>
<div> 12341234
    <?php if (CurrentUser::obj()->isAdmin() || ALINA_MODE !== 'PROD') : ?>
        <div>
            <?php
            $h1 = 'Alina Details';
            print_r("<h1>{$h1}</h1>");
            echo '<pre>';
            print_r(Sys::SUPER_DEBUG_INFO());
            echo '</pre>';
            print_r(\alina\utils\Sys::reportSpentTime());
            ?>
        </div>
    <?php endif; ?>
</div>
