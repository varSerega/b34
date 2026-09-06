<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\ModuleTable;

class bozenko_massfieldupdate extends CModule
{
    public $MODULE_ID = 'bozenko.massfieldupdate';
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '20260906';
    public $MODULE_NAME = 'Массовое изменение полей';
    public $MODULE_DESCRIPTION = 'Массовое изменение полей CRM и товаров по правам групп пользователей';

    public function DoInstall()
    {
        global $APPLICATION;

        $module = ModuleTable::getList([
            'select' => ['ID'],
            'filter' => ['=ID' => $this->MODULE_ID],
            'cache' => ['ttl' => 0],
        ])->fetch();

        if (!$module) {
            ModuleManager::registerModule($this->MODULE_ID);
        }
        $APPLICATION->IncludeAdminFile('Установка модуля', __DIR__.'/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        COption::RemoveOption($this->MODULE_ID);
        $module = ModuleTable::getList([
            'select' => ['ID'],
            'filter' => ['=ID' => $this->MODULE_ID],
            'cache' => ['ttl' => 0],
        ])->fetch();

        if ($module) {
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
        $APPLICATION->IncludeAdminFile('Удаление модуля', __DIR__.'/unstep.php');
    }
}
