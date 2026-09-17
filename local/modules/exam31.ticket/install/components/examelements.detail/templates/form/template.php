<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 */
?>

<?
$APPLICATION->IncludeComponent(
	'bitrix:ui.form',
	'.default',
	$arResult['form']
);
?>

<p class="ui-slider-paragraph"><a
		href="<?= $arResult['LIST_PAGE_URL'] ?>"><?= Loc::getMessage('EXAM31_ELEMENT_DETAIL_BACK_TO_LIST') ?></a></p>
