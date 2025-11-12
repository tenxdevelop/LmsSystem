<?php

namespace Legacy\Iblock;
use Legacy\General\Constants;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\DateTime;


class TestTable extends \Bitrix\Iblock\ElementTable
{
	public static function setDefaultScope($query){
        $query
            ->where("IBLOCK_ID", Constants::IB_TEST_IBLOCK)
            ->where("ACTIVE", true);
    }
	
	
	public static function withSelect(Query $query)
    {
        // Привязка свойства "Телефон"
        $query->registerRuntimeField(
            'PHONE',
            new ReferenceField(
                'PHONE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_PHONE),
                ]
            )
        );

        // Привязка свойства "Город"
        $query->registerRuntimeField(
            'CITY',
            new ReferenceField(
                'CITY',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_CITY),
                ]
            )
        );

        // Привязка свойства "Цена"
        $query->registerRuntimeField(
            'PRICE',
            new ReferenceField(
                'PRICE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_PRICE),
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
            'PHONE_VALUE' => 'PHONE.VALUE',
            'CITY_VALUE' => 'CITY.VALUE', 
            'PRICE_VALUE' => 'PRICE.VALUE',
        ]);
    }
	
	public static function withDetailSelect(Query $query)
    {
        // Детальная выборка с дополнительными свойствами
        $query->registerRuntimeField(
            'PHONE',
            new ReferenceField(
                'PHONE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_PHONE),
                ]
            )
        );

        $query->registerRuntimeField(
            'CITY',
            new ReferenceField(
                'CITY',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_CITY),
                ]
            )
        );

        $query->registerRuntimeField(
            'PRICE',
            new ReferenceField(
                'PRICE',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_PRICE),
                ]
            )
        );

        $query->registerRuntimeField(
            'DOCUMENT',
            new ReferenceField(
                'DOCUMENT',
                ElementPropertyTable::class,
                [
                    'this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                    'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::IB_PROP_TEST_IBLOCK_DOCUMENT),
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
            'PHONE_VALUE' => 'PHONE.VALUE',
            'CITY_VALUE' => 'CITY.VALUE',
            'PRICE_VALUE' => 'PRICE.VALUE',
            'DOCUMENT_VALUE' => 'DOCUMENT.VALUE',
        ]);
    }
	
	public static function withFilterByIDs(Query $query, $ids)
    {
        $query->whereIn('ID', $ids);
    }

    public static function withFilterByCode(Query $query, $code)
    {
        $query->where('CODE', $code);
    }

    public static function withOrderByDate(Query $query, $order = 'DESC')
    {
        $query->addOrder('ACTIVE_FROM', $order);
    }

    public static function withOrderBySort(Query $query, $sort = 'ASC')
    {
        $query->addOrder('SORT', $sort);
    }

    public static function withSectionCode(Query $query, $code)
    {
        if (isset($code)) {
            $query->addFilter('=IBLOCK_SECTION.CODE', $code);
        }
    }

    public static function withPage(Query $query, int $page)
    {
        if ($page > 0) {
            $query->setOffset(($page - 1) * $query->getLimit());
        }
    }

    public static function withDateActive(Query $query)
    {
        $dt = new DateTime();
        $query->addFilter(null, [
            'LOGIC' => 'OR',
            '<=ACTIVE_FROM' => $dt,
            'ACTIVE_FROM' => null,
        ]);
        $query->addFilter(null, [
            'LOGIC' => 'OR',
            '>=ACTIVE_TO' => $dt,
            'ACTIVE_TO' => null,
        ]);
    }

    public static function withFilterByCity(Query $query, $city)
    {
        if ($city) {
            $query->addFilter('=CITY.VALUE', $city);
        }
    }

    public static function withFilterByPriceRange(Query $query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->addFilter('>=PRICE.VALUE', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->addFilter('<=PRICE.VALUE', $maxPrice);
        }
    }
	
}
