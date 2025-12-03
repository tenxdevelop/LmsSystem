<?php
namespace Legacy\API;

use Legacy\General\DataProcessor;
use Legacy\General\Constants;
use Legacy\Iblock\CourseStudentTable;
use Bitrix\Main\Loader;

class FileUploadAPI
{
    public static function uploadToIblock($arRequest)
    {
        global $USER;

        // Проверка авторизации
        if (!$USER->IsAuthorized()) {
            return [
                'success' => false,
                'error' => 'Доступ запрещен. Требуется авторизация.',
                'error_code' => 'ACCESS_DENIED'
            ];
        }

        $fileId = (int)($arRequest['id'] ?? 0);

        if ($fileId <= 0) {
            return [
                'success' => false,
                'error' => 'Не указан ID файла',
                'error_code' => 'FILE_ID_REQUIRED'
            ];
        }

        $fileInfo = \CFile::GetFileArray($fileId);

        if (!$fileInfo) {
            return [
                'success' => false,
                'error' => 'Файл не найден',
                'error_code' => 'FILE_NOT_FOUND'
            ];
        }

        // Проверяем, существует ли файл физически
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $fileInfo['SRC'];
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'Файл отсутствует на диске',
                'error_code' => 'FILE_MISSING'
            ];
        }

        self::outputFile($filePath, $fileInfo['ORIGINAL_NAME'], $fileInfo['CONTENT_TYPE']);

        exit;
    }

    private static function outputFile($filePath, $fileName, $contentType)
    {
        // Очищаем буфер вывода
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Устанавливаем заголовки
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Читаем файл и отправляем пользователю
        readfile($filePath);
    }
}