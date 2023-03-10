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
<?if($arResult['PROPERTIES']){?>
    <form class="row elastic-form">
        <?foreach ($arResult['PROPERTIES'] as $arProperty){?>
            <div class="col-12">
                <?=$arProperty['NAME']?>
            </div>
            <div class="col-12">
                <?switch ($arProperty['USER_TYPE']){
                    case 'directory':?>
                    <ul class="directory-list">
                        <?foreach ($arProperty['VALUES'] as $arValue){
                            $id = sprintf('PROPERTY_ID_%s_%s',$arProperty['ID'],str_replace([' ','&quot;'],['_',''],$arValue['key']))?>
                            <li>
                                <input name="PROPERTY_<?=$arProperty['ID']?>[]" id="<?=$id?>"<?=$arValue['checked']?' checked':''?> value="<?=$arValue['key']?>" type="checkbox">
                                <label for="<?=$id?>"<?=$arValue['doc_count'] == 0?' class="disabled"':''?>><?=$arValue['value']['UF_NAME']?> <span class="items-count">(<?=$arValue['doc_count']?>)</span></label>
                            </li>
                        <?}?>
                    </ul>
                        <?
                        break;
                    default:?>
                    <ul>
                        <?foreach ($arProperty['VALUES'] as $arValue){
                            $id = sprintf('PROPERTY_ID_%s_%s',$arProperty['ID'],str_replace([' ','&quot;'],['_',''],$arValue['key']))?>
                            <li>
                                <input name="PROPERTY_<?=$arProperty['ID']?>[]" id="<?=$id?>"<?=$arValue['checked']?' checked':''?> value="<?=$arValue['key']?>" type="checkbox">
                                <label for="<?=$id?>"<?=$arValue['doc_count'] == 0?' class="disabled"':''?>><?=$arValue['value']?> <span class="items-count">(<?=$arValue['doc_count']?>)</span></label>
                            </li>
                        <?}?>
                    </ul>
                        <?
                        break;
                }?>
            </div>
        <?}?>
        <button class="set-filter">Применить <span class="total-count">(<?=$arResult['TOTAL']?>)</span></button>
    </form>
    <script type="text/javascript">
        var smartFilter = new JCElasticSmartFilter("<?=$this->getComponent()->getSignedParameters()?>");
    </script>
<?}?>