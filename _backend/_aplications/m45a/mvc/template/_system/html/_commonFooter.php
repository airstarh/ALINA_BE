<?php

use alina\mvc\model\CurrentUser;
use alina\utils\Sys;

?>
<div class="row no-gutters mt-5 alina-form p-5">
    <div class="col">
      <h3>
        Официальнй сайт TCH "ТСЖ Миронова 45 А"
      </h3>
      <p>
        394005, Воронежская область, город Воронеж, улица Миронова 45 А
      </p>
      <br>
    </div>
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
