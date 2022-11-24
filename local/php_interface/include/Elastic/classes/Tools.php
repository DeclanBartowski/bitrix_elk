<?php

namespace TQ;

use CUserTypeManager,
    CSearch,
    Elasticsearch\ClientBuilder;

\CModule::IncludeModule('iblock');
\CModule::IncludeModule("sale");
\CModule::IncludeModule("search");
\CModule::IncludeModule("catalog");

class Tools
{
    private $iBlockId;

    public function __construct($iBlockId = 2)
    {
        $this->iBlockId = $iBlockId;
    }

    public function getIndexIBlockElements()
    {
        $arIBlock = $this->getIBlock();
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
                        $value = CSearch::KillTags(
                            call_user_func_array($UserType["GetSearchContent"],
                                array(
                                    $arProperty,
                                    array("VALUE" => $arProperty["VALUE"]),
                                    array(),
                                )
                            )
                        );
                    } elseif (array_key_exists("GetPublicViewHTML", $UserType)) {
                        $value = CSearch::KillTags(
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
                    'NAME' => CSearch::KillTags($arFields['NAME']),
                    'PREVIEW_TEXT' => CSearch::KillTags($arFields['PREVIEW_TEXT']),
                    'DETAIL_TEXT' => CSearch::KillTags($arFields['DETAIL_TEXT'])
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
                    'DESCRIPTION' => CSearch::KillTags($arSection["DESCRIPTION"]),
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
        $client = $this->getElasticClient();
        $this->indexMapping([
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
                } catch (ClientResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (ServerResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (Exception $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                }
            }
        }
    }

    public function sendSectionsToElastic($arElements)
    {
        $client = $this->getElasticClient();
        $this->indexMapping([
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
                } catch (ClientResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (ServerResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (Exception $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                }
            }
        }
    }

    public function SearchProductElastic($query, $sort = '')
    {
        $client = $this->getElasticClient();
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
        $client = $this->getElasticClient();
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

    public function sendFilterToElastic()
    {
        $arElements = $this->getFilterProducts();
        $index = 'product_filter';
        $client = $this->getElasticClient();

        global $USER;
        if ($arElements) {
            foreach ($arElements as $arElement) {
                try {
                    $response = $client->index([
                        'index' => $index,
                        'id' => $arElement['ID'],
                        'body' => $arElement
                    ]);
                } catch (ClientResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (ServerResponseException $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                } catch (Exception $e) {
                    if ($USER->IsAdmin()) {
                        \Bitrix\Main\Diag\Debug::dump($e->getMessage());
                    }
                }
            }
        }
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
                $arFields['PROPERTIES'][$arProperty['CODE']] = $arProperty['VALUE'];
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

            $arProducts[] = $arData;
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

            $arIndexProperties[sprintf('PROPERTY_%s', $arFilterProperty['CODE'])] = [
                'type' => $type,
            ];
            $arIndexProperties[sprintf('PROPERTY_%s_keyword', $arFilterProperty['CODE'])] = [
                'type' => 'keyword',
            ];
        }
        $this->indexMapping($arIndexProperties, 'product_filter');


        return $arProducts;
    }

    private function indexMapping($arParams, $index)
    {
        $client = $this->getElasticClient();
        $params = [
            'index' => $index,
            'body' => [
                'properties' => $arParams,
            ],
        ];
        $isIndexExists = $client->indices()->exists(['index' => $index]);
        if ($isIndexExists) {
            $client->indices()->delete(['index' => $index]);
        }

        $client->indices()->create(['index' => $index]);
        $client->indices()->putMapping($params);
    }

    private function getFilterProperties()
    {
        $arProps = [];
        $arProperties = \CIBlockSectionPropertyLink::GetArray($this->iBlockId, 0);
        $arFilterProperties = array_filter($arProperties, function ($arItem) {
            return $arItem['SMART_FILTER'] == 'Y';
        });
        if ($arFilterProperties) {
            $arPropertyIds = array_column($arFilterProperties, 'PROPERTY_ID');
            $properties = \CIBlockProperty::GetList([], array(
                "ACTIVE" => "Y",
                "IBLOCK_ID" => $this->iBlockId,
            ));
            while ($arProperty = $properties->GetNext()) {
                if (in_array($arProperty['ID'], $arPropertyIds)) {
                    $arProperty['SECTION_DATA'] = $arFilterProperties[$arProperty['ID']];
                    $arProps[$arProperty['CODE']] = $arProperty;
                }
            }
        }
        return $arProps;
    }

    private function sendSearchStatistic($index, $query)
    {
        $client = $this->getElasticClient();
        $client->index([
            'index' => $index,
            'body' => [
                'query' => $query,
                'date' => date('d.m.Y')
            ]
        ]);
    }

    private function getElasticClient()
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
        return ClientBuilder::create()
            ->setHosts(['host'])
            ->setBasicAuthentication('elastic', 'password')
            ->build();
    }

    private function getIBlock()
    {
        return \CIBlock::GetList([], ['ID' => $this->iBlockId])->Fetch();
    }

}
