<?
if(!check_perms('admin_donor_log')) { error(403); }

include(SERVER_ROOT.'/sections/donate/config.php');

define('DONATIONS_PER_PAGE', 50);
list($Page,$Limit) = page_limit(DONATIONS_PER_PAGE);


$sql = "SELECT 
	SQL_CALC_FOUND_ROWS
	d.UserID, 
	d.Amount, 
	d.Currency, 
	d.Email, 
	m.Username,
	m.PermissionID,
	m.Enabled,
	i.Donor,
	i.Warned,
	d.Time 
	FROM donations AS d 
	LEFT JOIN users_main AS m ON m.ID=d.UserID
	LEFT JOIN users_info AS i ON i.UserID=d.UserID ";
if(!empty($_GET['search'])) {
	$sql .= "WHERE d.Email LIKE '%".db_string($_GET['search'])."%' ";
}
$sql .= "ORDER BY d.Time DESC LIMIT $Limit";
$DB->query($sql);
$Donations = $DB->to_array(false,MYSQLI_NUM);

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();

if (empty($_GET['search']) && !isset($_GET['page']) && !$DonationTimeline = $Cache->get_value('donation_timeline')) {
	include(SERVER_ROOT.'/classes/class_charts.php');
	$DB->query("SELECT DATE_FORMAT(Time,'%b \'%y') AS Month, SUM(Amount) FROM donations GROUP BY Month ORDER BY Time DESC LIMIT 1, 18");
	$Timeline = array_reverse($DB->to_array());
	$Area = new AREA_GRAPH(880,160,array('Break'=>1));
	foreach($Timeline as $Entry) {
		list($Label,$Amount) = $Entry;
		$Area->add($Label,$Amount);
	}
	$Area->transparent();
	$Area->grid_lines();
	$Area->color('3d7930');
	$Area->lines(2);
	$Area->generate();
	$DonationTimeline = $Area->url();
	$Cache->cache_value('donation_timeline',$DonationTimeline,mktime(0,0,0,date('n')+1,2));
}

show_header('Donation log');
if (empty($_GET['search']) && !isset($_GET['page'])) {
?>
<div class="box pad">
	<img src="<?=$DonationTimeline?>" />
</div>
<br / >
<? } ?>
<div>
	<form action="" method="get">
		<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
			<tr>
				<td class="label"><strong>Email:</strong></td>
				<td>
					<input type="hidden" name="action" value="donation_log" />
					<input type="text" name="search" size="60" value="<? if (!empty($_GET['search'])) { echo display_str($_GET['search']); } ?>" />
					&nbsp;
					<input type="submit" value="Search log" />
				</td>
			</tr>
		</table>	
	</form>
</div>
<br / >
<div class="linkbox">
<?
	$Pages=get_pages($Page,$Results,DONATIONS_PER_PAGE,11) ;
	echo $Pages;
?>
</div>
<table width="100%">
	<tr class="colhead">
		<td>User</td>
		<td>Amount</td>
		<td>Email</td>
		<td>Time</td>
	</tr>
<?
	foreach($Donations as $Donation) {
		list($UserID, $Amount, $Currency, $Email, $Username, $PermissionID, $Enabled, $Donor, $Warned, $DonationTime) = $Donation;
?>
	<tr>
		<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
		<td><?=display_str($Amount)?> <?=$Currency?></td>
		<td><?=display_str($Email)?></td>
		<td><?=time_diff($DonationTime)?></td>
	</tr>
<?	} ?>
</table>
<div class="linkbox">
	<?=$Pages?>
</div>
<? show_footer(); ?>
