<?php
namespace Legacy\API;

use Legacy\General\DataProcessor;
use Legacy\General\Constants;
use Legacy\Iblock\CourseStudentTable;
use Bitrix\Main\Loader;

class CourseApi
{
    public static function getAll($arRequest)
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

        $result = [];

        if (Loader::includeModule('iblock')) {
            $userId = $USER->GetID();
            $result = CourseStudentTable::getAll($userId);
        }

        return $result;
    }

}