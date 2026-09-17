<?php
defined('B_PROLOG_INCLUDED') || die;

//dumpl($arResult);

$isFirst = true;
foreach($arResult['value'] as $value)
{
	if(!$isFirst)
	{
		print '<br>';
	}
	$isFirst = false;
	print (!empty($value) ? $value : '');
}