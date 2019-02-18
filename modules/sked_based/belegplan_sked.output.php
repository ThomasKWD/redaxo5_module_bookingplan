<?php
// OUTPUT
// Belegungsplan mit Sked

setlocale (LC_ALL, 'de_DE.utf8');

// cleanup
// - $monthsNames
// - test code

// style
// - make leading &nbsp; for numbers in th (instead leading zero)

// injects my project styles (similarly to those used in TinyMCE)
if (rex::isBackend()) {
	// ??? could check if already imported, when several block of this module present
    print "<style>\n@import url(\"".rex_url::base('layout/css/maincontent_module.css')."\");\n</style>";
}

$bp = new kwd_bookingplan_sked('REX_VALUE[1]','REX_VALUE[2]'); // year, category id


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
	echo '<div class="table-responsive">';
	echo $bp->getTable();
	echo '</div>'; // .table-responsive

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
// - for this you must have alle updatedate of affected entries (slices)
// - we don't want slices of other years, just displayed ones
// - thus it is easier to store date in slice loop above!! see at '$latestUpdateDate'

// cannot work with sked
// echo '<p>
// 	'.replaceSprog('belegplan_geaendert').' '.date("d. m. Y",$latestUpdateDate)
// .'
// </p>';

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
		<li>
			Alle Einträge im Belegplan existieren als Blocks auf dieser Seite (so ähnlich wie bei Shuri-Ryu-Wochenplan). Du musst einen neuen Block vom Typ "Belegungs-Eintrag" auf der Seite hinzufügen, um einen neuen Eintrag zu erstellen. Die Reihenfolge der Einträge auf der Seite ist egal.
		</li>
		<li>
			Du kannst in den Plan klicken, um einen vorhandenen Eintrag zu bearbeiten.
		</li>
		<li>
			Überlappungen sind hier rot und mit "!" markiert, im Frontend jedoch normal als "gebucht".
		</li>
		<li>
			Es werden nur Einträge überprüft, die dem eingestellten Anzeigejahr entsprechen.
		</li>
		<li>
			Du kannst viele unabhängige Pläne anlegen. Wenn du Pläne von verschiedenen Unterkünften brauchst, musst du die Belegungs-Einträge auf verschiedenen Seiten speichern.
		</li>
	</ul>

<?php
} // rex::isBackend()
?>
</section>
