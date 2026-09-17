<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Diag\Debug;
\Bitrix\Main\UI\Extension::load('ui.sidepanel-content');

/**
 * @var array $arParams
 * @var array $arResult
 */
?>

<?
//Debug::dump($arResult['DISPLAY_ELEMENT']);
?>
<div class="ui-slider-section">
	<? if (!empty($arResult['ELEMENT'])): ?>
		<? foreach ($arResult['ELEMENT'] as $value): ?>
			<div class="ui-slider-content-box">
				<p class="ui-slider-paragraph"><?= $value ?></p>
			</div>
		<? endforeach; ?>
	<? endif; ?>
	<div class="ui-slider-content-box">
		<p class="ui-slider-paragraph"><a href="<?= $arResult['LIST_PAGE_URL'] ?>"><?= Loc::getMessage('EXAM31_ELEMENT_DETAIL_BACK_TO_LIST') ?></a></p>
	</div>
</div>