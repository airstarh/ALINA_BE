<!DOCTYPE html>
<html lang="ru">
<head>
    <meta name="mobile-web-app-capable" content="yes">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.svg">
    <base target="_parent">
    <title>B_O_R_G</title>
    <?= BorgTemplate::allCss(); ?>
    <?= BorgTemplate::allJs(); ?>
</head>

<body id="borg-real-body" class="borg-main-bg borg-main-txt">
<?= BorgTemplate::allContent(); ?>
</body>
</html>