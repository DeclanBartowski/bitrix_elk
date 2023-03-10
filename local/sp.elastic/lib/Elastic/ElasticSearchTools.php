<?php

namespace SP\Elastic\Elastic;

use Bitrix\Main\Loader;

Loader::IncludeModule('iblock');
Loader::IncludeModule("sale");
Loader::IncludeModule("search");
Loader::IncludeModule("catalog");

class ElasticSearchTools
{
    private $iBlockId,
        $sectionId;

    public function __construct($iBlockId = 2, $sectionId = 0)
    {
        $this->iBlockId = $iBlockId;
        $this->sectionId = $sectionId;
    }

    public function getIndexIBlockElements()
    {
        $arIBlock = ElasticHelper::getIBlock($this->iBlockId);
        $arElements = [];
        $arSections = [];
        if ($arIBlock["INDEX_ELEMENT"] == 'Y') {
            $arSelect = array(
                "ID",
                "IBLOCK_ID",
                "NAME",
                "PREVIEW_TEXT",
                "DETAIL_TEXT",
                "DETAIL_PAGE_URL",
                "TIMESTAMP_X"
            );
            $arFilter = ['IBLOCK_ID' => $this->iBlockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
            $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProperties = $ob->GetProperties(['sort' => 'asc'], ['ACTIVE' => 'Y', 'SEARCHABLE' => 'Y']);

                foreach ($arProperties as $arProperty) {
                    $value = '';
                    if (strlen($arProperty["USER_TYPE"]) > 0) {
                        $UserType = \CIBlockProperty::GetUserType($arProperty["USER_TYPE"]);
                    } else {
                        $UserType = array();
                    }
                    if (array_key_exists("GetSearchContent", $UserType)) {
                        $value = \CSearch::KillTags(
                            call_user_func_array($UserType["GetSearchContent"],
                                array(
                                    $arProperty,
                                    array("VALUE" => $arProperty["VALUE"]),
                                    array(),
                                )
                            )
                        );
                    } elseif (array_key_exists("GetPublicViewHTML", $UserType)) {
                        $value = \CSearch::KillTags(
                            call_user_func_array($UserType["GetPublicViewHTML"],
                                array(
                                    $arProperty,
                                    array("VALUE" => $arProperty["VALUE"]),
                                    array(),
                                )
                            )
                        );
                    } elseif ($arProperty["PROPERTY_TYPE"] == 'L') {
                        $value = $arProperty["VALUE_ENUM"];
                    } elseif ($arProperty["PROPERTY_TYPE"] == 'F') {
                        $arFile = \CIBlockElement::__GetFileContent($arProperty["VALUE"]);
                        if (is_array($arFile)) {
                            $value = $arFile["CONTENT"];
                        }
                    } else {
                        $value = $arProperty["VALUE"];
                    }
                    if ($value) {
                        $arFields['PROPERTIES'][$arProperty['CODE']] = $value;
                    }
                }
                $arData = [
                    'ID' => $arFields['ID'],
                    'DATE' => strtotime($arFields['TIMESTAMP_X']),
                    'NAME' => \CSearch::KillTags($arFields['NAME']),
                    'PREVIEW_TEXT' => \CSearch::KillTags($arFields['PREVIEW_TEXT']),
                    'DETAIL_TEXT' => \CSearch::KillTags($arFields['DETAIL_TEXT'])
                ];
                if ($arFields['PROPERTIES']) {
                    foreach ($arFields['PROPERTIES'] as $code => $value) {
                        $arData[sprintf('PROPERTY_%s', $code)] = $value;
                    }
                }

                $arElements[] = $arData;
            }
        }
        if ($arIBlock["INDEX_SECTION"] == 'Y') {
            // $arUserFields = [];
            $arUserFieldNames = [];
            $entityId = sprintf('IBLOCK_%s_SECTION', $this->iBlockId);
            $rs = \CUserTypeEntity::GetList(array(), ["ENTITY_ID" => $entityId, 'IS_SEARCHABLE' => 'Y']);
            while ($arUserField = $rs->Fetch()) {
                $arUserFieldNames[] = $arUserField['FIELD_NAME'];
                //$arUserFields[$arUserField['FIELD_NAME']] = $arUserField;
            }

            $arSelect = array_merge(["ID", "IBLOCK_ID", "NAME", "TIMESTAMP_X"], $arUserFieldNames);
            $arFilter = ['IBLOCK_ID' => $this->iBlockId, 'ACTIVE' => 'Y'];
            $res = \CIBlockSection::GetList([], $arFilter, false, $arSelect);
            while ($arSection = $res->Fetch()) {
                $arUserFieldValues = [];
                if ($arUserFieldNames) {
                    foreach ($arUserFieldNames as $field) {
                        if ($arSection[$field]) {
                            $arUserFieldValues[$field] = $arSection[$field];
                        }
                    }
                }
                $arData = [
                    'ID' => $arSection['ID'],
                    'NAME' => $arSection['NAME'],
                    'DATE' => strtotime($arSection['TIMESTAMP_X']),
                    'DESCRIPTION' => \CSearch::KillTags($arSection["DESCRIPTION"]),
                ];
                if ($arUserFieldValues) {
                    foreach ($arUserFieldValues as $code => $value) {
                        $arData[sprintf('FIELD_%s', $code)] = $value;
                    }
                }
                $arSections[] = $arData;
            }
        }
        return [
            'elements' => $arElements,
            'sections' => $arSections
        ];
    }

