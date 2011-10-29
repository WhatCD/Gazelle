</div>
<div id="footer">
<? 	if (!empty($Options['disclaimer'])) { ?>
	<br /><br />
	<div id="disclaimer_container" class="thin" style="text-align:center; margin-bottom:20px;">
		None of the files shown here are actually hosted on this server. The links are provided solely by this site's users. These BitTorrent files are meant for the distribution of backup files. By downloading the BitTorrent file, you are claiming that you own the original file. The administrator of this site (http://<?=NONSSL_SITE_URL?>) holds NO RESPONSIBILITY if these files are misused in any way and cannot be held responsible for what its users post, or any other actions of it.
	</div>
<?
	}
	if (count($UserSessions)>1) {
		foreach ($UserSessions as $ThisSessionID => $Session) {
			if ($ThisSessionID != $SessionID) {
				$LastActive = $Session;
				break;
			}
		}
	}
	
	$Load = sys_getloadavg();
?>
	<p>
		Site and design &copy; <?=date("Y")?> <?=SITE_NAME?>
	</p>
	<? if(!empty($LastActive)){ ?><p><a href="user.php?action=sessions" title="Manage Sessions">Last activity <?=time_diff($LastActive['LastUpdate'])?> from <?=$LastActive['IP']?>.</a></p><? } ?>
	<p>
		<strong>Time:</strong> <?=number_format(((microtime(true)-$ScriptStartTime)*1000),5)?> ms
		<strong>Used:</strong> <?=get_size(memory_get_usage(true))?>
		<strong>Load:</strong> <?=number_format($Load[0],2).' '.number_format($Load[1],2).' '.number_format($Load[2],2)?>
		<strong>Date:</strong> <?=date('M d Y, H:i')?>

	</p>
	</div>
<? if (DEBUG_MODE || check_perms('site_debug')) { ?>
	<!-- Begin Debugging -->
	<div id="site_debug">
<?
$Debug->flag_table();
$Debug->error_table();
$Debug->sphinx_table();
$Debug->query_table();
$Debug->cache_table();
$Debug->vars_table();
?>
	</div>
	<!-- End Debugging -->
<? } ?>

</div>
<div id="lightbox" class="lightbox hidden"></div>
<div id="curtain" class="curtain hidden"></div>

<!-- Extra divs, for stylesheet developers to add imagery -->
<div id="extra1"><span></span></div>
<div id="extra2"><span></span></div>
<div id="extra3"><span></span></div>
<div id="extra4"><span></span></div>
<div id="extra5"><span></span></div>
<div id="extra6"><span></span></div>
</body>
</html>
