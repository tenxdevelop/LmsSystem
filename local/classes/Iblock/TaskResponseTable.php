<?php

namespace Legacy\Iblock;

use Bitrix\Main\Loader;
use Legacy\General\Constants;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\Entity\ExpressionField;
class TaskResponseTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query){
        $query
            ->where("IBLOCK_ID", Constants::IB_TASK_RESPONSE_IBLOCK)
            ->where("ACTIVE", true);
    }

    public static function withSelect(Query $query)
    {

        $query->registerRuntimeField(
            'TASK_STUDENT',
            new ReferenceField(
                'TASK_STUDENT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_RESPONSE_IBLOCK_TASK_STUDENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'RESPONSE_CONTENT',
            new ReferenceField(
                'RESPONSE_CONTENT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_RESPONSE_IBLOCK_CONTENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'RESPONSE_URL',
            new ReferenceField(
                'RESPONSE_URL',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_RESPONSE_IBLOCK_URL),
                ]
            )
        );

        $query->registerRuntimeField(
            'DOCS',
            new ReferenceField(
                'DOCS',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_RESPONSE_IBLOCK_DOCUMENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'RESPONSE_SCORE',
            new ReferenceField(
                'RESPONSE_SCORE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_RESPONSE_IBLOCK_SCORE),
                ]
            )
        );

        $query->registerRuntimeField(
            'RESPONSE_COMMENT',
            new ReferenceField(
                'RESPONSE_COMMENT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_RESPONSE_IBLOCK_COMMENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'STUDENT_ID',
            new ReferenceField(
                'STUDENT_ID',
                ElementPropertyTable::class,
                [
                    'this.TASK_STUDENT.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TASK_STUDENTS_IBLOCK_STUDENT),
                ],
                ['join_type' => 'LEFT'])
        );


        $query->setSelect([
            'ID',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'STUDENT_ID_VALUE' => 'STUDENT_ID.VALUE',
            'TASK_STUDENT_ID' => 'TASK_STUDENT.VALUE',
            'CONTENT' => 'RESPONSE_CONTENT.VALUE',
            'URL_ANSWER' => 'RESPONSE_URL.VALUE',
            'SCORE' => 'RESPONSE_SCORE.VALUE',
            'DOCUMENT' => 'DOCS.VALUE',
            'COMMENT' => 'RESPONSE_COMMENT.VALUE',
        ]);
    }
    public static function getTaskResponse($userId, $ids)
    {
        try {
            $result = [];

            $query = self::query()
                ->countTotal(true)
                ->withSelect()
                ->withFilterByIDs($ids)
                ->withFilterByStudentIDs($userId)
                ->withOrderByDate();

            $q = $query->exec();
            $resultId = 0;
            while ($arr = $q->fetch()) {
                $id = $arr['ID'];
                if (!isset($result[$id])) {
                    if ($resultId <= $id)
                        $resultId = $id;
                    $result[$id] = [
                        'id' => $arr['TASK_STUDENT_ID'],
                        'content' => $arr['CONTENT'],
                        'urlAnswer' => $arr['URL_ANSWER'],
                        'createdAt' => $arr['ACTIVE_FROM'] !== null ? $arr['ACTIVE_FROM']->format('c') : null,
                        'score' => $arr['SCORE'],
                        'comment' => $arr['COMMENT'],
                        'fileAnswer' => [],
                    ];
                }

                if (!empty($arr['DOCUMENT'])) {
                    $docs = $arr['DOCUMENT'];
                    $result[$id]['fileAnswer'][] = self::getFileInfo($docs);
                }
            }

            return $result[$resultId];

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }
    public static function setTaskResponse($properties)
    {
        $el = new \CIBlockElement;

        $arLoadProductArray = [
            "MODIFIED_BY"    => $properties["userId"],
            "IBLOCK_SECTION_ID" => true,
            "IBLOCK_ID"      => Constants::IB_TASK_RESPONSE_IBLOCK,
            "PROPERTY_VALUES"=> $properties["propertyValues"],
            "NAME"           => $properties["username"] . " ответ на задание " . $properties["taskname"],
            "ACTIVE"         => "Y",
            "PREVIEW_TEXT"   => "",
            "DETAIL_TEXT"    => "",
            "ACTIVE_FROM" => new DateTime(),
        ];

        if ($elementId = $el->Add($arLoadProductArray)) {
            return [
                'success' => true,
                'id' => $elementId,
                'message' => 'Ответ на задание успешно добавлен'
            ];
        } else {
            return [
                'success' => false,
                'error' => $el->LAST_ERROR,
                'error_code' => 'ADD_ELEMENT_ERROR'
            ];
        }
    }

    public static function withOrderByDate(Query $query, $order = 'ASC')
    {
        $query->addOrder('ACTIVE_FROM', $order);
    }

    public static function withFilterByIDs(Query $query, $ids)
    {
        $query->whereIn('TASK_STUDENT_ID', $ids);
    }
    public static function withFilterByStudentIDs(Query $query, $ids)
    {
        $query->whereIn('STUDENT_ID_VALUE', $ids);
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
