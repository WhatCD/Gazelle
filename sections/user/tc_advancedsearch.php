<?
/**********************************************************************
 *>>>>>>>>>>>>>>>>>>>>>>>>>>> User search <<<<<<<<<<<<<<<<<<<<<<<<<<<<*
 * Best viewed with a wide screen monitor							 *
 **********************************************************************/
if (!empty($_GET['search'])) {
	if (preg_match("/^".IP_REGEX."$/", $_GET['search'])) {
		$_GET['ip'] = $_GET['search'];
	} elseif (preg_match("/^".EMAIL_REGEX."$/i", $_GET['search'])) {
		$_GET['email'] = $_GET['search'];
	} elseif (preg_match('/^[a-z0-9_?]{1,20}$/iD',$_GET['search'])) {
		$DB->query("SELECT ID FROM users_main WHERE Username='".db_string($_GET['search'])."'");
		if (list($ID) = $DB->next_record()) {
			header('Location: user.php?id='.$ID);
			die();
		}
		$_GET['username'] = $_GET['search'];
	} else {
		$_GET['comment'] = $_GET['search'];
	}
}
 
define('USERS_PER_PAGE', 30);

if(!check_perms("tc_advanced_user_search")) { error(403); }

function wrap($String, $ForceMatch = '', $IPSearch = false){
	if(!$ForceMatch){
		global $Match;
	} else {
		$Match = $ForceMatch;
	}
	if($Match == ' REGEXP '){
		if(strpos($String, '\'') !== false || preg_match('/^.*\\\\$/i', $String)){
			error('Regex contains illegal characters.');
		}
	} else {
		$String = db_string($String);
	}
	if($Match == ' LIKE '){
		// Fuzzy search
		// Stick in wildcards at beginning and end of string unless string starts or ends with |
		if (($String[0] != '|') && !$IPSearch) {
			$String = '%'.$String;
		} elseif ($String[0] == '|') {
			$String = substr($String, 1, strlen($String));
		}

		if(substr($String, -1, 1) != '|'){
			$String = $String.'%';
		} else {
			$String = substr($String, 0, -1);
		}

	}
	$String="'$String'";
	return $String;
}

function date_compare($Field, $Operand, $Date1, $Date2 = ''){
	$Date1 = db_string($Date1);
	$Date2 = db_string($Date2);
	$Return = array();

	switch($Operand){
		case 'on':
			$Return []= " $Field>='$Date1 00:00:00' ";
			$Return []= " $Field<='$Date1 23:59:59' ";
			break;
		case 'before':
			$Return []= " $Field<'$Date1 00:00:00' ";
			break;
		case 'after':
			$Return []= " $Field>'$Date1 23:59:59' ";
			break;
		case 'between':
			$Return []= " $Field>='$Date1 00:00:00' ";
			$Return []= " $Field<='$Date2 00:00:00' ";
			break;
	}

	return $Return;
}


function num_compare($Field, $Operand, $Num1, $Num2 = ''){
	
	if($Num1!=0){
		$Num1 = db_string($Num1);
	}
	if($Num2!=0){
		$Num2 = db_string($Num2);
	}
	
	$Return = array();

	switch($Operand){
		case 'equal':
			$Return []= " $Field='$Num1' ";
			break;
		case 'above':
			$Return []= " $Field>'$Num1' ";
			break;
		case 'below':
			$Return []= " $Field<'$Num1' ";
			break;
		case 'between':
			$Return []= " $Field>'$Num1' ";
			$Return []= " $Field<'$Num2' ";
			break;
		default:
			print_r($Return);
			die();
	}
	return $Return;
}

// Arrays, regexes, and all that fun stuff we can use for validation, form generation, etc

