<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Text\HtmlFilter;

class CBPExamTicketActivity extends BaseActivity
{
	public function __construct($name)
	{
		parent::__construct($name);

		$this->arProperties = [
			'ID' => 0,

			//return
			'DEMO_VALUE' => null,
		];

		$this->SetPropertiesTypes([
			'DEMO_VALUE' => ['Type' => FieldType::STRING],
		]);

	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected function internalExecute(): ErrorCollection
	{
		$errors = parent::internalExecute();

		/*
		/Демо
		*/
		$elementId = (int) $this->preparedProperties["ID"];
		if($elementId < 100)
		{
			//Значения найдены
			$this->preparedProperties['DEMO_VALUE'] = HtmlFilter::encode('DEMO_VALUE');
		}
		else
		{
			//Если нет данных, отдаем пустые значения
			$this->preparedProperties['ID'] = 0;
			$this->preparedProperties['DEMO_VALUE'] = '';

			//Пишем в журнал выполнения БП что данные не нашли
			$this->log(
				Loc::getMessage(
					'EXAM31_TICKET_ACTIVITY_LOG_TEXT_N',
					[
						'#ID#' => $elementId,
					]
				)
			);
		}
		/*
		*
		*/
		
		return $errors;
	}

	public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
	{
		$map = [
			'ID' => [
				'Name' => 'ID',
				'FieldName' => 'ID',
				'Type' => FieldType::INT,
				'Required' => true,
				'Default' => '',
				'Options' => [],
			],
		];
		
		return $map;
	}
}