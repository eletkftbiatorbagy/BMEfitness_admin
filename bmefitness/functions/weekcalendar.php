<?php
	// static mezok
	require_once("database.php");
	$MINHOUR = 0;
	$MAXHOUR = 23;
	$TDWIDTH = 120;
	$TDHEIGHT = 30;
	$MINHEIGHTFOREDZO = 70;
	$SHOWNAPTARINFO = true;

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
		$where = "naptar.aktiv AND NOT naptar.torolve";
		$where .= " AND naptar.ig > cast('".$firstday."' AS timestamp) AND naptar.tol < cast('".$lastday."' AS timestamp) AND naptar.ora = orak.id AND naptar.edzo = edzok.id";

		$naptarak = db_select_data("fitness.naptar, fitness.orak, fitness.edzok", "*, naptar.id AS naptar_id, orak.nev AS ora_nev, orak.id AS ora_id, edzok.id AS edzo_id, edzok.rovid_nev AS edzo_rovid_nev", $where, "");

//		print $naptarak; return;

		// szerintem az osszes datum szoveget atkonvertalom rendes datumra
		for ($i = 0; $i < count($naptarak); $i++) {
//			print "ora neve: ".$naptarak[$i]->ora_nev.", ora color = ".$naptarak[$i]->color."<br>";
			$naptarak[$i]->tol = strtotime($naptarak[$i]->tol);
			$naptarak[$i]->ig = strtotime($naptarak[$i]->ig);
			$naptarak[$i]->torolve_mikor = strtotime($naptarak[$i]->torolve_mikor);
			$naptarak[$i]->visszaigazolva = strtotime($naptarak[$i]->visszaigazolva);
			$naptarak[$i]->letrehozva = strtotime($naptarak[$i]->letrehozva);
		}

		//!! Figyelem: direkt van kulon ez a resz es nem egyben az elozovel, mert az elozoben alakitom at integerre szovg helyett a datumokat.
		for ($i = 0; $i < count($naptarak); $i++) {
			// itt pedig megkeressuk, hogy hany naptarbejegyzes talalhato meg atfedesben hozza kepest
			$naptarak[$i]->maxatfedes = 1; // hany darab naptar atfedesunk van..., azert kezdodik 1-rol, mert az egy azt jelenti, hogy csak az az egy bejegzesunk van... kesobb nem kell hozzaadni egyet...
			$naptarak[$i]->hanyadik = 0; // ha a masik naptar tol-ja elorebb van, akkor ennek bentebb kell lenni, es hogy mennyivel bentebb...
			for ($j = 0; $j < count($naptarak); $j++) {
				if ($j == $i) // nyilvan sajat magat nem ellenorizzuk...
					continue;

				// eleg csak a tol vagy ig idopontokat megnezni, hogy az adott naptar tolig kozott van-e, es ha valamelyik kozotte van, akkor atfedes van
				if ((date("Y-m-d H:i", $naptarak[$i]->tol) <= date("Y-m-d H:i", $naptarak[$j]->tol) && date("Y-m-d H:i", $naptarak[$i]->ig) >= date("Y-m-d H:i", $naptarak[$j]->tol)) ||
					(date("Y-m-d H:i", $naptarak[$i]->tol) <= date("Y-m-d H:i", $naptarak[$j]->ig) && date("Y-m-d H:i", $naptarak[$i]->ig) >= date("Y-m-d H:i", $naptarak[$j]->ig)) ||
					// vagy ha a masik tolja az kisebb, egyenlo, mint a mi tolunk, de ugyanakkor nagyobb vagy egyenlo a masik igje :)
					(date("Y-m-d H:i", $naptarak[$j]->tol) <= date("Y-m-d H:i", $naptarak[$i]->tol) && date("Y-m-d H:i", $naptarak[$j]->ig) >= date("Y-m-d H:i", $naptarak[$i]->tol))) {
					$naptarak[$i]->maxatfedes++;


					// tehat ha kisebb a masik tolja, vagy ha egyenlo es a mi igunk kisebb, akkor kerul bentebb
					if ((date("Y-m-d H:i", $naptarak[$j]->tol) < date("Y-m-d H:i", $naptarak[$i]->tol)) || (date("Y-m-d H:i", $naptarak[$j]->tol) == date("Y-m-d H:i", $naptarak[$i]->tol) && date("Y-m-d H:i", $naptarak[$j]->ig) > date("Y-m-d H:i", $naptarak[$i]->ig))) {
						$naptarak[$i]->hanyadik++;
					}
				}
			}
		}

