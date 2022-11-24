<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application,
    Bitrix\Main\Loader,
    TQ\Tools;

class ElasticSearch extends \CBitrixComponent
{
    private function getPage()
    {
        global $APPLICATION;
        $request = Application::getInstance()->getContext()->getRequest();
        $q = $request->get('q');

        $how = trim($request->get('how'));
        if($how == "d")
            $how = "d";
        elseif($how == "r")
            $how = "";
        else
            $how = "";

        $this->arResult["REQUEST"]["HOW"] = htmlspecialcharsbx($how);
        $this->arResult["URL"] = $APPLICATION->GetCurPage()
            ."?q=".urlencode($q);
        if ($q) {
            if($this->arParams["USE_LANGUAGE_GUESS"] == "N" || isset($_REQUEST["spell"]))
            {
                $this->arResult["REQUEST"]["~QUERY"] = $q;
                $this->arResult["REQUEST"]["QUERY"] = htmlspecialcharsex($q);
            }
            else
            {
                $arLang = \CSearchLanguage::GuessLanguage($q);
                if(is_array($arLang) && $arLang["from"] != $arLang["to"])
                {
                    $this->arResult["REQUEST"]["~ORIGINAL_QUERY"] = $q;
                    $this->arResult["REQUEST"]["ORIGINAL_QUERY"] = htmlspecialcharsex($q);

                    $this->arResult["REQUEST"]["~QUERY"] = CSearchLanguage::ConvertKeyboardLayout($this->arResult["REQUEST"]["~ORIGINAL_QUERY"], $arLang["from"], $arLang["to"]);
                    $this->arResult["REQUEST"]["QUERY"] = htmlspecialcharsex($this->arResult["REQUEST"]["~QUERY"]);
                }
                else
                {
                    $this->arResult["REQUEST"]["~QUERY"] = $q;
                    $this->arResult["REQUEST"]["QUERY"] = htmlspecialcharsex($q);
                }
            }
            if(isset($this->arResult["REQUEST"]["~ORIGINAL_QUERY"]))
            {
                $this->arResult["ORIGINAL_QUERY_URL"] = $APPLICATION->GetCurPage()
                    ."?q=".urlencode($this->arResult["REQUEST"]["~ORIGINAL_QUERY"])
                    ."&amp;spell=1";
            }
            $elastic = new Tools($this->arParams['IBLOCK_ID']);
            $arElements = $elastic->SearchProductElastic($this->arResult["REQUEST"]["QUERY"],$how);
            $arSections = $elastic->SearchSectionsElastic($this->arResult["REQUEST"]["QUERY"],$how);
            if($arElements){
                $this->arResult['ELEMENTS'] = array_map(function ($arItem){
                    return $arItem['_source']['ID'];
                },$arElements);
            }
            if($arSections){
                $this->arResult['SECTIONS'] = array_map(function ($arItem){
                    return $arItem['_source']['ID'];
                },$arSections);
            }

        }
    }

    public function executeComponent()
    {
        $this->getPage();
        $this->includeComponentTemplate();
    }
}
