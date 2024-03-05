<?php
/**
 * @var array $data : Array of paths to a file.
 */
?>
<script>
    <?php foreach($data as $file): ?>
    <?php require $file; ?>
    <?php endforeach; ?>
</script>
