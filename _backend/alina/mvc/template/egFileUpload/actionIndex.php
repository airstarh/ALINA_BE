<?php /** @var $data array|object */ ?>
<?php $url = '/egFileUpload' ?>

	<div>
		<a href="/egFileUpload">Reload page F5</a>
	</div>
	<div>
		<!-- Тип кодирования данных, enctype, ДОЛЖЕН БЫТЬ указан ИМЕННО так -->
		<form enctype="multipart/form-data" action="<?= $url ?>" method="POST">
			<!-- Поле MAX_FILE_SIZE должно быть указано до поля загрузки файла -->
			<input type="hidden" name="MAX_FILE_SIZE" value="930000"/>
			<!-- Название элемента input определяет имя в массиве $_FILES -->
			Отправить этот файл: <input name="userfile[]" multiple type="file"/>
			<input type="submit" value="Send File"/>
		</form>
	</div>


<?php

echo '<pre>';
print_r($_FILES);
echo '</pre>';

echo '<pre>';
print_r($_POST);
echo '</pre>';
?>