<?php
defined('B_PROLOG_INCLUDED') || die;
?>

<span class="fields string field-wrap">
	<?php
	foreach ($arResult['PREPARED_VALUES'] as $item)
	{ ?>
		<span class="fields string field-item">
			<?php
			print $item['FORMATTED_VALUE'];
			?>
		</span>
		<?php
	} ?>
</span>