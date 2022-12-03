<?php
/** @var $data stdClass */

use alina\utils\Request;

?>
<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($data->tale as $tale) { ?>

        <url>
            <loc>https://<?= Request::obj()->DOMAIN ?>/tale/upsert/<?= $tale->id ?></loc>
            <lastmod><?= \alina\utils\DateTime::toHumanDate($tale->publish_at) ?></lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.8</priority>
        </url>
    <?php } ?>
</urlset>
