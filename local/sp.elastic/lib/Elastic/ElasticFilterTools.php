<?php

namespace SP\Elastic\Elastic;

use Bitrix\Main\Loader;

Loader::IncludeModule('iblock');
Loader::IncludeModule("sale");
Loader::IncludeModule("search");
Loader::IncludeModule("catalog");

class ElasticFilterTools
{
    private $iBlockId,
        $skuIBlockId,
        $sectionId,
        $skuPropertyId;

    public function __construct($iBlockId = 2, $sectionId = 0)
    {
        $arCatalog = \CCatalogSKU::GetInfoByProductIBlock($iBlockId);
        $this->iBlockId = $iBlockId;
        $this->sectionId = $sectionId;
        $this->skuIBlockId = $arCatalog['IBLOCK_ID'];
        $this->skuPropertyId = $arCatalog['SKU_PROPERTY_ID'];
    }

    public function getPropertyQuantities($arFilterProperties, $arData = [])
    {
        $arAggs = [];
        $arAvailableProps = [];
        $input = str_replace('[]', '', $arData['input']);
        foreach ($arFilterProperties as $arFilterProperty) {
            $arAvailableProps[] = sprintf('PROPERTY_%s', $arFilterProperty['ID']);
            $arAggs[$arFilterProperty['ID']] = [
                'terms' => [
                    'field' => sprintf('PROPERTY_%s_keyword', $arFilterProperty['ID'])
                ]
            ];
        }


        $client = ElasticHelper::getElasticClient();
        $params = [
            'index' => 'product_filter_list',
            'body' => [
                'size' => 0,
                'aggs' => $arAggs
            ]
        ];


        $response = $client->search($params);
        $arProperties = [];
        if ($response['aggregations']) {
            foreach ($response['aggregations'] as $key => $aggregation) {
                $arAggregation = array_filter($aggregation['buckets'], function ($arItem) {
                    return !empty($arItem['key']) && $arItem['doc_count'];
                });
                foreach ($arAggregation as $arItem) {
                    $arProperties[$key][$arItem['key']] = $arItem;
                }
            }
        }
        if ($arData && $arAvailableProps) {
            foreach ($arProperties as $propertyId => &$arProperty) {
                foreach ($arProperty as &$arValue) {
                    if (in_array($arValue['key'], $arData[sprintf('PROPERTY_%s', $propertyId)])) {
                        $arValue['checked'] = true;
                    }
                    $arValue['doc_count'] = 0;
                }
                unset($arValue);
            }
            unset($arProperty);
            $arTerms = [];
            $index = 0;
            foreach ($arData as $key => $arDatum) {
                if (!in_array($key, $arAvailableProps)) {
                    continue;
                }
                if (is_array($arDatum)) {
                    foreach ($arDatum as $value) {
                        $arTerms[$index]['terms'][$key][] = $value;
                    }
                } else {
                    $arTerms[$index][$key] = $arDatum;
                }
                $index++;
            }
            if ($arTerms) {
                foreach ($arProperties as $propId => $arItem) {
                    $params['body']['query']['bool']['filter'] = array_values(array_filter($arTerms,
                        function ($arItem) use ($propId) {
                            return !$arItem['terms'][sprintf('PROPERTY_%s', $propId)];
                        }));
                    // $params['body']['query']['bool']['filter'] = $arTerms;

                    $response = $client->search($params);

                    if ($response['aggregations']) {
                        foreach ($response['aggregations'] as $key => $aggregation) {
                            $arAggregation = array_filter($aggregation['buckets'], function ($arItem) {
                                return !empty($arItem['key']) && $arItem['doc_count'];
                            });
                            foreach ($arAggregation as $arItem) {
                                if ($key == $propId && $arProperties[$key][$arItem['key']]) {
                                    $arProperties[$key][$arItem['key']]['doc_count'] = $arItem['doc_count'];
                                }
                            }
                        }
                    }
                }
            } else {
                $response = $client->search($params);

                if ($response['aggregations']) {
                    foreach ($response['aggregations'] as $key => $aggregation) {
                        $arAggregation = array_filter($aggregation['buckets'], function ($arItem) {
                            return !empty($arItem['key']) && $arItem['doc_count'];
                        });
                        foreach ($arAggregation as $arItem) {
                            if ($arProperties[$key][$arItem['key']]) {
                                $arProperties[$key][$arItem['key']]['doc_count'] = $arItem['doc_count'];
                            }
                        }
                    }
                }
            }
        }

        return [
            'total' => $response['hits']['total']['value'],
            'properties' => $arProperties
        ];
    }

