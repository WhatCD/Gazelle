<?
/**************************************************************************/
/*-- Invite tree class -----------------------------------------------------



***************************************************************************/

class INVITE_TREE {
	var $UserID = 0;
	var $Visible = true;
	
	// Set things up
	function INVITE_TREE($UserID, $Options = array()){
		$this->UserID = $UserID;
		if($Options['visible'] === false){
			$this->Visible = false;
		}
	}
	
	function make_tree(){
		$UserID = $this->UserID;
		global $DB;
?>
		<div class="invitetree pad">
<?
		$DB->query("SELECT 
			t1.TreePosition, 
			t1.TreeID, 
			t1.TreeLevel, 
			(SELECT 
				t2.TreePosition FROM invite_tree AS t2 
				WHERE TreeID=t1.TreeID AND TreeLevel=t1.TreeLevel AND t2.TreePosition>t1.TreePosition 
				ORDER BY TreePosition LIMIT 1
			) AS MaxPosition
			FROM invite_tree AS t1
			WHERE t1.UserID=$UserID");
		
		list($TreePosition, $TreeID, $TreeLevel, $MaxPosition) = $DB->next_record();
		if(!$MaxPosition){ $MaxPosition = 1000000; } // $MaxPermission is null if the user is the last one in that tree on that level
		if(!$TreeID){ return; }
		$DB->query("
			SELECT 
			it.UserID, 
			Username,
			Donor,
			Warned,
			Enabled,
			PermissionID,
			Uploaded,
			Downloaded,
			Paranoia,
			TreePosition,
			TreeLevel
			FROM invite_tree AS it
			JOIN users_main AS um ON um.ID=it.UserID
			JOIN users_info AS ui ON ui.UserID=it.UserID
			WHERE TreeID=$TreeID
			AND TreePosition>$TreePosition
			AND TreePosition<$MaxPosition
			AND TreeLevel>$TreeLevel
			ORDER BY TreePosition");
		
		$PreviousTreeLevel = $TreeLevel;
		
		// Stats for the summary
		$MaxTreeLevel = $TreeLevel; // The deepest level (this changes)
		$OriginalTreeLevel = $TreeLevel; // The level of the user we're viewing
		$BaseTreeLevel = $TreeLevel + 1; // The level of users invited by our user
		$Count = 0;
		$Branches = 0;
		$DisabledCount = 0;
		$DonorCount = 0;
		$ParanoidCount = 0;
		$TotalUpload = 0;
		$TotalDownload = 0;
		$TopLevelUpload = 0;
		$TopLevelDownload = 0;
		
		$ClassSummary = array();
		global $Classes;
		foreach ($Classes as $ClassID => $Val) {
			$ClassSummary[$ClassID] = 0;
		}
		
		// We store this in an output buffer, so we can show the summary at the top without having to loop through twice
		ob_start();
		while(list($ID, $Username, $Donor, $Warned, $Enabled, $Class, $Uploaded, $Downloaded, $Paranoia, $TreePosition, $TreeLevel) = $DB->next_record()){ 
			
			// Do stats
			$Count++;
			
			if($TreeLevel > $MaxTreeLevel){
				$MaxTreeLevel = $TreeLevel;
			}
			
			if($TreeLevel == $BaseTreeLevel){
				$Branches++;
				$TopLevelUpload += $Uploaded;
				$TopLevelDownload += $Downloaded;
			}
			
			$ClassSummary[$Class]++;
			if($Enabled == 2){
				$DisabledCount++;
			}
			if($Donor){
				$DonorCount++;
			}
			
			// Manage tree depth
			if($TreeLevel > $PreviousTreeLevel){
				for($i = 0; $i<$TreeLevel-$PreviousTreeLevel; $i++){ echo "<ul class=\"invitetree\">\n"; }
			} elseif($TreeLevel < $PreviousTreeLevel){
				for($i = 0; $i<$PreviousTreeLevel-$TreeLevel; $i++){ echo "</ul>\n"; }
			}
?>
			<li>
				<strong><?=format_username($ID, $Username, $Donor, $Warned, $Enabled == 2 ? false : true, $Class)?></strong>
<?
			if(check_paranoia(array('uploaded', 'downloaded'), $Paranoia, $UserClass)) {
				$TotalUpload += $Uploaded;
				$TotalDownload += $Downloaded;
?>
				&nbsp;Uploaded: <strong><?=get_size($Uploaded)?></strong>
				&nbsp;Downloaded: <strong><?=get_size($Downloaded)?></strong>
				&nbsp;Ratio: <strong><?=ratio($Uploaded, $Downloaded)?></strong>
<?
			} else {
				$ParanoidCount++;
?>
				&nbsp;Paranoia: <strong><?=number_format($Paranoia) ?></strong>
<?
			}
?>			
			</li>
<?			$PreviousTreeLevel = $TreeLevel;
		} 
		$Tree = ob_get_clean();
		if($Count){
		
?> 		<p style="font-weight: bold;">
			This tree has <?=$Count?> entries, <?=$Branches?> branches, and a depth of <?=$MaxTreeLevel - $OriginalTreeLevel?>.
			It has
<?
			$ClassStrings = array();
			foreach ($ClassSummary as $ClassID => $ClassCount) {
				if($ClassCount == 0) { continue; }
				$LastClass = make_class_string($ClassID);
				if($ClassCount>1) { 
					if($LastClass == "Torrent Celebrity") {
						 $LastClass = 'Torrent Celebrities';
					} else {
						$LastClass.='s'; 
					}
				}
				$LastClass= $ClassCount.' '.$LastClass.' (' . number_format(($ClassCount/$Count)*100) . '%)';
				
				$ClassStrings []= $LastClass;
			}
			if(count($ClassStrings)>1){
				array_pop($ClassStrings);
				echo implode(', ', $ClassStrings);
				echo ' and '.$LastClass;
			} else {
				echo $LastClass;
			}
			echo '. ';
			echo $DisabledCount;
			echo ($DisabledCount==1)?' user is':' users are';
			echo ' disabled (';
			if($DisabledCount == 0) { echo '0%)'; }
			else { echo number_format(($DisabledCount/$Count)*100) . '%)';}
			echo ', and ';
			echo $DonorCount;
			echo ($DonorCount==1)?' user has':' users have';
			echo ' donated (';
			if($DonorCount == 0) { echo '0%)'; }
			else { echo number_format(($DonorCount/$Count)*100) . '%)';}
			echo '. </p>';
			
			echo '<p style="font-weight: bold;">';
			echo 'The total amount uploaded by the entire tree was '.get_size($TotalUpload);
			echo ', the total amount downloaded was '.get_size($TotalDownload);
			echo ', and the total ratio is '.ratio($TotalUpload, $TotalDownload).'. ';
			echo '</p>';
			
			echo '<p style="font-weight: bold;">';
			echo 'The total amount uploaded by direct invitees (the top level) was '.get_size($TopLevelUpload);
			echo ', the total amount downloaded was '.get_size($TopLevelDownload);
			echo ', and the total ratio is '.ratio($TopLevelUpload, $TopLevelDownload).'. ';
			
			
			echo 'These numbers include the stats of paranoid users, and will be factored in to the invitation giving script.</p>';
			
			
			if($ParanoidCount){
				echo '<p style="font-weight: bold;">';
				echo $ParanoidCount;
				echo ($ParanoidCount==1)?' user (':' users (';
				echo number_format(($ParanoidCount/$Count)*100);
				echo '%) ';
				echo ($ParanoidCount==1)?'  is':' are';
				echo ' too paranoid to have their stats shown here, and ';
				echo ($ParanoidCount==1)?'  was':' were';
				echo ' not factored into the stats for the total tree.';
				echo '</p>';
			}
		}
		
?>
		<br />
		<?=$Tree?>
		</div>
<?
	}
}
?>
