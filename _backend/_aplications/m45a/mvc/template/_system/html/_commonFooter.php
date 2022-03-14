<?php

use alina\mvc\model\CurrentUser;
use alina\utils\Sys;

?>
<div class="mt-5 alina-form p-5">
    Мемориальный комплекс "Осетровский плацдарм"
    <a href="https://oixm.ru/" target="_blank">
        Острогожского историко-художественного музея им. И.Н. Крамского
    </a>
    <br>
    Воронежская область, Верхнемамонский район, 720 км федеральной автомобильной дороги М4 «Дон».
</div>
<?php if (AlinaAccessIfAdmin()) : ?>
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
