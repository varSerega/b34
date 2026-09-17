<?php B_PROLOG_INCLUDED === true || die();

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */
?>


<?
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult["grid"],
	$component
);
?>