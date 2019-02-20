<?php
  /*
    INPUT
    Modul BELEGPLAN

	required addons:
	- mform
	- sked Kalender

    VALUES:
     1  Anzeigejahr
	 2  Kategorie im sked (speichert ID!)
	 3  Anzeigemodus: year|month  (1 große oder 1..12 separate Tabellen)
   */

// init mform
if (!rex_addon::get('mform')->isAvailable()) {
	echo rex_view::error('benötigt das Addon "MForm"! Bitte installieren und aktivieren!');
}
else {

	$mform = new MForm();

	$mform->addSelectField(3, array( 'year' => 'Jahrestabelle', 'month' => 'Eine Tabelle pro Monat'), array('label'=>'Anzeige:'))->setDefaultValue('year');

	$years = array();
	$threeYears = intval(date("Y")) - 1;

	for ($i = $threeYears; $i < $threeYears + 4; $i++) {
		$years[$i] = $i;
	}

	// $years has keys equal to values
	$mform->addSelectField(1, $years, array('label'=>'Jahr:'))->setDefaultValue(intval(date("Y")));
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
	// ??? as mform element
	$select = new rex_select();
	$select->setId('sked_category');
	$select->setAttribute('class', 'form-control');
	$select->setName('REX_INPUT_VALUE[2]');
	$select->addSqlOptions('SELECT `name_1`, `id` FROM `' . rex::getTablePrefix() . 'sked_categories` ORDER BY `name_1` ASC');
	$select->addOption('Alle (nicht empfohlen)','');
	$select->setSelected('REX_VALUE[2]');
	// $catselect = $select->get();
	echo '<label for="sked_category">Kategorie aus sked für diese Belegung:</label>';
	$select->show();
}

// echo 'sprog test';
// echo sprogdown('hier ist was: ###anreisetag_beschreibung###, Ende der Ansage');
