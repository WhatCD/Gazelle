<?
function display_perm($Key,$Title) {
	global $Values;
	$Perm='<input type="checkbox" name="perm_'.$Key.'" id="'.$Key.'" value="1"';
	if (!empty($Values[$Key])) { $Perm.=" checked"; }
	$Perm.=' /> <label for="'.$Key.'">'.$Title.'</label><br />';
	echo $Perm;
}

show_header('Manage Permissions','validate');

echo $Val->GenerateJS('permform');
?>
<form name="permform" id="permform" method="post" action="" onsubmit="return formVal();">
	<input type="hidden" name="action" value="permissions" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<input type="hidden" name="id" value="<?=display_str($_REQUEST['id']); ?>" />
	<div class="linkbox">
		[<a href="tools.php?action=permissions">Back to permission list</a>]
		[<a href="tools.php">Back to Tools</a>]
	</div>
	<table class="permission_head">
		<tr>
			<td class="label">Permission Name</td>
			<td><input type="text" name="name" id="name" value="<?=(!empty($Name) ? display_str($Name) : '')?>" /></td>
		</tr>
		<tr>
			<td class="label">Class Level</td>
			<td><input type="text" name="level" id="level" value="<?=(!empty($Level) ? display_str($Level) : '')?>" /></td>
		</tr>
		<tr>
			<td class="label">Show on Staff page</td>
			<td><input type="checkbox" name="displaystaff" value="1" <? if (!empty($DisplayStaff)) { ?>checked<? } ?> /></td>
		</tr>
		<tr>
			<td class="label">Maximum number of personal collages</td>
			<td><input type="text" name="maxcollages" size="5" value="<?=$Values['MaxCollages']?>" /></td>
		</tr>
<? if (is_numeric($_REQUEST['id'])) { ?>
		<tr>
			<td class="label">Current users in this class</td>
			<td><?=number_format($UserCount)?></td>
		</tr>
<? } ?>
	</table>
<?
include(SERVER_ROOT."/classes/permissions_form.php");
permissions_form();
?>
</form>
<? show_footer(); ?>
