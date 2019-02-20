<?php


function kwd_getMonthName($monthOrTimeStamp,$shorten = true) {

	$m = intval($monthOrTimeStamp);
	if ($m <= 12) {
		// try to read timestamp
		$m = strtotime("2019-$m-01");
	}
	$month = strftime("%B",$m);
	if ($shorten) {
		return mb_substr($month,0,3).'<span class="sr-only">'.mb_substr($month,3).'</span>';
	}
	return $month;
}
