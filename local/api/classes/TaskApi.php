<?php
namespace Legacy\API;

use Legacy\General\Constants;
use Legacy\Iblock\CourseStudentTable;
use Bitrix\Main\Loader;
use Legacy\Iblock\TaskResponseTable;
use Legacy\Iblock\TaskStudentTable;

class TaskApi
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

            $result = TaskStudentTable::getAll($userId, $courseId);
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

        $id = $arRequest['id'] ?? null;

        if ($id === null) {
            return "error: don't have task id";
        }

        if (!Loader::includeModule('iblock')) {
            return "error: don't include module iblock";
        }

        $userId = $USER->GetID();
        $task = TaskStudentTable::getByIds($userId, $id);

        $lastAnswer = TaskResponseTable::getTaskResponse($userId, $id);

        $result = [
            'id' => $task['id'],
            'name' => $task['name'],
            'description' => $task['description'],
            'createdAt' => $task['createdAt'],
            'status' => $task['status'],
            'endDate' => $task['endDate'],
            'assignedFiles' => $task['assignedFiles'],
            'textAnswer' => $lastAnswer['content'],
            'urlAnswer' => $lastAnswer['urlAnswer'],
            'mark' => $lastAnswer['score'],
            'review' => $lastAnswer['comment'],
            'fileAnswer' => $lastAnswer['fileAnswer'][0],
        ];

        return $result;
    }
}