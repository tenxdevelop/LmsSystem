<?php
namespace Legacy\API;

use Legacy\General\Constants;
use Bitrix\Main\Loader;
use Legacy\Main\CLUser;
use Exception;
class AuthApi
{
    public static function login($arRequest)
    {
        try {
            global $USER;

            $login = trim($arRequest['login']);
            $password = trim($arRequest['password']);

            if (empty($login) || empty($password)) {
                throw new Exception('Логин и пароль обязательны для заполнения');
            }

            $authResult = $USER->Login($login, $password, 'Y');

            if ($authResult['TYPE'] === 'ERROR') {
                throw new Exception($authResult['MESSAGE']);
            }

            $currentUser = $USER->GetByLogin($login)->Fetch();

            if (!$currentUser) {
                throw new Exception('Пользователь не найден');
            }

            $USER->Authorize($currentUser['ID']);

            return [
                'success' => true,
                'data' => [
                    'id' => (int)$currentUser['ID'],
                    'login' => $currentUser['LOGIN'],
                    'email' => $currentUser['EMAIL'],
                    'name' => $currentUser['NAME'],
                    'last_name' => $currentUser['LAST_NAME']
                ],
                'message' => 'Авторизация успешна'
            ];
        }
        catch (Exception $exception)
        {
            return [
                'success' => false,
                'error' => $exception->getMessage()
            ];
        }

    }

    public static function logout($arRequest)
    {
        global $USER;
        $USER->Logout();

        return [
            'success' => true,
            'message' => 'Выход выполнен успешно'
        ];
    }

    public static function register($arRequest)
    {
        try {
            $user = new CLUser;

            $email = trim($arRequest['email']);
            $login = trim($arRequest['login']);
            $password = trim($arRequest['password']);

            $arFields = [
                'EMAIL' => $email,
                'LOGIN' => $login,
                'ACTIVE' => 'Y',
                'GROUP_ID' => [2],
                'PASSWORD' => $password,
                'CONFIRM_PASSWORD' => $password
            ];

            $ID = $user->Add($arFields);

            if (intval($ID) > 0) {

                // Авторизуем пользователя
                global $USER;
                $USER->Authorize($ID);

                $newUser = $USER->GetByID($ID)->Fetch();

                return [
                    'success' => true,
                    'data' => [
                        'id' => (int)$ID,
                        'login' => $newUser['LOGIN'],
                        'email' => $newUser['EMAIL'],
                        'name' => $newUser['NAME'],
                        'last_name' => $newUser['LAST_NAME']
                    ],
                    'message' => 'Пользователь успешно зарегистрирован'
                ];
            } else {
                throw new Exception($user->LAST_ERROR);
            }
        } catch (Exception $exception)
        {
            return [
                'success' => false,
                'error' => $exception->getMessage()
            ];
        }

    }

}