    public function sendFilterToElastic()
    {
        $arElements = $this->getFilterProducts();
        $index = 'product_filter';
        $client = ElasticHelper::getElasticClient();
        global $USER;
        if ($arElements) {
            foreach ($arElements as $arElement) {
                try {
                    $response = $client->index([
                        'index' => $index,
                        'id' => $arElement['ID'],
                        'body' => $arElement
                    ]);
                } catch (\ClientResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (\ServerResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (\Exception $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                }
            }
        }
    }

    public function sendIdFilterToElastic()
    {
        $arElements = $this->getFilterIdProducts();
        $index = 'product_filter_list';
        $client = ElasticHelper::getElasticClient();
        global $USER;
        if ($arElements) {
            foreach ($arElements as $arElement) {
                try {
                    $response = $client->index([
                        'index' => $index,
                        'id' => $arElement['ID'],
                        'body' => $arElement
                    ]);
                } catch (\ClientResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (\ServerResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (\Exception $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                }
            }
        }
    }

    public function getFilterProperties()
    {
        $arProps = $this->getFilterPropertiesByIBlockId($this->iBlockId);
        if ($this->skuIBlockId) {
            $arProps = array_merge($arProps, $this->getFilterPropertiesByIBlockId($this->skuIBlockId));
        }
        return $arProps;
    }

    private function getFilterProducts()
    {
        $arProducts = [];
        $arFilterProperties = $this->getFilterProperties();

        $arSelect = array(
            "ID",
            "IBLOCK_ID",
            "NAME",
            "PREVIEW_TEXT",
            "DETAIL_TEXT",
            "IBLOCK_SECTION_ID",
            "TIMESTAMP_X"
        );


        $arFilter = ['IBLOCK_ID' => $this->iBlockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
        $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arProperties = $ob->GetProperties(['sort' => 'asc'],
                ['ACTIVE' => 'Y', 'ID' => array_column($arFilterProperties, 'ID')]);
            foreach ($arProperties as $arProperty) {
                $arFields['PROPERTIES'][$arProperty['ID']] = $arProperty['VALUE'];
            }
            $arData = [
                'ID' => $arFields['ID'],
                'SECTION_ID' => $arFields['IBLOCK_SECTION_ID'],
            ];
            if ($arFields['PROPERTIES']) {
                foreach ($arFields['PROPERTIES'] as $code => $value) {
                    $arData[sprintf('PROPERTY_%s', $code)] = $value ?: '';
                    $arData[sprintf('PROPERTY_%s_keyword', $code)] = $value ?: '';
                }
            }

            $arProducts[$arData['ID']] = $arData;
        }
        if ($this->skuIBlockId) {
            $arFilter = ['IBLOCK_ID' => $this->skuIBlockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
            $res = \CIBlockElement::GetList([], $arFilter);
            while ($ob = $res->GetNextElement()) {
                $productId = 0;
                $arFields = $ob->GetFields();
                $arProperties = $ob->GetProperties(['sort' => 'asc'], [
                    'ACTIVE' => 'Y',
                    'ID' => array_merge(array_column($arFilterProperties, 'ID'), [$this->skuPropertyId])
                ]);
                foreach ($arProperties as $arProperty) {
                    if ($arProperty['ID'] == $this->skuPropertyId) {
                        $productId = $arProperty['VALUE'];
                    } else {
                        $arFields['PROPERTIES'][$arProperty['ID']] = $arProperty['VALUE'];
                    }
                }
                if ($arFields['PROPERTIES']) {
                    foreach ($arFields['PROPERTIES'] as $code => $value) {
                        if ($value) {
                            $propertyCode = sprintf('PROPERTY_%s', $code);
                            if ($arProducts[$productId][sprintf('PROPERTY_%s', $code)]) {
                                if (is_array($value)) {
                                    $arProducts[$productId][$propertyCode][] = $value;
                                    $arProducts[$productId][sprintf('%s_keyword', $propertyCode)][] = $value;
                                } else {
                                    $arProducts[$productId][$propertyCode] = [
                                        $arProducts[$productId][$propertyCode],
                                        $value
                                    ];
                                    $arProducts[$productId][sprintf('%s_keyword', $propertyCode)] = [
                                        $arProducts[$productId][sprintf('%s_keyword', $propertyCode)],
                                        $value
                                    ];
                                }
                            } else {
                                $arProducts[$productId][$propertyCode] = [$value];
                                $arProducts[$productId][sprintf('%s_keyword', $propertyCode)] = [$value];
                            }
                        }
                    }
                }
            }
        }

        $arIndexProperties = [];
        foreach ($arFilterProperties as $arFilterProperty) {
            switch ($arFilterProperty['PROPERTY_TYPE']) {
                case 'N':
                    $type = 'float';
                    break;
                default:
                    $type = 'text';
                    break;
            }

            $arIndexProperties[sprintf('PROPERTY_%s', $arFilterProperty['ID'])] = [
                'type' => $type,
                'analyzer' => 'whitespace',
            ];
            $arIndexProperties[sprintf('PROPERTY_%s_keyword', $arFilterProperty['ID'])] = [
                'type' => 'keyword',
            ];
        }

        ElasticHelper::indexMapping($arIndexProperties, 'product_filter');


        return $arProducts;
    }

    private function getFilterIdProducts()
    {
        $arProducts = [];
        $arFilterProperties = $this->getFilterProperties();

        $arSelect = array(
            "ID",
            "IBLOCK_ID",
            "NAME",
            "PREVIEW_TEXT",
            "DETAIL_TEXT",
            "IBLOCK_SECTION_ID",
            "TIMESTAMP_X"
        );


        $arFilter = ['IBLOCK_ID' => $this->iBlockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
        $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        while ($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $arProperties = $ob->GetProperties(['sort' => 'asc'],
                ['ACTIVE' => 'Y', 'ID' => array_column($arFilterProperties, 'ID')]);
            foreach ($arProperties as $arProperty) {
                if ($arProperty['VALUE']) {
                    if ($arProperty['USER_TYPE'] == 'directory') {
                        $arFields['PROPERTIES'][$arProperty['ID']] = $arProperty['VALUE'];
                    } else {
                        $arFields['PROPERTIES'][$arProperty['ID']] = $arProperty['VALUE_ENUM_ID'];
                    }
                }
            }
            $arData = [
                'ID' => $arFields['ID'],
                'SECTION_ID' => $arFields['IBLOCK_SECTION_ID'],
            ];
            if ($arFields['PROPERTIES']) {
                foreach ($arFields['PROPERTIES'] as $code => $value) {
                    $arData[sprintf('PROPERTY_%s', $code)] = $value ?: '';
                    $arData[sprintf('PROPERTY_%s_keyword', $code)] = $value ?: '';
                }
            }

            $arProducts[$arData['ID']] = $arData;
        }
        if ($this->skuIBlockId) {
            $arFilter = ['IBLOCK_ID' => $this->skuIBlockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
            $res = \CIBlockElement::GetList([], $arFilter);
            while ($ob = $res->GetNextElement()) {
                $productId = 0;
                $arFields = $ob->GetFields();
                $arProperties = $ob->GetProperties(['sort' => 'asc'], [
                    'ACTIVE' => 'Y',
                    'ID' => array_merge(array_column($arFilterProperties, 'ID'), [$this->skuPropertyId])
                ]);

                foreach ($arProperties as $arProperty) {
                    if ($arProperty['ID'] == $this->skuPropertyId) {
                        $productId = $arProperty['VALUE'];
                    } else {
                        $arFields['PROPERTIES'][$arProperty['ID']] = $arProperty['PROPERTY_VALUE_ID'];
                    }
                }
                if ($arFields['PROPERTIES']) {
                    foreach ($arFields['PROPERTIES'] as $code => $value) {
                        if ($value) {
                            $propertyCode = sprintf('PROPERTY_%s', $code);
                            if ($arProducts[$productId][sprintf('PROPERTY_%s', $code)]) {
                                if (is_array($value)) {
                                    $arProducts[$productId][$propertyCode][] = $value;
                                    $arProducts[$productId][sprintf('%s_keyword', $propertyCode)][] = $value;
                                } else {
                                    $arProducts[$productId][$propertyCode] = [
                                        $arProducts[$productId][$propertyCode],
                                        $value
                                    ];
                                    $arProducts[$productId][sprintf('%s_keyword', $propertyCode)] = [
                                        $arProducts[$productId][sprintf('%s_keyword', $propertyCode)],
                                        $value
                                    ];
                                }
                            } else {
                                $arProducts[$productId][$propertyCode] = [$value];
                                $arProducts[$productId][sprintf('%s_keyword', $propertyCode)] = [$value];
                            }
                        }
                    }
                }
            }
        }

        $arIndexProperties = [];
        foreach ($arFilterProperties as $arFilterProperty) {
            switch ($arFilterProperty['PROPERTY_TYPE']) {
                case 'N':
                    $type = 'float';
                    break;
                default:
                    $type = 'text';
                    break;
            }

            $arIndexProperties[sprintf('PROPERTY_%s', $arFilterProperty['ID'])] = [
                'type' => $type,
                'analyzer' => 'whitespace',
            ];
            $arIndexProperties[sprintf('PROPERTY_%s_keyword', $arFilterProperty['ID'])] = [
                'type' => 'keyword',
            ];
        }

        ElasticHelper::indexMapping($arIndexProperties, 'product_filter_list');


        return $arProducts;
    }

    private function getFilterPropertiesByIBlockId($iBlockId)
    {
        $arProps = [];
        $arProperties = \CIBlockSectionPropertyLink::GetArray($iBlockId, 0);
        $arFilterProperties = array_filter($arProperties, function ($arItem) {
            return $arItem['SMART_FILTER'] == 'Y';
        });
        if ($arFilterProperties) {
            $arPropertyIds = array_column($arFilterProperties, 'PROPERTY_ID');
            $properties = \CIBlockProperty::GetList([], array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => $iBlockId,
            ));
            while ($arProperty = $properties->GetNext()) {
                if (in_array($arProperty['ID'], $arPropertyIds)) {
                    $arProperty['SECTION_DATA'] = $arFilterProperties[$arProperty['ID']];
                    $arProps[$arProperty['ID']] = $arProperty;
                }
            }
        }
        return $arProps;
    }

}