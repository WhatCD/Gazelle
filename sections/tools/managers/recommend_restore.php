<?
//******************************************************************************//
//--------------- Restore all VH-recommended torrents to NL --------------------//
//---- For use after resetting the FL/NL database (after sitewide freeleech) ---//
authorize();

if (!check_perms('site_manage_recommendations')) {
	error(403);
}

$DB->query('
	SELECT GroupID
	FROM torrents_recommended');
$ToNL = $DB->next_record();
Torrents::freeleech_groups($ToNL, 2, 3);
?>
Done
