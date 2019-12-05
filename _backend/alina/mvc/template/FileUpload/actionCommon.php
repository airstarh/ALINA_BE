<?php /** @var $data array|object */ ?>
<?php $url = '/FileUpload/main' ?>

<div>
    <h1>
        <a href="<?= $url ?>">Reload page F5</a>
    </h1>
</div>
<div>
    <!-- Тип кодирования данных, enctype, ДОЛЖЕН БЫТЬ указан ИМЕННО так -->
    <form enctype="multipart/form-data" action="<?= $url ?>" method="POST">
        <!-- Поле MAX_FILE_SIZE должно быть указано до поля загрузки файла -->
        <input type="hidden" name="MAX_FILE_SIZE" value="930000"/>
        <!-- Название элемента input определяет имя в массиве $_FILES -->
        Отправить этот файл: <input name="<?= ALINA_FILE_UPLOAD_KEY ?>[]" multiple type="file"/>
        <input type="submit" value="Send File"/>
    </form>
</div>


