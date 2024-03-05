<?php
/**
 * @var array $data : Array of paths to a file.
 */
?>
<style>
    <?php foreach($data as $file): ?>
    <?php require $file; ?>
    <?php endforeach; ?>
</style>
