<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$component = $this->getComponent();
CJSCore::init(['uf']);

$attrList = [
	'class' => $component->getHtmlBuilder()->getCssClassName(),
	'tabindex' => '0',
	'type' => 'text',
	'name' => $arResult['fieldName']
];

foreach($arResult['value'] as $key => $value)
{
	$attrList['value'] = (int) $value;
	$arResult['fieldValues'][$key] = [
		'attrList' => $attrList,
	];
}

