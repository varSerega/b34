<?php

defined('B_PROLOG_INCLUDED') || die;

$values = [];
if (
	isset($arResult['additionalParameters']['bVarsFromForm'])
	&& $arResult['additionalParameters']['bVarsFromForm'])
{
	$values['FORMAT'] = $GLOBALS[$arResult['additionalParameters']['NAME']]['FORMAT'] ?? '';
}
elseif (isset($arResult['userField']) && $arResult['userField'])
{
	$values['FORMAT'] = $arResult['userField']['SETTINGS']['FORMAT'];
}

$arResult['VALUES'] = $values;