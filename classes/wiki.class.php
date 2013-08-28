<?
/*########################################################################
##							 Wiki class									##
##########################################################################

Seeing as each page has to manage its wiki separately (for performance
reasons - JOINs instead of multiple queries), this class is rather bare.

The only useful function in here is revision_history(). It creates a
table with the revision history for that particular wiki page.


wiki.class depends on your wiki table being structured like this:

+------------+--------------+------+-----+----------------------+-------+
| Field		 | Type			| Null | Key | Default				| Extra |
+------------+--------------+------+-----+----------------------+-------+
| RevisionID | int(12)		| NO   | PRI | 0					|		|
| PageID	 | int(10)		| NO   | MUL | 0					|		|
| Body		 | text			| YES  |	 | NULL					|		|
| UserID	 | int(10)		| NO   | MUL | 0					|		|
| Summary	 | varchar(100) | YES  |	 | NULL					|		|
| Time		 | datetime		| NO   | MUL | 0000-00-00 00:00:00  |		|
+------------+--------------+------+-----+----------------------+-------+

It is also recommended that you have a field in the main table for
whatever the page is (e.g. details.php main table = torrents), so you can
do a JOIN.


########################################################################*/

class Wiki {

	public static function revision_history($Table = '', $PageID = 0, $BaseURL = '') {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			SELECT
				RevisionID,
				Summary,
				Time,
				UserID
			FROM $Table AS wiki
			WHERE wiki.PageID = $PageID
			ORDER BY RevisionID DESC");
?>
	<table cellpadding="6" cellspacing="1" border="0" width="100%" class="border">
		<tr class="colhead">
			<td>Revision</td>
			<td>Date</td>
			<td>User</td>
			<td>Summary</td>
		</tr>
<?
		$Row = 'a';
		while (list($RevisionID, $Summary, $Time, $UserID, $Username) = G::$DB->next_record()) {
			$Row = (($Row == 'a') ? 'b' : 'a');
?>
		<tr class="row<?=$Row?>">
			<td>
				<?= "<a href=\"$BaseURL&amp;revisionid=$RevisionID\">#$RevisionID</a>" ?>
			</td>
			<td>
				<?=$Time?>
			</td>
			<td>
				<?=Users::format_username($UserID, false, false, false)?>
			</td>
			<td>
				<?=($Summary ? $Summary : '(empty)')?>
			</td>
		</tr>
<?		} // while ?>
	</table>
<?
		G::$DB->set_query_id($QueryID);
	} // function
} // class
?>