//		foreach (orakAtHourOfDayInNaptarak($naptarak, '2014', '05', '09', '17') as $adat) {
//			print "bejegyzes id: ".$adat->bejegyzes->id.", tol: ".date("Y-m-d H:i", $adat->bejegyzes->tol).", ig: ".date("Y-m-d H:i", $adat->bejegyzes->ig).", min: ".$adat->min.", max: ".$adat->max."<br>\n";
//		}

//		return;

		$minhour = $GLOBALS['MINHOUR'];
		$maxhour = $GLOBALS['MAXHOUR'];
		$tdwidth = $GLOBALS['TDWIDTH'];
		$tdheight = $GLOBALS['TDHEIGHT'];
		$minheightforedzo = $GLOBALS['MINHEIGHTFOREDZO'];
		$shownaptarinfo = $GLOBALS['SHOWNAPTARINFO'];

		print "<table class=\"calendartable\">\n";
		// azert minusz egy, mert az elso oszlop az orak kiiratasa
		for ($hours = $minhour - 1; $hours <= $maxhour; $hours++) {
			print "<tr style=\"height: ".$tdheight."px;\">\n";
			// azert minusz egytol kezdodik, mert a -1 sor a datumot tartalmazza
			for ($day = -1; $day < count($weekdays); $day++) {
				$tdstyle = "<td style=\"";
				$tdcontent = "";
				// ekkor a fejlecet iratjuk ki (datumokat)
				if ($day == -1 && $hours == $minhour - 1) {
					$tdstyle .= " padding: 5px;";
					$tdcontent = "óra";
				}
				else if ($day == -1) {
					$tdstyle .= " padding: 5px;";
					$tdcontent = $hours;
				}
				else if ($hours == $minhour - 1) {
					$tdstyle .= "width: ".$tdwidth."px; padding: 5px 0px 5px 0px;";
					$tdcontent = date("Y", $weekdays[$day])." ".shortMonthName(date("n", $weekdays[$day]))." ".date("j", $weekdays[$day]).".<br>".dayName($day + 1)."\n";
				}
				// ekkor mar az orakat
				else {
					$tdcontent = "<div style=\"position: relative; width: ".$tdwidth."px; height: ".$tdheight."px;\">";
					foreach (orakAtHourOfDayInNaptarak($naptarak, date("Y", $weekdays[$day]), date("m", $weekdays[$day]), date("d", $weekdays[$day]), $hours) as $adat) {
						// szin kiszedese
						$bcolor = "#FFFFFF"; // feher lesz, ha nincs szine...
						if ($adat->bejegyzes->color != "")
							$bcolor = "#".$adat->bejegyzes->color;

						// atalakitjuk, hogy osszefuggo legyen az egesz...
						$amax = $adat->max == 59 ? 60 : $adat->max; // hogy teljesen ki legyen toltve...
						$minm = round($tdheight / 60 * $adat->min); // top...
						$maxm = round($tdheight / 60 * ($amax - $adat->min)); // height

						// ha min 0, akkor el kell felul tuntetni a bordert
						if ($adat->min == 0)
							$tdstyle .= " border-top-color: ".$bcolor.";";
						// ha max 60, akkor meg alulrol tuntetjuk el a bordert
						if ($amax == 60)
							$tdstyle .= "border-bottom-color: ".$bcolor.";";

						$width = $tdwidth / $adat->bejegyzes->maxatfedes;
						$widthsz = " width: ".$width."px;";
						$leftsz = " left: ".($adat->bejegyzes->hanyadik * $width)."px;";

						// kiszamitjuj a magassagot, mert ez kell a naptar info kiiratasahoz is
						$topsz = " top: ".$minm."px;";

						$tdcontent .= "<div style=\"position: absolute;".$widthsz.$leftsz.$topsz.($amax >= 60 && $adat->min == 0 ? " height: 100%;" : " height: ".$maxm."px;")." background-color: ".$bcolor.";\"></div>";

						// kikapcsoljuk a naptarinfot, ha nem egyeduli bejegyzes
						if ($adat->bejegyzes->maxatfedes > 1)
							$shownaptarinfo = false;

						if ($shownaptarinfo) {
							// kiiratjuk a naptar nevet es egyeb infojat, ha ez a kezdodatum.
							$mindate = date("Y", $weekdays[$day])."-".date("m", $weekdays[$day])."-".date("d", $weekdays[$day])." ".$hours.":".($adat->min < 10 ? "0".$adat->min : $adat->min);

							// ha az elso napon az elso oraban vagyunk es az esemenynek korabbi a kezdo idopontja, akkor megjelenitjuk a neve elott egy '<-' szoveget is.
							if ($hours == $minhour && $day == 0 && date("Y-m-d H:i", $adat->bejegyzes->tol) < date("Y-m-d H:i", strtotime($mindate))) {
								$tdcontent .= "<div style=\"position: relative; z-index: 1;".$topsz."\"><- ".$adat->bejegyzes->ora_nev;
								// ha nagyobb vagy egyenlo, mint 70 perc, akkor megjelenitjuk az edzo rovid nevet is, mert egyebkent nem fer ki...
								if (($adat->bejegyzes->ig - strtotime(date("Y", $weekdays[$day])."-".date("m", $weekdays[$day])."-".date("d", $weekdays[$day])." ".$hours.":00") / 60) >= $minheightforedzo)
									$tdcontent .= "<br>".$adat->bejegyzes->edzo_rovid_nev;
								$tdcontent .= "</div>";
							}
							// ha az utolso napon az utolso oraban vagyunk es az esemenynek kesobbi a zaro idopontja, akkor megjelenitjuk '->' szoveget. // nevre itt nincs szukseg, mert elotte szerepel
							else if ($hours == $maxhour && $day == count($weekdays) - 1 && date("Y-m-d H:i", $adat->bejegyzes->ig) > date("Y-m-d H:i", strtotime(date("Y", $weekdays[$day])."-".date("m", $weekdays[$day])."-".date("d", $weekdays[$day])." ".$hours.":59"))) {
								$tdcontent .= "<div style=\"position: relative; z-index: 1;".$topsz."\">-></div>";
							}
							// egyebkent csak akkor iratjuk ki az ora adatait, ha ez a kezdodatum
							else if (date("Y-m-d H:i", $adat->bejegyzes->tol) == date("Y-m-d H:i", strtotime($mindate))) {
								$tdcontent .= "<div style=\"position: relative; z-index: 1;".$topsz."\">".$adat->bejegyzes->ora_nev;
								// ha nagyobb vagy egyenlo, mint 70 perc, akkor megjelenitjuk az edzo rovid nevet is, mert egyebkent nem fer ki...
								if ((($adat->bejegyzes->ig - $adat->bejegyzes->tol) / 60) >= $minheightforedzo)
									$tdcontent .= "<br>".$adat->bejegyzes->edzo_rovid_nev;
								$tdcontent .= "</div>";
							}
						}
					}
					$tdcontent .= "</div>";
				}

				$tdstyle .= "\">";
				print $tdstyle.$tdcontent."</td>\n";
			}
			print "</tr>\n";
		}
		print "</table>\n";
	}

?>
