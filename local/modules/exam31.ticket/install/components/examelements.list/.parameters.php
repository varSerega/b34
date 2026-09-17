<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
	'PARAMETERS' => [
		'ELEMENT_COUNT' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('EXAM31_ELEMENTS_LIST_ELEMENT_COUNT'),
			'TYPE' => 'STRING',
		],
		'DETAIL_URL' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('EXAM31_ELEMENTS_LIST_DETAIL_URL'),
			'TYPE' => 'STRING',
		],
	],
];
