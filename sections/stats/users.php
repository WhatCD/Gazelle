<?
if (!list($Countries,$Rank,$CountryUsers,$CountryMax,$CountryMin,$LogIncrements) = $Cache->get_value('geodistribution')) {
	include_once(SERVER_ROOT.'/classes/class_charts.php');
	$DB->query('SELECT Code, Users FROM users_geodistribution');
	$Data = $DB->to_array();
	$Count = $DB->record_count()-1;
	
	if($Count<30) {
		$CountryMinThreshold = $Count;
	} else {
		$CountryMinThreshold = 30;
	}
	
	$CountryMax = ceil(log(Max(1,$Data[0][1]))/log(2))+1;
	$CountryMin = floor(log(Max(1,$Data[$CountryMinThreshold][1]))/log(2));

	$CountryRegions = array('RS' => array('RS-KM')); // Count Kosovo as Serbia as it doesn't have a TLD
	foreach ($Data as $Key => $Item) {
		list($Country,$UserCount) = $Item;
		$Countries[] = $Country;
		$CountryUsers[] = number_format((((log($UserCount)/log(2))-$CountryMin)/($CountryMax-$CountryMin))*100,2);
		$Rank[] = round((1-($Key/$Count))*100);

		if(isset($CountryRegions[$Country])) {
			foreach($CountryRegions[$Country] as $Region) {
				$Countries[] = $Region;
				$Rank[] = end($Rank);
			}
		}
	}
	reset($Rank);
	
	for ($i=$CountryMin;$i<=$CountryMax;$i++) {
		$LogIncrements[] = human_format(pow(2,$i));
	}
	$Cache->cache_value('geodistribution',array($Countries,$Rank,$CountryUsers,$CountryMax,$CountryMin,$LogIncrements),0);
}

if(!$ClassDistribution = $Cache->get_value('class_distribution')) {
	include_once(SERVER_ROOT.'/classes/class_charts.php');
	$DB->query("SELECT p.Name, COUNT(m.ID) AS Users FROM users_main AS m JOIN permissions AS p ON m.PermissionID=p.ID WHERE m.Enabled='1' GROUP BY p.Name ORDER BY Users DESC");
	$ClassSizes = $DB->to_array();
	$Pie = new PIE_CHART(750,400,array('Other'=>1,'Percentage'=>1));
	foreach($ClassSizes as $ClassSize) {
		list($Label,$Users) = $ClassSize;
		$Pie->add($Label,$Users);
	}
	$Pie->transparent();
	$Pie->color('FF33CC');
	$Pie->generate();
	$ClassDistribution = $Pie->url();
	$Cache->cache_value('class_distribution',$ClassDistribution,3600*24*14);
}
if(!$PlatformDistribution = $Cache->get_value('platform_distribution')) {
	include_once(SERVER_ROOT.'/classes/class_charts.php');
	
	
	$DB->query("SELECT OperatingSystem, COUNT(UserID) AS Users FROM users_sessions GROUP BY OperatingSystem ORDER BY Users DESC");
	
	$Platforms = $DB->to_array();
	$Pie = new PIE_CHART(750,400,array('Other'=>1,'Percentage'=>1));
	foreach($Platforms as $Platform) {
		list($Label,$Users) = $Platform;
		$Pie->add($Label,$Users);
	}
	$Pie->transparent();
	$Pie->color('8A00B8');
	$Pie->generate();
	$PlatformDistribution = $Pie->url();
	$Cache->cache_value('platform_distribution',$PlatformDistribution,3600*24*14);
}

if(!$BrowserDistribution = $Cache->get_value('browser_distribution')) {
	include_once(SERVER_ROOT.'/classes/class_charts.php');
	
	
	$DB->query("SELECT Browser, COUNT(UserID) AS Users FROM users_sessions GROUP BY Browser ORDER BY Users DESC");
	
	$Browsers = $DB->to_array();
	$Pie = new PIE_CHART(750,400,array('Other'=>1,'Percentage'=>1));
	foreach($Browsers as $Browser) {
		list($Label,$Users) = $Browser;
		$Pie->add($Label,$Users);
	}
	$Pie->transparent();
	$Pie->color('008AB8');
	$Pie->generate();
	$BrowserDistribution = $Pie->url();
	$Cache->cache_value('browser_distribution',$BrowserDistribution,3600*24*14);
}


