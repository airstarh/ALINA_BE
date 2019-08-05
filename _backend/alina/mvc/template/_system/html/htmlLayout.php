<?php /** @var $data \alina\mvc\view\html */ ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<!--region	Bootstrap requires.-->
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<?= $data->css() ?>
	<?= $data->js() ?>

	<!--region Bootstrap Framework recommendations.-->
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<!--endregion	Bootstrap requires.-->

	<title><?= \alina\app::getConfig('title'); ?></title>

</head>
<body>

<!-- Fixed navbar -->

<?=  (new \alina\mvc\view\html())->piece('/_system/html/menu.php') ?>

<div class="container theme-showcase" role="main">

	<!-- Main jumbotron for a primary marketing message or call to action -->
	<div class="jumbotron">
		<h1><?= \alina\app::getConfig('title'); ?></h1>
	</div>

	<?= $data->messages(); ?>
	<?= $data->content(); ?>

	<!-- ToDo: Security! Delete on PROD.-->
	<?php if (ALINA_MODE !== 'PROD') : ?>
		<div>
            <?php
            $h1 = 'Alina Details';
            print_r("<h1>{$h1}</h1>");

            echo '<pre>';
            print_r(getallheaders());
            echo '</pre>';

            echo '<pre>';
            print_r($_SERVER);
            echo '</pre>';

            echo '<pre>';
            print_r(\alina\app::get()->router);
            echo '</pre>';


            //            echo '<pre>';
            //            print_r([
            //                'currentController' => \alina\app::get()->currentController,
            //                'currentAction'     => \alina\app::get()->currentAction,
            //            ]);
            //            echo '</pre>';

            print_r(reportSpentTime());

            ?>
		</div>
    <?php endif; ?>

</div> <!-- /container -->
</body>
</html>
