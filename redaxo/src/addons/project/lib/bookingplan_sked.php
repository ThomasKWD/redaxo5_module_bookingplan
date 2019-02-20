<?php

// style
// - make leading &nbsp; for numbers in th (instead leading zero)

// styles must be included by module

class kwd_bookingplan_sked {

	protected $displayYear = 2015;
	protected $categoryId = '1';
	protected $skedEntries = array();
	protected $sprog = false;
	protected $bookingPlan = array();
	protected $bookingList = array();
	protected $overlappingList = array();
	protected $invalidList = array();

	function __construct($year, $id, $generateNow = true) {

		// if u need a plan for 1999 write me a letter :-)
		if(intval($year) > 2000 ) $this->displayYear = $year;

		if (is_numeric($id)) $this->categoryId = $id;

		$this->sprog = rex_addon::get('sprog')->isAvailable();

		$this->initSkedEntries();

		if ($generateNow) $this->generateBookingPlan();
	}

	function initSkedEntries() {

		if (rex_addon::get('sked')->isAvailable()) {
			$start = strtotime($this->displayYear."-01-01");
			$end = strtotime($this->displayYear."-12-31 23:59:00");

			$this->skedEntries = \Sked\Handler\SkedHandler::getEntries(date("Y-m-d H:i:s",$start), date("Y-m-d H:i:s",$end), true, 'SORT_ASC', $this->categoryId);

			// $list = '';
			// foreach($skedEntries as $entry) {
			// 	$e = $entry['entry'];
			// 	$list.= "<li>"
			// 		.$e->entry_name
			// 		.' -'.$e->category_id.'- '
			// 		.($e->entries_arrivaltype == '2' ? 'ganzer Tag belegt' : 'Anreise nachmittags')
			// 		."| Abreise: "
			// 		.$e->entry_end_date->format("d.m.Y") // DateTime object
			// 		." (".($e->entries_departuretype == '2' ? 'ganzer Tag belegt' : 'Abreise vormittags')
			// 		.")</li>";
			// }
			//
			//
			// if (!$list) $list = '<p>Keine Belegungs-Einträge in diesem Zeitraum.</p>'; else $list = "<ul>$list</ul>";

			// echo '<pre>';
			// print_r($this->skedEntries);
			// echo '</pre>';

			if (count($this->skedEntries)) return true;
		}
		// for the case init is called by user later
		else {
			$this->skedEntries = array();
			// ??? module should put warning
			// if (rex::isBackend()) {
			// 	echo rex_view::error('Benötigt das Addon "Sked Kalender"! Bitte installieren und aktivieren!');
			// }
		}

		return false;
	}

	function getEntries() {
		return $this->skedEntries; // lot of copying
	}

	/** read updatedate of each entry and find latest
	*	- finds entries of category set in instance
	*
	*	??? only select entries in the current year
	*   ??? having updatedate in the initial api response would save this extra query
	*/
	function getLastestUpdateDate() {
		$sql = rex_sql::factory();
		$query = 'SELECT `name_1`, `id`, `updatedate` FROM `' . rex::getTablePrefix() . 'sked_entries` WHERE `category`=1 ORDER BY `updatedate` DESC LIMIT 1';
		$sql->setQuery($query);
		return strftime('%d. %B %Y',$sql->getDateTimeValue('updatedate'));
	}

	function replaceSprog($text) {
		if ($this->sprog) return sprogcard($text);
		return $text;
	}

	// ??? needed when sked?
	function sort_startzeit($a,$b) {
		return strcmp($a["starttime"], $b["starttime"]); // ! content actually numbers, but too lazy to change code
	}

	protected function getEntryLinkStart($id) {
		if (!$id) return '<a href="index.php?page=sked/entries&func=add" target="myskedpage">';
		return '<a href="index.php?page=sked/entries&func=edit&id='.$id.'" target="myskedpage">';
	}


