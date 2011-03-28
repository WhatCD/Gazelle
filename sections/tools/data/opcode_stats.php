<?
//TODO: Accelerator cache keys, removed scripts (stats here and a class to manage them (we'd probably never use it, but I like completeness))
//INFO: http://bart.eaccelerator.net/doc/phpdoc/
//INFO: http://bakery.cakephp.org/articles/view/eaccelerator-cache-engine - pertains to potential todo for eAccelerator cache class
if(!check_perms('site_debug')) { error(403); }

if (!extension_loaded('eAccelerator')) {
	error('eAccelerator Extension not loaded.');
}

if (isset($_POST['submit'])) {
	if($_POST['cache'] == 1) {
		authorize();

		eaccelerator_caching(true);
	} else {
		eaccelerator_caching(false);
	}
	if (function_exists('eaccelerator_optimizer')) {
		if($_POST['optimize'] == 1) {
		
			authorize();

			eaccelerator_optimizer(true);
		} else {
			eaccelerator_optimizer(false);
		}
	}
	
	if (isset($_POST['clear'])) {
		authorize();
		eaccelerator_clear();
	}

	if (isset($_POST['clean'])) {
		authorize();
		eaccelerator_clean();
	}

	if (isset($_POST['purge'])) {
		authorize();
		eaccelerator_purge();
	}
}
$Opcode = eaccelerator_info();
$CachedScripts = eaccelerator_cached_scripts();
//$RemovedScripts = eaccelerator_removed_scripts();



show_header("Opcode Stats");
?>
<div class="thin">
	<div>
		<form action="" method="post">
			<div>
				<input type="hidden" name="action" value="opcode_stats" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			</div>
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td><strong>Enable:</strong></td>
					<td>
						<input type="checkbox" name="cache" value="1" id="cache"<?=($Opcode['cache'])?' checked="checked"':''?> />
						<label for="cache">Cache</label>
<? if (function_exists('eaccelerator_optimizer')) { ?>
						<input type="checkbox" name="optimize" value="1" id="optimize"<?=($Opcode['optimizer'])?' checked="checked"':''?> />
						<label for="optimize">Optimize</label>
<? } ?>
					</td>
				</tr>
				<tr>
					<td><strong>Controls:</strong></td>
					<td>
						<input type="checkbox" name="clear" value="clear" id="clear" />
						<label for="clear">Clear</label>
						<input type="checkbox" name="clean" value="clean" id="clean" />
						<label for="clean">Clean</label>
						<input type="checkbox" name="purge" value="purge" id="purge" />
						<label for="purge">Purge</label>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="center">
						<input type="submit" name="submit" value="Update" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
	<br /><br />
	<table>
		<tr>
			<td colspan="6" class="colhead">Status</td>
		</tr>
		<tr>
			<td>Total Storage:</td>
			<td><?=get_size($Opcode['memorySize'])?></td>
			<td>Used Storage:</td>
			<td><?=get_size($Opcode['memoryAllocated'])?> (<?=number_format(($Opcode['memoryAllocated']/$Opcode['memorySize'])*100, 3);?>%)</td>
			<td>Free Storage:</td>
			<td><?=get_size($Opcode['memoryAvailable'])?> (<?=number_format(($Opcode['memoryAvailable']/$Opcode['memorySize'])*100, 3);?>%)</td>
		</tr>
		<tr>
			<td>Cached Scripts:</td>
			<td><?=number_format($Opcode['cachedScripts'])?></td>
			<td>Removed Scripts:</td>
			<td><?=number_format($Opcode['removedScripts'])?></td>
			<td>Cached Keys:</td>
<? if (function_exists('eaccelerator_get')) { ?>
			<td><?=number_format($Opcode['cachedKeys'])?></td>
<? } else { ?>
			<td>N/A</td>
<? } ?>
		</tr>
	</table>
	<br /><br />
	<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
	<tr class="colhead">
		<td>File Path</td>
		<td>Age</td>
		<td>Size</td>
		<td>Hits</td>
	</tr>
<?
if(count($CachedScripts) == 0) { // Uh-oh, try again.
	echo '<tr><td colspan="5">No scripts cached.</td></tr>';
}
$Row = 'a'; // For the pretty colours
foreach ($CachedScripts as $Script) {
	list($FilePath, $Modified, $Size, $Reloads, $Uses, $Hits) = array_values($Script);
	$Row = ($Row == 'a') ? 'b' : 'a';
?>
		<tr class="row<?=$Row?>">
			<td><?=$FilePath?></td>
			<td><?=time_diff($Modified)?></td>
			<td><?=get_size($Size)?></td>
			<td><?=number_format($Hits)?></td>
		</tr>
<?
}
?>
	</table>
</div>
<? show_footer(); ?>
