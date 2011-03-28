<?
/*########################################################################
##							 Wiki class							  ##
##########################################################################

Seeing as each page has to manage its wiki separately (for performance 
reasons - JOINs instead of multiple queries), this class is rather bare.

The only useful function in here is revision_history(). It creates a 
table with the revision history for that particular wiki page. 


class_wiki depends on your wiki table being structured like this:

+------------+---------------+------+-----+---------------------+-------+
| Field	  | Type		  | Null | Key | Default			 | Extra |
+------------+---------------+------+-----+---------------------+-------+
| RevisionID | int(12)	   | NO   | PRI | 0				   |	   |
| PageID	 | int(10)	   | NO   | MUL | 0				   |	   |
| Body	   | text		  | YES  |	 | NULL				|	   |
| UserID	 | int(10)	   | NO   | MUL | 0				   |	   |
| Summary	| varchar(100)  | YES  |	 | NULL				|	   |
| Time	   | datetime	  | NO   | MUL | 0000-00-00 00:00:00 |	   |
+------------+---------------+------+-----+---------------------+-------+

It is also recommended that you have a field in the main table for 
whatever the page is (eg. details.php main table = torrents), so you can
do a JOIN. 


########################################################################*/

class WIKI {
	var $Table = '';
	var $PageID = 0;
	var $BaseURL = '';
	function WIKI($Table, $PageID, $BaseURL = ''){
		$this->Table = $Table;
		$this->PageID = $PageID;
		$this->BaseURL = $BaseURL;
	}
	
	function revision_history(){
		global $DB;
			
		$BaseURL = $this->BaseURL;
		$DB->query("SELECT 
				RevisionID, 
				Summary,
				Time,
				UserID,
				users.Username
				FROM ".$this->Table." AS wiki
				JOIN users_main AS users ON users.ID = wiki.UserID
				WHERE wiki.PageID = ".$this->PageID."
				ORDER BY RevisionID DESC");
//----------------------------------------------- ?>
	<table cellpadding='6' cellspacing='1' border='0' width='100%' class='border'>
		<tr class="colhead">
			<td>Revision</td>
			<td>Summary</td>
		</tr>
<? //-----------------------------------------
		$Row = 'a';
		while(list($RevisionID, $Summary, $Time, $UserID, $Username) = $DB->next_record()){ 
			$Row = ($Row == 'a') ? 'b' : 'a';
//------------------------------------------------------ ?>
		<tr class="row<?=$Row?>">
			<td>
				<?= "<a href='$BaseURL&amp;revisionid=$RevisionID'>#$RevisionID</a>" ?>

			</td>
			<td>
				<strong>Edited by</strong> <a href="user.php?id=<?=$UserID?>"><?=$Username ?></a>
				<strong>Reason:</strong> <?=$Summary?>
			</td>
		</tr>
<? //---------------------------------------------------
		}
//-------------------------------------------- ?>
	</table>
<?
		
	}
} // class
?>