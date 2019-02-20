<?php
setlocale (LC_ALL, 'de_DE.utf8');

// cleanup
// - $monthsNames
// - test code

// style
// - make leading &nbsp; for numbers in th (instead leading zero)

if (rex::isBackend()) {
	print "<style>\n@import url(\"".rex_url::base('layout/css/bookingplan.css')."\");\n</style>";
}

$bp = new kwd_bookingplan_sked('REX_VALUE[1]','REX_VALUE[2]'); // year, category id

$bp->getLastestUpdateDate();

// ! direct echo into HTML below this point

if (!isset($togglePlanLayoutLink)) {
	$togglePlanLayoutLink = 1;
	if (!rex::isBackend()) {
		echo '<p>';
		if (rex_request('bookingplan')) {
			echo '<a href="'.rex_getUrl('').'">###link_tagesansicht###</a>';
			echo '<p>###beleg_liste_einleitung###</p>';
		}
		else {
			echo '<a href="'.rex_getUrl('', '', array ('bookingplan' => 'list')).'">###link_listenansicht### <span class="you-may-set-sr-only">###link_zusatz_liste###</span></a>';
		}
		echo '</p>';
	}
}
else {
	$togglePlanLayoutLink++;
}

echo '<section class="abschnitt">';
echo "<h2>".$bp->getYear()."</h2>"; // not just REX_VALUE[1], because invalid checks apply

if (rex::isBackend() || !rex_request('bookingplan')) {

	if ('REX_VALUE[3]'=='month') {
		echo '<div class="row">';
		for ($i=1; $i <= 12; $i++) {
			echo '<div class="col-xs-12 col-sm-6 col-md-4">';
			echo $bp->getMonthTable($i,true); // true: display year in each month's caption
			echo '</div>';
		}
		echo '</div>';
	}
	else {
		echo '<div class="table-responsive">';
		echo $bp->getTable();
		echo '</div>'; // .table-responsive
	}

	// INFO FOR HORI SCROLL
	if (!rex::isBackend()) echo '<p class="table-scroll-info" aria-hidden="true">(<span class="display-info">###kleinedisplays###: </span>###tabelleverschieben###)</p>';
}


// LISTENANSICHT

// ??? could still be defined and invoked here in case not working in class
// usort($bookingList,'sort_startzeit');

$bookingList = $bp->getList();

if (rex::isBackend()) {

	// Title not in sprog because never seen in Frontend
	echo '<h3>Listenansicht ('.count($bookingList).' Einträge)</h3>';
	echo '<ul class="booking-list">';

	$overlappingList = $bp->getOverlappingList();
	foreach ($bookingList as $value) {
		echo $value['text'];
		if (array_key_exists($value['id'],$overlappingList)) {
			echo '  <span class="be_warning">Überlappung!</span>';
		}
		echo '<br />';
	}
	echo '</ul>';
}
else if (rex_request('bookingplan')) {
	// we need to repeat this because integrated into table above
	echo '<ul class="booking-list">';
	foreach ($bookingList as $value) {
		echo '<li>';
		echo $value['text'];
		echo '</li>';
	}
	echo '</ul>';
}

if (rex::isBackend()) {

	// if (count($overlappingList)) {
	// 	echo '<h3>Überlappende Einträge</h3>';
	// 	foreach ($overlappingList as $i) {
	// 		echo $i . '<br />';
	// 	}
	// }
	$invalidList = $bp->getInvalidList();
	if (count($invalidList)) {
		$errorList = '';
		foreach ($invalidList as $i) {
			$errorList .= "<li>$i</li>";
		}
		echo rex_view::warning('Fehlerhafte Einträge:<ul>'.$errorList.'</ul>');
	}


}

// DATE OF LAST CHANGE
echo '<p>
	'.$bp->replaceSprog('belegplan_geaendert').' '.$bp->getLastestUpdateDate()
.'
</p>';

if (!rex_request('bookingplan')) {
	// LEGEND

	// - show before list view
	// - internal structure must be like table above -- for styles

	// - using $bp->replaceSprog instead of sprogcard directly, because secure in case no sprog installed
	?>
		<div class="table-responsive">
			<table class="bookingplan bookingplan--legend">
				<caption><h3><?=$bp->replaceSprog('legende')?></h3></caption>
				<tr class="sr-only">
					<th>Feld</th>
					<th>Bezeichnung</th>
				</tr>
				<tr>
					<td class="arrival">
						<span class="arrival__marker"></span>
						<span class="sr-only"><?=$bp->replaceSprog('anreisetag')?></span>
					</td>
					<td class="description"><?=$bp->replaceSprog('anreisetag_beschreibung')?></td>
				</tr>
				<tr>
					<td class="booked">
						<span class="sr-only"><?=$bp->replaceSprog('belegt')?></span>
					</td>
					<td class="description"><?=$bp->replaceSprog('belegt_beschreibung')?></td>
				</tr>
				<tr>
					<td class="departure">
						<span class="departure__marker"></span>
						<span class="sr-only"><?=$bp->replaceSprog('abreisetag')?></span>
					</td>
					<td class="description"><?=$bp->replaceSprog('abreisetag_beschreibung')?></td>
				</tr>
			</table>
		</div>
	<?php
}

if (rex::isBackend()) {
	?>
	<h3>Hinweise:</h3>

	<ul>
		<li>Die Daten des Plans werden im "Sked Kalender" verwaltet. Du kannst Einträge bearbeiten, indem du hier <strong>in den Plan klickst</strong>. "Sked" öffnet sich in einem anderen Browser-Tab. Ich empfehle beide Tabs geöffnet zu lassen und zur Übericht immer hierher in die Artikel-Ansicht zurück zu gehen.
		</li>
		<li>	Füge einen neuen Eintrag hinzu, indem du auf einen freien Tag klickst. Dann gelangst du in die "Sked"-Eingabe. Dort musst du das Datum aber nochmal neu festsetzen.
		</li>
		<li>Du kannst jedem Eintrag eine Bezeichnung geben, die nur hier und niemals im Frontend zu sehen ist.</li>
		<li>
			Überlappungen sind hier rot und mit "!" markiert, im Frontend jedoch normal als "gebucht".
		</li>
		<li>Fehlerhafte Einträge erscheinen separat, z. B. bei einer negativen Zeitspanne. Klicke darauf, um sie zu bearbeiten.</li>
		<li>
			Es werden nur Einträge überprüft, die dem eingestellten Anzeigejahr entsprechen.
		</li>
		<li>
			Du kannst viele unabhängige Pläne anlegen. Wenn du Pläne von verschiedenen Unterkünften brauchst, musst du im "Sked Kalender" eine andere "Kategorie" anlegen und verwenden.
		</li>
	</ul>

<?php

} // rex::isBackend()
?>
</section>
