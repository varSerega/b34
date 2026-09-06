<?php

namespace Bozenko\MassFieldUpdate;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class InputParser
{
    public static function idsFromText(string $text): array
    {
        $items = preg_split('/[,;\s]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $ids = [];

        foreach ((array)$items as $item) {
            if (ctype_digit($item) && (int)$item > 0) {
                $ids[(int)$item] = (int)$item;
            }
        }

        return array_values($ids);
    }

    public static function idsFromFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Файл не был загружен.');
        }

        $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw new \RuntimeException('Поддерживаются только CSV, TXT и XLSX.');
        }

        if ($extension === 'xlsx') {
            $composerAutoload = $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
            if (is_file($composerAutoload)) {
                require_once $composerAutoload;
            }
            if (!class_exists(IOFactory::class)) {
                throw new \RuntimeException('Для XLSX установите зависимости Composer: composer install.');
            }

            $sheet = IOFactory::load($file['tmp_name'])->getActiveSheet();
            $values = [];
            foreach ($sheet->toArray() as $row) {
                $values[] = (string)($row[0] ?? '');
            }

            return self::idsFromText(implode(',', $values));
        }

        return self::idsFromText((string)file_get_contents($file['tmp_name']));
    }
}
