<?PHP
/*
  OUTPUT
  Modul BELEGPLAN

	- values of module type "Belegungs-Eintrag" are set as constants for PHP code
*/

if (!defined('START_TIME')) {
	define ('START_TIME',3);
	define ('ARRIVAL_TYPE',4);
	define ('END_TIME',5);
	define ('DEPARTURE_TYPE',6);
	define ('NAME',1);
}

// this code is used to wrap SPROG addon functions to have text replacements in *backend*
// which are heavily used for this module
// so the editors can see nicely replaced text already in backend preview
// in case SPROG is not installed, it still works,
// ! when using replaceSprog, texts *without* markers are used
// ! only texts are wrapped which are seen in backend, there are more sprog tags
// TODO: provide var for text markers
if(rex_addon::get('sprog')->isAvailable()) {
	if (!function_exists('replaceSprog')) {
		function replaceSprog($text) {
			// sorry that I still use the old tag markers ###text###
			return sprogcard($text); // omit 2nd which would define certain clang
		}
	}
}
else {
	if (!function_exists('replaceSprog')) {
		function replaceSprog($text) {
			return $text;
		}
	}
}

$moduleRecognitionValue = 7;
$displayYear = intval('REX_VALUE[1]');

// table id counter needed for unique ids in html (table id - heders relations)
// for the case more than 1 tables on page
// the counting does not work anymore in Redaxo 5.x
if (!isset($tableId)) $tableId = 1;
else $tableId++;
// echo '#'.$tableId;

if (rex::isBackend()) {
	// ??? could check if already imported, when several block of this module present
    print "<style>\n@import url(\"".rex_url::base('layout/css/maincontent_module.css')."\");\n</style>";
}

if (!function_exists('sort_startzeit')) {
  function sort_startzeit($a,$b) {
    return strcmp($a["starttime"], $b["starttime"]); // ! content actually numbers, but too lazy to change code
  }
}

if (!function_exists('escapeTags')) {
    function escapeTags($s) {
        // eigene Routine f?r "<",">", da htmlentities Probleme macht (DB nach utf8 Umlaute)
        $s = str_replace('<','&lt;',$s);
        $s = str_replace('>','&gt;',$s);
        return $s;
    }
}



// makes link
if (!function_exists('getSliceLinkStart')) {
	function getSliceLinkStart($slice) {
		return '<a href="index.php?page=content/edit&article_id=REX_ARTICLE_ID&slice_id='.
			$slice->getId() .
			'&clang=1&ctype=REX_CTYPE_ID&function=edit#slice'.
			$slice->getId().
			'">';

	}
}



if (!function_exists('getDateStringFromValue')) {
	// TODO: we cannot use $monthsNames inside, we would need to define a class and every functionality inside
	function getDateStringFromValue($str,$showYear) {
		$ret = '<span class="booking-date">';

		$date = explode('-',$str);
		$ret .= (count($date) >= 3 ? $date[2] : '?') .'. ';
		$ret .= (count($date) >= 2 ? $date[1] : '?') .'. ';
		if ($showYear) $ret .= (count($date) >= 1 ? $date[0] : '?') .'. ';

		$ret .= '</span>';

		return $ret;
	}
}
// - adds link
// TODO: improve readability also usable for frontend
if (!function_exists('getSliceText')) {
	function getSliceText($slice) {

		$ret = '';

		//  check if start OR end is NOT current display year
		$dYear = "REX_VALUE[1]"; // to prevent repeated year
		if (strstr($slice->getValue(START_TIME),$dYear) == false || strstr($slice->getValue(5),$dYear) == false) $otherYear = true;
		else $otherYear = false;

		$ret .= getDateStringFromValue($slice->getValue(START_TIME), $otherYear);
		if ($slice->getValue(END_TIME) != '2') $ret .= replaceSprog('nachmittags');
		$ret .= ' bis ';
		$ret .= getDateStringFromValue($slice->getValue(5), $otherYear);
		if ($slice->getValue(DEPARTURE_TYPE) != '2') $ret .= replaceSprog('vormittags');

		if (rex::isBackend()) $ret = getSliceLinkStart($slice) . $ret . ' ('.$slice->getValue(1).')</a>';

		// return getSliceLinkStart($slice) .	$slice->getValue(1) . ': <b>'.$slice->getValue(3).' - '.$slice->getValue(5). '</b>'.

		return $ret;
	}
}

if (!function_exists('addValueAndCheckOverlap')) {
	function addValueAndCheckOverlap(&$plan, &$overlap, $row, $col, $slice, $value) {
		$prevId = $plan[$row][$col]['id'];

		$plan[$row][$col]['value'] += $value;
		$plan[$row][$col]['id'] = $slice->getId();

		if ($plan[$row][$col]['value'] > 3) {
			$overlap[$slice->getId()] = getSliceText($slice);
			if ($prevId) {
				$overlap[$prevId] = getSliceText(rex_article_slice::getArticleSliceById($prevId));
			}
		}
	}
}

