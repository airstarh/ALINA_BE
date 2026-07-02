<?php

if (! extension_loaded('imagick')) {
    http_response_code(500);
    die('ERROR: Imagick extension is NOT loaded.');
}

try {
    // 1. Генерируем случайный HEX-цвет (например, #A3F1C9)
    $randomColor = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);

    // 2. Создаём холст 200×200 с этим случайным цветом
    $image = new Imagick();
    $image->newImage(200, 200, new ImagickPixel($randomColor));

    // 3. Добавляем текст (опционально). Если шрифт не найден — закомментируй строку setFont
    $draw = new ImagickDraw();
    $draw->setFillColor('white');
    $draw->setFontSize(24);
    $draw->setTextAlignment(Imagick::ALIGN_CENTER);

    // Попробуй Liberation-Sans, если DejaVu-Sans нет в образе
    $draw->setFont('DejaVu-Sans');

    $image->annotateImage($draw, 100, 110, 0, 'Reload me!');

    // 4. Меняем размер до 100×100
    $image->resizeImage(100, 100, Imagick::FILTER_LANCZOS, 1);

    // 5. ВАЖНО: явно задаём формат перед getImageBlob()
    $image->setImageFormat('png');

    // 6. Отправляем заголовок и бинарные данные
    header('Content-Type: image/png');
    echo $image->getImageBlob();
}
catch (ImagickException $e) {
    error_log('Imagick error: ' . $e->getMessage());
    http_response_code(500);
    die('Image generation failed. Check logs.');
}
catch (Throwable $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    http_response_code(500);
    die('Unexpected error.');
}
