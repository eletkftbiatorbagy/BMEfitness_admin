
<?php
	require_once("../functions/database.php");
	require_once("../functions/functions.php");

	print "<div style=\"border-width: 2px; border-color: #333334; border-style: solid;\"><h1 style=\"color: #489d1e;\">Infó</h1></div>";
	print "<div id=\"rightcontent\" style=\"margin-left: 0px;\">";
	$query = "SELECT * FROM fitness.info;";
	$result = db_query_object_array($query);

	print "<div onclick=\"begin_new_or_edit_data('info'); neworeditClick();\"class=\"action_button\">Szerkesztés</div>";

	if (!is_null($result) == count($result) > 0) {
		print "<b>Bemutatkozás</b><br><p>";
		print $result[0]->bemutatkozas."</p>";

		print "<b>Házirend</b><br><p>";
		print $result[0]->hazirend."</p>";

		print "<b>Nyitvatartás</b><br><p>";
		print $result[0]->nyitvatartas."</p>";
	}
	else {
		print "<b>Bemutatkozás</b><br><p><div style=\"color: red;\">Nincs adat hozzáadva</div></p>";
		print "<b>Házirend</b><br><p><div style=\"color: red;\">Nincs adat hozzáadva</div></p>";
		print "<b>Nyitvatartás</b><br><p><div style=\"color: red;\">Nincs adat hozzáadva</div></p>";
	}

	print "<div onclick=\"begin_new_or_edit_data('info'); neworeditClick();\"class=\"action_button\">Szerkesztés</div>";

	print "</div>";
?>