<?php
namespace Legacy\Iblock;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Entity\Query;
use Legacy\General\Constants;

class UserRepository
{
    public static function searchStudents($searchQuery)
    {
        $studentGroupId = 6;
        $students = [];

        $query = new Query(UserTable::getEntity());
        $query->setSelect([
            'ID',
            'NAME',
            'LAST_NAME',
            'SECOND_NAME'
        ]);

        $filter = [
            '=ACTIVE' => 'Y',
            '=GROUPS.GROUP_ID' => $studentGroupId,
            [
                'LOGIC' => 'OR',
                '%NAME' => $searchQuery,
                '%LAST_NAME' => $searchQuery,
                '%SECOND_NAME' => $searchQuery,
                '%EMAIL' => $searchQuery
            ]
        ];

        $query->setFilter($filter);
        $query->setOrder(['LAST_NAME' => 'ASC', 'NAME' => 'ASC']);

        $result = $query->exec();

        while ($user = $result->Fetch()) {
            $students[] = [
                'id' => (int)$user['ID'],
                'firstName' => $user['NAME'],
                'lastName' => $user['LAST_NAME'],
                'secondName' => $user['SECOND_NAME']
            ];
        }

        return $students;
    }

    public static function getStudents($page, $limit)
    {
        $studentGroupId = 6;
        $students = [];
        $offset = ($page - 1) * $limit;

        $query = new Query(UserTable::getEntity());
        $query->setSelect([
            'ID',
            'NAME',
            'LAST_NAME',
            'SECOND_NAME'
        ]);

        $filter = [
            '=ACTIVE' => 'Y',
            '=GROUPS.GROUP_ID' => $studentGroupId
        ];

        $query->setFilter($filter);

        $query->setOffset($offset);
        $query->setLimit($limit);
        $query->setDistinct(true);

        $query->setOrder(['LAST_NAME' => 'ASC', 'NAME' => 'ASC']);

        $result = $query->exec();

        while ($user = $result->Fetch()) {
            $students[] = [
                'id' => (int)$user['ID'],
                'firstName' => $user['NAME'],
                'lastName' => $user['LAST_NAME'],
                'secondName' => $user['SECOND_NAME']
            ];
        }

        return $students;
    }

    public static function getTotalStudentCount()
    {
        $studentGroupId = 6;
        $query = new Query(UserTable::getEntity());
        $query->setSelect(['ID']);

        $filter = [
            '=ACTIVE' => 'Y',
            '=GROUPS.GROUP_ID' => $studentGroupId
        ];
        $query->setFilter($filter);
        $query->setDistinct(true);

        $totalCount = $query->exec()->getSelectedRowsCount();

        return $totalCount;

    }
}