	// ??? make own array of sekd entries and only save timestamps
	// want to omit year in most cases
	// $showYear boolean
	protected function getDateStringFromValue($timeStamp,$showYear) {
		$ret = '<span class="booking-date">';

		// ??? use DateTime or other php functions
		// $date = explode('-',$str);
		// $ret .= (count($date) >= 3 ? $date[2] : '?') .'. ';
		// $ret .= (count($date) >= 2 ? $date[1] : '?') .'. ';
		// if ($showYear) $ret .= (count($date) >= 1 ? $date[0] : '?') .'. ';
		$ret .= strftime('%a %d. ',$timeStamp) . $this->getMonthName(date('n',$timeStamp));
		if ($showYear) $ret .= strftime(' %Y',$timeStamp); // with space!
		$ret .= '</span>';

		return $ret;
	}

	// ! has a lot to copy
	// ??? get one of the list $skedEntries
	//
	protected function getSkedEntry($id) {
		foreach($this->skedEntries as $e) {
			if (intval($e['id']) == $id) return $e['entry'];
		}

		return null;
			// ! has a lot to copy
			// ??? get one of the list $skedEntries
			// function getSkedEntry($id) {
			// 	$ret = \Sked\Handler\SkedHandler::getEntry($id);
			// 	if (is_array($ret)) return $ret[0];
			// 	return $ret;
			// }

	}


	// - adds link
	// ??? pass values instead of whole entry object
	function getEntryInfo($e) {

		$ret = '';

		if (is_array($e)) {
			$e = $e[0];
			trigger_error('found $entry inside array #'.$e->entry_id);
		}

		if ($e->entry_start_date->format('Y') != $this->displayYear
			|| $e->entry_end_date->format('Y') != $this->displayYear) $otherYear = true;
		else $otherYear = false;

		$ret .= $this->getDateStringFromValue($e->entry_start_date->getTimeStamp(), $otherYear) . ' ';

		// ! value 2 always is second option == whole day booked
		if ($e->entries_arrivaltype != '2') $ret .= $this->replaceSprog('nachmittags');
		$ret .= ' &ndash; '; // ??? also replaceSprog
		$ret .= $this->getDateStringFromValue($e->entry_end_date->getTimeStamp(), $otherYear) . ' ';
		if ($e->entries_departuretype != '2') $ret .= $this->replaceSprog('vormittags');

		if (rex::isBackend()) $ret = $this->getEntryLinkStart($e->entry_id) . $ret . ' ('.$e->entry_name.')</a>';

		return $ret;
	}

	function addValueAndCheckOverlap(&$plan, &$overlap, $row, $col, $entry, $value) {
		$prevId = $plan[$row][$col]['id'];

		$plan[$row][$col]['value'] += $value;
		$plan[$row][$col]['id'] = $entry->entry_id;

		if ($plan[$row][$col]['value'] > 3) {
			$overlap[$entry->entry_id] = $this->getEntryInfo($entry);
			if ($prevId) {
				$overlap[$prevId] = $this->getEntryInfo($this->getSkedEntry($prevId));
			}
		}
	}


