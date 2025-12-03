<?php
namespace Legacy\API;

use Legacy\General\Constants;
use Legacy\Iblock\TaskResponseTable;
use Bitrix\Main\Loader;
use Legacy\Iblock\TaskStudentTable;

class TaskResponseApi
{
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

        $result = [];

        if (Loader::includeModule('iblock')) {

            $responseId = $arRequest['taskStudentId'] ?? null;

            if ($responseId === null) {
                return "error: don't have response id";
            }

            $userId = $USER->GetID();

            $result = TaskResponseTable::getTaskResponse($userId, $responseId);
        }

        return $result;
    }

    public static function addResponse($arRequest)
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

        $responseId = $arRequest['id'] ?? null;
        $userId = $USER->GetID();
        $userName = $USER->GetFirstName();
        $content = $arRequest['textAnswer'] ?? null;
        $urlAnswer = $arRequest['urlAnswer'] ?? null;
        $file = $arRequest['fileAnswer'];

        if ($responseId === null) {
            return "error: don't have response id";
        }

        if (!Loader::includeModule('iblock')) {
            return "error: don't have iblock module";
        }

        $taskName = TaskStudentTable::getTaskNameById($responseId);

        $properties = [
            "userId" => $userId,
            "taskname" => $taskName,
            "propertyValues" => [
                "CONTENT" => $content,
                "TASK_STUDENT" => $responseId,
                "URL" => $urlAnswer
            ],
            "username" => $userName
        ];

        $result = TaskResponseTable::setTaskResponse($properties);

        $fileArray = [
            'name' => $file['name'],
            'type' => $file['type'],
            'tmp_name' => $file['tmp_name'],
            'error' => $file['error'],
            'size' => $file['size'],
        ];

        $fileId = \CFile::SaveFile($fileArray, 'iblock');

        $propertyCode = Constants::IB_PROP_TASK_RESPONSE_IBLOCK_DOCUMENT;

        \CIBlockElement::SetPropertyValueCode(
            $result['id'],
            $propertyCode,
            array_merge(
                self::getCurrentPropertyValues($result['id'], $propertyCode),
                [['VALUE' => $fileId]]
            )
        );

        return $result;

    }

    private static function getCurrentPropertyValues($elementId, $propertyCode)
    {
        $values = [];
        $propertyRes = \CIBlockElement::GetProperty(
            null,
            $elementId,
            [],
            ['CODE' => $propertyCode]
        );

        while ($prop = $propertyRes->Fetch()) {
            if ($prop['VALUE']) {
                $values[] = [
                    'VALUE' => $prop['VALUE']
                ];
            }
        }

        return $values;
    }

}