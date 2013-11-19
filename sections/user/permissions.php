<?
//TODO: Redo HTML
if (!check_perms('admin_manage_permissions')) {
	error(403);
}
if (!isset($_REQUEST['userid']) || !is_number($_REQUEST['userid'])) {
	error(404);
}

include(SERVER_ROOT."/classes/permissions_form.php");

list($UserID, $Username, $PermissionID) = array_values(Users::user_info($_REQUEST['userid']));

$DB->query("
	SELECT CustomPermissions
	FROM users_main
	WHERE ID = '$UserID'");

list($Customs) = $DB->next_record(MYSQLI_NUM, false);


$Defaults = Permissions::get_permissions_for_user($UserID, array());

$Delta = array();
if (isset($_POST['action'])) {
	authorize();

	foreach ($PermissionsArray as $Perm => $Explaination) {
		$Setting = isset($_POST["perm_$Perm"]) ? 1 : 0;
		$Default = isset($Defaults[$Perm]) ? 1 : 0;
		if ($Setting != $Default) {
			$Delta[$Perm] = $Setting;
		}
	}
	if (!is_number($_POST['maxcollages']) && !empty($_POST['maxcollages'])) {
		error("Please enter a valid number of extra personal collages");
	}
	$Delta['MaxCollages'] = $_POST['maxcollages'];

	$Cache->begin_transaction("user_info_heavy_$UserID");
	$Cache->update_row(false, array('CustomPermissions' => $Delta));
	$Cache->commit_transaction(0);
	$DB->query("
		UPDATE users_main
		SET CustomPermissions = '".db_string(serialize($Delta))."'
		WHERE ID = '$UserID'");
} elseif (!empty($Customs)) {
	$Delta = unserialize($Customs);
}

$Permissions = array_merge($Defaults, $Delta);
$MaxCollages = $Customs['MaxCollages'] + $Delta['MaxCollages'];

function display_perm($Key, $Title) {
	global $Defaults, $Permissions;
	$Perm = "<input id=\"default_$Key\" type=\"checkbox\" disabled=\"disabled\"";
	if (isset($Defaults[$Key]) && $Defaults[$Key]) {
		$Perm .= ' checked="checked"';
	}
	$Perm .= " /><input type=\"checkbox\" name=\"perm_$Key\" id=\"$Key\" value=\"1\"";
	if (isset($Permissions[$Key]) && $Permissions[$Key]) {
		$Perm .= ' checked="checked"';
	}
	$Perm .= " /> <label for=\"$Key\">$Title</label><br />";
	echo "$Perm\n";
}

View::show_header("$Username &gt; Permissions");
?>
<script type="text/javascript">//<![CDATA[
function reset() {
	for (i = 0; i < $('#permissionsform').raw().elements.length; i++) {
		element = $('#permissionsform').raw().elements[i];
		if (element.id.substr(0, 8) == 'default_') {
			$('#' + element.id.substr(8)).raw().checked = element.checked;
		}
	}
}
//]]>
</script>
<div class="header">
	<h2><?=Users::format_username($UserID, false, false, false)?> &gt; Permissions</h2>
	<div class="linkbox">
		<a href="#" onclick="reset(); return false;" class="brackets">Defaults</a>
	</div>
</div>
<div class="box pad">
	<p>Before using permissions, please understand that it allows you to both add and remove access to specific features. If you think that to add access to a feature, you need to uncheck everything else, <strong>YOU ARE WRONG</strong>. The check boxes on the left, which are grayed out, are the standard permissions granted by their class (and donor/artist status). Any changes you make to the right side will overwrite this. It's not complicated, and if you screw up, click the "Defaults" link at the top. It will reset the user to their respective features granted by class, then you can select or deselect the one or two things you want to change. <strong>DO NOT DESELECT EVERYTHING.</strong> If you need further clarification, ask a developer before using this tool.</p>
</div>
<br />
<form class="manage_form" name="permissions" id="permissionsform" method="post" action="">
	<table class="layout permission_head">
		<tr>
			<td class="label">Extra personal collages</td>
			<td><input type="text" name="maxcollages" size="5" value="<?=($MaxCollages ? $MaxCollages : '0') ?>" /></td>
		</tr>
	</table>
	<input type="hidden" name="action" value="permissions" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<input type="hidden" name="id" value="<?=$_REQUEST['userid']?>" />
<?
permissions_form();
?>
</form>
<? View::show_footer(); ?>
