
<?php
	require_once("../functions/database.php");
	require_once("../functions/functions.php");

	$tablename = "edzok"; // ezt kesobb is felhasznalom, azert van itt...
	$table = "fitness.".$tablename;

	print "<div style=\"border-width: 2px; border-color: #333334; border-style: solid;\"><h1 style=\"color: #489d1e;\">Edzők</h1></div>";
	print "<div id=\"leftcontent\">";
	$result = db_select_data($table, "*", "", $tablename.".sorszam");

	if (!is_null($result)) {
		print "Korábban létrehozott adatok:<br>";
		print "<div class=\"scrollcontent\">";
		if (count($result) == 0) {
			print "<div style=\"color: red;\">Nincs adat hozzáadva</div><br>";
		}
		else {
			for ($i = 0; $i < count($result); $i++) {
				$jsonobject = json_from_object($result[$i]);
				// a js mar egybol tudja, hogy ez egy object, szoval ott nem kell atalakitani...
				$upsorszam = $result[$i]->sorszam - 1; // felfele mozgatjuk
				$downsorszam = $result[$i]->sorszam + 1; // lefele mozgatjuk

				if (!is_null($jsonobject)) {
					print "<div class=\"edit_data_available\" onclick='change_edit_data_site(\"".$tablename."\", ".$jsonobject.");'><b>".$result[$i]->vnev." ".$result[$i]->knev."</b>";
					if ($i > 0) // csak akkor van velfele nyil, ha van is felette valami...
						print "<div onclick='changeSorszam(\"".$table."\", \"".$result[$i]->id."\", \"".$upsorszam."\"); window.event.stopPropagation();' style=\"float: right;\"><img src=\"code/images/icon_accordion_arrow_up.png\"></div>";
					print "<br>";
					print "<span style=\"font-size: smaller;\"><i>".$result[$i]->rovid_nev."</i></span>";
					if ($i < count($result) - 1) // csak akkor jelenitjuk meg, ha van is alatta valami
						print "<div onclick='changeSorszam(\"".$table."\", \"".$result[$i]->id."\", \"".$downsorszam."\"); window.event.stopPropagation();' style=\"float: right;\"><img src=\"code/images/icon_accordion_arrow_down.png\"></div>";
					print "</div>";
				}
			}
		}
		print "</div>";
		print "<div onclick=\"begin_new_or_edit_data('".$tablename."');\" class=\"action_button\">Új adat hozzáadása</div>";
	}
	print "</div>";

	if (isset($_GET["selectedObject"]) && $_GET["selectedObject"] != "") {
		// visszaalakitjuk, hogy tudjuk hasznalni...
		$jsonobject = $_GET["selectedObject"];
		$object = object_from_array($jsonobject);

		// megint at kell alakitani, hogy jo legyen a new_or_edit_data_forms.php-ban...
		$ojson = json_from_object($object);

		// megjelenes kovetkezik...
		print "<div id=\"rightcontent\">";
			print "<div onclick='begin_new_or_edit_data(\"".$tablename."\", ".$ojson.");' class=\"action_button\">Szerkesztés</div>";
			print "<p>";
				print "<table>";
					print "<tr><td><b>Vezetéknév:</b></td><td>".$object->vnev."</td></tr>";
					print "<tr><td><b>Keresztnév:</b></td><td>".$object->knev."</td></tr>";
					print "<tr><td><b>Rövid név:</b></td><td>".$object->rovid_nev."</td></tr>";
					print "<tr><td><b>Alcím:</b></td><td>".$object->alcim."</td></tr>";
					print "<tr><td><b>Leírás:</b></td><td>".$object->leiras."</td></tr>";
					print "<tr><td><b>Értékelés:</b></td><td>".$object->ertekeles."</td></tr>";
					print "<tr><td><b>Kép:</b></td><td>".($object->foto == "" ? "" : "<img src=\"data/data_edzok/".$object->foto.".jpg\">")."</td></tr>";
				print "</table>";
			print "</p>";
			print "<div onclick='begin_new_or_edit_data(\"".$tablename."\", ".$ojson.");' class=\"action_button\">Szerkesztés</div>";
		print "</div>";
	}
	else {
		print "<div id=\"rightcontent\"><br><div style=\"color: red; padding: 5px; border-color: black; border-width: 1px; border-style: solid;\">Nincs adat kiválasztva</div></div>";
	}
?>