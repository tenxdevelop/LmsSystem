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

class CourseStudentTable extends \Bitrix\Iblock\ElementTable
{
    public static function setDefaultScope($query){
        $query
            ->where("IBLOCK_ID", Constants::IB_COURSE_STUDENTS_IBLOCK)
            ->where("ACTIVE", true);
    }

    public static function withSelect(Query $query)
    {
        $query->registerRuntimeField(
            'STUDENTS',
            new ReferenceField(
                'STUDENTS',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSE_STUDENTS_IBLOCK_STUDENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'COURSES',
            new ReferenceField(
                'COURSES',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSE_STUDENTS_IBLOCK_COURSE_ID),
                ]
            )
        );

        $query->setSelect([
            'ID',
            'STUDENT_ID' => 'STUDENTS.VALUE',
            'COURSE_ID' => 'COURSES.VALUE',
            ]);
    }

    public static function withSelectDetails(Query $query)
    {

        $query->registerRuntimeField(
            'STUDENT_ID',
            new ReferenceField(
                'STUDENT_ID',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSE_STUDENTS_IBLOCK_STUDENT),
                ]
            )
        );

        $query->registerRuntimeField(
            'COURSE_ID',
            new ReferenceField(
                'COURSE_ID',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSE_STUDENTS_IBLOCK_COURSE_ID),
                ]
            )
        );

        $query->registerRuntimeField(
            'COURSE_TABLE',
            new ReferenceField(
                'COURSE_TABLE',
                \Bitrix\Iblock\ElementTable::class,
                [
                    'this.COURSE_ID.VALUE' => 'ref.ID', // СОЕДИНЕНИЕ через значение свойства COURSE_ID
                    'ref.IBLOCK_ID' => new SqlExpression('?', Constants::IB_COURSE_IBLOCK), // Ограничение по инфоблоку курсов
                ],
                ['join_type' => 'LEFT']
            )
        );

        $query->registerRuntimeField(
            'COURSE_DESCRIPTION',
            new ReferenceField(
                'COURSE_DESCRIPTION',
                ElementPropertyTable::class,
                [
                    'this.COURSE_ID.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSE_IBLOCK_DESCRIPTION),
                ],
                ['join_type' => 'LEFT'])
        );

        $query->registerRuntimeField(
            'COURSE_PICTURES',
            new ReferenceField(
                'COURSE_PICTURES',
                ElementPropertyTable::class,
                [
                    'this.COURSE_ID.VALUE' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_COURSE_IBLOCK_PICTURE),
                ],
                ['join_type' => 'LEFT'])
        );

        $query->setSelect([
            'ID',
            'COURSE_NAME' => 'COURSE_TABLE.NAME',
            'CODE',
            'PREVIEW_TEXT',
            'DETAIL_TEXT',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'STUDENT' => 'STUDENT_ID.VALUE',
            'COURSE' => 'COURSE_ID.VALUE',
            'DESCRIPTION' => 'COURSE_DESCRIPTION.VALUE',
            'PICTURE' => 'COURSE_PICTURES.VALUE',
        ]);
    }

    public static function existCourseInStudent($studentId, $courseId)
    {
        $result = false;

        try {

            $query = self::query()
                    ->countTotal(true)
                    ->withSelect()
                    ->where('STUDENT_ID', $studentId)
                    ->where('COURSE_ID', $courseId);

            $q = $query->exec();

            $result = $q->getCount() !== 0;

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
        return $result;
    }

    public static function getAll($studentId)
    {
        try {
            $result = [];

            $query = self::query()
                ->countTotal(true)
                ->withSelectDetails()
                ->withFilterByStudentIDs($studentId);

            $q = $query->exec();

            while ($arr = $q->fetch()) {
                $result[] = [
                    'id' => $arr['COURSE'],
                    'name' => $arr['COURSE_NAME'],
                    'description' => $arr['DESCRIPTION'],
                    'coverBase64' => self::getFileBase64($arr['PICTURE']),
                ];
            }
            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
        }
    }

    public static function withFilterByStudentIDs(Query $query, $ids)
    {
        $query->whereIn('STUDENT', $ids);
    }

    public static function getFileBase64($pictureId)
    {
        $file = \CFile::GetFileArray($pictureId);
        if (!$file || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file['SRC'])) {
            return null;
        }

        $filePath = $_SERVER['DOCUMENT_ROOT'] . $file['SRC'];
        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            return null;
        }

        $base64 = base64_encode($fileContent);
        $mimeType = $file['CONTENT_TYPE'] ?: mime_content_type($filePath);

        return $base64;
    }
}
