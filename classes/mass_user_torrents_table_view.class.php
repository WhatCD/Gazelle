<?php

/**
 * This class outputs a table that can be used to sort torrents through a drag/drop
 * interface, an automatic column sorter, or manual imput.
 *
 * It places checkboxes to delete items.
 *
 * (It creates a div#thin.)
 *
 * It can be used for Bookmarks, Collages, or anywhere where torrents are managed.
 */
class MASS_USER_TORRENTS_TABLE_VIEW {
	/**
	 * Used to set text the page heading (h2 tag)
	 * @var string $Heading
	 */
	private $Heading = 'Manage Torrents';

	/**
	 * Sets the value of the input name="type"
	 * Later to be used as $_POST['type'] in a form processor
	 * @var string $EditType
	 */
	private $EditType;

	/**
	 * Flag for empty $TorrentList
	 * @var bool $HasTorrentList
	 */
	private $HasTorrents;

	/**
	 * Internal reference to the TorrentList
	 * @var array $TorrentList
	 */
	private $TorrentList;

	/**
	 * Ref. to $CollageDataList
	 * @var array $CollageDataList
	 */
	private $CollageDataList;

	/**
	 * Counter for number of groups
	 * @var in $NumGroups
	 */
	private $NumGroups = 0;

	/**
	 * When creating a new instance of this class, TorrentList and
	 * CollageDataList must be passed. Additionally, a heading can be added.
	 *
	 * @param array $TorrentList
	 * @param array $CollageDataList
	 * @param string $EditType
	 * @param string $Heading
	 */
	public function __construct (array &$TorrentList, array &$CollageDataList, $EditType, $Heading = null) {
		$this->set_heading($Heading);
		$this->set_edit_type($EditType);

		$this->TorrentList = $TorrentList;
		$this->CollageDataList = $CollageDataList;

		$this->HasTorrents = !empty($TorrentList);
		if (!$this->HasTorrents) {
			$this->no_torrents();
		}
	}

	private function no_torrents () {
?>
		<div class="thin">
			<div class="header">
				<h2>No torrents found.</h2>
			</div>
			<div class="box pad" align="center">
				<p>Add some torrents and come back later.</p>
			</div>
		</div>
<?
	}

	/**
	 * Renders a complete page and table
	 */
	public function render_all () {
		$this->header();
		$this->body();
		$this->footer();
	}

	/**
	 * Renders a comptele page/table header: div#thin, h2, scripts, notes,
	 * form, table, etc.
	 */
	public function header () {
		if ($this->HasTorrents) {
?>

<div class="thin">
	<div class="header">
		<h2><?=display_str($this->Heading)?></h2>
	</div>

	<table width="100%" class="layout">
		<tr class="colhead"><td id="sorting_head">Sorting</td></tr>
		<tr>
			<td id="drag_drop_textnote">
			<ul>
				<li>Click on the headings to organize columns automatically.</li>
				<li>Sort multiple columns simultaneously by holding down the shift key and clicking other column headers.</li>
				<li>Click and drag any row to change its order.</li>
				<li>Double-click on a row to check it.</li>
			</ul>
			</td>
		</tr>
	</table>

	<form action="bookmarks.php" method="post" id="drag_drop_collage_form">

<?			$this->buttons(); ?>

		<table id="manage_collage_table">
			<thead>
				<tr class="colhead">
					<th style="width: 7%;" data-sorter="false">Order</th>
					<th style="width: 1%;"><span><abbr class="tooltip" title="Current order">#</abbr></span></th>
					<th style="width: 1%;"><span>Year</span></th>
					<th style="width: 15%;" data-sorter="ignoreArticles"><span>Artist</span></th>
					<th data-sorter="ignoreArticles"><span>Torrent</span></th>
					<th style="width: 5%;" data-sorter="relativeTime"><span>Bookmarked</span></th>
					<th style="width: 1%;" id="check_all" data-sorter="false"><span>Remove</span></th>
				</tr>
			</thead>
			<tbody>
<?
		}
	}

