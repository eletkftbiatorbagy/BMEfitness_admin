
<?php
	require_once("../functions/database.php");
	require_once("../functions/functions.php");

	$tablename = "orak"; // ezt kesobb is felhasznalom, azert van itt...
	$table = "fitness.".$tablename;

	print "<div id=\"leftcontent\">";
	$result = db_select_data($table, "*", "", $tablename.".sorszam");
	$object = NULL;
	
	$selectedObject = NULL;
	if (isset($_POST["selectedObject"])) {
		$selectedObject = $_POST["selectedObject"];
		if ($selectedObject === "")
			$selectedObject = 0;
	}

	if (!is_null($result)) {
		print "<div class=\"scrollcontent\">";
		if (count($result) == 0) {
			print "<div style=\"color: red;\">Nincs adat hozzáadva</div><br>";
		}
		else {
			for ($i = 0; $i < count($result); $i++) {
				// a js mar egybol tudja, hogy ez egy object, szoval ott nem kell atalakitani...
				$upsorszam = $result[$i]->sorszam - 1; // felfele mozgatjuk
				$downsorszam = $result[$i]->sorszam + 1; // lefele mozgatjuk

				$isSelected = false;
				if (is_null($selectedObject) || $result[$i]->id == $selectedObject || ($selectedObject == 0 && $i == 0)) {
					$object = $result[$i];
					$isSelected = true;
				}

				print "<div class=\"edit_data_available".($isSelected ? " selected_data" : "")."\" onclick='change_edit_data_site(\"".$tablename."\", ".$result[$i]->id.");'><b>".$result[$i]->nev."</b>";
				if ($i > 0) // csak akkor van velfele nyil, ha van is felette valami...
					print "<div onclick='changeSorszam(-1, \"".$table."\", \"".$result[$i]->id."\", \"".$upsorszam."\"); window.event.stopPropagation();' style=\"float: right;\"><img src=\"code/images/icon_accordion_arrow_up.png\"></div>";
				print "<br>";
				print "<span style=\"font-size: smaller;\"><i>".$result[$i]->alcim."</i></span>";
				if ($i < count($result) - 1) // csak akkor jelenitjuk meg, ha van is alatta valami
					print "<div onclick='changeSorszam(1, \"".$table."\", \"".$result[$i]->id."\", \"".$downsorszam."\"); window.event.stopPropagation();' style=\"float: right;\"><img src=\"code/images/icon_accordion_arrow_down.png\"></div>";
				print "</div>";
			}
		}
		print "</div>";
		print "<div onclick=\"begin_new_or_edit_data('".$tablename."');\" class=\"action_button\">Új adat hozzáadása</div>";
	}
	print "</div>";

	if ($object) {
		// orahoz rendelt edzok szama
		$query = "SELECT count(*) FROM fitness.foglalkozas WHERE ora=".$object->id.";";
		$edzokoraicount = db_query_object_array($query);
		$acount = 0;
		if (is_array($edzokoraicount) && count($edzokoraicount) > 0)
			$acount = $edzokoraicount[0]->count;

		// orahoz rendelt termek szama
		$query = "SELECT count(*) FROM fitness.oraterme WHERE ora=".$object->id.";";
		$teremoraicount = db_query_object_array($query);
		$atcount = 0;
		if (is_array($teremoraicount) && count($teremoraicount) > 0) {
			$atcount = $teremoraicount[0]->count;
		}

		// megjelenes kovetkezik...
		print "<div id=\"rightcontent\">";
			print "<div onclick='begin_new_or_edit_data(\"".$tablename."\", ".$object->id.");' class=\"action_button\">Szerkesztés</div>";
			print "<p>";
				print "<table>";
					print "<tr><td><b>Név:</b></td><td>".$object->nev."</td></tr>";
					print "<tr><td><b>Rövid név:</b></td><td>".$object->rovid_nev."</td></tr>";
					print "<tr><td><b>Alcím:</b></td><td>".$object->alcim."</td></tr>";
					print "<tr><td><b>Leírás:</b></td><td>".$object->leiras."</td></tr>";
					print "<tr><td><b>Perc:</b></td><td>".$object->perc."</td></tr>";
					print "<tr><td><b>Belépődíj:</b></td><td>".($object->belepodij == "t" ? "Van" : "Nincs")."</td></tr>";
					print "<tr><td><b>Max létszám:</b></td><td>".$object->max_letszam."</td></tr>";
					print "<tr><td><b>Edzők:</b></td><td>".$acount." db</td></tr>";
					print "<tr><td><b>Termek:</b></td><td>".$atcount." db</td></tr>";
					print "<tr><td><b>Fotó:</b></td><td>".($object->foto == "" ? "" : "<img style=\"max-height: 150px; max-width: 300px\" src=\"data/data_orak/".$object->foto.".jpg\">")."</td></tr>";
					print "<tr><td><b>Logó:</b></td><td>".($object->logo == "" ? "" : "<img style=\"max-height: 96px; max-width: 96px\" src=\"data/data_orak/".$object->logo.".jpg\">")."</td></tr>";
				print "</table>";
			print "</p>";
			print "<div onclick='begin_new_or_edit_data(\"".$tablename."\", ".$object->id.");' class=\"action_button\">Szerkesztés</div>";
		print "</div>";
	}
	else {
		print "<div id=\"rightcontent\"><br><div style=\"color: red; padding: 5px; border-color: black; border-width: 1px; border-style: solid;\">Nincs adat kiválasztva</div></div>";
	}

?>