<?php
namespace Legacy\API;

use Legacy\General\Constants;
use Legacy\Iblock\LectureTable;
use Legacy\Iblock\CourseStudentTable;
use Bitrix\Main\Loader;

class LectureApi
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

            $courseId = $arRequest['courseId'] ?? null;

            if ($courseId === null) {
                return "error: don't have course id";
            }

            $userId = $USER->GetID();
            if(!CourseStudentTable::existCourseInStudent($userId, $courseId))
            {
                return "error: the course is not available to the student";
            }

            $result = LectureTable::getAll($courseId);
        }

        return $result;
    }

    public static function getById($arRequest)
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

        if (!Loader::includeModule('iblock')) {
            return "error: don't includeModule iblock";
        }

        $id = $arRequest['id'] ?? null;

        if ($id === null) {
            return "error: don't have lecture id";
        }

        $result = LectureTable::getById($id);

        $userId = $USER->GetID();
        if(!CourseStudentTable::existCourseInStudent($userId, $result["courseId"]))
        {
            return "error: the course is not available to the student";
        }

        return $result;
    }

}