// $bookingList has internal structure (sub arrays): [id, starttime, text]
// ! values of slice are read directly by number again; TODO: make constants for slice values
if (!function_exists('setListEntry')) {
	function setListEntry(&$blist, $slice, $displayYear) {
		$id = $slice->getId();

		$blist[$id]['id'] = $id;
		$test = strtotime($slice->getValue(START_TIME));
		$blist[$id]['starttime'] = $test;
		$blist[$id]['text'] = getSliceText($slice,$displayYear);
		// $bookingList[$slice->getId()] = getSliceText($slice,$displayYear);
	}
}

if (!function_exists('generateTableCell')) {
	// $state string that specifies empty|half-am|half-pm|error
	// $type string that specifies additional states like "angefragt" in contrast to empty or "booked"
	// $id leads to link + title  for backend
	// - conflict cells ($state == overlap) are marked as "booked" in frontend
	// $title is used differently, usually not as HTML title attr
	function generateTableCell($state, $type, $id) {
		// 	.$char.'</a>';
		$cellTypeMarker = '';
		switch ($state) {
			case '!' :
				if ($id) {
					$cssClass = 'overlap';
					break; // yes, break inside block
				}
			case 'X' :
				$cssClass = 'booked';
				$title = replaceSprog('belegt');
				break;
			case '/':
				$cssClass = 'arrival';
				$title = replaceSprog('anreisetag');
				$cellTypeMarker = '<span class="arrival__marker"></span>';
				break;
			case '\\':
				$cssClass = 'departure';
				$title = replaceSprog('abreisetag');
				$cellTypeMarker = '<span class="departure__marker"></span>';
				break;
			case '=':
				$cssClass = 'invalid';
				$title = '';
				break;
			default :
				$cssClass = 'free';
				$title = replaceSprog('frei');
				break;
		}
		$linkStart = '';
		//  TODO: check isBackend again if $id can occur in Frontend!
		if ($id) {
			$slice = rex_article_slice::getArticleSliceById($id);
			if ($slice) {
				$linkStart = getSliceLinkStart($slice);
				$title = $slice->getValue(1);
			}
		}
		if ($cssClass=='overlap') {
			$cellText = '!';
		}
		else if ($id){
			$cellText = '&nbsp;';
		}
		else {
			// ! HTML
			$cellText  = '<span class="sr-only">'.$title.'</span>';
		}
		// ! $title is used for sr-only/aria-hidden, real title only in backend
		return '			<td class="'.$cssClass.'"'.
		($id ? ' title="'.$title.'"' : '').
		'>'.$cellTypeMarker.$linkStart.$cellText.($linkStart ? '</a>' : '').'</td>
		';
	}
}

// GENERATE booking plan matrix
// [row][col][value|id]

$bookingplan = array();
for ($row = 1; $row <= 12; $row++) {
	for ($col = 1; $col <= 31; $col++) {
		$bookingplan[$row][$col]['value'] = 0;
		$bookingplan[$row][$col]['id'] = 0;
	}
}

$bookingList = array();

if (!"REX_LINK[id=1 output=id]") {
  $aid="REX_ARTICLE_ID"; // sich selbst nehmen, wenn nichts gefunden...
}
else {
  $aid ="REX_LINK[id=1 output=id]";
}

// ! when providing REX_CLANG_ID it works correctly even as included article in another language.
// ! when omitting the REX_CLANG_ID it will use the actual language of the page - which is not desired
$slice = rex_article_slice::getFirstSliceForArticle($aid,REX_CLANG_ID);
$sliceCount=0; // TODO: remove if not really needed
$invalidList = array();
$overlappingList = array();
$latestUpdateDate = 0;

