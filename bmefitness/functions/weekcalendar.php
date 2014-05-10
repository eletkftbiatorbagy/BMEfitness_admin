<?php
	// static mezok
	require_once("database.php");
	$MINHOUR = 0;
	$MAXHOUR = 23;
	$TDWIDTH = 100;
	$TDHEIGHT = 30;

	/*! Egy 7 tagú tömb lesz a végeredmény, így könnyen fogjuk tudni az adott nap adatait (például hétfő az a 0. elem)
	 */
	function weekdays($thedate) {
		$days = array();
		$dayofweek = date("N", $thedate);
		for ($i = 1 - $dayofweek; $i < 8 - $dayofweek; $i++)
			$days[] = $thedate + ((24 * $i) * 60 * 60);

		return $days;
	}

	/*!	Hány héttel a mai dátumhoz képest szeretnénk az időt. Ha 0, akkor a mai dátum lesz az eredmény.
	 */
	function dateForNextWeek($week) {
		$thedate = time() + ((7 * $week) * 24 * 60 * 60);
		return $thedate;
	}

	/*!	Természetesen annyi, hogy 1-12 kell megadni a hónap számát, és azért csökkentem egyel a visszatérésnél...
	 */
	function shortMonthName($month) {
		$names = array("jan.", "feb.", "már.", "ápr.", "máj.", "jún.", "júl.", "aug.", "szep.", "okt.", "nov.", "dec.");
		return $names[$month - 1];
	}

	/*!	Természetesen annyi, hogy 1-7 kell megadni a het nap szamat, és azért csökkentem egyel a visszatérésnél...
	 */
	function dayName($day) {
		$names = array("hétfő", "kedd", "szerda", "csütörtök", "péntek", "szombat", "vasárnap");
		return $names[$day - 1];
	}

	/*!	Ez egy tombot fog kidobni, hogy hany kulonbozo ora adatai vannak a megadott oraban.
	 *	Pontosabban visszakuldi az ora azonositojat, es ahhoz az orahoz tartozo kezdesi es befejezesi percet fogja tartalmazni.
	 *	Mivel csak 1 hetet vizsgalunk, igy eleg az aznapi datum, marmint a nap, es az ora, amire kivancsiak vagyunk
	 *	Egy tomb eleme:
	 *		- adat->bejegyzes, azaz konkretan az adott naptar bejegyzes az adatbazisban
	 *		- adat->min, azaz az adott oraban mennyi a minimum oraja. Ez 0-59 kozott van.
	 *		- adat->max, azaz az adott oraban mennyi a maximuma oraja. Ez 0-59 kozott van.
	 */
	function orakAtHourOfDayInNaptarak($naptarak, $year, $month, $day, $hour) {
		$eredmeny = array();
		$adate = $year."-".$month."-".$day." ".$hour;
//		print "adate: ".$adate.":00<br>";

		foreach ($naptarak as $bejegyzes) {
//			print "bejegyzes<br>";
			$tol = date("Y-m-d H:i", $bejegyzes->tol);
			$ig = date("Y-m-d H:i", $bejegyzes->ig);
//			print "tol, ig: ".$tol." - ".$ig."<br>";

//			$tol = strtotime($tol);
//			$ig = strtotime($ig);
//			if ($tol <= date("Y-m-d H:i", strtotime($adate.":00")))
//				print "bejegyzes id: ".$bejegyzes->id." toltol nagyobb<br>";

//			if ($ig > date("Y-m-d H:i", strtotime($adate.":00")))
//				print "bejegyzes id: ".$bejegyzes->id." igtol kisebb<br>";

			if (($tol <= date("Y-m-d H:i", strtotime($adate.":00")) || $tol <= date("Y-m-d H:i", strtotime($adate.":59"))) &&
				($ig > date("Y-m-d H:i", strtotime($adate.":59")) || $ig > date("Y-m-d H:i", strtotime($adate.":00")))) {
//				print "van<br>";
				//date("j", $bejegyzes->ig) <= $day) {
				// ekkor biztosan aznap is van...
				// megkeressuk a minimumot
				$min = 0;
				for ($i = 0; $i < 60; $i++) {
					if ($tol <= date("Y-m-d H:i", strtotime($adate.($i < 10 ? ":0".$i : ":".$i)))) {
						$min = $i;
						break;
					}
				}

				// megkeressuk a maximumot
				$max = 0;
				for ($i = 59; $i >= 0; $i--) {
					if ($ig >= date("Y-m-d H:i", strtotime($adate.($i < 10 ? ":0".$i : ":".$i)))) {
						$max = $i;
						break;
					}
				}

				$adat = new stdClass;
				$adat->bejegyzes = $bejegyzes;
				$adat->min = $min;
				$adat->max = $max;

				$eredmeny[] = $adat;
			}
		}

		return $eredmeny;
	}

	/*!	Órák lefoglalásának időpontjai.
	 */
	function printOrakTable($weekplusz = 0) {
		$thedate = dateForNextWeek($weekplusz);
		$weekdays = weekdays($thedate);

		// SELECT * FROM fitness.naptar WHERE tol > cast('2014-05-09' AS date) AND ig <= cast('2014-05-10' AS date);
		// megkeressuk az elso es utols utani napot // H:i:s
		$firstday = date("Y-m-d", $weekdays[0]);
		$firstday .= " 00:00:00";
		$lastday = date("Y-m-d", $weekdays[count($weekdays) - 1]);
		$lastday .= " 23:59:59";

		// forditva kell lekerdezni, tehat az ig, a befejezodesnek nagyobbnak kell lennie, mint az elso nap reggele, es a tol, azaz el kell kezdodnie a het utolso perce elott...
		$where = "ig > cast('".$firstday."' AS timestamp) AND tol < cast('".$lastday."' AS timestamp)";

		$naptarak = db_select_all_data("fitness.naptar", $where);// "firstday: ".$firstday."<br>lastday: ".$lastday."<br>where: ".$where;

//		print $naptarak; return;

		// szerintem az osszes datum szoveget atkonvertalom rendes datumra
		for ($i = 0; $i < count($naptarak); $i++) {
			$naptarak[$i]->tol = strtotime($naptarak[$i]->tol);
			$naptarak[$i]->ig = strtotime($naptarak[$i]->ig);
			$naptarak[$i]->torolve_mikor = strtotime($naptarak[$i]->torolve_mikor);
			$naptarak[$i]->visszaigazolva = strtotime($naptarak[$i]->visszaigazolva);
			$naptarak[$i]->letrehozva = strtotime($naptarak[$i]->letrehozva);
		}

//		foreach (orakAtHourOfDayInNaptarak($naptarak, '2014', '05', '09', '17') as $adat) {
//			print "bejegyzes id: ".$adat->bejegyzes->id.", tol: ".date("Y-m-d H:i", $adat->bejegyzes->tol).", ig: ".date("Y-m-d H:i", $adat->bejegyzes->ig).", min: ".$adat->min.", max: ".$adat->max."<br>\n";
//		}

//		return;

		$minhour = $GLOBALS['MINHOUR'];
		$maxhour = $GLOBALS['MAXHOUR'];
		$tdwidth = $GLOBALS['TDWIDTH'];
		$tdheight = $GLOBALS['TDHEIGHT'];

/*
		.bg {
		position: absolute;
		top: 0;
		bottom: 0;
		width: 50%;
		}
		.content {
		position: relative;
			z-index: 1;
		}
		.yellow {
		left: 0;
			background-color: yellow;
		}
		.green {
		right: 0;
			background-color: green;
		}
 */


		print "<table class=\"calendartable\">\n";
		for ($hours = $minhour - 1; $hours <= $maxhour; $hours++) {
			print "<tr style=\"height: ".$tdheight."px;\">\n";
			for ($weeks = -1; $weeks < count($weekdays); $weeks++) {
				$tdstyle = "<td style=\"";
				$tdcontent = "";
				// ekkor a fejlecet iratjuk ki (datumokat)
				if ($weeks == -1 && $hours == $minhour - 1) {
					$tdstyle .= " padding: 5px;";
					$tdcontent = "óra";
				}
				else if ($weeks == -1) {
					$tdstyle .= " padding: 5px;";
					$tdcontent = $hours;
				}
				else if ($hours == $minhour - 1) {
					$tdstyle .= "width: ".$tdwidth."px; padding: 5px;";
					$tdcontent = date("Y", $weekdays[$weeks])." ".shortMonthName(date("n", $weekdays[$weeks]))." ".date("j", $weekdays[$weeks]).".<br>".dayName($weeks + 1)."\n";
				}
				// ekkor mar az orakat
				else {

					$tdcontent = "<div style=\"position: relative; width: 100%; height: ".$tdheight."px;\">";
					foreach (orakAtHourOfDayInNaptarak($naptarak, date("Y", $weekdays[$weeks]), date("m", $weekdays[$weeks]), date("d", $weekdays[$weeks]), $hours) as $adat) {
						$bcolor = "red";
						$amax = $adat->max == 59 ? 60 : $adat->max; // hogy teljesen ki legyen toltve...
						$minm = $adat->min / 60 * 100;
						$maxm = ($amax - $adat->min) / 60 * 100;
						if ($adat->min == 0)
							$tdstyle .= " border-top-color: ".$bcolor.";";
						if ($amax == 60)
							$tdstyle .= "border-bottom-color: ".$bcolor.";";
						$tdcontent .= "<div style=\"position: absolute; width: 100%;".($adat->min == 0 ? "" : " top: ".$minm."%;").($amax >= 60 ? " height: 100%;" : " height: ".$maxm."%;")." background-color: ".$bcolor.";\"></div>";
					}
					$tdcontent .= "</div>";
				}

				$tdstyle .= "\">";
				print $tdstyle.$tdcontent."</td>\n";
			}
			print "</tr>\n";
		}
		print "</table>\n";
		/*
		for ($i = 0; $i < count($weekdays); $i++) {
			print "weekdate[".($i + 1)."]".date("Y", $weekdays[$i])." ".shortMonthName(date("n", $weekdays[$i]))." ".date("j", $weekdays[$i])."<br>";
		}

		$weekofyear = date("W", $thedate);
		$year = date("Y", $thedate);
		$month = date("m", $thedate);

		$dayofmonth = date("j", $thedate);
		$dayofweek = date("N", $thedate);
		$numberofdaysinmonth = date("t", $thedate);

		print "weekplusz: ".$weekplusz."<br>";
		print "weekofyear: ".$weekofyear."<br>";
		print "year: ".$year."<br>";
		print "month: ".$month."<br>";
		print "dayofmonth: ".$dayofmonth."<br>";
		print "dayofweek: ".$dayofweek."<br>";
		print "dd: ".($dayofmonth + $dayofweek)."<br>";
		 */
	}

?>
