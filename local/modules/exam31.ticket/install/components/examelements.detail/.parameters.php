<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
	'PARAMETERS' => [
		'ELEMENT_ID' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('EXAM31_ELEMENTS_DETAIL_EXAM_ELEMENT_ID'),
			'TYPE' => 'STRING',
		],
		'LIST_PAGE_URL' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('EXAM31_ELEMENTS_LIST_PAGE_URL'),
			'TYPE' => 'STRING',
		],
	],
];
