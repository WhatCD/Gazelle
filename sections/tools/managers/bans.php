<?
if(!check_perms('admin_manage_ipbans')) { error(403); }

if (isset($_POST['submit'])) {
	authorize();

	if ($_POST['submit'] == 'Delete') { //Delete
		if(!is_number($_POST['id']) || $_POST['id'] == ''){ error(0); }
		$DB->query('DELETE FROM ip_bans WHERE ID='.$_POST['id']);
		$Bans = $Cache->delete_value('ip_bans');
	} else { //Edit & Create, Shared Validation
		$Val->SetFields('start', '1','regex','You must inculde starting IP address.',array('regex'=>'/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i'));
		$Val->SetFields('end', '1','regex','You must inculde ending IP address.',array('regex'=>'/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i'));
		$Val->SetFields('notes', '1','string','You must inculde a note regarding the reason for the ban.');
		$Err=$Val->ValidateForm($_POST); // Validate the form
		if($Err){ error($Err); }
	
		$Notes = db_string($_POST['notes']);
		$Start = ip2unsigned($_POST['start']); //Sanitized by Validation regex
		$End = ip2unsigned($_POST['end']); //See above

		if($_POST['submit'] == 'Edit'){ //Edit
			if(empty($_POST['id']) || !is_number($_POST['id'])) {
				error(404);
			}
			$DB->query("UPDATE ip_bans SET
				FromIP=$Start,
				ToIP='$End',
				Reason='$Notes'
				WHERE ID='".$_POST['id']."'");
			$Bans = $Cache->get_value('ip_bans');
			$Cache->begin_transaction();
			$Cache->update_row($_POST['id'], array($_POST['id'], $Start, $End));
			$Cache->commit_transaction();
		} else { //Create
			$DB->query("INSERT INTO ip_bans
				(FromIP, ToIP, Reason) VALUES
				('$Start','$End', '$Notes')");
			$ID = $DB->inserted_id();
			$Bans = $Cache->get_value('ip_bans');
			$Bans[$ID] = array($ID, $Start, $End);
			$Cache->cache_value('ip_bans', $Bans, 0);
		}
	}
}

define('BANS_PER_PAGE', '20');
list($Page,$Limit) = page_limit(BANS_PER_PAGE);

$sql = "SELECT SQL_CALC_FOUND_ROWS ID, FromIP, ToIP, Reason FROM ip_bans AS i ";

if(!empty($_REQUEST['notes'])) {
	$sql .= "WHERE Reason LIKE '%".db_string($_REQUEST['notes'])."%' ";
}

if(!empty($_REQUEST['ip']) && preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $_REQUEST['ip'])) {
	if (!empty($_REQUEST['notes'])) {
		$sql .= "AND '".ip2unsigned($_REQUEST['ip'])."' BETWEEN FromIP AND ToIP ";
	} else {
		$sql .= "WHERE '".ip2unsigned($_REQUEST['ip'])."' BETWEEN FromIP AND ToIP ";
	}
}

$sql .= "ORDER BY FromIP ASC";
$sql .= " LIMIT ".$Limit;
$Bans = $DB->query($sql);

$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();

$PageLinks=get_pages($Page,$Results,BANS_PER_PAGE,11);

$DB->set_query_id($Bans);
show_header('IP Bans');
?>

<h2>IP Bans</h2>

<div>
	<form action="" method="get">
		<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
			<tr>
				<td class="label"><label for="ip">IP:</label></td>
				<td>
					<input type="hidden" name="action" value="ip_ban" />
					<input type="text" id="ip" name="ip" size="20" value="<?=(!empty($_GET['ip']) ? display_str($_GET['ip']) : '')?>" />
				</td>
				<td class="label"><label for="notes">Notes:</label></td>
				<td>
					<input type="hidden" name="action" value="ip_ban" />
					<input type="text" id="notes" name="notes" size="60" value="<?=(!empty($_GET['notes']) ? display_str($_GET['notes']) : '')?>" />
				</td>
				<td>
					<input type="submit" value="Search" />
				</td>
			</tr>
		</table>	
	</form>
</div>
<br / >

<h3>Manage</h3>
<?=$PageLinks?>
<table width="100%">
	<tr class="colhead">
		<td colspan="2">Range</td>
		<td>Notes</td>
		<td>Submit</td>
	</tr>
	<tr class="rowa">
		<form action="" method="post">
			<input type="hidden" name="action" value="ip_ban" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<td colspan="2">
				<input type="text" size="12" name="start" />
				<input type="text" size="12" name="end" />
			</td>
			<td>
				<input type="text" size="72" name="notes" />
			</td>
			<td>
				<input type="submit" name="submit" value="Create" />
			</td>
			
		</form>
	</tr>
<?
$Row = 'a';
while(list($ID, $Start, $End, $Reason) = $DB->next_record()){
	$Row = ($Row === 'a' ? 'b' : 'a');
	$Start=long2ip($Start);
	$End=long2ip($End);
?>
	<tr class="row<?=$Row?>">
		<form action="" method="post">
			<input type="hidden" name="id" value="<?=$ID?>" />
			<input type="hidden" name="action" value="ip_ban" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<td colspan="2">
				<input type="text" size="12" name="start" value="<?=$Start?>" />
				<input type="text" size="12" name="end" value="<?=$End?>" />
			</td>
			<td>
				<input type="text" size="72" name="notes" value="<?=$Reason?>" />
			</td>
			<td>
				<input type="submit" name="submit" value="Edit" />
				<input type="submit" name="submit" value="Delete" />
			</td>
			
		</form>
	</tr>
<?
}
?>
</table>
<?=$PageLinks?>
<? show_footer(); ?>
