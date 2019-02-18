<?php
/*
INPUT
Belegungsplan mit sked

required addons:
- mform
- sked Kalender
recommended:
- sprog

VALUES:
 1  Anzeigejahr
 2  Kategorie im sked (speichert ID!)
*/

// init mform
if (!rex_addon::get('mform')->isAvailable()) {
	echo rex_view::error('benötigt das Addon "MForm"! Bitte installieren und aktivieren!');
}
else {

	$mform = new MForm();

	$years = array();
	$threeYears = intval(date("Y")) - 1;

	for ($i = $threeYears; $i < $threeYears + 4; $i++) {
		$years[$i] = $i;
	}

	// $years has keys equal to values
	$mform->addSelectField(1, $years, array('label'=>'Zeige Jahr:'))->setDefaultValue(intval(date("Y")));
	// how to define selected
	// if (rex_addon::get('sked')->isAvailable()) {
	// 	$skedCats = array( 'Scheune','Hauptgebäude', 'Wohnwagen blau');
	// 	$mform->addSelectField(1)
	// 	->setOptions('SELECT `name_1`, `id` FROM `' . rex::getTablePrefix() . 'sked_categories` ORDER BY `name_1` ASC')
	// 	->setLabel('Kategorie im Kalender');
	//
	// 	$mform->addDescription('Du musst mindestens 1 Kategorie in "Sked Kalender" hinzufügen. Es werden dann alle Termine mit dieser Kategorie zu diesem Belegplan hinzugefügt.');
	// }
	// else {
	// 	echo rex_view::error('benötigt das Addon "sked Kalender"!');
	// }

	echo $mform->show();

}

if (!rex_addon::get('sked')->isAvailable()) {
	echo rex_view::error('benötigt das Addon "Sked Kalender"! Bitte installieren und aktivieren!');
}
else {
	$select = new rex_select();
	$select->setId('sked_category');
	$select->setAttribute('class', 'form-control');
	$select->setName('REX_INPUT_VALUE[2]');
	$select->addSqlOptions('SELECT `name_1`, `id` FROM `' . rex::getTablePrefix() . 'sked_categories` ORDER BY `name_1` ASC');
	$select->addOption('Alle (nicht empfohlen)','');
	$select->setSelected('REX_VALUE[2]');
	// $catselect = $select->get();
	$select->show();
}

// echo 'sprog test';
// echo sprogdown('hier ist was: ###anreisetag_beschreibung###, Ende der Ansage');
