<?
  /*
    idee: output des plans bei erstem Eintrag im Artikel (wie bei Galerie id-zähler)
    Hinweise im Backend
  */
  if (rex::isBackend()) {

	  // TODO: Gibt es mform funktionen fuer Output????
	  // TODO: Anzeige kompakt in 2 Zeilen

	  // TODO: the toArray stuff can be omitted if the rex value number is typed as normal int in INPUT
	  $value1 = "<b>REX_VALUE[1]</b>";

	  // echo "REX_VALUE[1]";
	  echo $value1.' ';

	  switch (rex_var::toArray("REX_VALUE[2]")[0]) {
		  case 2 : echo ' <b>(angefragt)</b>'; break;
		  case 3 : echo ' <b><i>(Zeitraum gesperrt!)</i></b>'; break;
		  // default :  echo ' gebucht'; break;
	  }
	  echo '<br />';

	  echo 'Beginn: ';
	  $startdate = strtotime(trim('REX_VALUE[3]'));
	  if (!$startdate) echo 'Fehler in Datum';
	  else {
		  // echo $startdate;
		  echo date("d. m. Y",$startdate);
	  }
	  echo '<br />';

	  echo 'Anreise:';
	  switch ("REX_VALUE[4]") {
		  case "2" : echo ' ganzen Tag belegen'; break;
		  default :  echo 'nachmittags'; break;
	  }
	  echo '<br />';

	  echo 'Ende: ';
	  $enddate = strtotime("REX_VALUE[5]");
	  if (!$enddate) echo 'Fehler in Datum';
	  else {
		  // echo $enddate;
		  echo date("d. m. Y", $enddate);
		  if ($enddate < $startdate) {
			  echo ' / Ende vor Start. Bitte korrigieren!';
		  }
	  }
	  echo '<br />';

	  echo 'Abreise:';
	  switch ("REX_VALUE[6]") {
		  case "2" : echo ' ganzen Tag belegen'; break;
		  default :  echo 'vormittags'; break;
	  }
	  echo '<br />';

	  // checks the type of module (to prevent checking module_id)
	  echo rex_var::toArray("REX_VALUE[7]")[0];

    // $article = rex_article::get("REX_LINK[id=1 output=id]");
    // if ($article) $articletitle = $article->getValue('name');
    // print '
    // <b>'.ucfirst("REX_VALUE[1]").'</b>
    // REX_VALUE[3] - REX_VALUE[4]:
    // '.$articletitle.'
    // (REX_VALUE[5])
    // '.strtoupper("REX_VALUE[6]").'
    // ';
  }
?>
