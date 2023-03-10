<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;
Loader::includeModule('iblock');
$arIBlockIds = [];

$res = CIBlock::GetList(['NAME' => 'asc'], ['ACTIVE' => 'Y']);
while ($ob = $res->Fetch()) {
    $arIBlockIds[$ob['ID']] = sprintf('%s [%s]', $ob['NAME'], $ob['ID']);
}

$arComponentParameters = array(
    'PARAMETERS' => array(
        "IBLOCK_ID" => array(
            "NAME" => Loc::getMessage('IBLOCK_ID'),
            "TYPE" => "LIST",
            "DEFAULT" => "",
            "VALUES" => $arIBlockIds,
            "PARENT" => "BASE",
        ),
        "USE_LANGUAGE_GUESS" => array(
            "NAME" => Loc::getMessage('USE_LANGUAGE_GUESS'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "PARENT" => "BASE",
        ),
    )
);
