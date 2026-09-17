<?php
defined('B_PROLOG_INCLUDED') || die;

$component = $this->getComponent();
$values = (array)$arResult['value'] ?? [];

$arResult['PREPARED_VALUES'] = [];
foreach($values as $key => $val)
{
	$arResult['PREPARED_VALUES'][$key] = $component->prepareValue($val);
}
