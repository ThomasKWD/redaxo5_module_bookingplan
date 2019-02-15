<?php
  /*
    INPUT
    Modul BELEGPLAN

	required addons:
	- mform

    VALUES:
     1  Anzeigejahr
	 2  DEAKTIVIERT Start-Format (Tabelle|Liste)
	LINK
	 1  Startkategorie

	 Headline and text should be made bz Artikeltext module

	 PLANNED:
	 - choose colors

   */

// init mform
$mform = new MForm();

$years = array();
$threeYears = intval(date("Y")) - 1;

for ($i = $threeYears; $i < $threeYears + 4; $i++) {
	$years[$i] = $i;
}

// $years has keys equal to values
$mform->addSelectField(1, $years, array('label'=>'Jahr:'))->setDefaultValue(intval(date("Y")));
// how to define selected

// $mform->addSelectField(2, array('1'=>'Tabelle (Standard)','2'=>'Liste'), array('label'=>'Start-Format:'))->addDescription('Ein Link zum jeweils anderen Format wird immer erzeugt.');

$mform->addLinkField(1,array('label' => 'Belegungs-Einträge in' ));
$mform->addDescription('wenn kein Artikel eingestellt, wird aktueller genommen, in dem sich der "Belegplan" befindet.');


echo $mform->show();

// echo 'sprog test';
// echo sprogdown('hier ist was: ###anreisetag_beschreibung###, Ende der Ansage');
?>
