<?php
defined('B_PROLOG_INCLUDED') || die;

$component = $this->getComponent();
?>
<span class='field-wrap'>
	<?php
	foreach ($arResult['fieldValues'] as $value)
	{
		?>
		<span class='field-item'>
			<input <?= $component->getHtmlBuilder()->buildTagAttributes($value['attrList']) ?>>
		</span>
		<?php
	}
	if ($arResult['userField']['MULTIPLE'] === 'Y')
	{
		print $component->getHtmlBuilder()->getCloneButton($arResult['fieldName']);
	}
	?>
</span>