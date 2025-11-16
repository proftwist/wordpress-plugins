<?php
// debug-translations.php
require_once('../../../wp-load.php');

$locale = get_locale();
echo "Текущая локаль: " . $locale . "\n";

// Проверяем существование файлов
$json_files = glob(__DIR__ . '/languages/*.json');
echo "Найдено JSON файлов: " . count($json_files) . "\n";

foreach ($json_files as $file) {
    echo "Файл: " . basename($file) . "\n";
}

// Принудительно создаем JSON файлы
if (function_exists('wp_i18n')) {
    system('cd ' . __DIR__ . ' && wp i18n make-json languages --no-purge');
    echo "JSON файлы пересозданы!\n";
}