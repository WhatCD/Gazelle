<?

define('PREFIX', 'percentiles_'); // Prefix for memcache keys, to make life easier

class USER_RANK {
	
	// Returns a 101 row array (101 percentiles - 0 - 100), with the minimum value for that percentile as the value for each row
	// BTW - ingenious
	function build_table($MemKey, $Query) {
		global $Cache,$DB;
		
		$DB->query("DROP TEMPORARY TABLE IF EXISTS temp_stats");
		
		$DB->query("CREATE TEMPORARY TABLE temp_stats
			(ID int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			Val bigint(20) NOT NULL);");
		
		$DB->query("INSERT INTO temp_stats (Val) ".$Query);
		
		$DB->query("SELECT COUNT(ID) FROM temp_stats");
		list($UserCount) = $DB->next_record();
		
		$DB->query("SELECT MIN(Val) FROM temp_stats GROUP BY CEIL(ID/(".(int)$UserCount."/100));");
		
		$Table = $DB->to_array();
		
		// Give a little variation to the cache length, so all the tables don't expire at the same time
		$Cache->cache_value($MemKey, $Table, 3600*24*rand(800,1000)*0.001); 
		
		return $Table;
	}
	
	function table_query($TableName) {
		switch($TableName) {
			case 'uploaded':
				$Query =  "SELECT Uploaded FROM users_main WHERE Enabled='1' AND Uploaded>0 ORDER BY Uploaded;";
				break;
			case 'downloaded':
				$Query =  "SELECT Downloaded FROM users_main WHERE Enabled='1' AND Downloaded>0 ORDER BY Downloaded;";
				break;
			case 'uploads':
				$Query = "SELECT COUNT(t.ID) AS Uploads FROM users_main AS um JOIN torrents AS t ON t.UserID=um.ID WHERE um.Enabled='1' GROUP BY um.ID ORDER BY Uploads;";
				break;
			case 'requests':
				$Query = "SELECT COUNT(r.ID) AS Requests FROM users_main AS um JOIN requests AS r ON r.FillerID=um.ID WHERE um.Enabled='1' GROUP BY um.ID ORDER BY Requests;";
				break;
			case 'posts':
				$Query = "SELECT COUNT(p.ID) AS Posts FROM users_main AS um JOIN forums_posts AS p ON p.AuthorID=um.ID WHERE um.Enabled='1' GROUP BY um.ID ORDER BY Posts;";
				break;
			case 'bounty':
				//Request bunny exception
				$Query = "SELECT SUM(rv.Bounty) AS Bounty FROM users_main AS um JOIN requests_votes AS rv ON rv.UserID=um.ID WHERE um.Enabled='1' AND um.ID <> 260542 GROUP BY um.ID ORDER BY Bounty;";
				break;
			case 'artists':
				$Query = "SELECT COUNT(ta.ArtistID) AS Artists FROM torrents_artists AS ta JOIN torrents_group AS tg ON tg.ID=ta.GroupID JOIN torrents AS t ON t.GroupID = tg.ID WHERE t.UserID != ta.UserID GROUP BY tg.ID ORDER BY Artists ASC";
				break;
		}
		return $Query;
	}
	
	function get_rank($TableName, $Value) {
		if($Value == 0) { return 0; }
		global $Cache, $DB;
			
		$Table = $Cache->get_value(PREFIX.$TableName);
		if(!$Table) {
			//Cache lock!
			$Lock = $Cache->get_value(PREFIX.$TableName."_lock");
			if($Lock) {
				?><script type="script/javascript">setTimeout('window.location="http://<?=NONSSL_SITE_URL?><?=$_SERVER['REQUEST_URI']?>"', 5)</script><?
			} else {
				$Cache->cache_value(PREFIX.$TableName."_lock", '1', 10);
				$Table = $this->build_table(PREFIX.$TableName, $this->table_query($TableName));
			}
		}
		$LastPercentile = 0;
		foreach ($Table as $Row) {
			list($CurValue) = $Row;
			if($CurValue>=$Value) {
				return $LastPercentile;
			}
			$LastPercentile++;
		}
		return 100; // 100th percentile
	}
	
	function overall_score($Uploaded, $Downloaded, $Uploads, $Requests, $Posts, $Bounty, $Artists, $Ratio){
		// We can do this all in 1 line, but it's easier to read this way
		if($Ratio>1) { $Ratio = 1; }
		$TotalScore = 0;
		$TotalScore += $Uploaded*15;
		$TotalScore += $Downloaded*8;
		$TotalScore += $Uploads*25;
		$TotalScore += $Requests*2;
		$TotalScore += $Posts;
		$TotalScore += $Bounty;
		$TotalScore += $Artists;
		$TotalScore /= (15+8+25+2+1+1+1);
		$TotalScore *= $Ratio;
		return $TotalScore;
		
	}
	
}


?>
