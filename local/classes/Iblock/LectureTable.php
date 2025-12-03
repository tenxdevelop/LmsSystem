<?php

namespace Legacy\Iblock;

use Legacy\General\Constants;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\Entity\ExpressionField;

class LectureTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query){
        $query
            ->where("IBLOCK_ID", Constants::IB_LECTURE_IBLOCK)
            ->where("ACTIVE", true);
    }

    public static function withSelect(Query $query)
    {

        $query->registerRuntimeField(
            'DESCRIPT',
            new ReferenceField(
                'DESCRIPT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURE_IBLOCK_DESCRIPTION),
                ]
            )
        );

        $query->registerRuntimeField(
            'CONTENTS',
            new ReferenceField(
                'CONTENTS',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURE_IBLOCK_CONTENT),
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
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURE_IBLOCK_DOCUMENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'COURSE',
            new ReferenceField(
                'COURSE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_LECTURE_IBLOCK_COURSE_ID),
                ]
            )
        );

        $query->setSelect([
            'ID',
            'NAME',
            'CODE',
            'PREVIEW_TEXT',
            'DETAIL_TEXT',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'COURSE_ID' => 'COURSE.VALUE',
            'DESCRIPTION' => 'DESCRIPT.VALUE',
            'CONTENT' => 'CONTENTS.VALUE',
            'DOCUMENT' => 'DOCS.VALUE',
        ]);
    }

    public static function getAll($courseId)
    {
        try {
            $result = [];

            $query = self::query()
                ->countTotal(true)
                ->withSelect()
                ->withFilterByCourseIDs($courseId);

            $q = $query->exec();

            while ($arr = $q->fetch()) {
                $id = $arr['ID'];
                if (!isset($result[$id])) {

                    $result[$id] = [
                        'id' => $id,
                        'courseId' => $arr['COURSE_ID'],
                        'name' => $arr['NAME'],
                        'description' => $arr['DESCRIPTION'],
                        'content' => $arr['CONTENT'],
                        'createdAt' => $arr['ACTIVE_FROM'] !== null ? $arr['ACTIVE_FROM']->format('c') : null,
                        'files' => [],
                    ];
                }

                if (!empty($arr['DOCUMENT'])) {
                    $docs = $arr['DOCUMENT'];
                    $result[$id]['files'][] = self::getFileInfo($docs);
                }
            }

            $result = array_values($result);

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }

    public static function getById($id)
    {
        try {
            $result = [];

            $query = self::query()
                ->countTotal(true)
                ->withSelect()
                ->withFilterByIDs($id);

            $q = $query->exec();

            while ($arr = $q->fetch()) {
                $id = $arr['ID'];
                if (!isset($result[$id])) {

                    $result[$id] = [
                        'id' => $id,
                        'courseId' => $arr['COURSE_ID'],
                        'name' => $arr['NAME'],
                        'description' => $arr['DESCRIPTION'],
                        'content' => $arr['CONTENT'],
                        'createdAt' => $arr['ACTIVE_FROM'] !== null ? $arr['ACTIVE_FROM']->format('c') : null,
                        'files' => [],
                    ];
                }

                if (!empty($arr['DOCUMENT'])) {
                    $docs = $arr['DOCUMENT'];
                    $result[$id]['files'][] = self::getFileInfo($docs);
                }
            }

            return $result[$id];

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }

    public static function withFilterByCourseIDs(Query $query, $ids)
    {
        $query->whereIn('COURSE_ID', $ids);
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