    public function sendToElastic($arElements)
    {
        $client = ElasticHelper::getElasticClient();
        ElasticHelper::indexMapping([
            'DATE' => [
                'type' => 'date',
            ]
        ], 'products');
        global $USER;
        if ($arElements) {
            foreach ($arElements as $arElement) {
                try {
                    $response = $client->index([
                        'index' => 'products',
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

    public function sendSectionsToElastic($arElements)
    {
        $client = ElasticHelper::getElasticClient();
        ElasticHelper::indexMapping([
            'DATE' => [
                'type' => 'date',
            ]
        ], 'categories');
        global $USER;
        if ($arElements) {
            foreach ($arElements as $arElement) {
                try {
                    $response = $client->index([
                        'index' => 'categories',
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

    public function SearchProductElastic($query, $sort = '')
    {
        $client = ElasticHelper::getElasticClient();
        $arSort = $this->getSort($sort);
        $params = [
            'index' => 'products',
            'body' => [
                "sort" => $arSort,
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => [
                            'NAME',
                            'PREVIEW_TEXT',
                            'DETAIL_TEXT',
                            'PROPERTY_*'
                        ],
                        'type' => 'best_fields',
                        'fuzziness' => 'auto',
                        'operator' => 'and',
                    ],
                ]
            ]
        ];
        $response = $client->search($params);
        if ($response['hits']['hits']) {
            $this->sendSearchStatistic('product_search', $query);
        }
        return $response['hits']['hits'];
    }

    public function SearchSectionsElastic($query, $sort = '')
    {
        $client = ElasticHelper::getElasticClient();
        $arSort = $this->getSort($sort);
        $params = [
            'index' => 'categories',
            'body' => [
                "sort" => $arSort,
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => [
                            'NAME',
                            'DESCRIPTION',
                            'FIELD_*'
                        ],
                        'type' => 'best_fields',
                        'fuzziness' => 'auto',
                        'operator' => 'and',
                    ],
                ]
            ]
        ];
        $response = $client->search($params);
        if ($response['hits']['hits']) {
            $this->sendSearchStatistic('category_search', $query);
        }
        return $response['hits']['hits'];
    }

    private function sendSearchStatistic($index, $query)
    {
        $client = ElasticHelper::getElasticClient();
        $client->index([
            'index' => $index,
            'body' => [
                'query' => $query,
                'date' => date('d.m.Y')
            ]
        ]);
    }

    private function getSort($sort = '')
    {
        switch ($sort) {
            case 'd':
                $arSort = [
                    [
                        'DATE' => [
                            'order' => 'desc',
                            'format' => 'strict_date_optional_time_nanos'
                        ]
                    ]
                ];
                break;
            default:
                $arSort = [
                    [
                        '_score' => [
                            'order' => 'desc'
                        ]
                    ]
                ];
                break;
        }
        return $arSort;
    }

}