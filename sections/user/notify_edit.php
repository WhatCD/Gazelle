<?
if(!check_perms('site_torrents_notify')){ error(403); }
show_header('Manage notifications');
?>
<div class="thin">
	<h2>Notify me of all new torrents with...<a href="torrents.php?action=notify">(View)</a></h2>
<?
$DB->query("SELECT ID, Label, Artists, ExcludeVA, NewGroupsOnly, Tags, NotTags, ReleaseTypes, Categories, Formats, Encodings, Media, FromYear, ToYear FROM users_notify_filters WHERE UserID='$LoggedUser[ID]' UNION ALL SELECT NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL");
$i = 0;
$NumFilters = $DB->record_count()-1;

$Notifications = $DB->to_array();

foreach($Notifications as $N) { //$N stands for Notifications
	$N['Artists']		= implode(', ', explode('|', substr($N['Artists'],1,-1)));
	$N['Tags']		= implode(', ', explode('|', substr($N['Tags'],1,-1)));
	$N['NotTags']		= implode(', ', explode('|', substr($N['NotTags'],1,-1)));
	$N['ReleaseTypes'] 	= explode('|', substr($N['ReleaseTypes'],1,-1));
	$N['Categories'] 	= explode('|', substr($N['Categories'],1,-1));
	$N['Formats'] 		= explode('|', substr($N['Formats'],1,-1));
	$N['Encodings'] 	= explode('|', substr($N['Encodings'],1,-1));
	$N['Media'] 		= explode('|', substr($N['Media'],1,-1));
	if($N['FromYear'] ==0) { $N['FromYear'] = ''; }
	if($N['ToYear'] ==0) { $N['ToYear'] = ''; }
	$i++;

	if($i>$NumFilters && $NumFilters>0){ ?>
			<h3>Create a new notification filter</h3>
<?	} elseif($NumFilters>0) { ?>
			<h3>
				<a href="feeds.php?feed=torrents_notify_<?=$N['ID']?>_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode($N['Label'])?>"><img src="<?=STATIC_SERVER?>/common/symbols/rss.png" alt="RSS feed" /></a>
				<?=display_str($N['Label'])?>
				<a href="user.php?action=notify_delete&amp;id=<?=$N['ID']?>&amp;auth=<?=$LoggedUser['AuthKey']?>">(Delete)</a>
			</h3>
<?	} ?>
	<form action="user.php" method="post">
		<input type="hidden" name="action" value="notify_handle" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table>
<?	if($i>$NumFilters){ ?>
			<tr>
				<td class="label"><strong>Label</strong></td>
				<td>
					<input type="text" name="label" style="width: 100%" />
					<p class="min_padding">A label for the filter set, to tell different filters apart.</p>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<strong>All fields below here are optional</strong>
				</td>
			</tr>
<?	} else { ?>
			<input type="hidden" name="id" value="<?=$N['ID']?>" />
<?	} ?>
			<tr>
				<td class="label"><strong>One of these artists</strong></td>
				<td>
					<textarea name="artists" style="width:100%" rows="5"><?=display_str($N['Artists'])?></textarea>
					<p class="min_padding">Comma-separated list - eg. <em>Pink Floyd, Led Zeppelin, Neil Young</em></p>
					<input type="checkbox" name="excludeva" id="excludeva_<?=$N['ID']?>"<? if($N['ExcludeVA']=="1") { echo ' checked="checked"';} ?> />
					<label for="excludeva_<?=$N['ID']?>">Exclude Various Artists releases</label>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>At least one of these tags</strong></td>
				<td>
					<textarea name="tags" style="width:100%" rows="2"><?=display_str($N['Tags'])?></textarea>
					<p class="min_padding">Comma-separated list - eg. <em>rock, jazz, pop</em></p>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>None of these tags</strong></td>
				<td>
					<textarea name="nottags" style="width:100%" rows="2"><?=display_str($N['NotTags'])?></textarea>
					<p class="min_padding">Comma-separated list - eg. <em>rock, jazz, pop</em></p>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Only these categories</strong></td>
				<td>
<?	foreach($Categories as $Category){ ?>
					<input type="checkbox" name="categories[]" id="<?=$Category?>_<?=$N['ID']?>" value="<?=$Category?>"<? if(in_array($Category, $N['Categories'])) { echo ' checked="checked"';} ?> />
					<label for="<?=$Category?>_<?=$N['ID']?>"><?=$Category?></label>
<?	} ?>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Only these types</strong></td>
				<td>
<?	foreach($ReleaseTypes as $ReleaseType){ ?>
					<input type="checkbox" name="releasetypes[]" id="<?=$ReleaseType?>_<?=$N['ID']?>" value="<?=$ReleaseType?>"<? if(in_array($ReleaseType, $N['ReleaseTypes'])) { echo ' checked="checked"';} ?> />
					<label for="<?=$ReleaseType?>_<?=$N['ID']?>"><?=$ReleaseType?></label>
<?	} ?>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Only these formats</strong></td>
				<td>
<?	foreach($Formats as $Format){ ?>
					<input type="checkbox" name="formats[]" id="<?=$Format?>_<?=$N['ID']?>" value="<?=$Format?>"<? if(in_array($Format, $N['Formats'])) { echo ' checked="checked"';} ?> />
					<label for="<?=$Format?>_<?=$N['ID']?>"><?=$Format?></label>
<?	} ?>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Only these bitrates</strong></td>
				<td>
<?	foreach($Bitrates as $Bitrate){ ?>
					<input type="checkbox" name="bitrates[]" id="<?=$Bitrate?>_<?=$N['ID']?>" value="<?=$Bitrate?>"<? if(in_array($Bitrate, $N['Encodings'])) { echo ' checked="checked"';} ?> />
					<label for="<?=$Bitrate?>_<?=$N['ID']?>"><?=$Bitrate?></label>
<?	} ?>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Only these media</strong></td>
				<td>
<?	foreach($Media as $Medium){ ?>
					<input type="checkbox" name="media[]" id="<?=$Medium?>_<?=$N['ID']?>" value="<?=$Medium?>"<? if(in_array($Medium, $N['Media'])) { echo ' checked="checked"';} ?> />
					<label for="<?=$Medium?>_<?=$N['ID']?>"><?=$Medium?></label>
<?	} ?>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Between the years</strong></td>
				<td>
					<input type="text" name="fromyear" value="<?=$N['FromYear']?>" size="6" />
					and
					<input type="text" name="toyear" value="<?=$N['ToYear']?>" size="6" />
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Only new releases</strong></td>
				<td>
					<input type="checkbox" name="newgroupsonly" id="newgroupsonly_<?=$N['ID']?>"<? if($N['NewGroupsOnly']=="1") { echo ' checked="checked"';} ?> />
<label for="newgroupsonly_<?=$N['ID']?>">Only notify for new releases, not new formats</label>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="<?=($i>$NumFilters)?'Create filter':'Update filter'?>" />
				</td>
			</tr>
		</table>
	</form>
	<br /><br />
<? } ?>
</div>
<?
show_footer();
?>
