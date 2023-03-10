<?php
/**
 * @global $APPLICATION
 */

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\HttpApplication,
    Bitrix\Main\Config\Option;

$server = HttpApplication::getInstance()->getContext()->getServer()->toArray();
Loc::loadMessages($server["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages($server["DOCUMENT_ROOT"] . "/bitrix/modules/" . $module_id . "/include.php");
Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$arPost = $request->getPostList()->toArray();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

$arAvailableFields = [
  'host',
  'username',
  'password',
];
if ($REQUEST_METHOD == "POST" && check_bitrix_sessid()) {
    foreach ($arAvailableFields as $code){
        Option::set($module_id, $code, $arPost[$code]);
    }
}
$allModuleOptions = Option::getForModule($module_id);
$arAllOptions = [
    [
        'DIV' => 'settings',
        'TAB' => Loc::getMessage("SP_ELASTIC_MAIN_TAB_NAME"),
        'TITLE' => Loc::getMessage("SP_ELASTIC_MAIN_TAB_TITLE"),
        'OPTIONS' => [
            [
                'host',
                Loc::getMessage("SP_ELASTIC_MAIN_HOST"),
                '',
                [
                    'text',
                    50,
                ],
            ],
            [
                'username',
                Loc::getMessage("SP_ELASTIC_MAIN_USERNAME"),
                '',
                [
                    'text',
                    50,
                ],
            ],
            [
                'password',
                Loc::getMessage("SP_ELASTIC_MAIN_PASSWORD"),
                '',
                [
                    'text',
                    50,
                ],
            ],
        ],
    ],
];
$tabControl = new CAdminTabControl("tabControl", $arAllOptions);
?>

<form method="POST"
      action="<?
      echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialchars($mid) ?>&lang=<?= LANGUAGE_ID ?>"
      enctype="multipart/form-data">
    <?
    $tabControl->Begin(); ?>

    <?
    foreach ($arAllOptions as $aTab) {
        if ($aTab["OPTIONS"]) {
            $tabControl->BeginNextTab();

            __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
        }
    }
    $tabControl->Buttons();
    ?>
    <input class="adm-btn-save" type="submit" name="Apply" value="<?= Loc::getMessage("MAIN_OPT_APPLY") ?>"
           title="<?= Loc::getMessage("MAIN_OPT_APPLY_TITLE") ?>">
    <?= bitrix_sessid_post(); ?>

    <?
    $tabControl->End(); ?>
</form>
