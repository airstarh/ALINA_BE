Case Sensitive Template!

<br><?= \alina\app::get()->currentController  ?>
<br><?= \alina\app::get()->currentAction  ?>

<br><h1>Params</h1>
<?php
echo '<pre>';
print_r($data);
echo '</pre>';
?>

<br><h1>\alina\app::get()->currentActionParams</h1>
<?php
echo '<pre>';
print_r(\alina\app::get()->currentActionParams);
echo '</pre>';
?>