$DateChoices = array('inarray'=>array('on', 'before', 'after', 'between'));
$SingleDateChoices = array('inarray'=>array('on', 'before', 'after'));
$NumberChoices = array('inarray'=>array('equal', 'above', 'below', 'between', 'buffer'));
$YesNo = array('inarray'=>array('any', 'yes', 'no'));
$OrderVals = array('inarray'=>array('Username', 'Ratio', 'IP', 'Email', 'Joined', 'Last Seen', 'Uploaded', 'Downloaded', 'Invites', 'Snatches'));
$WayVals = array('inarray'=>array('Ascending', 'Descending'));

if(count($_GET)){
	$DateRegex = array('regex'=>'/\d{4}-\d{2}-\d{2}/');

	$ClassIDs = array();
	$SecClassIDs = array();
	foreach ($Classes as $ClassID => $Value) {
		if ($Value['Secondary']) {
			$SecClassIDs[]=$ClassID;
		} else {
			$ClassIDs[]=$ClassID;
		}
	}

	$Val->SetFields('comment','0','string','Comment is too long.', array('maxlength'=>512));
	$Val->SetFields('disabled_invites', '0', 'inarray', 'Invalid disabled_invites field', $YesNo);


	$Val->SetFields('joined', '0', 'inarray', 'Invalid joined field', $DateChoices);
	$Val->SetFields('join1', '0', 'regex', 'Invalid join1 field', $DateRegex);
	$Val->SetFields('join2', '0', 'regex', 'Invalid join2 field', $DateRegex);

	$Val->SetFields('lastactive', '0', 'inarray', 'Invalid lastactive field', $DateChoices);
	$Val->SetFields('lastactive1', '0', 'regex', 'Invalid lastactive1 field', $DateRegex);
	$Val->SetFields('lastactive2', '0', 'regex', 'Invalid lastactive2 field', $DateRegex);

	$Val->SetFields('ratio', '0', 'inarray', 'Invalid ratio field', $NumberChoices);
	$Val->SetFields('uploaded', '0', 'inarray', 'Invalid uploaded field', $NumberChoices);
	$Val->SetFields('downloaded', '0', 'inarray', 'Invalid downloaded field', $NumberChoices);
	//$Val->SetFields('snatched', '0', 'inarray', 'Invalid snatched field', $NumberChoices);

	$Val->SetFields('matchtype', '0', 'inarray', 'Invalid matchtype field', array('inarray'=>array('strict', 'fuzzy', 'regex')));


	$Val->SetFields('enabled', '0', 'inarray', 'Invalid enabled field', array('inarray'=>array('', 0, 1, 2)));
	$Val->SetFields('class', '0', 'inarray', 'Invalid class', array('inarray'=>$ClassIDs));
	$Val->SetFields('secclass', '0', 'inarray', 'Invalid class', array('inarray'=>$SecClassIDs));
	$Val->SetFields('donor', '0', 'inarray', 'Invalid donor field', $YesNo);
	$Val->SetFields('warned', '0', 'inarray', 'Invalid warned field', $YesNo);
	$Val->SetFields('disabled_uploads', '0', 'inarray', 'Invalid disabled_uploads field', $YesNo);

	$Val->SetFields('order', '0', 'inarray', 'Invalid ordering', $OrderVals);
	$Val->SetFields('way', '0', 'inarray', 'Invalid way', $WayVals);
	
	$Val->SetFields('passkey', '0', 'string', 'Invalid passkey', array('maxlength'=>32));
	$Val->SetFields('avatar', '0', 'string', 'Avatar URL too long', array('maxlength'=>512));
	$Val->SetFields('stylesheet', '0', 'inarray', 'Invalid stylesheet', array_unique(array_keys($Stylesheets)));
	$Val->SetFields('cc', '0', 'inarray', 'Invalid Country Code', array('maxlength'=>2));

	$Err = $Val->ValidateForm($_GET);

	if(!$Err){
		// Passed validation. Let's rock.
		$RunQuery = false; // if we should run the search

		if(isset($_GET['matchtype']) && $_GET['matchtype'] == 'strict'){
			$Match = ' = ';
		} elseif(isset($_GET['matchtype']) && $_GET['matchtype'] == 'regex') {
			$Match = ' REGEXP ';
		} else {
			$Match = ' LIKE ';
		}

		$OrderTable = array('Username'=>'um1.Username', 'Joined'=>'ui1.JoinDate', 'Email'=>'um1.Email', 'IP'=>'um1.IP', 'Last Seen'=>'um1.LastAccess');

		$WayTable = array('Ascending'=>'ASC', 'Descending'=>'DESC');

		$Where = array();
		$Having = array();
		$Join = array();
		$Group = array();
		$Distinct = '';
		$Order = '';


		$SQL = 'SQL_CALC_FOUND_ROWS
			um1.ID,
			um1.Username,
			um1.Email,
			um1.IP,
			ui1.JoinDate,
			um1.LastAccess
			FROM users_main AS um1 JOIN users_info AS ui1 ON ui1.UserID=um1.ID ';

		if(!empty($_GET['username'])){
			$Where[]='um1.Username'.$Match.wrap($_GET['username']);
		}

		if(!empty($_GET['email'])){
			if(isset($_GET['email_history'])){
				$Distinct = 'DISTINCT ';
				$Join['he']=' JOIN users_history_emails AS he ON he.UserID=um1.ID ';
				$Where[]= ' he.Email '.$Match.wrap($_GET['email']);
					$Where[] = " he.Email NOT LIKE '%what.cd'";
			} else {
				$Where[]='um1.Email'.$Match.wrap($_GET['email']);
			}
		}
		$Where[] = " um1.Email NOT LIKE '%what.cd'";

		if (!empty($_GET['email_cnt'])) {
			$Query = "SELECT UserID FROM users_history_emails GROUP BY UserID HAVING COUNT(DISTINCT Email) ";
			if ($_GET['emails_opt'] === 'equal') {
				$operator = '=';
			}
			if ($_GET['emails_opt'] === 'above') {
				$operator = '>';
			}
			if ($_GET['emails_opt'] === 'below') {
				$operator = '<';
			}
			$Query .= $operator." ".$_GET['email_cnt'];
			$DB->query($Query);
			$Users = implode(',', $DB->collect('UserID'));
			if (!empty($Users)) {
				$Where[] = "um1.ID IN (".$Users.")";
			}
		}


		if(!empty($_GET['ip'])){
			if(isset($_GET['ip_history'])){
				$Distinct = 'DISTINCT ';
				$Join['hi']=' JOIN users_history_ips AS hi ON hi.UserID=um1.ID ';
				$Where[]= ' hi.IP '.$Match.wrap($_GET['ip'], '', true);
				$Where[] = " hi.IP <> '127.0.0.1'";
				$Where[] = " hi.IP <> '0.0.0.0'";
			} else {
				$Where[]='um1.IP'.$Match.wrap($_GET['ip'], '', true);
			}
		}
		
		//Search for only user to torrent master classes. 
		$Where[] = " um1.IP <> '127.0.0.1'";
		$Where[] = " um1.IP <> '0.0.0.0'";
		$Where[]=" um1.PermissionID BETWEEN 2 AND 5 OR um1.PermissionID = 7";
		

		if($OrderTable[$_GET['order']] && $WayTable[$_GET['way']]){
			$Order = ' ORDER BY '.$OrderTable[$_GET['order']].' '.$WayTable[$_GET['way']].' ';
		}

		//---------- Finish generating the search string

		$SQL = 'SELECT '.$Distinct.$SQL;
		$SQL .= implode(' ', $Join);
		
		if(count($Where)){
			$SQL .= ' WHERE '.implode(' AND ', $Where);
		}
		
		if(count($Having)){
			$SQL .= ' HAVING '.implode(' AND ', $Having);
		}
		$SQL .= $Order;
		
		if(count($Where)>0 || count($Join)>0 || count($Having)>0){
			$RunQuery = true;
		}

		list($Page,$Limit) = page_limit(USERS_PER_PAGE);
		$SQL.=" LIMIT $Limit";
	} else { error($Err); }

}
show_header('User search');
?>
<div class="thin">
	<form action="user.php" method="get">
		<input type="hidden" name="action" value="search" />
		<table class="layout">
			<tr>
				<td class="label nobr">Username:</td>
				<td width="24%">
					<input type="text" name="username" size="20" value="<?=display_str($_GET['username'])?>" />
				</td>
			</tr>
			<tr>
				<td class="label nobr">Email:</td>
				<td>
					<input type="text" name="email" size="20" value="<?=display_str($_GET['email'])?>" />
				</td>
			</tr>
			<tr>
				<td class="label nobr">IP:</td>
				<td>
					<input type="text" name="ip" size="20" value="<?=display_str($_GET['ip'])?>" />
				</td>
			<tr>
				<td class="label nobr">Extra:</td>
				<td>
					<input type="checkbox" name="ip_history" id="ip_history"<? if($_GET['ip_history']){ echo ' checked="checked"'; }?> />
					<label for="ip_history">IP History</label>

					<input type="checkbox" name="email_history" id="email_history"<? if($_GET['email_history']){ echo ' checked="checked"'; }?> />
					<label for="email_history">Email History</label>
				</td>
			</tr>

			<tr>
				<td class="label nobr">Type</td>
				<td>
					Strict <input type="radio" name="matchtype" value="strict"<? if($_GET['matchtype'] == 'strict' || !$_GET['matchtype']){ echo ' checked="checked"'; } ?> /> |
					Fuzzy <input type="radio" name="matchtype" value="fuzzy"<? if($_GET['matchtype'] == 'fuzzy' || !$_GET['matchtype']){ echo ' checked="checked"'; } ?> /> |
					Regex <input type="radio" name="matchtype" value="regex"<? if($_GET['matchtype'] == 'regex'){ echo ' checked="checked"'; } ?> />
				</td>
				<td class="label nobr">Order:</td>
				<td class="nobr">
					<select name="order">
					<?
						foreach(array_shift($OrderVals) as $Cur){ ?>
						<option value="<?=$Cur?>"<? if(isset($_GET['order']) && $_GET['order'] == $Cur || (!isset($_GET['order']) && $Cur == 'Joined')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
					<?	}?>
					</select>
					<select name="way">
					<?	foreach(array_shift($WayVals) as $Cur){ ?>
						<option value="<?=$Cur?>"<? if(isset($_GET['way']) && $_GET['way'] == $Cur || (!isset($_GET['way']) && $Cur == 'Descending')) { echo ' selected="selected"'; } ?>><?=$Cur?></option>
					<?	}?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="6" class="center">
					<input type="submit" value="Search users" />
				</td>
			</tr>
		</table>
	</form>
</div>
<?
if($RunQuery){
	$Results = $DB->query($SQL);
	$DB->query('SELECT FOUND_ROWS();');
	list($NumResults) = $DB->next_record();
$DB->set_query_id($Results);
}

$Pages=get_pages($Page,$NumResults,USERS_PER_PAGE,11);
if ($Pages) { ?>
	<div class="linkbox pager"><?=$Pages?></div>
<? } ?>
<div class="box pad center">
	<table width="100%">
		<tr class="colhead">
			<td>Username</td>
			<td>IP</td>
			<td>Email</td>
			<td>Joined</td>
			<td>Last Seen</td>
		</tr>
<?
while(list($UserID, $Username, $Email, $IP, $JoinDate, $LastAccess) = $DB->next_record()){ ?>
		<tr>
			<td><?=format_username($UserID, true, true, true, true)?></td>
			<td><?=display_str($IP)?> (<?=get_cc($IP)?>)</td>
			<td><?=display_str($Email)?></td>
			<td><?=time_diff($JoinDate)?></td>
			<td><?=time_diff($LastAccess)?></td>
		</tr>
<?
}
?>
	</table>
<? if ($Pages) { ?>
	<div class="linkbox pager"><?=$Pages?></div>
<? } ?>	
</div>
<?
show_footer();
?>
