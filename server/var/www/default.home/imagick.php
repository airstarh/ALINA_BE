<?php

if (!extension_loaded('imagick')) {
    die("ERROR: Imagick extension is NOT loaded.\n");
}

echo "OK: Imagick extension loaded.\n";

$imagick = new Imagick();
$version = $imagick->getVersion();
echo "ImageMagick version: " . ($version['versionString'] ?? 'unknown') . "\n\n";

try {
    // Создаём холст 200×200, красный фон
    $image = new Imagick();
    $image->newImage(200, 200, new ImagickPixel('red'));

    // Убрали annotateImage — больше нет ошибки про шрифт

    // Изменяем размер до 100×100
    $image->resizeImage(100, 100, Imagick::FILTER_LANCZOS, 1);

    $outputPath = '/tmp/test_imagick_output.png';
    $image->writeImage($outputPath);

    echo "SUCCESS: Image created and saved to $outputPath\n";
    echo "File size: " . filesize($outputPath) . " bytes\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
