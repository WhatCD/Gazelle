<?
if(!check_perms('site_view_flow')) { error(403); }

//Timeline generation
if(!isset($_GET['page'])) {
	if (!list($Labels,$InFlow,$OutFlow,$Max) = $Cache->get_value('users_timeline')) {
		$DB->query("SELECT DATE_FORMAT(JoinDate,'%b \'%y') AS Month, COUNT(UserID) FROM users_info GROUP BY Month ORDER BY JoinDate DESC LIMIT 1, 12");
		$TimelineIn = array_reverse($DB->to_array());
		$DB->query("SELECT DATE_FORMAT(BanDate,'%b \'%y') AS Month, COUNT(UserID) FROM users_info GROUP BY Month ORDER BY BanDate DESC LIMIT 1, 12");
		$TimelineOut = array_reverse($DB->to_array());
		foreach($TimelineIn as $Month) {
			list($Label,$Amount) = $Month;
			if ($Amount > $Max) {
				$Max = $Amount;
			}
		}
		foreach($TimelineOut as $Month) {
			list($Label,$Amount) = $Month;
			if ($Amount > $Max) {
				$Max = $Amount;
			}
		}
		foreach($TimelineIn as $Month) {
			list($Label,$Amount) = $Month;
			$Labels[] = $Label;
			$InFlow[] = number_format(($Amount/$Max)*100,4);
		}
		foreach($TimelineOut as $Month) {
			list($Label,$Amount) = $Month;
			$OutFlow[] = number_format(($Amount/$Max)*100,4);
		}
		$Cache->cache_value('users_timeline',array($Labels,$InFlow,$OutFlow,$Max),mktime(0,0,0,date('n')+1,2));
	}
}
//End timeline generation


define('DAYS_PER_PAGE', 100);
list($Page,$Limit) = page_limit(DAYS_PER_PAGE);

$RS = $DB->query("SELECT
		SQL_CALC_FOUND_ROWS
		j.Date,
		DATE_FORMAT(j.Date,'%Y-%m') AS Month,
		CASE ISNULL(j.Flow)
			WHEN 0 THEN j.Flow
			ELSE '0'
		END AS Joined,
		CASE ISNULL(m.Flow)
			WHEN 0 THEN m.Flow
			ELSE '0'
		END AS Manual,
		CASE ISNULL(r.Flow)
			WHEN 0 THEN r.Flow
			ELSE '0'
		END AS Ratio,
		CASE ISNULL(i.Flow)
			WHEN 0 THEN i.Flow
			ELSE '0'
		END AS Inactivity
		FROM (
			SELECT
				DATE_FORMAT(JoinDate,'%Y-%m-%d') AS Date,
				COUNT(UserID) AS Flow
				FROM users_info
			 	WHERE JoinDate != '0000-00-00 00:00:00'
				GROUP BY Date
		) AS j
		LEFT JOIN (
			SELECT
				DATE_FORMAT(BanDate,'%Y-%m-%d') AS Date,
			 	COUNT(UserID) AS Flow
			 	FROM users_info
			 	WHERE BanDate != '0000-00-00 00:00:00'
			 	AND BanReason = '1'
			 	GROUP BY Date
		) AS m ON j.Date=m.Date
		LEFT JOIN (
			SELECT
				DATE_FORMAT(BanDate,'%Y-%m-%d') AS Date,
			 	COUNT(UserID) AS Flow
			 	FROM users_info
			 	WHERE BanDate != '0000-00-00 00:00:00'
			 	AND BanReason = '2'
			 	GROUP BY Date
		) AS r ON j.Date=r.Date
		LEFT JOIN (
			SELECT
				DATE_FORMAT(BanDate,'%Y-%m-%d') AS Date,
			 	COUNT(UserID) AS Flow
			 	FROM users_info
			 	WHERE BanDate != '0000-00-00 00:00:00'
			 	AND BanReason = '3'
			 	GROUP BY Date
		) AS i ON j.Date=i.Date
		ORDER BY j.Date DESC
		LIMIT $Limit");
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$DB->set_query_id($RS);

show_header('User Flow');
?>
<div class="thin">
<? if(!isset($_GET['page'])) { ?>
	<div class="box pad">
		<img src="http://chart.apis.google.com/chart?cht=lc&chs=820x160&chco=000D99,99000D&chg=0,-1,1,1&chxt=y,x&chxs=0,h&chxl=1:|<?=implode('|',$Labels)?>&chxr=0,0,<?=$Max?>&chd=t:<?=implode(',',$InFlow)?>|<?=implode(',',$OutFlow)?>&chls=2,4,0&chdl=New+Registrations|Disabled+Users&amp;chf=bg,s,FFFFFF00" />
	</div>
<? } ?>
	<div class="linkbox">
<?
$Pages=get_pages($Page,$Results,DAYS_PER_PAGE,11) ;
echo $Pages;
?>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>Date</td>
			<td>(+) Joined</td>
			<td>(-) Manual</td>
			<td>(-) Ratio</td>
			<td>(-) Inactivity</td>			
			<td>(-) Total</td>
			<td>Net Growth</td>
		</tr>
<?
	while(list($Date, $Month, $Joined, $Manual, $Ratio, $Inactivity)=$DB->next_record()) {
	$TotalOut = $Ratio + $Inactivity + $Manual;
	$TotalGrowth = $Joined - $TotalOut;
?>
		<tr class="rowb">
			<td><?=$Date?></td>
			<td><?=number_format($Joined)?></td>
			<td><?=number_format($Manual)?></td>
			<td><?=number_format((double) $Ratio)?></td>
			<td><?=number_format($Inactivity)?></td>
			<td><?=number_format($TotalOut)?></td>
			<td><?=number_format($TotalGrowth)?></td>
		</tr>
<?	} ?>
	</table>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<? show_footer(); ?>
