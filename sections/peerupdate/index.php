<?
// We keep torrent groups cached. However, the peer counts change ofter, so our solutions are to not cache them for long, or to update them. Here is where we updated them. 

if ((!isset($argv[1]) || $argv[1]!=SCHEDULE_KEY) && !check_perms('admin_schedule')) { // authorization, Fix to allow people with perms hit this page.
	error(403);
}

if (check_perms('admin_schedule')) {
	show_header();
	echo '<pre>';
}

ignore_user_abort();
ini_set('max_execution_time',600);
ini_set('memory_limit','4096M');
ob_end_flush();
gc_enable();


$DB->query("TRUNCATE TABLE torrents_peerlists_compare");
$DB->query("INSERT INTO torrents_peerlists_compare (GroupID, SeedersList, LeechersList, SnatchedList)
	SELECT GroupID,
	GROUP_CONCAT(CONCAT(ID,'.',Seeders) ORDER BY ID SEPARATOR '|'),
	GROUP_CONCAT(CONCAT(ID,'.',Leechers) ORDER BY ID SEPARATOR '|'),
	GROUP_CONCAT(CONCAT(ID,'.',Snatched) ORDER BY ID SEPARATOR '|')
	FROM torrents GROUP BY GroupID;
");
	
$DB->query("select t1.GroupID,t2.SeedersList,t2.LeechersList,t2.SnatchedList FROM torrents_peerlists AS t1 JOIN torrents_peerlists_compare AS t2 ON t1.GroupID=t2.GroupID WHERE t1.SeedersList!=t2.SeedersList OR t1.LeechersList!=t2.LeechersList OR t1.SnatchedList!=t2.SnatchedList ORDER BY t1.GroupID");

while(list($GroupID,$Seeders,$Leechers,$Snatched) = $DB->next_record(MYSQLI_NUM)) {
	$Data = $Cache->get_value('torrent_group_'.$GroupID);
	if(!is_array($Data)) { continue; }
	
	$Changed = false;
	
	$Seeders = explode('|',$Seeders);
	$Leechers = explode('|',$Leechers);
	$Snatched = explode('|',$Snatched);
	
	$TotalSeeders = 0;
	$TotalLeechers = 0;
	$TotalSnatched = 0;
	
	foreach($Seeders as $Nums) {
		list($TorrentID, $Val) = explode('.',$Nums);
		if(is_array($Data['Torrents']) && isset($Data['Torrents'][$TorrentID]) && is_array($Data['Torrents'][$TorrentID])) {
			if($Data['Torrents'][$TorrentID]['Seeders']!=$Val) {
				$Data['Torrents'][$TorrentID]['Seeders']=$Val;
				$Changed = true;
			}
		}
		$TotalSeeders+=$Val;
	}
	foreach($Leechers as $Nums) {
		list($TorrentID, $Val) = explode('.',$Nums);
		if(is_array($Data['Torrents']) && isset($Data['Torrents'][$TorrentID]) && is_array($Data['Torrents'][$TorrentID])) {
			if($Data['Torrents'][$TorrentID]['Leechers']!=$Val) {
				$Data['Torrents'][$TorrentID]['Leechers']=$Val;
				$Changed = true;
			}
		}
		$TotalLeechers+=$Val;
	}
	foreach($Snatched as $Nums) {
		list($TorrentID, $Val) = explode('.',$Nums);
		if(is_array($Data['Torrents']) && isset($Data['Torrents'][$TorrentID]) && is_array($Data['Torrents'][$TorrentID])) {
			if($Data['Torrents'][$TorrentID]['Snatched']!=$Val) {
				$Data['Torrents'][$TorrentID]['Snatched']=$Val;
				$Changed = true;
			}
		}
		$TotalSnatched=$Val;
	}
	if($Changed) {
		$Cache->cache_value('torrent_group_'.$GroupID, $Data, 0);
	}
	unset($Data);
}

$DB->query("TRUNCATE TABLE torrents_peerlists");
$DB->query("INSERT INTO torrents_peerlists 
	(GroupID, SeedersList, LeechersList, SnatchedList)
	SELECT GroupID, SeedersList, LeechersList, SnatchedList FROM torrents_peerlists_compare");
	

echo microtime(true)-$ScriptStartTime;
if (check_perms('admin_schedule')) {	
	echo '<pre>';
	show_footer();
}
?>