// Einlesen je nach Ausgabeziel
while ($slice) {
	if (($slice->getValue($moduleRecognitionValue) == 'belegungseintrag')) {

		$sliceCount++;

		$startDate = trim($slice->getValue(START_TIME));
		$endDate = trim($slice->getValue(END_TIME));
		$start = strtotime($startDate);
		$end = strtotime($endDate);

		// first check wrong dates and negative time span
		if (!$start || !$end || ($end < $start && (date("Y",$start) == $displayYear || date("Y",$end) == $displayYear))) {
			// writing the id as index prevents duplicate entries
			$invalidList[$slice->getId()] = getSliceText($slice);
		}
		else {
			$startYear = $displayYear;
			$startMonth = 1;
			$startDay = 1;
			$endYear = $displayYear;
			$endMonth = 1;
			$endDay = 1;

			// TODO: make function
			$startEntry = explode('-',$startDate);
			if (count($startEntry) != 3) {
				echo 'error in date '.$startDate;
			}
			else {
				$startYear = intval($startEntry[0]);
				$startMonth = intval($startEntry[1]);
				$startDay = intval($startEntry[2]);
			}
			$endEntry = explode('-',$endDate);
			if (count($endEntry) != 3) {
				echo 'error in date '.$endDate;
			}
			else {
				$endYear = intval($endEntry[0]);
				$endMonth = intval($endEntry[1]);
				$endDay = intval($endEntry[2]);
			}

			// check for year
			if ($startYear == $displayYear || $endYear == $displayYear) {
				// echo $sliceCount.' valid '.$slice->getId().'<br />';

				// clip earlier start to 1.1.
				if ($startYear != $displayYear) {
					$startYear = $displayYear;
					$startMonth = 1;
					$startDay = 0; // to prevent showing a half startday
				}
				// clip later end to 31.12.
				if ($endYear != $displayYear) {
					$endYear = $displayYear;
					$endMonth = 12;
					$endDay = 32;  // to prevent showing a half endday
				}

				// echo "month: $startMonth/$endMonth";
				// echo "day: $startDay/$endDay";
				// write cells of $bookingplan
				// the += lead to indicate overlappings automatically
				for ($row = $startMonth; $row <= $endMonth; $row++) {
					for ($col = 1; $col <= 31; $col++) {
						if (
							($startMonth < $endMonth && $row == $startMonth && $col > $startDay) ||
							($row > $startMonth && $row < $endMonth) ||
							($startMonth < $endMonth && $row == $endMonth && $col < $endDay) ||
							($startMonth == $endMonth && $row == $startMonth && $col > $startDay && $col < $endDay)
						) {
							addValueAndCheckOverlap($bookingplan, $overlappingList, $row,$col,$slice,3);
							// $bookingplan[$row][$col]['value'] += 3;
							// $bookingplan[$row][$col]['id'] = $slice->getId();
						}
						// start day
						if ($row == $startMonth && $col == $startDay) {
							if ($slice->getValue(ARRIVAL_TYPE)=='2') $wholeDay = 2; else $wholeDay = 0;
							addValueAndCheckOverlap($bookingplan, $overlappingList, $row,$col,$slice,1 + $wholeDay);

							// $bookingplan[$row][$col]['value'] += 1 + $wholeDay;
							// $bookingplan[$row][$col]['id'] = $slice->getId();
						}
						// end day
						if ($row == $endMonth && $col == $endDay) {
							if ($slice->getValue(DEPARTURE_TYPE)=='2') $wholeDay = 1; else $wholeDay = 0;
							addValueAndCheckOverlap($bookingplan, $overlappingList, $row,$col,$slice, 2 + $wholeDay);
							// $bookingplan[$row][$col]['value'] += 2 + $wholeDay;
							// $bookingplan[$row][$col]['id'] = $slice->getId();
						}
					}
				}

				// make list after 'for' over days because now we have found overlappings
				// $bookingList[$slice->getId()] = getSliceText($slice,$displayYear);
				setListEntry($bookingList,$slice,$displayYear);
				// store latest updatedate found
				// echo  'date: '.date("d.m.Y",$slice->getValue('updatedate'));
				$t = intval($slice->getValue('updatedate'));
				$latestUpdateDate = ($t > $latestUpdateDate) ? $t : $latestUpdateDate;
			}
		}
	}
	$slice = $slice->getNextSlice();
} // while

// - don't use date() or other php function for names because setlocale can have different results on different servers
// - first entry '' to have Januar on index==1
$monthsNames = array ( '','Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
	'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');

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
echo "<h2>$displayYear</h2>";


