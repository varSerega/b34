<?php
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;

use Exam31\Ticket\SomeElementTable;

class ExamElementsDetailComponent extends CBitrixComponent implements Controllerable, Errorable
{
	use ErrorableImplementation;
	private ?int $elementId = null;
	
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	function onPrepareComponentParams($arParams)
	{
		if (!Loader::includeModule('exam31.ticket'))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'))
			);
			return $arParams;
		}

		if (isset($arParams['ELEMENT_ID']))
		{
			$this->elementId = (int) $arParams['ELEMENT_ID'] ?: null;
		}

		return $arParams;
	}

	private function displayErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error->getMessage());
		}
	}

	function executeComponent(): void
	{
		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}

		//flat
		$this->arResult['ELEMENT'] = $this->getEntityData();

		//form
		$this->arResult['form'] = $this->PrepareForm($this->arResult['ELEMENT']);
		$this->arResult['LIST_PAGE_URL'] = $this->arParams['LIST_PAGE_URL'];
		$this->arResult['DETAIL_PAGE_URL'] = $this->arParams['DETAIL_PAGE_URL'];

		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE', ['#ID#' => $this->arResult['ELEMENT']['ID']]));
	}

	protected function PrepareForm($element): array
	{
		return [
			'MODULE_ID' => null,
			'CONFIG_ID' => null,
			'GUID' => 'GUIDSomeElement',
			'ENTITY_TYPE_NAME' => 'SomeElement',

			'ENTITY_CONFIG_EDITABLE' => true,
			'READ_ONLY' => false,
			'ENABLE_CONFIG_CONTROL' => false,

			'ENTITY_ID' => $this->elementId,

			'ENTITY_FIELDS' => $this->getEntityFields(),
			'ENTITY_CONFIG' => $this->getEntityConfig(),
			'ENTITY_DATA' => $element,
			'ENTITY_CONTROLLERS' => [],

			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $this->getName(),
				'SIGNED_PARAMETERS' => $this->getSignedParameters()
			],
		];
	}
	protected function getEntityConfig(): array
	{
		//Демо-данные - конфигурация формы
		return [
			[
				'type' => 'column',
				'name' => 'default_column',
				'elements' => [
					[
						'name' => 'main',
						'title' => $this->elementId ? Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE', ['#ID#' => $this->elementId]) : Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE_NEW'),

						'type' => 'section',
						'elements' => [
							['name' => 'ID'],
							['name' => 'DATE_MODIFY'],							
							['name' => 'ACTIVE'],
							['name' => 'TITLE'],
							['name' => 'TEXT'],
						]
					],
				]
			]
		];
	}

	protected function getEntityFields(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();

		//Демо-данные - поля формы
		return [
			[
				'name' => 'ID',
				'title' => $fieldsLabel['ID'] ?? 'ID',
				'editable' => false,
				'type' => 'text',
			],
			[
				'name' => 'DATE_MODIFY',
				'title' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY',
				'editable' => false,
				'type' => 'datetime',
			],			
			[
				'name' => 'ACTIVE',
				'title' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE',
				'editable' => true,
				'type' => 'boolean',
			],
			[
				'name' => 'TITLE',
				'title' => $fieldsLabel['TITLE'] ?? 'TITLE',
				'editable' => true,
				'type' => 'text',
			],
			[
				'name' => 'TEXT',
				'title' => $fieldsLabel['TEXT'] ?? 'TEXT',
				'editable' => true,
				'type' => 'textarea',
			],
		];
	}

	protected function getEntityData(): array
	{
		if (!$this->elementId)
		{
			return [];
		}

		//Демо-данные полей для формы
		$element = [
			'ID' => 2,
			'DATE_MODIFY' => (new DateTime())->toString(),
			'TITLE' => 'TITLE 2',
			'TEXT' => 'TEXT 2',
			'ACTIVE' => 'Y'
		];

		return $element;
	}

	//Ajax
	public function saveAction(array $data): AjaxJson
	{
		//Заглушка для отработки ajax
		$element = [];
		$isUdpateSuccess = true;
		try
		{
			if ($isUdpateSuccess)
			{
				$element['ID'] = '1';
			}
			else
			{
				throw new SystemException(Loc::getMessage('EXAM31_ELEMENT_DETAIL_UPDATE_ERROR'));
			}

			return AjaxJson::createSuccess([
				'ENTITY_ID' => $element['ID'],
				//REDIRECT_URL необходим для корректной работы формы в слайдере
				//'REDIRECT_URL' => $this->getDetailPageUrl($element['ID']),
			]);
		}
		catch (SystemException $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return AjaxJson::createError($this->errorCollection);
		}
	}

	public function configureActions(): array
	{
		return [];
	}
	protected function listKeysSignedParameters(): array
	{
		return ['ELEMENT_ID', 'DETAIL_PAGE_URL'];
	}

	protected function getDetailPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['DETAIL_PAGE_URL']);
	}
}