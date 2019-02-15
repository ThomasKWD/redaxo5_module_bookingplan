<?php
/*
INPUT
Modul BELEGUNGS-EINTRAG

Eigenentwicklung für Belegplanzeige nur basierend auf Artikeldaten

Benoetigte Addons:
- MForm


Values:
1 Bezeichnung, kannfrei vergeben werden
2 spezieller Status, z.B. angefragt
3 Startdatum
4 Zusatz zu Startdatum, z.B. ob ganzer Tag (kein halber wegen Anreise abends, was Standard wäre)
5 Enddatum
6 Zusatz zu Enddatum, z.B. ob ganzer Tag (kein halber wegen Abreise morgens, was Standard wäre)
7 hidden module recognizer // ??? make it value 20  
*/

// because of mform date bug
error_reporting(E_ALL & ~E_NOTICE);
// TODO: try datepicker from addon ui_tools

// init mform
$mform = new MForm();

// fieldset
$mform->addFieldset('Typisierung');

// Bezeichn.
$mform->addTextField(1, array('label'=>'Bezeichnung')); // use string for x.0 json values
// description
$mform->addDescription('zum leichteren Wiederfinden einer Buchung in der Liste. Dies wird <i>nicht</i> im Frontend angezeigt.');

// Status
$mform->addSelectField("2.0", array(1=>'gebucht (Standard)',2=>'angefragt',3=>'Zeitraum gesperrt'), array('label'=>'Status')); // use string for x.0 json values


// fieldset
$mform->addFieldset('Zeitraum');

// Beginn
$mform->addInputField("date", 3,array('label'=>'Beginn')); // Datum
$mform->addSelectField(4, array(1=>'nachmittags (Standard)',2=>'ganzen Tag belegen'), array('label'=>'Anreisetyp')); // use string for x.0 json values

// Ende
$mform->addInputField("date", 5, array('label'=>'Ende')); // Datum
// TODO: if you like to use the datepicker of ui-tools you must consider to ALWAYS convert date from string via PHP function, not simple string operation because saved format is different
// $mform->addTextField(5, array('label' => 'Headline', 'style' => 'width:200px', 'class' => 'datepicker')); // datepicker comes from Addon Ui-tools

// date test demo
if (rex::getUser()->hasPerm('admin[]')) {
	$mform->addTextField(8, array('label' => 'Date test', 'class' => 'datepicker')); // datepicker comes from Addon Ui-tools
}

$mform->addSelectField(6, array(1=>'vormittags (Standard)',2=>'ganzen Tag belegen'), array('label'=>'Abreisetyp'));

$mform->addHiddenField(7, array('value' => 'belegungseintrag'));

// fieldset
// $mform->addFieldset('Erläuterungen');



// parse form
echo $mform->show();

// text innerhalb siehe "strukturelle inline Elemente"
?>