	/** produce a cell in output table
	* @param state string that specifies empty|half-am|half-pm|error
	* PLANNED: $type string that specifies additional states like "angefragt" in contrast to empty or "booked"
	* @param id value or unset or null or 0 == backend, -1 == frontend
	* - conflict cells ($state == overlap) are marked as "booked" in frontend
	* $title is used differently, usually not as HTML title attr
	*/
	function generateTableCell($row,$col, $writeDay = false) {

		$cellTypeMarker = '';
		$id = 0;
		$linkStart = '';
		$cellText = '&nbsp;';


		$e = $this->bookingPlan[$row][$col]['value'];

		if (rex::isBackend()) {
			// $out .= getSliceLinkStart(rex_article_slice::getArticleSliceById($bookingplan[$row][$col]['id']))
			// 	.$char.'</a>';
			$id = intval($this->bookingPlan[$row][$col]['id']);
		}
		else $id = -1; // ! to distinguish between frontend and unset $id

		if ($e) {
			$char = '';
			if ($e == 1) {
				$cssClass = 'arrival';
				$title = $this->replaceSprog('anreisetag');
				$cellTypeMarker = '<span class="arrival__marker"></span>';
			}
			else if ($e == 2) {
				$cssClass = 'departure';
				$title = $this->replaceSprog('abreisetag');
				$cellTypeMarker = '<span class="departure__marker"></span>';
			}
			else if ($e == 3) {
				$cssClass = 'booked';
				$title = $this->replaceSprog('belegt');
			}
			// $bookingplan[$row][$col]['id'];
			else if ($e > 3) {
				if ($id > 0) {
					$cssClass = 'overlap';
				}
				else $cssClass = 'booked';
				$title = $this->replaceSprog('belegt');
			}

		}
		else {
			$cssClass = 'free';
			$title = $this->replaceSprog('frei');
		}



		$cellText = ($writeDay) ? $col : '';

		if ($id > -1) {
			$linkStart = $this->getEntryLinkStart($id);
			if ($id > 0) {
				$entry = $this->getSkedEntry($id);
				if ($entry) {
					$title = $entry->entry_name;
				}
				else $title = 'sked Entry';
			}
		}
		if ($cssClass=='overlap') {
			$cellText .= '!';
		}
		else if (!$writeDay){
			$cellText .= '&nbsp;';
		}
		else {
			// ??? consider whole date for screen readers in each cell
			$cellText  .= '<span class="sr-only"> ('.$title.')</span>';
		}


		// ! $title is used for sr-only/aria-hidden, real title only in backend
		return '<td class="'.$cssClass.'"'.
		($id > 0 ? ' title="'.$title.'"' : '').
		'>'.$cellTypeMarker.$linkStart.$cellText.($linkStart ? '</a>' : '').'</td>
		';
	}

	protected function generateEmptyTableCell() {
		// ! $title is used for sr-only/aria-hidden, real title only in backend
		return '<td class="invalid">&nbsp;</td>';
	}

	protected function generateBookingPlan() {
		// GENERATE booking plan matrix
		// [row][col][value|id]

		$displayYear = $this->displayYear;

		$bookingplan = array();

		for ($row = 1; $row <= 12; $row++) {
			for ($col = 1; $col <= 31; $col++) {
				$bookingplan[$row][$col]['value'] = 0;
				$bookingplan[$row][$col]['id'] = 0;
			}
		}

		// ! resets instance vars
		$this->bookingList = array();
		$this->overlappingList = array();
		$this->invalidList = array();

		$sliceCount=0;
		// $latestUpdateDate = 0;
		//
		// echo '<pre>';
		// print_r($skedEntries);
		// exit();

		foreach ($this->skedEntries as $entry) {


			$sliceCount++;

			$e = $entry['entry'];

			// print_r($e);
			// exit();

			$start = $e->entry_start_date->getTimeStamp(); // format("Y-n-j"); // month and day without leading zeros
			$end = $e->entry_end_date->getTimeStamp();

			// first check wrong dates and negative time span
			if (!$start || !$end || ($end < $start && (date("Y",$start) == $displayYear || date("Y",$end) == $displayYear))) {
				// writing the id as index prevents duplicate entries
				$this->invalidList[$e->entry_id] = $this->getEntryInfo($e);
			}
			else {

				$startYear = intval(date('Y',$start));
				$startMonth = intval(date('n',$start)); // month simple number
				$startDay = intval(date('j',$start)); // day simple number

				$endYear = intval(date('Y',$end));
				$endMonth = intval(date('n',$end));
				$endDay = intval(date('j',$end));

				// rewrite invalid data
				// ??? not needed anymore with Sked
				if (!$startYear) $startYear = $displayYear;
				if (!$startMonth) $startMonth = 1;
				if (!$startDay) $startDay = 1;
				if (!$endYear) $endYear = $displayYear;
				if (!$endMonth) $endMonth = 1;
				if (!$endDay) $endDay = 1;

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
								$this->addValueAndCheckOverlap($bookingplan, $this->overlappingList, $row,$col,$e,3);
								// $bookingplan[$row][$col]['value'] += 3;
								// $bookingplan[$row][$col]['id'] = $slice->getId();
							}
							// start day
							if ($row == $startMonth && $col == $startDay) {
								if ($e->entries_arrivaltype == '2') $wholeDay = 2; else $wholeDay = 0;
								$this->addValueAndCheckOverlap($bookingplan, $this->overlappingList, $row, $col, $e, 1 + $wholeDay);

								// $bookingplan[$row][$col]['value'] += 1 + $wholeDay;
								// $bookingplan[$row][$col]['id'] = $slice->getId();
							}
							// end day
							if ($row == $endMonth && $col == $endDay) {
								if ($e->entries_departuretype == '2') $wholeDay = 1; else $wholeDay = 0;
								$this->addValueAndCheckOverlap($bookingplan, $this->overlappingList, $row,$col,$e, 2 + $wholeDay);
								// $bookingplan[$row][$col]['value'] += 2 + $wholeDay;
								// $bookingplan[$row][$col]['id'] = $slice->getId();
							}
						}
					}

