<?php
if(!defined('B_PROLOG_INCLUDED')||B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('PROJECT_NAME_SEARCH'),
    'DESCRIPTION' => Loc::getMessage('PROJECT_NAME_SEARCH'),
    'SORT' => 10,
    'PATH' => [
        'ID' => 'project',
        'NAME' => Loc::getMessage('PROJECT'),
        'SORT' => 10,
    ]
];