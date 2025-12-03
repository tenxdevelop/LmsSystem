<?php

namespace Legacy\Iblock;

use Legacy\General\Constants;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\Entity\ExpressionField;

class TaskStudentTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query)
    {
        $query
            ->where("IBLOCK_ID", Constants::IB_TASK_STUDENTS_IBLOCK)
            ->where("ACTIVE", true);
    }
    public static function withSelect(Query $query)
    {

        $query->registerRuntimeField(
            'STUDENT_ID',
            new ReferenceField(
                'STUDENT_ID',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_STUDENTS_IBLOCK_STUDENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'TASK_ID',
            new ReferenceField(
                'TASK_ID',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_STUDENTS_IBLOCK_TASK),
                ]
            )
        );

        $query->registerRuntimeField(
            'STATUS_PROP',
            new ReferenceField(
                'STATUS_PROP',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_STUDENTS_IBLOCK_STATUS),
                ]
            )
        );

        $query->registerRuntimeField(
            'STATUS',
            new ReferenceField(
                'STATUS',
                PropertyEnumerationTable::class,
                [
                    '=this.STATUS_PROP.VALUE' => 'ref.ID',
                ]
            )
        );

        $query->registerRuntimeField(
            'TASK_TABLE',
            new ReferenceField(
                'TASK_TABLE',
                \Bitrix\Iblock\ElementTable::class,
                [
                    'this.TASK_ID.VALUE' => 'ref.ID',
                    'ref.IBLOCK_ID' => new SqlExpression('?', Constants::IB_TASK_IBLOCK),
                ],
                ['join_type' => 'LEFT']
            )
        );

        $query->registerRuntimeField(
            'DEADLINE',
            new ReferenceField(
                'DEADLINE',
                ElementPropertyTable::class,
                [
                    'this.TASK_ID.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_IBLOCK_DEAD_LINE),
                ],
                ['join_type' => 'LEFT'])
        );

        $query->registerRuntimeField(
            'TASK_TABLE_CONTENT',
            new ReferenceField(
                'TASK_TABLE_CONTENT',
                ElementPropertyTable::class,
                [
                    'this.TASK_ID.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_IBLOCK_CONTENT),
                ],
                ['join_type' => 'LEFT'])
        );

        $query->registerRuntimeField(
            'DOCS',
            new ReferenceField(
                'DOCS',
                ElementPropertyTable::class,
                [
                    'this.TASK_ID.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_IBLOCK_DOCUMENT),
                ],
                ['join_type' => 'LEFT'])
        );

        $query->registerRuntimeField(
            'COURSE',
            new ReferenceField(
                'COURSE',
                ElementPropertyTable::class,
                [
                    'this.TASK_ID.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_IBLOCK_COURSE),
                ],
                ['join_type' => 'LEFT'])
        );

        $query->setSelect([
            'ID',
            'NAME',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'STUDENT_ID_VALUE' => 'STUDENT_ID.VALUE',
            'STATUS_VALUE' => 'STATUS.VALUE',
            'TASK_NAME' => 'TASK_TABLE.NAME',
            'TASK_DEAD_LINE' => 'DEADLINE.VALUE',
            'TASK_CONTENT' => 'TASK_TABLE_CONTENT.VALUE',
            'DOCUMENT' => 'DOCS.VALUE',
            'COURSE_ID' => 'COURSE.VALUE',
        ]);
    }
    public static function getAll($userId, $courseId)
    {
        try {
            $result = [];

            $query = self::query()
                ->countTotal(true)
                ->withSelect()
                ->withFilterByCourseIDs($courseId)
                ->withFilterByStudentIDs($userId);

            $q = $query->exec();

            while ($arr = $q->fetch()) {
                $id = $arr['ID'];
                if (!isset($result[$id])) {

                    $result[$id] = [
                        'id' => $id,
                        'name' => $arr['TASK_NAME'],
                        'description' => $arr['TASK_CONTENT'],
                        'createdAt' => $arr['ACTIVE_FROM'] !== null ? $arr['ACTIVE_FROM']->format('c') : null,
                        'status' => $arr['STATUS_VALUE'],
                        'endDate' => $arr['TASK_DEAD_LINE'],
                        'assignedFiles' => [],
                    ];
                }

                if (!empty($arr['DOCUMENT'])) {
                    $docs = $arr['DOCUMENT'];
                    $result[$id]['assignedFiles'][] = self::getFileInfo($docs);
                }
            }

            $result = array_values($result);

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }
    public static function getByIds($userId, $id)
    {
        try {

            $result = [];

            $query = self::query()
                ->countTotal(true)
                ->withSelect()
                ->withFilterByStudentIDs($userId)
                ->withFilterByIDs($id);

            $q = $query->exec();

            while ($arr = $q->fetch()) {
                $id = $arr['ID'];
                if (!isset($result[$id])) {

                    $result[$id] = [
                        'id' => $id,
                        'name' => $arr['TASK_NAME'],
                        'description' => $arr['TASK_CONTENT'],
                        'createdAt' => $arr['ACTIVE_FROM'] !== null ? $arr['ACTIVE_FROM']->format('c') : null,
                        'status' => $arr['STATUS_VALUE'],
                        'endDate' => $arr['TASK_DEAD_LINE'],
                        'assignedFiles' => [],
                    ];
                }

                if (!empty($arr['DOCUMENT'])) {
                    $docs = $arr['DOCUMENT'];
                    $result[$id]['assignedFiles'][] = self::getFileInfo($docs);
                }
            }

            return $result[$id];

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }

    public static function getTaskNameById($id)
    {
        try {

            $query = self::query()
                ->withSelect()
                ->withFilterByIDs($id);

            $q = $query->exec();

            $arr = $q->fetch();

            return $arr['TASK_NAME'];

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }
    public static function withFilterByCourseIDs(Query $query, $ids)
    {
        $query->whereIn('COURSE_ID', $ids);
    }

    public static function withFilterByStudentIDs(Query $query, $ids)
    {
        $query->whereIn('STUDENT_ID_VALUE', $ids);
    }

    public static function withFilterByIDs(Query $query, $ids)
    {
        $query->whereIn('ID', $ids);
    }

    public static function getFileInfo($fileId)
    {
        $file = \CFile::GetFileArray($fileId);

        if (!$file || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file['SRC'])) {
            return null;
        }

        return [
            'id' => $fileId,
            'name' => $file['ORIGINAL_NAME'],
            'date' => self::formatDate($file['TIMESTAMP_X'])
        ];
    }

    public static function formatDate($date, $format = 'd.m.Y H:i')
    {
        if (!$date) {
            return null;
        }

        // Если это строка с датой
        if (is_string($date)) {
            try {
                $dateTime = new \DateTime($date);
                return $dateTime->format($format);
            } catch (\Exception $e) {
                return $date;
            }
        }

        // Если это объект DateTime или Bitrix\Main\Type\DateTime
        if ($date instanceof \DateTime) {
            return $date->format($format);
        }

        return $date;
    }
}

