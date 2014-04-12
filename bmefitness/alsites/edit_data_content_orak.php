
<?php
	require_once("../functions/database.php");

	print "<div class=\"leftcontent\">";
	$query = "SELECT * FROM fitness.orak;";
	$result = db_query_object_array($query);

	if (!is_null($result)) {
		print "Korábban létrehozott adatok:<br>";
		print "<div class=\"scrollcontent\">";
		if (count($result) == 0) {
			print "<div style=\"color: red;\">Nincs adat hozzáadva</div><br>";
		}
		else {
			for ($i = 0; $i < count($result); $i++) {
				print "<div class=\"edit_data_available\">óra id: ".$result[$i]->id.", név: ".$result[$i]->nev."</div>";
			}
		}
		print "</div>";
		print "<div onclick=\"begin_new_data('orak'); neworeditClick();\" style=\"cursor: pointer; margin: 10px; padding: 5px; border-color: black; border-width: 1px; border-style: solid;\">Új adat hozzáadása</div>";
	}
	print "</div>";


	print "<div class=\"rightcontent\"></div>";

?>