//Timeline generation
if (!list($Labels,$InFlow,$OutFlow,$Max) = $Cache->get_value('users_timeline')) {
	$DB->query("SELECT DATE_FORMAT(JoinDate,'%b \\'%y') AS Month, COUNT(UserID) FROM users_info GROUP BY Month ORDER BY JoinDate DESC LIMIT 1, 12");
	$TimelineIn = array_reverse($DB->to_array());
	$DB->query("SELECT DATE_FORMAT(BanDate,'%b \\'%y') AS Month, COUNT(UserID) FROM users_info GROUP BY Month ORDER BY BanDate DESC LIMIT 1, 12");
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

	$Labels = array();
	foreach($TimelineIn as $Month) {
		list($Label,$Amount) = $Month;
		$Labels[] = $Label;
		$InFlow[] = number_format(($Amount/$Max)*100,4);
	}
	foreach($TimelineOut as $Month) {
		list($Label,$Amount) = $Month;
		$OutFlow[] = number_format(($Amount/$Max)*100,4);
	}
	$Cache->cache_value('users_timeline',array($Labels,$InFlow,$OutFlow,$Max),mktime(0,0,0,date('n')+1,2)); //Tested: fine for dec -> jan
}
//End timeline generation

show_header('Detailed User Statistics');
?>
<h3>User Flow</h3>
<div class="box pad center">
	<img src="http://chart.apis.google.com/chart?cht=lc&chs=880x160&chco=000D99,99000D&chg=0,-1,1,1&chxt=y,x&chxs=0,h&chxl=1:|<?=implode('|',$Labels)?>&chxr=0,0,<?=$Max?>&chd=t:<?=implode(',',$InFlow)?>|<?=implode(',',$OutFlow)?>&chls=2,4,0&chdl=New+Registrations|Disabled+Users&amp;chf=bg,s,FFFFFF00" />
</div>
<br />
<h3>User Classes</h3>
<div class="box pad center">
	<img src="<?=$ClassDistribution?>" />
</div>
<br />
<h3>User Platforms</h3>
<div class="box pad center">
	<img src="<?=$PlatformDistribution?>" />
</div>
<br />
<h3>User Browsers</h3>
<div class="box pad center">
	<img src="<?=$BrowserDistribution?>" />
</div>
<br />
<h3>Geographical Distribution Map</h3>
<div class="box center">
	<img src="http://chart.apis.google.com/chart?cht=map:fixed=-55,-180,73,180&chs=440x220&chd=t:<?=implode(',',$Rank)?>&chco=FFFFFF,EDEDED,1F0066&chld=<?=implode('|',$Countries)?>&chf=bg,s,CCD6FF" />
	<img src="http://chart.apis.google.com/chart?cht=map:fixed=37,-26,65,67&chs=440x220&chs=440x220&chd=t:<?=implode(',',$Rank)?>&chco=FFFFFF,EDEDED,1F0066&chld=<?=implode('|',$Countries)?>&chf=bg,s,CCD6FF" />
	<br />
	<img src="http://chart.apis.google.com/chart?cht=map:fixed=-46,-132,24,21.5&chs=440x220&chd=t:<?=implode(',',$Rank)?>&chco=FFFFFF,EDEDED,1F0066&chld=<?=implode('|',$Countries)?>&chf=bg,s,CCD6FF" />
	<img src="http://chart.apis.google.com/chart?cht=map:fixed=-11,22,50,160&chs=440x220&chd=t:<?=implode(',',$Rank)?>&chco=FFFFFF,EDEDED,1F0066&chld=<?=implode('|',$Countries)?>&chf=bg,s,CCD6FF" />
	<br />
	<img src="http://chart.apis.google.com/chart?cht=map:fixed=-36,-57,37,100&chs=440x220&chd=t:<?=implode(',',$Rank)?>&chco=FFFFFF,EDEDED,1F0066&chld=<?=implode('|',$Countries)?>&chf=bg,s,CCD6FF" />
	<img src="http://chart.apis.google.com/chart?cht=map:fixed=14.8,15,45,86&chs=440x220&chd=t:<?=implode(',',$Rank)?>&chco=FFFFFF,EDEDED,1F0066&chld=<?=implode('|',$Countries)?>&chf=bg,s,CCD6FF" />
	<br />
	<img src="http://chart.apis.google.com/chart?chxt=y,x&chg=0,-1,1,1&chxs=0,h&cht=bvs&chco=76A4FB&chs=880x300&chd=t:<?=implode(',',array_slice($CountryUsers,0,31))?>&chxl=1:|<?=implode('|',array_slice($Countries,0,31))?>|0:|<?=implode('|',$LogIncrements)?>&amp;chf=bg,s,FFFFFF00" />
</div>
<?
show_footer();
