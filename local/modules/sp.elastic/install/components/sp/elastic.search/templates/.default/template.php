<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
use Bitrix\Main\Page\Asset;
/** @var array $arParams */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var array $arResult */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

?>

<form method="get" action="">
    <input type="hidden" name="how" value="<?echo $arResult["REQUEST"]["HOW"]=="d"? "d": "r"?>" />
    <input type="text" name="q" placeholder="Поиск" value="<?=$arResult["REQUEST"]["QUERY"]?>">
    <button>Найти</button>
</form>
<?if($arResult["REQUEST"]["HOW"]=="d"):?>
    <a href="<?=$arResult["URL"]?>&amp;how=r<?echo $arResult["REQUEST"]["FROM"]? '&amp;from='.$arResult["REQUEST"]["FROM"]: ''?><?echo $arResult["REQUEST"]["TO"]? '&amp;to='.$arResult["REQUEST"]["TO"]: ''?>"><?=GetMessage("SEARCH_SORT_BY_RANK")?></a>&nbsp;|&nbsp;<b><?=GetMessage("SEARCH_SORTED_BY_DATE")?></b>
<?else:?>
    <b><?=GetMessage("SEARCH_SORTED_BY_RANK")?></b>&nbsp;|&nbsp;<a href="<?=$arResult["URL"]?>&amp;how=d<?echo $arResult["REQUEST"]["FROM"]? '&amp;from='.$arResult["REQUEST"]["FROM"]: ''?><?echo $arResult["REQUEST"]["TO"]? '&amp;to='.$arResult["REQUEST"]["TO"]: ''?>"><?=GetMessage("SEARCH_SORT_BY_DATE")?></a>
<?endif;?>
<?if(isset($arResult["ORIGINAL_QUERY_URL"])){
    ?>
    <div class="search-language-guess">
        <?echo GetMessage("CT_BSP_KEYBOARD_WARNING", array("#query#"=>'<a href="'.$arResult["ORIGINAL_QUERY_URL"].'">'.$arResult["REQUEST"]["ORIGINAL_QUERY"].'</a>'))?>
    </div>
<?}?>

<?if($arResult['SECTIONS']){
    $GLOBALS['sectionsFilter']['ID'] = $arResult['SECTIONS']?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:catalog.section.list",
        "",
        Array(
            "ADD_SECTIONS_CHAIN" => "N",
            "CACHE_FILTER" => "N",
            "CACHE_GROUPS" => "Y",
            "CACHE_TIME" => "36000000",
            "CACHE_TYPE" => "A",
            "COUNT_ELEMENTS" => "Y",
            "COUNT_ELEMENTS_FILTER" => "CNT_ACTIVE",
            "FILTER_NAME" => "sectionsFilter",
            "IBLOCK_ID" => $arParams['IBLOCK_ID'],
            "IBLOCK_TYPE" => "catalog",
            "SECTION_CODE" => "",
            "SECTION_FIELDS" => array("",""),
            "SECTION_ID" => "",
            "SECTION_URL" => "",
            "SECTION_USER_FIELDS" => array("",""),
            "SHOW_PARENT_NAME" => "Y",
            "TOP_DEPTH" => "5",
            "VIEW_MODE" => "LIST"
        )
    );?>