					// make list after 'for' over days because now we have found overlappings
					// set booking list entry
					$id = $e->entry_id;
					$this->bookingList[$id]['id'] = $id;
					$test = $start; // start timestamp
					$this->bookingList[$id]['starttime'] = $test;
					$this->bookingList[$id]['text'] = $this->getEntryInfo($e,$displayYear);

					// store latest updatedate found
					// ??? cannot work with sked
					// $t = intval($slice->getValue('updatedate'));
					// $latestUpdateDate = ($t > $latestUpdateDate) ? $t : $latestUpdateDate;
				}
			}
		} // foreach

		$this->bookingPlan = $bookingplan; // :-( heavy copying again
	}

	function getMonthName($monthOrTimeStamp,$shorten = true) {

		$m = intval($monthOrTimeStamp);
		if ($m <= 12) {
			$m = strtotime("$m/01");
		}
		$month = strftime("%B",$m);
		if ($shorten) {
			// don't use "%b" (can produce "Mrz"), because screen readers will read the whole word
			return mb_substr($month,0,3).'<span class="sr-only">'.mb_substr($month,3).'</span>';
		}
		return $month;
	}

	function getTable() {

		if (!count($this->bookingPlan)) return '';

		$out = '	<table class="bookingplan">';
		$out .= '		<tr>';
		$out .= '			<th class="col-header"><span class="sr-only">Monat</span></th>'; // proper accessible first col header!
		for ($col = 1; $col <= 31; $col++) {
			$out .= '			<th>'.$col.'</th>';
		}
		$out .= '		</tr>';

		// table generate loop
		for($row = 1; $row <= 12; $row++) {
			$out .= '		<tr>';

			// start of row is a td which is defined as row header!
			// ! new concept: full name if read by screen reader, short name for eyes:
			// ! title attr is always discouraged
			$out .= '			<td class="row-header">'
				// .mb_substr(strftime('%B',strtotime("2019-$row-01")),0,3)
				.$this->getMonthName($row)
				.'</td>';

			$daysInMonth = intval(date('t',strtotime($this->displayYear.'-'.($row).'-01')));

			for ($col = 1; $col <= 31; $col++) {

				// ??? all inside this for is needed twice
				// ??? put all inside generateTableCell
				// ??? plus you can cleanup checks because you don't need to write '$char' and yhen parse it again

				// handle shorter months and leapyear
				// ??? you could use `date('t',strtotime($displayYear.'-'.($row).'-1'))`
				// if (
				// 	($col == 31 && ($row == 2 || $row == 4 || $row == 6 || $row ==9 || $row == 11)) ||
				// 	($col == 30 && ($row == 2)) ||
				// 	($col == 29 && ($this->displayYear % 4) != 0  && $row == 2)
				// ) {
				// 	$char = '=';
				// }
				// else {
				// 	$e = $this->bookingPlan[$row][$col]['value'];
				//
				// 	if (rex::isBackend()) {
				// 		// $out .= getSliceLinkStart(rex_article_slice::getArticleSliceById($bookingplan[$row][$col]['id']))
				// 		// 	.$char.'</a>';
				// 		$id = $this->bookingPlan[$row][$col]['id'];
				// 	}
				// 	else $id = -1; // ! to distinguish between frontend and unset $id
				//
				// 	if ($e) {
				// 		$char = '';
				// 		if ($e == 1) $char = '/';
				// 		else if ($e == 2) $char = '\\';
				// 		else if ($e == 3) $char = 'X';
				// 			// $bookingplan[$row][$col]['id'];
				// 		else if ($e > 3) {
				// 			$char = '!';
				// 			// TODO: already when writing into matrix, so we can save old and new overlapping entries
				// 			// writing the id as index prevents duplicate entries
				// 			// $slice = rex_article_slice::getArticleSliceById($bookingplan[$row][$col]['id']);
				// 			// if ($slice) {
				// 			// 	$o = $slice->getValue(1) . ': <b>'.$slice->getValue(3).' - '.$slice->getValue(5). '</b>';
				// 			// }
				// 			// else $o = "slice $bookingplan[$row][$col]['id'] error";
				// 			// if (!isset($overlappingList[$bookingplan[$row][$col]['id']])) $overlappingList[$bookingplan[$row][$col]['id']] = $o;
				// 		}
				// 	}
				// 	else $char = '&middot;';
				// }
				if ($col <= $daysInMonth) $out .= $this->generateTableCell($row,$col); // change usage to rex::isBackend as param if slice interna needed in Frontend
				else $out .= $this->generateEmptyTableCell();
			}
			$out .= "		</tr>\n";
		}

		$out .= '	</table>';

		return $out;
	}

	/** return a HTML table for the month passed
	*	- init ($this->generateBookingPlan();) mus be done
	*	@param int $month starts at 1, can be a string which resolves to a valid integer
	*/
	function getMonthTable($month,$year = false) {
		$out = '';

		if (count($this->bookingPlan)) {
			// in case we get weird strings
			if (intval($month)) {
				// TODO: class in SASS
				$out .= '<table class="bookingplan--month">';
				$out .= '<caption>'.$this->getMonthName($month,false).($year ? ' '.$this->displayYear : '').'</caption>';
				$out .= '<tr>';

				for($i = 0; $i < 7; $i++) {
					$out .=
					'<th>'
					.strftime('%a',strtotime('01/'.$i)). ' '
					.'</th>';
				}

				$out .= '</tr>';

				// needed at least 2 times
				$monthStartTime = strtotime($this->displayYear.'-'.$month.'-01');
				// will count with position in table hence starting with index of 1st of month in 1st week
				$startWeekDay = date('N',$monthStartTime) - 1;
				$daysInMonth = date('t',$monthStartTime);
				// $row, $col in $this->bookingPlan
				$row = intval($month); // is exact because $row counts from 1 in bookingPlan
				$col = 1;

				// -assume always 6 rows, but break when month finshed (easier than calculate and have if statements in advance)
				for ($week = 0; $week < 6; $week++) {
					$out .= '<tr>';

					// bloedsinnn, need the td anyways
					for($weekDay = 0; $weekDay < 7; $weekDay++) {
						if (($week > 0 || $weekDay >= $startWeekDay) && $col <= $daysInMonth ) {
							$out .= $this->generateTableCell($row,$col, true); // true: display number

							$col ++;
						}
						else {
							$out .= $this->generateEmptyTableCell();
						}
					}

					$out .= '</tr>';
				}

				$out .= '	</table>';
			}

		}
		return $out;
	}

	function getList() {
		// first sort entries
		// - each entry of $bookingList has a sub field 'starttime' as timestamp (numeric)
		// ??? check if still needed
		// usort($bookingList,'kwd_bookingplan_sked::sort_startzeit');
		return $this->bookingList;
	}

	function getOverlappingList() {
		return $this->overlappingList;
	}

	function getInvalidList() {
		return $this->invalidList;
	}

	function getYear() {
		return $this->displayYear;
	}
}