if (rex::isBackend() || !rex_request('bookingplan')) {

	echo '<div class="table-responsive">';
	echo '	<table class="bookingplan">';
	echo '		<tr>';
	echo '			<th class="col-header" title="Monat">Monat</th>'; // proper accessible first col header!
	for ($col = 1; $col <= 31; $col++) {
		echo '			<th>'.$col.'</th>';
	}
	echo '		</tr>';

	// table generate loop
	for($row = 1; $row <= 12; $row++) {
		echo '		<tr>';
		// start of row is a td which is defined as row header!
		// ! new concept: full name if read by screen reader, short name for eyes:
		// ! title attr is always discouraged
		echo '			<td class="row-header">'.kwd_getMonthName($row).'</td>';
		for ($col = 1; $col <= 31; $col++) {

			// handle shorter months and leapyear
			if (
				($col == 31 && ($row == 2 || $row == 4 || $row == 6 || $row ==9 || $row == 11)) ||
				($col == 30 && ($row == 2)) ||
				($col == 29 && ($displayYear % 4) != 0  && $row == 2)
			) {
				$char = '=';
			}
			else {
				$e = $bookingplan[$row][$col]['value'];

				if (rex::isBackend()) {
					// echo getSliceLinkStart(rex_article_slice::getArticleSliceById($bookingplan[$row][$col]['id']))
					// 	.$char.'</a>';
					$id = $bookingplan[$row][$col]['id'];
				}
				else $id = 0;

				if ($e) {
					$char = '';
					if ($e == 1) $char = '/';
					else if ($e == 2) $char = '\\';
					else if ($e == 3) $char = 'X';
						// $bookingplan[$row][$col]['id'];
					else if ($e > 3) {
						$char = '!';
						// TODO: already when writing into matrix, so we can save old and new overlapping entries
						// writing the id as index prevents duplicate entries
						// $slice = rex_article_slice::getArticleSliceById($bookingplan[$row][$col]['id']);
						// if ($slice) {
						// 	$o = $slice->getValue(1) . ': <b>'.$slice->getValue(3).' - '.$slice->getValue(5). '</b>';
						// }
						// else $o = "slice $bookingplan[$row][$col]['id'] error";
						// if (!isset($overlappingList[$bookingplan[$row][$col]['id']])) $overlappingList[$bookingplan[$row][$col]['id']] = $o;
					}
				}
				else $char = '&middot;';
			}
			echo generateTableCell($char,'',$id); // change usage to rex::isBackend as param if slice interna needed in Frontend
		}
		echo "		</tr>\n";
	}

	echo '	</table>';
	echo '</div>'; // .table-responsive

	// INFO FOR HORI SCROLL
	if (!rex::isBackend()) echo '<p class="table-scroll-info" aria-hidden="true">(<span class="display-info">###kleinedisplays###: </span>###tabelleverschieben###)</p>';
}


// LISTENANSICHT

// first sort entries
// - each entry of $bookingList has a sub field 'starttime' as timestamp (numeric)
usort($bookingList,'sort_startzeit');

if (rex::isBackend()) {
	// Title not in sprog because never seen in Frontend
	echo '<h3>Listenansicht</h3>';
	echo '<p>';
	foreach ($bookingList as $value) {
		echo $value['text'];
		if (array_key_exists($value['id'],$overlappingList)) {
			echo ' (Überlappung!)';
		}
		echo '<br />';
	}
	echo '</p>';
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

// DATE OF LAST CHANGE
// - for this you must have alle updatedate of affected entries (slices)
// - we don't want slices of other years, just displayed ones
// - thus it is easier to store date in slice loop above!! see at '$latestUpdateDate'
echo '<p>
	'.replaceSprog('belegplan_geaendert').' '.date("d. m. Y",$latestUpdateDate)
.'
</p>';

if (!rex_request('bookingplan')) {
	// LEGEND

	// - show before list view
	// - internal structure must be like table above -- for styles

	?>
		<div class="table-responsive">
			<table class="bookingplan bookingplan--legend">
				<caption><h3><?php echo replaceSprog('legende') ?></h3></caption>
				<tr class="sr-only">
					<th>Feld</th>
					<th>Bezeichnung</th>
				</tr>
				<tr>
					<td class="arrival">
						<span class="arrival__marker"></span>
						<span class="sr-only"><?php echo replaceSprog('anreisetag') ?></span>
					</td>
					<td class="description"><?php echo replaceSprog('anreisetag_beschreibung') ?></td>
				</tr>
				<tr>
					<td class="booked">
						<span class="sr-only"><?php echo replaceSprog('belegt') ?></span>
					</td>
					<td class="description"><?php echo replaceSprog('belegt_beschreibung') ?></td>
				</tr>
				<tr>
					<td class="departure">
						<span class="departure__marker"></span>
						<span class="sr-only"><?php echo replaceSprog('abreisetag') ?></span>
					</td>
					<td class="description"><?php echo replaceSprog('abreisetag_beschreibung') ?></td>
				</tr>
			</table>
		</div>
	<?php
}

if (rex::isBackend()) {

	// if (count($overlappingList)) {
	// 	echo '<h3>Überlappende Einträge</h3>';
	// 	foreach ($overlappingList as $i) {
	// 		echo $i . '<br />';
	// 	}
	// }
	if (count($invalidList)) {
		echo '<h3>Fehlerhafte Einträge</h3>';
		echo '<p>';
		foreach ($invalidList as $i) {
			echo $i . '<br />';
		}
		echo '</p>';
	}

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
