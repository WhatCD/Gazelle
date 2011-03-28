<?
//Diff function by Leto of StC.
function diff($OldText, $NewText) {
	$LineArrayOld = explode("\n",$OldText);
	$LineArrayNew = explode("\n",$NewText);
	$LineOffset = 0;
	$Result = array();
	
	foreach($LineArrayOld as $OldLine => $OldString) {
		$Key = $OldLine + $LineOffset;
		if($Key<0) { $Key = 0; }
		$Found = -1;

		while($Key<count($LineArrayNew)) {
			if ($OldString != $LineArrayNew[$Key]) {
				$Key++;
			} elseif ($OldString == $LineArrayNew[$Key]) {
				$Found = $Key;
				break;
			}
		}
		
		if($Found=='-1') { //we never found the old line in the new array
			$Result[] = '<span class="line_deleted">&larr; '.$OldString.'</span><br />';
			$LineOffset = $LineOffset - 1;
		} elseif ($Found ==$OldLine+$LineOffset) { 
			$Result[] = '<span class="line_unchanged"><strong>&#8597; '.$OldString.'</strong></span><br />';
		} elseif ($Found != $OldLine+$LineOffset) {
			if ($Found < $OldLine+$LineOffset) {
				$Result[] = '<span class="line_moved"><strong>&#8676; '.$OldString.'</strong></span><br />'; 
			} else { 
				$Result[] = '<span class="line_moved">&larr; '.$OldString.'</span><br />';
				$Key = $OldLine + $LineOffset;
				while ($Key<$Found) {
					$Result[] = '<span class="line_new"><strong>&rarr; '.$LineArrayNew[$Key].'</strong></span><br />';
					$Key++;
				}
				$Result[] = '<span class="line_moved"><strong>&rarr; '.$OldString.'</strong></span><br />'; 
			}
				$LineOffset = $Found-$OldLine; 
		}
	}
	if(count($LineArrayNew)>count($LineArrayOld) + $LineOffset) { 
		$Key = count($LineArrayOld) + $LineOffset;
		while($Key<count($LineArrayNew)) {
			$Result[] = '<span class="line_new"><strong>&rarr; '.$LineArrayNew[$Key].'</strong></span><br />';
			$Key++;
		}
	}
	return $Result;

}

function get_body($ID, $Rev) {
	global $DB, $Revision, $Body;
	if($Rev == $Revision) {
		$Str = $Body;
	} else {
		$DB->query("SELECT Body FROM wiki_revisions WHERE ID='$ID' AND Revision='$Rev'");
		if(!$DB->record_count()) { error(404); }
		list($Str) = $DB->next_record();
	}
	return $Str;
}

if (
	!isset($_GET['old']) || 
	!isset($_GET['new']) || 
	!isset($_GET['id']) || 
	!is_number($_GET['old']) || 
	!is_number($_GET['new']) || 
	!is_number($_GET['id']) || 
	$_GET['old'] > $_GET['new']
) { error(0); }

$ArticleID = $_GET['id'];

$Article = $Alias->article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName) = array_shift($Article);
if($Read > $LoggedUser['Class']){ error(404); }

show_header('Compare Article Revisions');
$Diff2 = get_body($ArticleID, $_GET['new']);
$Diff1 = get_body($ArticleID, $_GET['old']);
?>
<div class="thin">
	<h2>Compare <a href="wiki.php?action=article&id=<?=$ArticleID?>"><?=$Title?></a> Revisions</h2>
	<div class="box center_revision" id="center">
		<div class="body"><? foreach(diff($Diff1, $Diff2) AS $Line) { echo $Line; } ?></div>
	</div>
</div>
<?
show_footer();
?>
