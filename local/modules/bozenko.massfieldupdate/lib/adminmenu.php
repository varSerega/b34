<?php

namespace Bozenko\MassFieldUpdate;

final class AdminMenu
{
    public static function onBuildGlobalMenu(&$globalMenu, &$moduleMenu): void
    {
        $moduleMenu[] = [
            'parent_menu' => 'global_menu_settings',
            'section' => 'bozenko_massfieldupdate',
            'sort' => 500,
            'text' => 'Массовое изменение полей',
            'title' => 'Настройка прав массового изменения полей CRM и товаров',
            'url' => '/local/massfieldupdate/settings.php?lang='.LANGUAGE_ID,
            'icon' => 'iblock_menu_icon_types',
            'page_icon' => 'iblock_page_icon_types',
            'items_id' => 'bozenko_massfieldupdate',
        ];
    }
}
