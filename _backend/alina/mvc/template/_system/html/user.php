<?php

use alina\mvc\model\CurrentUser as CurrentUserAlias;
use alina\utils\Request as RequestAlias;

?>
<div>
    <table>
        <tr>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>CurrentUserAlias::obj()->id</td>
            <td><?= CurrentUserAlias::obj()->id ?></td>
        </tr>
        <tr>
            <td>RequestAlias::obj()->BROWSER_enc</td>
            <td><?= RequestAlias::obj()->BROWSER_enc ?></td>
        </tr>
    </table>
</div>
