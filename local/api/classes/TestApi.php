<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Legacy\General\DataProcessor;
use Legacy\General\Constants;
use Legacy\Iblock\TestTable;

class TestApi
{
	
	private static function processData($query)
    {
        $result = [];

        while ($arr = $query->fetch()) {
            $result[] = [
                'id' => $arr['ID'],
                'name' => $arr['NAME'],
                'code' => $arr['CODE'],
                'previewText' => $arr['PREVIEW_TEXT'],
                'phone' => $arr['PHONE_VALUE'],
                'city' => $arr['CITY_VALUE'],
                'price' => (float)$arr['PRICE_VALUE'],
                'activeFrom' => $arr['ACTIVE_FROM'] ? $arr['ACTIVE_FROM']->format('c') : null,
                'activeTo' => $arr['ACTIVE_TO'] ? $arr['ACTIVE_TO']->format('c') : null,
            ];
        }

        return $result;
    }
	
	private static function processDetailData($query)
    {
        $result = [];

        if ($arr = $query->fetch()) {
            $result = [
                'id' => $arr['ID'],
                'name' => $arr['NAME'],
                'code' => $arr['CODE'],
                'previewText' => $arr['PREVIEW_TEXT'],
                'detailText' => $arr['DETAIL_TEXT'],
                'phone' => $arr['PHONE_VALUE'],
                'city' => $arr['CITY_VALUE'],
                'price' => (float)$arr['PRICE_VALUE'],
                'document' => getFilePath($arr['DOCUMENT_VALUE']),
                'activeFrom' => $arr['ACTIVE_FROM'] ? $arr['ACTIVE_FROM']->format('c') : null,
                'activeTo' => $arr['ACTIVE_TO'] ? $arr['ACTIVE_TO']->format('c') : null,
            ];
        }

        return $result;
    }
	
	public static function getList($arRequest)
    {
        $result = [];
        
        if (Loader::includeModule('iblock')) {
            
			$page = max(1, (int)($arRequest['page'] ?? 1));
			$limit = max(1, min(50, (int)($arRequest['limit'] ?? 10)));
			$city = $arRequest['city'] ?? null;
			$minPrice = isset($arRequest['minPrice']) ? (float)$arRequest['minPrice'] : null;
			$maxPrice = isset($arRequest['maxPrice']) ? (float)$arRequest['maxPrice'] : null;
			
			try {
				$query = TestTable::query()
					->countTotal(true)
					->withSelect()
					->setLimit($limit)
					->withPage($page)
					->withDateActive();

				// Применяем фильтры
				if ($city) {
					$query->withFilterByCity($city);
				}
				if ($minPrice !== null || $maxPrice !== null) {
					$query->withFilterByPriceRange($minPrice, $maxPrice);
				}
				
				$q = $query->exec();

				$result['totalCount'] = $q->getCount();
				$result['currentPage'] = $page;
				$result['pageSize'] = $limit;
				$result['totalPages'] = ceil($result['totalCount'] / $limit);
				$result['items'] = self::processData($q);

			} catch (\Exception $e) {
				throw new \Exception('Ошибка при получении данных: ' . $e->getMessage());
			}
		}
		
        return $result;
    }
	
	public static function getByIds($arRequest)
    {
        $ids = $arRequest['ids'] ?? [];
		$result = [];
		
        if (empty($ids)) {
            return $result;
        }
		
        if (Loader::includeModule('iblock')) {
            
			try {
				$q = TestTable::query()
					->withSelect()
					->withFilterByIDs($ids)
					->exec();

				$result = self::processData($q);

			} catch (\Exception $e) {
				throw new \Exception('Ошибка при получении данных по ID: ' . $e->getMessage());
			}
		}
		
        return DataProcessor::sortResultByIDs($result, $ids);
    }
	
	
}