<?php
// function kwd_testfunc($text) {
// 	if (rex::getUser()->hasPerm('admin[]')) {
// 		echo 'TET: '.$text;
// 	}
// }

// TODO: make class for private static properties...

function kwd_getMonthName($monthOrTimeStamp) {

	$m = intval($monthOrTimeStamp);
	if ($m > 12) {
		// try to read timestamp
		$m = intval(date('n',$m));
	}
	// the switch has the advantage to not have declared the whole array in advance
	// + no additional string operation for splitting
	switch ($m) {
		case 1 : $s = 'Jan'; $e = 'uar'; break;
		case 2 : $s = 'Feb'; $e = 'ruar'; break;
		case 3 : $s = 'Mär'; $e = 'z'; break;
		case 4 : $s = 'Apr'; $e = 'il'; break;
		case 5 : $s = 'Mai'; $e = ''; break;
		case 6 : $s = 'Jun'; $e = 'i'; break;
		case 7 : $s = 'Jul'; $e = 'i'; break;
		case 8 : $s = 'Aug'; $e = 'ust'; break;
		case 9 : $s = 'Sep'; $e = 'tember'; break;
		case 10 : $s = 'Okt'; $e = 'ober'; break;
		case 11 : $s = 'Nov'; $e = 'ember'; break;
		case 12 : $s = 'Dez'; $e = 'ember'; break;
		default : $s = ''; $e = ''; break;
	}
// <span aria-hidden="true">'.mb_substr($monthsNames[$row],0,3).'</span><span class="sr-only">'.$monthsNames[$row].'</span>
	return $s.'<span class="sr-only">'.$e.'</span>';
}
?>
