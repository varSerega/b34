<?
$aMenuLinks = Array(
	Array(
		"CRM", 
		"/crm/", 
		Array(), 
		Array(), 
		"CBXFeatures::IsFeatureEnabled('crm') && CModule::IncludeModule('crm') && CCrmPerms::IsAccessEnabled()" 
	),
	Array(
		"Массовое изменение полей", 
		"/local/massfieldupdate/index.php", 
		Array(), 
		Array(), 
		"is_file(\$_SERVER['DOCUMENT_ROOT'].'/local/massfieldupdate/index.php')" 
	)
);
?>