<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application,
    Bitrix\Main\Loader,
    Bitrix\Highloadblock\HighloadBlockTable as HLBT,
    TQ\Tools,
    Bitrix\Main\Engine\ActionFilter\Authentication,
    Bitrix\Main\Engine\ActionFilter,
    Bitrix\Main\Engine\Contract\Controllerable;

Loader::IncludeModule('highloadblock');

class ElasticFilter extends \CBitrixComponent implements Controllerable
{
    private $arHlProperties = [];

    public function configureActions()
    {
        return [
            'updateCount' => [ // Ajax-метод
                'prefilters' => [],
            ],
        ];
    }

    protected function listKeysSignedParameters()
    {
        return [  //массива параметров которые надо брать из параметров компонента
            'SECTION_ID',
            'IBLOCK_ID',
        ];
    }

    public function updateCountAction()
    {
        $arData = Application::getInstance()->getContext()->getRequest()->getPostList()->toArray();
        $sectionId = intval($this->arParams['SECTION_ID']) ?: 0;
        $elastic = new Tools($this->arParams['IBLOCK_ID'], $sectionId);
        $arFilterProperties = $elastic->getFilterProperties();
        $arPropertyQuantities = $elastic->getPropertyQuantities($arFilterProperties,
            array_filter($arData, function ($key) {
                return strpos($key, 'PROPERTY_') !== false;
            }, ARRAY_FILTER_USE_KEY));

        return [
            'quantities' => $arPropertyQuantities
        ];
    }

    private function setDirectoryValues($arHlTables)
    {
        if ($arHlTables) {
            $arTableCodes = array_keys($arHlTables);
            $res = HLBT::getList([
                'filter' => [
                    'TABLE_NAME' => $arTableCodes
                ]
            ]);
            while ($arItem = $res->fetch()) {
                $entityDataClass = HLBT::compileEntity($arItem)->getDataClass();
                $arValues = $entityDataClass::getList([
                    'filter' => [
                        'UF_XML_ID' => $arHlTables[$arItem['TABLE_NAME']]
                    ]
                ])->fetchAll();
                if ($arValues) {
                    $arValues = array_combine(array_column($arValues, 'UF_XML_ID'), $arValues);

                    foreach ($this->arHlProperties[$arItem['TABLE_NAME']] as $propertyId) {
                        foreach ($this->arResult['PROPERTIES'][$propertyId]['VALUES'] as $key => &$arValue) {
                            if ($arValues[$arValue['key']]) {
                                $arValue['value'] = $arValues[$arValue['key']];
                            } else {
                                unset($this->arResult['PROPERTIES'][$propertyId]['VALUES'][$key]);
                            }
                        }
                        unset($arValue);
                    }
                }
            }
        }
    }
    private function setListValues()
    {
        $arValueIds = [];
        foreach ($this->arResult['PROPERTIES'] as $arItem){
            if($arItem['PROPERTY_TYPE'] == 'L' && $arItem['VALUES']){
                $arValueIds = array_merge($arValueIds,array_column($arItem['VALUES'],'key'));
            }
        }
        $res = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'],'ID'=>$arValueIds]);
        while ($arVal = $res->Fetch()) {
            $arPropertyValues[$arVal['ID']] = $arVal['VALUE'];
        }
        foreach ($this->arResult['PROPERTIES'] as &$arItem){
            if($arItem['PROPERTY_TYPE'] == 'L' ){
              foreach ($arItem['VALUES'] as &$arValue){
                  $arValue['value'] = $arPropertyValues[$arValue['key']];
              }
              unset($arValue);
            }
        }
        unset($arItem);
    }

    private function setCurrentFilter(){
        $sectionId = intval($this->arParams['SECTION_ID']) ?: 0;
        $elastic = new Tools($this->arParams['IBLOCK_ID'], $sectionId);
        $arFilterProperties = $elastic->getFilterProperties();
        $arPropertyIds = array_column($arFilterProperties,'ID');
        $arRequest = Application::getInstance()->getContext()->getRequest()->getQueryList()->toArray();
        $arValidatedRequest = [];
        foreach ($arPropertyIds as $id){
            $code = sprintf('PROPERTY_%s',$id);
            if($arRequest[$code]){
                $arValidatedRequest[$code] = $arRequest[$code];
            }
        }
        $GLOBALS[$this->arParams['FILTER_NAME']?:'arrFilter'] = $arValidatedRequest;
        return $arValidatedRequest;
    }

    private function getPage()
    {
        if ($this->arParams['IBLOCK_ID']) {
            $arData = $this->setCurrentFilter();
            $sectionId = intval($this->arParams['SECTION_ID']) ?: 0;
            $elastic = new Tools($this->arParams['IBLOCK_ID'], $sectionId);
            $arFilterProperties = $elastic->getFilterProperties();
            $arPropertyQuantities = $elastic->getPropertyQuantities($arFilterProperties,$arData);
            $this->arResult['TOTAL'] = $arPropertyQuantities['total'];
            $arHlTables = [];
            foreach ($arFilterProperties as $arFilterProperty) {
                if ($arValues = $arPropertyQuantities['properties'][$arFilterProperty['ID']]) {
                    switch ($arFilterProperty['USER_TYPE']) {
                        case 'directory':
                            if ($arFilterProperty['USER_TYPE_SETTINGS']['TABLE_NAME']) {
                                $this->arHlProperties[$arFilterProperty['USER_TYPE_SETTINGS']['TABLE_NAME']][] = $arFilterProperty['ID'];
                                foreach ($arValues as $arValue) {
                                    if (!in_array($arValue['key'],
                                        $arHlTables[$arFilterProperty['USER_TYPE_SETTINGS']['TABLE_NAME']])) {
                                        $arHlTables[$arFilterProperty['USER_TYPE_SETTINGS']['TABLE_NAME']][] = $arValue['key'];
                                    }
                                }

                                $arFilterProperty['VALUES'] = $arValues;
                            }
                            break;
                        default:
                            $arFilterProperty['VALUES'] = $arValues;
                            break;
                    }
                    if ($arFilterProperty['VALUES']) {
                        $this->arResult['PROPERTIES'][$arFilterProperty['ID']] = $arFilterProperty;
                    }
                }
            }
            $this->setDirectoryValues($arHlTables);
            $this->setListValues();
        }
    }

    public function executeComponent()
    {
        $this->getPage();
        $this->includeComponentTemplate();
    }
}
