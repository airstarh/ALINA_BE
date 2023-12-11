Case Sensitive Template!

<br><?= Alina()->currentController  ?>
<br><?= Alina()->currentAction  ?>

<br><h1>Params</h1>
<?php
echo '<pre>';
print_r($data);
echo '</pre>';
?>

<br><h1>app::get()->currentActionParams</h1>
<?php
echo '<pre>';
print_r(Alina()->currentActionParams);
echo '</pre>';
?>
