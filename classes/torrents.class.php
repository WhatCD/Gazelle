<?
class Torrents {
	const FilelistDelim = 0xF7; // Hex for &divide; Must be the same as phrase_boundary in sphinx.conf!

	/*
	 * Function to get data and torrents for an array of GroupIDs.
	 * In places where the output from this is merged with sphinx filters, it will be in a different order.
	 *
	 * @param array $GroupIDs
	 * @param boolean $Return if false, nothing is returned. For priming cache.
	 * @param boolean $GetArtists if true, each group will contain the result of
	 *	Artists::get_artists($GroupID), in result[$GroupID]['ExtendedArtists']
	 * @param boolean $Torrents if true, each group contains a list of torrents, in result[$GroupID]['Torrents']
	 *
	 * @return array each row of the following format:
	 * GroupID => (
	 *	ID
	 *	Name
	 *	Year
	 *	RecordLabel
	 *	CatalogueNumber
	 *	TagList
	 *	ReleaseType
	 *	VanityHouse
	 *	WikiImage
	 *	CategoryID
	 *	Torrents => {
	 *		ID => {
	 *			GroupID, Media, Format, Encoding, RemasterYear, Remastered,
	 *			RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber, Scene,
	 *			HasLog, HasCue, LogScore, FileCount, FreeTorrent, Size, Leechers,
	 *			Seeders, Snatched, Time, HasFile, PersonalFL, IsSnatched
	 *		}
	 *	}
	 *	Artists => {
	 *		{
	 *			id, name, aliasid // Only main artists
	 *		}
	 *	}
	 *	ExtendedArtists => {
	 *		[1-6] => { // See documentation on Artists::get_artists
	 *			id, name, aliasid
	 *		}
	 *	}
	 *	Flags => {
	 *		IsSnatched
	 *	}
	 */
	public static function get_groups($GroupIDs, $Return = true, $GetArtists = true, $Torrents = true) {
		global $DB, $Cache, $Debug;

		// Make sure there's something in $GroupIDs, otherwise the SQL
		// will break
		if (count($GroupIDs) == 0) {
			return array('matches'=>array(),'notfound'=>array());
		}

		$Found = $NotFound = array_flip($GroupIDs);
		$Key = $Torrents ? 'torrent_group_' : 'torrent_group_light_';

		foreach ($GroupIDs as $GroupID) {
			$Data = $Cache->get_value($Key.$GroupID, true);
			if (!empty($Data) && (@$Data['ver'] == CACHE::GROUP_VERSION)) {
				unset($NotFound[$GroupID]);
				$Found[$GroupID] = $Data['d'];
			}
		}

		$IDs = implode(',',array_flip($NotFound));

		/*
		Changing any of these attributes returned will cause very large, very dramatic site-wide chaos.
		Do not change what is returned or the order thereof without updating:
			torrents, artists, collages, bookmarks, better, the front page,
		and anywhere else the get_groups function is used.
		Update self::array_group(), too
		*/

		if (count($NotFound) > 0) {
			$DB->query("
				SELECT
					g.ID, g.Name, g.Year, g.RecordLabel, g.CatalogueNumber, g.TagList, g.ReleaseType, g.VanityHouse, g.WikiImage, g.CategoryID
				FROM torrents_group AS g
				WHERE g.ID IN ($IDs)");

			while ($Group = $DB->next_record(MYSQLI_ASSOC, true)) {
				unset($NotFound[$Group['ID']]);
				$Found[$Group['ID']] = $Group;
				$Found[$Group['ID']]['Torrents'] = array();
				$Found[$Group['ID']]['Artists'] = array();
			}

			// Orphan torrents. There shouldn't ever be any
			if (count($NotFound) > 0) {
				foreach (array_keys($NotFound) as $GroupID) {
					unset($Found[$GroupID]);
				}
			}

			if ($Torrents) {
				$DB->query("
					SELECT
						ID, GroupID, Media, Format, Encoding, RemasterYear, Remastered, RemasterTitle,
						RemasterRecordLabel, RemasterCatalogueNumber, Scene, HasLog, HasCue, LogScore,
						FileCount, FreeTorrent, Size, Leechers, Seeders, Snatched, Time, ID AS HasFile
					FROM torrents AS t
					WHERE GroupID IN($IDs)
					ORDER BY GroupID, Remastered, (RemasterYear != 0) DESC, RemasterYear, RemasterTitle,
							RemasterRecordLabel, RemasterCatalogueNumber, Media, Format, Encoding, ID");
				while ($Torrent = $DB->next_record(MYSQLI_ASSOC, true)) {
					$Found[$Torrent['GroupID']]['Torrents'][$Torrent['ID']] = $Torrent;
				}

				// Cache it all
				foreach ($Found as $GroupID => $GroupInfo) {
					$Cache->cache_value('torrent_group_'.$GroupID,
							array('ver' => CACHE::GROUP_VERSION, 'd' => $GroupInfo), 0);
					$Cache->cache_value('torrent_group_light_'.$GroupID,
							array('ver' => CACHE::GROUP_VERSION, 'd' => $GroupInfo), 0);
				}

			} else {
				foreach ($Found as $Group) {
					$Cache->cache_value('torrent_group_light_'.$Group['ID'], array('ver' => CACHE::GROUP_VERSION, 'd' => $Found[$Group['ID']]), 0);
				}
			}
		}
		if ($GetArtists) {
			$Artists = Artists::get_artists($GroupIDs);
		} else {
			$Artists = array();
		}

		if ($Return) { // If we're interested in the data, and not just caching it
			foreach ($Artists as $GroupID => $Data) {
				if (array_key_exists(1, $Data) || array_key_exists(4, $Data) || array_key_exists(6, $Data)) {
					$Found[$GroupID]['Artists'] = isset($Data[1]) ? $Data[1] : null; // Only use main artists (legacy)
					for ($i = 1; $i <= 6; $i++) {
						$Found[$GroupID]['ExtendedArtists'][$i] = isset($Data[$i]) ? $Data[$i] : null;
					}
				}
				else {
					$Found[$GroupID]['ExtendedArtists'] = false;
				}
			}
			// Fetch all user specific torrent properties
			foreach ($Found as &$Group) {
				$Group['Flags'] = array('IsSnatched' => false);
				if (!empty($Group['Torrents'])) {
					foreach ($Group['Torrents'] as &$Torrent) {
						self::torrent_properties($Torrent, $Group['Flags']);
					}
				}
			}

			$Matches = array('matches'=>$Found, 'notfound'=>array_flip($NotFound));

			return $Matches;
		}
	}

	/**
	 * Returns a reconfigured array from a Torrent Group
	 *
	 * Use this with extract() instead of the volatile list($GroupID, ...)
	 * Then use the variables $GroupID, $GroupName, etc
	 *
	 * @example  extract(Torrents::array_group($SomeGroup));
	 * @param array $Group torrent group
	 * @return array Re-key'd array
	 */
	public static function array_group (array &$Group)
	{
		return array(
			'GroupID' => $Group['ID'],
			'GroupName' => $Group['Name'],
			'GroupYear' => $Group['Year'],
			'GroupCategoryID' => $Group['CategoryID'],
			'GroupRecordLabel' => $Group['RecordLabel'],
			'GroupCatalogueNumber' => $Group['CatalogueNumber'],
			'GroupVanityHouse' => $Group['VanityHouse'],
			'GroupFlags' => $Group['Flags'],
			'TagList' => $Group['TagList'],
			'ReleaseType' => $Group['ReleaseType'],
			'WikiImage' => $Group['WikiImage'],
			'Torrents' => $Group['Torrents'],
			'Artists' => $Group['Artists'],
			'ExtendedArtists' => $Group['ExtendedArtists']
		);
	}

	/**
	 * Supplements a torrent array with information that only concerns certain users and therefore cannot be cached
	 *
	 * @param array $Torrent torrent array preferably in the form used by Torrents::get_groups() or get_group_info()
	 * @param int $TorrentID
	 */
	public static function torrent_properties(&$Torrent, &$Flags) {
		$Torrent['PersonalFL'] = empty($Torrent['FreeTorrent']) && self::has_token($Torrent['ID']);
		if ($Torrent['IsSnatched'] = self::has_snatched($Torrent['ID'])) {
			$Flags['IsSnatched'] = true;
		}
	}


	/*
	 * Write to the group log.
	 *
	 * @param int $GroupID
	 * @param int $TorrentID
	 * @param int $UserID
	 * @param string $Message
	 * @param boolean $Hidden Currently does fuck all. TODO: Fix that.
	 */
	public static function write_group_log($GroupID, $TorrentID, $UserID, $Message, $Hidden) {
		global $DB, $Time;
		$DB->query("
			INSERT INTO group_log
				(GroupID, TorrentID, UserID, Info, Time, Hidden)
			VALUES
				($GroupID, $TorrentID, $UserID, '".db_string($Message)."', '".sqltime()."', $Hidden)");
	}


	/**
	 * Delete a torrent.
	 *
	 * @param int $ID The ID of the torrent to delete.
	 * @param int $GroupID Set it if you have it handy, to save a query. Otherwise, it will be found.
	 * @param string $OcelotReason The deletion reason for ocelot to report to users.
	 */
	public static function delete_torrent($ID, $GroupID = 0, $OcelotReason = -1) {
		global $DB, $Cache, $LoggedUser;
		if (!$GroupID) {
			$DB->query("
				SELECT GroupID, UserID
				FROM torrents
				WHERE ID='$ID'");
			list($GroupID, $UploaderID) = $DB->next_record();
		}
		if (empty($UserID)) {
			$DB->query("
				SELECT UserID
				FROM torrents
				WHERE ID='$ID'");
			list($UserID) = $DB->next_record();
		}

		$RecentUploads = $Cache->get_value('recent_uploads_'.$UserID);
		if (is_array($RecentUploads)) {
			foreach ($RecentUploads as $Key => $Recent) {
				if ($Recent['ID'] == $GroupID) {
					$Cache->delete_value('recent_uploads_'.$UserID);
				}
			}
		}


		$DB->query("
			SELECT info_hash
			FROM torrents
			WHERE ID = ".$ID);
		list($InfoHash) = $DB->next_record(MYSQLI_BOTH, false);
		$DB->query("
			DELETE FROM torrents
			WHERE ID = ".$ID);
		Tracker::update_tracker('delete_torrent', array('info_hash' => rawurlencode($InfoHash), 'id' => $ID, 'reason' => $OcelotReason));

		$Cache->decrement('stats_torrent_count');

		$DB->query("
			SELECT COUNT(ID)
			FROM torrents
			WHERE GroupID='$GroupID'");
		list($Count) = $DB->next_record();

		if ($Count == 0) {
			Torrents::delete_group($GroupID);
		} else {
			Torrents::update_hash($GroupID);
		}

		// Torrent notifications
		$DB->query("
			SELECT UserID
			FROM users_notify_torrents
			WHERE TorrentID='$ID'");
		while (list($UserID) = $DB->next_record()) {
			$Cache->delete_value('notifications_new_'.$UserID);
		}
		$DB->query("
			DELETE FROM users_notify_torrents
			WHERE TorrentID='$ID'");

		$DB->query("
			UPDATE reportsv2
			SET
				Status='Resolved',
				LastChangeTime='".sqltime()."',
				ModComment='Report already dealt with (Torrent deleted)'
			WHERE TorrentID = $ID
				AND Status != 'Resolved'");
		$Reports = $DB->affected_rows();
		if ($Reports) {
			$Cache->decrement('num_torrent_reportsv2', $Reports);
		}

		$DB->query("
			DELETE FROM torrents_files
			WHERE TorrentID = '$ID'");
		$DB->query("
			DELETE FROM torrents_bad_tags
			WHERE TorrentID = ".$ID);
		$DB->query("
			DELETE FROM torrents_bad_folders
			WHERE TorrentID = ".$ID);
		$DB->query("
			DELETE FROM torrents_bad_files
			WHERE TorrentID = ".$ID);
		$DB->query("
			DELETE FROM torrents_cassette_approved
			WHERE TorrentID = ".$ID);
		$DB->query("
			DELETE FROM torrents_lossymaster_approved
			WHERE TorrentID = ".$ID);
		$DB->query("
			DELETE FROM torrents_lossyweb_approved
			WHERE TorrentID = ".$ID);

		// Tells Sphinx that the group is removed
		$DB->query("
			REPLACE INTO sphinx_delta (ID, Time)
			VALUES ($ID, UNIX_TIMESTAMP())");

		$Cache->delete_value('torrent_download_'.$ID);
		$Cache->delete_value('torrent_group_'.$GroupID);
		$Cache->delete_value('torrents_details_'.$GroupID);
	}


	/**
	 * Delete a group, called after all of its torrents have been deleted.
	 * IMPORTANT: Never call this unless you're certain the group is no longer used by any torrents
	 *
	 * @param int $GroupID
	 */
	public static function delete_group($GroupID) {
		global $DB, $Cache;

		Misc::write_log("Group $GroupID automatically deleted (No torrents have this group).");

		$DB->query("
			SELECT CategoryID
			FROM torrents_group
			WHERE ID='$GroupID'");
		list($Category) = $DB->next_record();
		if ($Category == 1) {
			$Cache->decrement('stats_album_count');
		}
		$Cache->decrement('stats_group_count');



		// Collages
		$DB->query("
			SELECT CollageID
			FROM collages_torrents
			WHERE GroupID='$GroupID'");
		if ($DB->record_count() > 0) {
			$CollageIDs = $DB->collect('CollageID');
			$DB->query("
				UPDATE collages
				SET NumTorrents=NumTorrents-1
				WHERE ID IN (".implode(', ', $CollageIDs).')');
			$DB->query("
				DELETE FROM collages_torrents
				WHERE GroupID='$GroupID'");

			foreach ($CollageIDs as $CollageID) {
				$Cache->delete_value('collage_'.$CollageID);
			}
			$Cache->delete_value('torrent_collages_'.$GroupID);
		}

		// Artists
		// Collect the artist IDs and then wipe the torrents_artist entry
		$DB->query("
			SELECT ArtistID
			FROM torrents_artists
			WHERE GroupID = ".$GroupID);
		$Artists = $DB->collect('ArtistID');

		$DB->query("
			DELETE FROM torrents_artists
			WHERE GroupID='$GroupID'");

		foreach ($Artists as $ArtistID) {
			if (empty($ArtistID)) {
				continue;
			}
			// Get a count of how many groups or requests use the artist ID
			$DB->query("
				SELECT COUNT(ag.ArtistID)
				FROM artists_group as ag
					LEFT JOIN requests_artists AS ra ON ag.ArtistID=ra.ArtistID
				WHERE ra.ArtistID IS NOT NULL
					AND ag.ArtistID = '$ArtistID'");
			list($ReqCount) = $DB->next_record();
			$DB->query("
				SELECT COUNT(ag.ArtistID)
				FROM artists_group as ag
					LEFT JOIN torrents_artists AS ta ON ag.ArtistID=ta.ArtistID
				WHERE ta.ArtistID IS NOT NULL
					AND ag.ArtistID = '$ArtistID'");
			list($GroupCount) = $DB->next_record();
			if (($ReqCount + $GroupCount) == 0) {
				//The only group to use this artist
				Artists::delete_artist($ArtistID);
			} else {
				//Not the only group, still need to clear cache
				$Cache->delete_value('artist_groups_'.$ArtistID);
			}
		}

		// Requests
		$DB->query("
			SELECT ID
			FROM requests
			WHERE GroupID='$GroupID'");
		$Requests = $DB->collect('ID');
		$DB->query("
			UPDATE requests
			SET GroupID = NULL
			WHERE GroupID = '$GroupID'");
		foreach ($Requests as $RequestID) {
			$Cache->delete_value('request_'.$RequestID);
		}

		$DB->query("DELETE FROM torrents_group WHERE ID='$GroupID'");
		$DB->query("DELETE FROM torrents_tags WHERE GroupID='$GroupID'");
		$DB->query("DELETE FROM torrents_tags_votes WHERE GroupID='$GroupID'");
		$DB->query("DELETE FROM torrents_comments WHERE GroupID='$GroupID'");
		$DB->query("DELETE FROM bookmarks_torrents WHERE GroupID='$GroupID'");
		$DB->query("DELETE FROM wiki_torrents WHERE PageID='$GroupID'");

		$Cache->delete_value('torrents_details_'.$GroupID);
		$Cache->delete_value('torrent_group_'.$GroupID);
		$Cache->delete_value('groups_artists_'.$GroupID);
	}


	/**
	 * Update the cache and sphinx delta index to keep everything up to date.
	 *
	 * @param int $GroupID
	 */
	public static function update_hash($GroupID) {
		global $DB, $Cache;
		$DB->query("
			UPDATE torrents_group
			SET TagList=(
					SELECT REPLACE(GROUP_CONCAT(tags.Name SEPARATOR ' '),'.','_')
					FROM torrents_tags AS t
						INNER JOIN tags ON tags.ID=t.TagID
					WHERE t.GroupID='$GroupID'
					GROUP BY t.GroupID
					)
			WHERE ID='$GroupID'");

		// Fetch album vote score
		$DB->query("
			SELECT Score
			FROM torrents_votes
			WHERE GroupID=$GroupID");
		if ($DB->record_count()) {
			list($VoteScore) = $DB->next_record();
		} else {
			$VoteScore = 0;
		}

		// Fetch album artists
		$DB->query("
			SELECT GROUP_CONCAT(aa.Name separator ' ')
			FROM torrents_artists AS ta
				JOIN artists_alias AS aa ON aa.AliasID=ta.AliasID
			WHERE ta.GroupID=$GroupID
				AND ta.Importance IN ('1', '4', '5', '6')
			GROUP BY ta.GroupID");
		if ($DB->record_count()) {
			list($ArtistName) = $DB->next_record(MYSQLI_NUM, false);
		} else {
			$ArtistName = '';
		}

		$DB->query("
			REPLACE INTO sphinx_delta
				(ID, GroupID, GroupName, TagList, Year, CategoryID, Time, ReleaseType, RecordLabel,
				CatalogueNumber, VanityHouse, Size, Snatched, Seeders, Leechers, LogScore, Scene,
				HasLog, HasCue, FreeTorrent, Media, Format, Encoding, RemasterYear, RemasterTitle,
				RemasterRecordLabel, RemasterCatalogueNumber, FileList, VoteScore, ArtistName)
			SELECT
				t.ID, g.ID, Name, TagList, Year, CategoryID, UNIX_TIMESTAMP(t.Time), ReleaseType,
				RecordLabel, CatalogueNumber, VanityHouse, Size >> 10 AS Size, Snatched, Seeders,
				Leechers, LogScore, CAST(Scene AS CHAR), CAST(HasLog AS CHAR), CAST(HasCue AS CHAR),
				CAST(FreeTorrent AS CHAR), Media, Format, Encoding,
				RemasterYear, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber,
				REPLACE(FileList, '_', ' ') AS FileList, $VoteScore, '".db_string($ArtistName)."'
			FROM torrents AS t
				JOIN torrents_group AS g ON g.ID=t.GroupID
			WHERE g.ID=$GroupID");

/*		$DB->query("
			INSERT INTO sphinx_delta
				(ID, ArtistName)
			SELECT torrents.ID, artists.ArtistName
			FROM (
				SELECT
					GroupID,
					GROUP_CONCAT(aa.Name separator ' ') AS ArtistName
				FROM torrents_artists AS ta
					JOIN artists_alias AS aa ON aa.AliasID=ta.AliasID
				WHERE ta.GroupID=$GroupID
					AND ta.Importance IN ('1', '4', '5', '6')
				GROUP BY ta.GroupID
			) AS artists
				JOIN torrents USING(GroupID)
			ON DUPLICATE KEY UPDATE ArtistName=values(ArtistName)");
*/
		$Cache->delete_value('torrents_details_'.$GroupID);
		$Cache->delete_value('torrent_group_'.$GroupID);

		$ArtistInfo = Artists::get_artist($GroupID);
		foreach ($ArtistInfo as $Importances => $Importance) {
			foreach ($Importance as $Artist) {
				$Cache->delete_value('artist_groups_'.$Artist['id']); //Needed for at least freeleech change, if not others.
			}
		}

		$Cache->delete_value('groups_artists_'.$GroupID);
	}

	/**
	 * Regenerate a torrent's filelist from its meta data,
	 * update the database record and clear relevant cache keys
	 *
	 * @param int $TorrentID
	 */
	public static function regenerate_filelist($TorrentID) {
		global $DB, $Cache;
		$DB->query("
			SELECT tg.ID,
				tf.File
			FROM torrents_files AS tf
				JOIN torrents AS t ON t.ID=tf.TorrentID
				JOIN torrents_group AS tg ON tg.ID=t.GroupID
			WHERE tf.TorrentID = ".$TorrentID);
		if ($DB->record_count() > 0) {
			list($GroupID, $Contents) = $DB->next_record(MYSQLI_NUM, false);
			if (Misc::is_new_torrent($Contents)) {
				$Tor = new BencodeTorrent($Contents);
				$FilePath = (isset($Tor->Dec['info']['files']) ? Format::make_utf8($Tor->get_name()) : '');
			} else {
				$Tor = new TORRENT(unserialize(base64_decode($Contents)), true);
				$FilePath = (isset($Tor->Val['info']->Val['files']) ? Format::make_utf8($Tor->get_name()) : '');
			}
			list($TotalSize, $FileList) = $Tor->file_list();
			foreach ($FileList as $File) {
				$TmpFileList[] = self::filelist_format_file($File);
			}
			$FileString = implode("\n", $TmpFileList);
			$DB->query("
				UPDATE torrents
				SET Size = $TotalSize, FilePath = '".db_string($FilePath)."', FileList = '".db_string($FileString)."'
				WHERE ID = $TorrentID");
			$Cache->delete_value('torrents_details_'.$GroupID);
		}
	}

	/**
	 * Return UTF-8 encoded string to use as file delimiter in torrent file lists
	 */
	public static function filelist_delim() {
		static $FilelistDelimUTF8;
		if (isset($FilelistDelimUTF8)) {
			return $FilelistDelimUTF8;
		}
		return $FilelistDelimUTF8 = utf8_encode(chr(self::FilelistDelim));
	}

	/**
	 * Create a string that contains file info in a format that's easy to use for Sphinx
	 *
	 * @param array $File (File size, File name)
	 * @return string with the format .EXT sSIZEs NAME DELIMITER
	 */
	public static function filelist_format_file($File) {
		list($Size, $Name) = $File;
		$Name = Format::make_utf8(strtr($Name, "\n\r\t", '   '));
		$ExtPos = strrpos($Name, '.');
		// Should not be $ExtPos !== false. Extensionless files that start with a . should not get extensions
		$Ext = ($ExtPos ? trim(substr($Name, $ExtPos + 1)) : '');
		return sprintf("%s s%ds %s %s", ".$Ext", $Size, $Name, self::filelist_delim());
	}

	/**
	 * Create a string that contains file info in the old format for the API
	 *
	 * @param string $File string with the format .EXT sSIZEs NAME DELIMITER
	 * @return string with the format NAME{{{SIZE}}}
	 */
	public static function filelist_old_format($File) {
		$File = self::filelist_get_file($File);
		return $File['name'] . '{{{' . $File['size'] . '}}}';
	}

	/**
	 * Translate a formatted file info string into a more useful array structure
	 *
	 * @param string $File string with the format .EXT sSIZEs NAME DELIMITER
	 * @return file info array with the keys 'ext', 'size' and 'name'
	 */
	public static function filelist_get_file($File) {
		// Need this hack because filelists are always display_str()ed
		$DelimLen = strlen(display_str(self::filelist_delim())) + 1;
		list($FileExt, $Size, $Name) = explode(' ', $File, 3);
		if ($Spaces = strspn($Name, ' ')) {
			$Name = str_replace(' ', '&nbsp;', substr($Name, 0, $Spaces)) . substr($Name, $Spaces);
		}
		return array(
					'ext' => $FileExt,
					'size' => substr($Size, 1, -1),
					'name' => substr($Name, 0, -$DelimLen)
					);
	}

	/**
	 * Format the information about a torrent.
	 * @param $Data an array a subset of the following keys:
	 *	Format, Encoding, HasLog, LogScore HasCue, Media, Scene, RemasterYear
	 *	RemasterTitle, FreeTorrent, PersonalFL
	 * @param boolean $ShowMedia if false, Media key will be omitted
	 * @param boolean $ShowEdition if false, RemasterYear/RemasterTitle will be omitted
	 */
	public static function torrent_info($Data, $ShowMedia = false, $ShowEdition = false) {
		$Info = array();
		if (!empty($Data['Format'])) { $Info[] = $Data['Format']; }
		if (!empty($Data['Encoding'])) { $Info[] = $Data['Encoding']; }
		if (!empty($Data['HasLog'])) {
			$Str = 'Log';
			if (!empty($Data['LogScore'])) {
				$Str.=' ('.$Data['LogScore'].'%)';
			}
			$Info[] = $Str;
		}
		if (!empty($Data['HasCue'])) { $Info[] = 'Cue'; }
		if ($ShowMedia && !empty($Data['Media'])) { $Info[] = $Data['Media']; }
		if (!empty($Data['Scene'])) { $Info[] = 'Scene'; }
		if ($ShowEdition) {
			$EditionInfo = array();
			if (!empty($Data['RemasterYear'])) { $EditionInfo[] = $Data['RemasterYear']; }
			if (!empty($Data['RemasterTitle'])) { $EditionInfo[] = $Data['RemasterTitle']; }
			if (count($EditionInfo)) { $Info[] = implode(' ', $EditionInfo); }
		}
		if ($Data['IsSnatched']) { $Info[] = Format::torrent_label('Snatched!'); }
		if ($Data['FreeTorrent'] == '1') { $Info[] = Format::torrent_label('Freeleech!'); }
		if ($Data['FreeTorrent'] == '2') { $Info[] = Format::torrent_label('Neutral Leech!'); }
		if ($Data['PersonalFL']) { $Info[] = Format::torrent_label('Personal Freeleech!'); }
		return implode(' / ', $Info);
	}


	/**
	 * Will freeleech / neutralleech / normalise a set of torrents
	 *
	 * @param array $TorrentIDs An array of torrents IDs to iterate over
	 * @param int $FreeNeutral 0 = normal, 1 = fl, 2 = nl
	 * @param int $FreeLeechType 0 = Unknown, 1 = Staff picks, 2 = Perma-FL (Toolbox, etc.), 3 = Vanity House
	 */
	public static function freeleech_torrents($TorrentIDs, $FreeNeutral = 1, $FreeLeechType = 0) {
		global $DB, $Cache, $LoggedUser;

		if (!is_array($TorrentIDs)) {
			$TorrentIDs = array($TorrentIDs);
		}

		$DB->query("
			UPDATE torrents
			SET FreeTorrent = '$FreeNeutral', FreeLeechType = '$FreeLeechType'
			WHERE ID IN (".implode(', ', $TorrentIDs).')');

		$DB->query('
			SELECT ID, GroupID, info_hash
			FROM torrents
			WHERE ID IN ('.implode(', ', $TorrentIDs).') ORDER BY GroupID ASC');
		$Torrents = $DB->to_array(false, MYSQLI_NUM, false);
		$GroupIDs = $DB->collect('GroupID');

		foreach ($Torrents as $Torrent) {
			list($TorrentID, $GroupID, $InfoHash) = $Torrent;
			Tracker::update_tracker('update_torrent', array('info_hash' => rawurlencode($InfoHash), 'freetorrent' => $FreeNeutral));
			$Cache->delete_value('torrent_download_'.$TorrentID);
			Misc::write_log($LoggedUser['Username']." marked torrent $TorrentID freeleech type $FreeLeechType!");
			Torrents::write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "marked as freeleech type $FreeLeechType!", 0);
		}

		foreach ($GroupIDs as $GroupID) {
			Torrents::update_hash($GroupID);
		}
	}


	/**
	 * Convenience function to allow for passing groups to Torrents::freeleech_torrents()
	 *
	 * @param array $GroupIDs the groups in question
	 * @param int $FreeNeutral see Torrents::freeleech_torrents()
	 * @param int $FreeLeechType see Torrents::freeleech_torrents()
	 */
	public static function freeleech_groups($GroupIDs, $FreeNeutral = 1, $FreeLeechType = 0) {
		global $DB;

		if (!is_array($GroupIDs)) {
			$GroupIDs = array($GroupIDs);
		}

		$DB->query('
			SELECT ID
			FROM torrents
			WHERE GroupID IN ('.implode(', ', $GroupIDs).')');
		if ($DB->record_count()) {
			$TorrentIDs = $DB->collect('ID');
			Torrents::freeleech_torrents($TorrentIDs, $FreeNeutral, $FreeLeechType);
		}
	}


	/**
	 * Check if the logged in user has an active freeleech token
	 *
	 * @param int $TorrentID
	 * @return true if an active token exists
	 */
	public static function has_token($TorrentID) {
		global $DB, $Cache, $LoggedUser;
		if (empty($LoggedUser)) {
			return false;
		}

		static $TokenTorrents;
		$UserID = $LoggedUser['ID'];
		if (!isset($TokenTorrents)) {
			$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
			if ($TokenTorrents === false) {
				$DB->query("
					SELECT TorrentID
					FROM users_freeleeches
					WHERE UserID=$UserID
						AND Expired=0");
				$TokenTorrents = array_fill_keys($DB->collect('TorrentID', false), true);
				$Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
			}
		}
		return isset($TokenTorrents[$TorrentID]);
	}


	/**
	 * Check if the logged in user can use a freeleech token on this torrent
	 *
	 * @param int $Torrent
	 * @return boolen True if user is allowed to use a token
	 */
	public static function can_use_token($Torrent) {
		global $LoggedUser;
		if (empty($LoggedUser)) {
			return false;
		}
		return ($LoggedUser['FLTokens'] > 0
			&& $Torrent['Size'] < 1073741824
			&& !$Torrent['PersonalFL']
			&& empty($Torrent['FreeTorrent'])
			&& $LoggedUser['CanLeech'] == '1');
	}


	public static function has_snatched($TorrentID, $AllUsers = false) {
		global $DB, $Cache, $LoggedUser;
		if (!$AllUsers && (empty($LoggedUser) || !$LoggedUser['ShowSnatched'])) {
			return false;
		}

		$UserID = $LoggedUser['ID'];
		$Buckets = 64;
		$LastBucket = $Buckets - 1;
		$BucketID = $TorrentID & $LastBucket;
		static $SnatchedTorrents = array(), $LastUpdate = 0;

		if (empty($SnatchedTorrents)) {
			$SnatchedTorrents = array_fill(0, $Buckets, false);
			$LastUpdate = $Cache->get_value('users_snatched_'.$UserID.'_lastupdate') ?: 0;
		} elseif (isset($SnatchedTorrents[$BucketID][$TorrentID])) {
			return true;
		}

		// Torrent was not found in the previously inspected snatch lists
		$CurSnatchedTorrents =& $SnatchedTorrents[$BucketID];
		if ($CurSnatchedTorrents === false) {
			$CurTime = time();
			// This bucket hasn't been checked before
			$CurSnatchedTorrents = $Cache->get_value('users_snatched_'.$UserID.'_'.$BucketID, true);
			if ($CurSnatchedTorrents === false || $CurTime - $LastUpdate > 1800) {
				$Updated = array();
				if ($CurSnatchedTorrents === false || $LastUpdate == 0) {
					for ($i = 0; $i < $Buckets; $i++) {
						$SnatchedTorrents[$i] = array();
					}
					// Not found in cache. Since we don't have a suitable index, it's faster to update everything
					$DB->query("
						SELECT fid, tstamp AS TorrentID
						FROM xbt_snatched
						WHERE uid = '$UserID'");
					while (list($ID) = $DB->next_record(MYSQLI_NUM, false)) {
						$SnatchedTorrents[$ID & $LastBucket][(int)$ID] = true;
					}
					$Updated = array_fill(0, $Buckets, true);
				} elseif (isset($CurSnatchedTorrents[$TorrentID])) {
					// Old cache, but torrent is snatched, so no need to update
					return true;
				} else {
					// Old cache, check if torrent has been snatched recently
					$DB->query("
						SELECT fid
						FROM xbt_snatched
						WHERE uid = '$UserID'
							AND tstamp >= $LastUpdate");
					while (list($ID) = $DB->next_record(MYSQLI_NUM, false)) {
						$CurBucketID = $ID & $LastBucket;
						if ($SnatchedTorrents[$CurBucketID] === false) {
							$SnatchedTorrents[$CurBucketID] = $Cache->get_value('users_snatched_'.$UserID.'_'.$CurBucketID, true);
							if ($SnatchedTorrents[$CurBucketID] === false) {
								$SnatchedTorrents[$CurBucketID] = array();
							}
						}
						$SnatchedTorrents[$CurBucketID][(int)$ID] = true;
						$Updated[$CurBucketID] = true;
					}
				}
				for ($i = 0; $i < $Buckets; $i++) {
					if ($Updated[$i]) {
						$Cache->cache_value('users_snatched_'.$UserID.'_'.$i, $SnatchedTorrents[$i], 0);
					}
				}
				$Cache->cache_value('users_snatched_'.$UserID.'_lastupdate', $CurTime, 0);
				$LastUpdate = $CurTime;
			}
		}
		return isset($CurSnatchedTorrents[$TorrentID]);
	}


	public static function edition_string(array $Torrent, array $Group) {
		if ($Torrent['Remastered'] && $Torrent['RemasterYear'] != 0) {
			$EditionName = $Torrent['RemasterYear'];
			$AddExtra = ' - ';
			if ($Torrent['RemasterRecordLabel']) {
				$EditionName .= $AddExtra . display_str($Torrent['RemasterRecordLabel']);
				$AddExtra = ' / ';
			}
			if ($Torrent['RemasterCatalogueNumber']) {
				$EditionName .= $AddExtra . display_str($Torrent['RemasterCatalogueNumber']);
				$AddExtra = ' / ';
			}
			if ($Torrent['RemasterTitle']) {
				$EditionName .= $AddExtra . display_str($Torrent['RemasterTitle']);
				$AddExtra = ' / ';
			}
			$EditionName .= $AddExtra . display_str($Torrent['Media']);
		} else {
			$AddExtra = " / ";
			if (!$Torrent['Remastered']) {
				$EditionName = "Original Release";
				if ($Group['RecordLabel']) {
					$EditionName .= $AddExtra . $Group['RecordLabel'];
					$AddExtra = ' / ';
				}
				if ($Group['CatalogueNumber']) {
					$EditionName .= $AddExtra . $Group['CatalogueNumber'];
					$AddExtra = ' / ';
				}
			} else {
				$EditionName = 'Unknown Release(s)';
			}
			$EditionName .= $AddExtra . display_str($Torrent['Media']);
		}
		return $EditionName;
	}
}
?>
