<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/**
 * @var CMain $APPLICATION
 */

$APPLICATION->IncludeComponent(
	'exam31.ticket:examelements',
	'.default',
	[
		'SEF_FOLDER' => '/exam31/',
		'SEF_URL_TEMPLATES' => [
			'list' => '',
			'detail' => 'detail/#ELEMENT_ID#/',
		],
		'DEFAULT_PAGE' => 'list'
	]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';