	/**
	 * Closes header code
	 */
	public function footer () {
		if ($this->HasTorrents) {
?>

			</tbody>
		</table>

<?			$this->buttons(); ?>

		<div>
			<input type="hidden" name="action" value="mass_edit" />
			<input type="hidden" name="type" value="<?=display_str($this->EditType)?>" />
			<input type="hidden" name="auth" value="<?=G::$LoggedUser['AuthKey']?>" />
		</div>
	</form>
</div>

<?
		}
	}

	/**
	 * Formats data for use in row
	 *
	 */
	public function body () {
		if ($this->HasTorrents)
			foreach ($this->TorrentList as $GroupID => $Group) {
				$Artists = array();

				extract($Group);
				extract($this->CollageDataList[$GroupID]);

				$this->NumGroups++;

				$DisplayName = self::display_name($ExtendedArtists, $Artists, $VanityHouse);
				$TorrentLink = '<a href="torrents.php?id='.$GroupID.'" class="tooltip" title="View torrent">'.$Name.'</a>';
				$Year = $Year > 0 ? $Year : '';
				$DateAdded = date($Time);

				$this->row($Sort, $GroupID, $Year, $DisplayName, $TorrentLink, $DateAdded);
			}
	}

	/**
	 * Outputs a single row
	 *
	 * @param string|int $Sort
	 * @param string|int $GroupID
	 * @param string|int $GroupYear
	 * @param string $DisplayName
	 * @param string $TorrentLink
	 */
	public function row ($Sort, $GroupID, $GroupYear, $DisplayName, $TorrentLink, $DateAdded) {
		$CSS = $this->NumGroups % 2 === 0 ? 'rowa' : 'rowb';
?>

					<tr class="drag <?=$CSS?>" id="li_<?=$GroupID?>">
						<td>
							<input class="sort_numbers" type="text" name="sort[<?=$GroupID?>]" value="<?=$Sort?>" id="sort_<?=$GroupID?>" size="4" />
						</td>
						<td><?=$this->NumGroups?></td>
						<td><?=$GroupYear ? trim($GroupYear) : ' '?></td>
						<td><?=$DisplayName ? trim($DisplayName) : ' '?></td>
						<td><?=$TorrentLink ? trim($TorrentLink) : ' '?></td>
						<td class="nobr tooltip" title="<?=$DateAdded?>"><?=$DateAdded ? time_diff($DateAdded) : ' '?></td>
						<td class="center"><input type="checkbox" name="remove[<?=$GroupID?>]" value="" /></td>
					</tr>
<?
	}

	/**
	 * Parses a simple display name
	 *
	 * @param array $ExtendedArtists
	 * @param array $Artists
	 * @param string $VanityHouse
	 * @return string $DisplayName
	 */
	public static function display_name (array &$ExtendedArtists, array &$Artists, $VanityHouse) {
		$DisplayName = '';
		if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4])
				|| !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2], $ExtendedArtists[3]);
			$DisplayName = Artists::display_artists($ExtendedArtists, true, false);
		} elseif (count($Artists) > 0) {
			$DisplayName = Artists::display_artists(array('1'=>$Artists), true, false);
		}
		if ($VanityHouse) {
			$DisplayName .= ' [<abbr class="tooltip" title="This is a Vanity House release">VH</abbr>]';
		}
		return $DisplayName;
	}

	/**
	 * Renders buttons used at the top and bottom of the table
	 */
	public function buttons () {
?>
		<div class="drag_drop_save">
			<input type="submit" name="update" value="Update ranking" title="Save your rank" class="tooltip save_sortable_collage" />
			<input type="submit" name="delete" value="Delete checked" title="Remove items" class="tooltip save_sortable_collage" />
		</div>
<?
	}


	/**
	 * @param string $EditType
	 */
	public function set_edit_type ($EditType) {
		$this->EditType = $EditType;
	}

	/**
	 * Set's the current page's heading
	 * @param string $Heading
	 */
	public function set_heading ($Heading) {
		$this->Heading = $Heading;
	}
}
