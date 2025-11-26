<?php
namespace Legacy\API;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Entity\Query;
use Legacy\General\Constants;
use Legacy\Iblock\UserRepository;

class UserApi
{
    public static function getStudents($arRequest)
    {
        global $USER;

        // Проверка авторизации (опционально)
        if (!$USER->IsAuthorized()) {
            return [
                'success' => false,
                'error' => 'Доступ запрещен. Требуется авторизация.'
            ];
        }

        if (!Loader::includeModule('main')) {
            return [
                'success' => false,
                'error' => 'Модуль main не подключен'
            ];
        }

        $studentGroupId = 6; // ID группы "Студенты"

        $page = max(1, (int)($arRequest['page'] ?? 1));
        $limit = max(1, min(100, (int)($arRequest['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        try {
            $totalCount = UserRepository::getTotalStudentCount();


            $users = UserRepository::getStudents($page, $limit);

            return [
                'success' => true,
                'totalCount' => $totalCount,
                'currentPage' => $page,
                'pageSize' => $limit,
                'totalPages' => ceil($totalCount / $limit),
                'students' => $users
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка при получении списка студентов: ' . $e->getMessage()
            ];
        }
    }

    public static function searchStudents($arRequest)
    {
        global $USER;

        // Проверка авторизации (опционально)
        if (!$USER->IsAuthorized()) {
            return [
                'success' => false,
                'error' => 'Доступ запрещен. Требуется авторизация.'
            ];
        }

        if (!Loader::includeModule('main')) {
            return [
                'success' => false,
                'error' => 'Модуль main не подключен'
            ];
        }

        if (!Loader::includeModule('iblock')) {
            return [
                'success' => false,
                'error' => 'Модуль iblock не подключен'
            ];
        }

        $searchQuery = trim($arRequest['query'] ?? '');

        if (empty($searchQuery)) {
            return [
                'success' => false,
                'error' => 'Пустой поисковый запрос'
            ];
        }

        try {

            $users = UserRepository::searchStudents($searchQuery);

            return [
                'success' => true,
                'found' => count($users),
                'students' => $users
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка при поиске студентов: ' . $e->getMessage()
            ];
        }
    }

}