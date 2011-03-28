<?
/************************************************************************
||------------|| Artist wiki history page ||---------------------------||

This page lists previous revisions of the artists page. It gets called 
if $_GET['action'] == 'history'. 

It also requires $_GET['artistid'].

The wiki class is used here to generate the page history.

************************************************************************/




$GroupID = $_GET['groupid'];
if(!is_number($GroupID) || !$GroupID) { error(0); }

include(SERVER_ROOT.'/classes/class_wiki.php'); // Wiki class
$Wiki = new WIKI('wiki_torrents', $GroupID, "/torrents.php?id=$GroupID");

// Get the artist name and the body of the last revision
$DB->query("SELECT Name FROM torrents_group WHERE ID='$GroupID'");
list($Name) = $DB->next_record();

if(!$Name) { error(404); }

show_header("Revision history for $Name"); // Set title

// Start printing form
?>
<div class="thin">
	<h2>Revision history for <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></h2>
<?
$Wiki->revision_history(); // the wiki class takes over from here
?>
</div>
<?
show_footer();
?>
