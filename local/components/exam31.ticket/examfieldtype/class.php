<?php
defined('B_PROLOG_INCLUDED') || die;

use Exam31\Ticket\ExamFieldType;
use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;

class SomeElementFieldComponent extends BaseUfComponent
{

	public function __construct($component = null)
	{
		Loader::requireModule('exam31.ticket');
		parent::__construct($component);
	}

	protected static function getUserTypeId(): string
	{
		return ExamFieldType::USER_TYPE_ID;
	}

	public function prepareValue($value)
	{
		$preparedValue = [
			'VALUE' => (int) $value,
		];

		$formatValueTemplate = HtmlFilter::encode((string) $this->arResult['userField']['SETTINGS']['FORMAT'] ?? '#ID#');

		$preparedValue['FORMATTED_VALUE'] = str_replace(
			'#ID#',
			$preparedValue['VALUE'],
			$formatValueTemplate
		);

		return $preparedValue;

	}
}