<?}?>
<?if($arResult['ELEMENTS']){
    $GLOBALS['arrFilter']['ID'] = $arResult['ELEMENTS']?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:catalog.section",
        "",
        Array(
            "ACTION_VARIABLE" => "action",
            "ADD_PICT_PROP" => "-",
            "ADD_PROPERTIES_TO_BASKET" => "Y",
            "ADD_SECTIONS_CHAIN" => "N",
            "ADD_TO_BASKET_ACTION" => "ADD",
            "AJAX_MODE" => "N",
            "AJAX_OPTION_ADDITIONAL" => "",
            "AJAX_OPTION_HISTORY" => "N",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_STYLE" => "Y",
            "BACKGROUND_IMAGE" => "-",
            "BASKET_URL" => "/personal/basket.php",
            "BROWSER_TITLE" => "-",
            "CACHE_FILTER" => "N",
            "CACHE_GROUPS" => "Y",
            "CACHE_TIME" => "36000000",
            "CACHE_TYPE" => "A",
            "COMPATIBLE_MODE" => "Y",
            "CONVERT_CURRENCY" => "N",
            "CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
            "DETAIL_URL" => "",
            "DISABLE_INIT_JS_IN_COMPONENT" => "N",
            "DISPLAY_BOTTOM_PAGER" => "Y",
            "DISPLAY_COMPARE" => "N",
            "DISPLAY_TOP_PAGER" => "N",
            "ELEMENT_SORT_FIELD" => "ID",
            "ELEMENT_SORT_ORDER" => $arResult['ELEMENTS'],
            "ENLARGE_PRODUCT" => "STRICT",
            "FILTER_NAME" => "arrFilter",
            "HIDE_NOT_AVAILABLE" => "N",
            "HIDE_NOT_AVAILABLE_OFFERS" => "N",
            "IBLOCK_ID" => $arParams['IBLOCK_ID'],
            "IBLOCK_TYPE" => "catalog",
            "INCLUDE_SUBSECTIONS" => "Y",
            "LABEL_PROP" => array(),
            "LAZY_LOAD" => "N",
            "LINE_ELEMENT_COUNT" => "3",
            "LOAD_ON_SCROLL" => "N",
            "MESSAGE_404" => "",
            "MESS_BTN_ADD_TO_BASKET" => "В корзину",
            "MESS_BTN_BUY" => "Купить",
            "MESS_BTN_DETAIL" => "Подробнее",
            "MESS_BTN_SUBSCRIBE" => "Подписаться",
            "MESS_NOT_AVAILABLE" => "Нет в наличии",
            "META_DESCRIPTION" => "-",
            "META_KEYWORDS" => "-",
            "OFFERS_FIELD_CODE" => array("", ""),
            "OFFERS_LIMIT" => "5",
            "OFFERS_SORT_FIELD" => "sort",
            "OFFERS_SORT_FIELD2" => "id",
            "OFFERS_SORT_ORDER" => "asc",
            "OFFERS_SORT_ORDER2" => "desc",
            "PAGER_BASE_LINK_ENABLE" => "N",
            "PAGER_DESC_NUMBERING" => "N",
            "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
            "PAGER_SHOW_ALL" => "N",
            "PAGER_SHOW_ALWAYS" => "N",
            "PAGER_TEMPLATE" => ".default",
            "PAGER_TITLE" => "Товары",
            "PAGE_ELEMENT_COUNT" => "18",
            "PARTIAL_PRODUCT_PROPERTIES" => "N",
            "PRICE_CODE" => array("BASE"),
            "PRICE_VAT_INCLUDE" => "Y",
            "PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons",
            "PRODUCT_DISPLAY_MODE" => "N",
            "PRODUCT_ID_VARIABLE" => "id",
            "PRODUCT_PROPS_VARIABLE" => "prop",
            "PRODUCT_QUANTITY_VARIABLE" => "quantity",
            "PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
            "PRODUCT_SUBSCRIPTION" => "Y",
            "PROPERTY_CODE_MOBILE" => array(),
            "RCM_PROD_ID" => $_REQUEST["PRODUCT_ID"],
            "RCM_TYPE" => "personal",
            "SECTION_CODE" => "",
            "SECTION_ID" => $_REQUEST["SECTION_ID"],
            "SECTION_ID_VARIABLE" => "SECTION_ID",
            "SECTION_URL" => "",
            "SECTION_USER_FIELDS" => array("", ""),
            "SEF_MODE" => "N",
            "SET_BROWSER_TITLE" => "Y",
            "SET_LAST_MODIFIED" => "N",
            "SET_META_DESCRIPTION" => "Y",
            "SET_META_KEYWORDS" => "Y",
            "SET_STATUS_404" => "N",
            "SET_TITLE" => "Y",
            "SHOW_404" => "N",
            "SHOW_ALL_WO_SECTION" => "Y",
            "SHOW_CLOSE_POPUP" => "N",
            "SHOW_DISCOUNT_PERCENT" => "N",
            "SHOW_FROM_SECTION" => "N",
            "SHOW_MAX_QUANTITY" => "N",
            "SHOW_OLD_PRICE" => "N",
            "SHOW_PRICE_COUNT" => "1",
            "SHOW_SLIDER" => "Y",
            "SLIDER_INTERVAL" => "3000",
            "SLIDER_PROGRESS" => "N",
            "TEMPLATE_THEME" => "blue",
            "USE_ENHANCED_ECOMMERCE" => "N",
            "USE_MAIN_ELEMENT_SECTION" => "N",
            "USE_PRICE_COUNT" => "N",
            "USE_PRODUCT_QUANTITY" => "N"
        )
    );?>
<?}else{
    echo 'Товары не найдены';
}?>
