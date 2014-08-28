<?
class Torrents {
	const FILELIST_DELIM = 0xF7; // Hex for &divide; Must be the same as phrase_boundary in sphinx.conf!
	const SNATCHED_UPDATE_INTERVAL = 3600; // How often we want to update users' snatch lists
	const SNATCHED_UPDATE_AFTERDL = 300; // How long after a torrent download we want to update a user's snatch lists

	/**
	 * Function to get data and torrents for an array of GroupIDs. Order of keys doesn't matter
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
		$Found = $NotFound = array_fill_keys($GroupIDs, false);
		$Key = $Torrents ? 'torrent_group_' : 'torrent_group_light_';

		foreach ($GroupIDs as $i => $GroupID) {
			if (!is_number($GroupID)) {
				unset($GroupIDs[$i], $Found[$GroupID], $NotFound[$GroupID]);
				continue;
			}
			$Data = G::$Cache->get_value($Key . $GroupID, true);
			if (!empty($Data) && is_array($Data) && $Data['ver'] == CACHE::GROUP_VERSION) {
				unset($NotFound[$GroupID]);
				$Found[$GroupID] = $Data['d'];
			}
		}
		// Make sure there's something in $GroupIDs, otherwise the SQL will break
		if (count($GroupIDs) === 0) {
			return array();
		}

		/*
		Changing any of these attributes returned will cause very large, very dramatic site-wide chaos.
		Do not change what is returned or the order thereof without updating:
			torrents, artists, collages, bookmarks, better, the front page,
		and anywhere else the get_groups function is used.
		Update self::array_group(), too
		*/

		if (count($NotFound) > 0) {
			$IDs = implode(',', array_keys($NotFound));
			$NotFound = array();
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT
					ID, Name, Year, RecordLabel, CatalogueNumber, TagList, ReleaseType, VanityHouse, WikiImage, CategoryID
				FROM torrents_group
				WHERE ID IN ($IDs)");

			while ($Group = G::$DB->next_record(MYSQLI_ASSOC, true)) {
				$NotFound[$Group['ID']] = $Group;
				$NotFound[$Group['ID']]['Torrents'] = array();
				$NotFound[$Group['ID']]['Artists'] = array();
			}
			G::$DB->set_query_id($QueryID);

			if ($Torrents) {
				$QueryID = G::$DB->get_query_id();
				G::$DB->query("
					SELECT
						ID, GroupID, Media, Format, Encoding, RemasterYear, Remastered, RemasterTitle,
						RemasterRecordLabel, RemasterCatalogueNumber, Scene, HasLog, HasCue, LogScore,
						FileCount, FreeTorrent, Size, Leechers, Seeders, Snatched, Time, ID AS HasFile
					FROM torrents
					WHERE GroupID IN ($IDs)
					ORDER BY GroupID, Remastered, (RemasterYear != 0) DESC, RemasterYear, RemasterTitle,
							RemasterRecordLabel, RemasterCatalogueNumber, Media, Format, Encoding, ID");
				while ($Torrent = G::$DB->next_record(MYSQLI_ASSOC, true)) {
					$NotFound[$Torrent['GroupID']]['Torrents'][$Torrent['ID']] = $Torrent;
				}
				G::$DB->set_query_id($QueryID);
			}

			foreach ($NotFound as $GroupID => $GroupInfo) {
				G::$Cache->cache_value($Key . $GroupID, array('ver' => CACHE::GROUP_VERSION, 'd' => $GroupInfo), 0);
			}

			$Found = $NotFound + $Found;
		}

		// Filter out orphans (elements that are == false)
		$Found = array_filter($Found);

		if ($GetArtists) {
			$Artists = Artists::get_artists($GroupIDs);
		} else {
			$Artists = array();
		}

		if ($Return) { // If we're interested in the data, and not just caching it
			foreach ($Artists as $GroupID => $Data) {
				if (!isset($Found[$GroupID])) {
					continue;
				}
				if (array_key_exists(1, $Data) || array_key_exists(4, $Data) || array_key_exists(6, $Data)) {
					$Found[$GroupID]['Artists'] = isset($Data[1]) ? $Data[1] : null; // Only use main artists (legacy)
					// TODO: find a better solution than this crap / rewrite the artist system
					for ($i = 1; $i <= 7; $i++) {
						$Found[$GroupID]['ExtendedArtists'][$i] = isset($Data[$i]) ? $Data[$i] : null;
					}
				}
				else {
					$Found[$GroupID]['ExtendedArtists'] = false;
				}
			}
			// Fetch all user specific torrent properties
			if ($Torrents) {
				foreach ($Found as &$Group) {
					$Group['Flags'] = array('IsSnatched' => false);
					if (!empty($Group['Torrents'])) {
						foreach ($Group['Torrents'] as &$Torrent) {
							self::torrent_properties($Torrent, $Group['Flags']);
						}
					}
				}
			}
			return $Found;
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
	public static function array_group(array &$Group) {
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
		global $Time;
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			INSERT INTO group_log
				(GroupID, TorrentID, UserID, Info, Time, Hidden)
			VALUES
				($GroupID, $TorrentID, $UserID, '".db_string($Message)."', '".sqltime()."', $Hidden)");
		G::$DB->set_query_id($QueryID);
	}


	/**
	 * Delete a torrent.
	 *
	 * @param int $ID The ID of the torrent to delete.
	 * @param int $GroupID Set it if you have it handy, to save a query. Otherwise, it will be found.
	 * @param string $OcelotReason The deletion reason for ocelot to report to users.
	 */
	public static function delete_torrent($ID, $GroupID = 0, $OcelotReason = -1) {
		$QueryID = G::$DB->get_query_id();
		if (!$GroupID) {
			G::$DB->query("
				SELECT GroupID, UserID
				FROM torrents
				WHERE ID = '$ID'");
			list($GroupID, $UploaderID) = G::$DB->next_record();
		}
		if (empty($UserID)) {
			G::$DB->query("
				SELECT UserID
				FROM torrents
				WHERE ID = '$ID'");
			list($UserID) = G::$DB->next_record();
		}

		$RecentUploads = G::$Cache->get_value("recent_uploads_$UserID");
		if (is_array($RecentUploads)) {
			foreach ($RecentUploads as $Key => $Recent) {
				if ($Recent['ID'] == $GroupID) {
					G::$Cache->delete_value("recent_uploads_$UserID");
				}
			}
		}


		G::$DB->query("
			SELECT info_hash
			FROM torrents
			WHERE ID = $ID");
		list($InfoHash) = G::$DB->next_record(MYSQLI_BOTH, false);
		G::$DB->query("
			DELETE FROM torrents
			WHERE ID = $ID");
		Tracker::update_tracker('delete_torrent', array('info_hash' => rawurlencode($InfoHash), 'id' => $ID, 'reason' => $OcelotReason));

		G::$Cache->decrement('stats_torrent_count');

		G::$DB->query("
			SELECT COUNT(ID)
			FROM torrents
			WHERE GroupID = '$GroupID'");
		list($Count) = G::$DB->next_record();

		if ($Count == 0) {
			Torrents::delete_group($GroupID);
		} else {
			Torrents::update_hash($GroupID);
		}

		// Torrent notifications
		G::$DB->query("
			SELECT UserID
			FROM users_notify_torrents
			WHERE TorrentID = '$ID'");
		while (list($UserID) = G::$DB->next_record()) {
			G::$Cache->delete_value("notifications_new_$UserID");
		}
		G::$DB->query("
			DELETE FROM users_notify_torrents
			WHERE TorrentID = '$ID'");

		G::$DB->query("
			UPDATE reportsv2
			SET
				Status = 'Resolved',
				LastChangeTime = '".sqltime()."',
				ModComment = 'Report already dealt with (torrent deleted)'
			WHERE TorrentID = $ID
				AND Status != 'Resolved'");
		$Reports = G::$DB->affected_rows();
		if ($Reports) {
			G::$Cache->decrement('num_torrent_reportsv2', $Reports);
		}

		G::$DB->query("
			DELETE FROM torrents_files
			WHERE TorrentID = '$ID'");
		G::$DB->query("
			DELETE FROM torrents_bad_tags
			WHERE TorrentID = $ID");
		G::$DB->query("
			DELETE FROM torrents_bad_folders
			WHERE TorrentID = $ID");
		G::$DB->query("
			DELETE FROM torrents_bad_files
			WHERE TorrentID = $ID");
		G::$DB->query("
			DELETE FROM torrents_cassette_approved
			WHERE TorrentID = $ID");
		G::$DB->query("
			DELETE FROM torrents_lossymaster_approved
			WHERE TorrentID = $ID");
		G::$DB->query("
			DELETE FROM torrents_lossyweb_approved
			WHERE TorrentID = $ID");

		// Tells Sphinx that the group is removed
		G::$DB->query("
			REPLACE INTO sphinx_delta (ID, Time)
			VALUES ($ID, UNIX_TIMESTAMP())");

		G::$Cache->delete_value("torrent_download_$ID");
		G::$Cache->delete_value("torrent_group_$GroupID");
		G::$Cache->delete_value("torrents_details_$GroupID");
		G::$DB->set_query_id($QueryID);
	}


	/**
	 * Delete a group, called after all of its torrents have been deleted.
	 * IMPORTANT: Never call this unless you're certain the group is no longer used by any torrents
	 *
	 * @param int $GroupID
	 */
	public static function delete_group($GroupID) {
		$QueryID = G::$DB->get_query_id();

		Misc::write_log("Group $GroupID automatically deleted (No torrents have this group).");

		G::$DB->query("
			SELECT CategoryID
			FROM torrents_group
			WHERE ID = '$GroupID'");
		list($Category) = G::$DB->next_record();
		if ($Category == 1) {
			G::$Cache->decrement('stats_album_count');
		}
		G::$Cache->decrement('stats_group_count');



		// Collages
		G::$DB->query("
			SELECT CollageID
			FROM collages_torrents
			WHERE GroupID = '$GroupID'");
		if (G::$DB->has_results()) {
			$CollageIDs = G::$DB->collect('CollageID');
			G::$DB->query("
				UPDATE collages
				SET NumTorrents = NumTorrents - 1
				WHERE ID IN (".implode(', ', $CollageIDs).')');
			G::$DB->query("
				DELETE FROM collages_torrents
				WHERE GroupID = '$GroupID'");

			foreach ($CollageIDs as $CollageID) {
				G::$Cache->delete_value("collage_$CollageID");
			}
			G::$Cache->delete_value("torrent_collages_$GroupID");
		}

		// Artists
		// Collect the artist IDs and then wipe the torrents_artist entry
		G::$DB->query("
			SELECT ArtistID
			FROM torrents_artists
			WHERE GroupID = $GroupID");
		$Artists = G::$DB->collect('ArtistID');

		G::$DB->query("
			DELETE FROM torrents_artists
			WHERE GroupID = '$GroupID'");

		foreach ($Artists as $ArtistID) {
			if (empty($ArtistID)) {
				continue;
			}
			// Get a count of how many groups or requests use the artist ID
			G::$DB->query("
				SELECT COUNT(ag.ArtistID)
				FROM artists_group AS ag
					LEFT JOIN requests_artists AS ra ON ag.ArtistID = ra.ArtistID
				WHERE ra.ArtistID IS NOT NULL
					AND ag.ArtistID = '$ArtistID'");
			list($ReqCount) = G::$DB->next_record();
			G::$DB->query("
				SELECT COUNT(ag.ArtistID)
				FROM artists_group AS ag
					LEFT JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
				WHERE ta.ArtistID IS NOT NULL
					AND ag.ArtistID = '$ArtistID'");
			list($GroupCount) = G::$DB->next_record();
			if (($ReqCount + $GroupCount) == 0) {
				//The only group to use this artist
				Artists::delete_artist($ArtistID);
			} else {
				//Not the only group, still need to clear cache
				G::$Cache->delete_value("artist_groups_$ArtistID");
			}
		}

		// Requests
		G::$DB->query("
			SELECT ID
			FROM requests
			WHERE GroupID = '$GroupID'");
		$Requests = G::$DB->collect('ID');
		G::$DB->query("
			UPDATE requests
			SET GroupID = NULL
			WHERE GroupID = '$GroupID'");
		foreach ($Requests as $RequestID) {
			G::$Cache->delete_value("request_$RequestID");
		}

		// comments
		Comments::delete_page('torrents', $GroupID);

		G::$DB->query("
			DELETE FROM torrents_group
			WHERE ID = '$GroupID'");
		G::$DB->query("
			DELETE FROM torrents_tags
			WHERE GroupID = '$GroupID'");
		G::$DB->query("
			DELETE FROM torrents_tags_votes
			WHERE GroupID = '$GroupID'");
		G::$DB->query("
			DELETE FROM bookmarks_torrents
			WHERE GroupID = '$GroupID'");
		G::$DB->query("
			DELETE FROM wiki_torrents
			WHERE PageID = '$GroupID'");

		G::$Cache->delete_value("torrents_details_$GroupID");
		G::$Cache->delete_value("torrent_group_$GroupID");
		G::$Cache->delete_value("groups_artists_$GroupID");
		G::$DB->set_query_id($QueryID);
	}


	/**
	 * Update the cache and sphinx delta index to keep everything up-to-date.
	 *
	 * @param int $GroupID
	 */
	public static function update_hash($GroupID) {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			UPDATE torrents_group
			SET TagList = (
					SELECT REPLACE(GROUP_CONCAT(tags.Name SEPARATOR ' '), '.', '_')
					FROM torrents_tags AS t
						INNER JOIN tags ON tags.ID = t.TagID
					WHERE t.GroupID = '$GroupID'
					GROUP BY t.GroupID
					)
			WHERE ID = '$GroupID'");

		// Fetch album vote score
		G::$DB->query("
			SELECT Score
			FROM torrents_votes
			WHERE GroupID = $GroupID");
		if (G::$DB->has_results()) {
			list($VoteScore) = G::$DB->next_record();
		} else {
			$VoteScore = 0;
		}

		// Fetch album artists
		G::$DB->query("
			SELECT GROUP_CONCAT(aa.Name separator ' ')
			FROM torrents_artists AS ta
				JOIN artists_alias AS aa ON aa.AliasID = ta.AliasID
			WHERE ta.GroupID = $GroupID
				AND ta.Importance IN ('1', '4', '5', '6')
			GROUP BY ta.GroupID");
		if (G::$DB->has_results()) {
			list($ArtistName) = G::$DB->next_record(MYSQLI_NUM, false);
		} else {
			$ArtistName = '';
		}

		G::$DB->query("
			REPLACE INTO sphinx_delta
				(ID, GroupID, GroupName, TagList, Year, CategoryID, Time, ReleaseType, RecordLabel,
				CatalogueNumber, VanityHouse, Size, Snatched, Seeders, Leechers, LogScore, Scene, HasLog,
				HasCue, FreeTorrent, Media, Format, Encoding, Description, RemasterYear, RemasterTitle,
				RemasterRecordLabel, RemasterCatalogueNumber, FileList, VoteScore, ArtistName)
			SELECT
				t.ID, g.ID, Name, TagList, Year, CategoryID, UNIX_TIMESTAMP(t.Time), ReleaseType,
				RecordLabel, CatalogueNumber, VanityHouse, Size, Snatched, Seeders,
				Leechers, LogScore, CAST(Scene AS CHAR), CAST(HasLog AS CHAR), CAST(HasCue AS CHAR),
				CAST(FreeTorrent AS CHAR), Media, Format, Encoding, Description,
				RemasterYear, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber,
				REPLACE(REPLACE(FileList, '_', ' '), '/', ' ') AS FileList, $VoteScore, '".db_string($ArtistName)."'
			FROM torrents AS t
				JOIN torrents_group AS g ON g.ID = t.GroupID
			WHERE g.ID = $GroupID");

		G::$Cache->delete_value("torrents_details_$GroupID");
		G::$Cache->delete_value("torrent_group_$GroupID");
		G::$Cache->delete_value("torrent_group_light_$GroupID");

		$ArtistInfo = Artists::get_artist($GroupID);
		foreach ($ArtistInfo as $Importances => $Importance) {
			foreach ($Importance as $Artist) {
				G::$Cache->delete_value('artist_groups_'.$Artist['id']); //Needed for at least freeleech change, if not others.
			}
		}

		G::$Cache->delete_value("groups_artists_$GroupID");
		G::$DB->set_query_id($QueryID);
	}

	/**
	 * Regenerate a torrent's file list from its meta data,
	 * update the database record and clear relevant cache keys
	 *
	 * @param int $TorrentID
	 */
	public static function regenerate_filelist($TorrentID) {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			SELECT tg.ID,
				tf.File
			FROM torrents_files AS tf
				JOIN torrents AS t ON t.ID = tf.TorrentID
				JOIN torrents_group AS tg ON tg.ID = t.GroupID
			WHERE tf.TorrentID = $TorrentID");
		if (G::$DB->has_results()) {
			list($GroupID, $Contents) = G::$DB->next_record(MYSQLI_NUM, false);
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
			G::$DB->query("
				UPDATE torrents
				SET Size = $TotalSize, FilePath = '".db_string($FilePath)."', FileList = '".db_string($FileString)."'
				WHERE ID = $TorrentID");
			G::$Cache->delete_value("torrents_details_$GroupID");
		}
		G::$DB->set_query_id($QueryID);
	}

	/**
	 * Return UTF-8 encoded string to use as file delimiter in torrent file lists
	 */
	public static function filelist_delim() {
		static $FilelistDelimUTF8;
		if (isset($FilelistDelimUTF8)) {
			return $FilelistDelimUTF8;
		}
		return $FilelistDelimUTF8 = utf8_encode(chr(self::FILELIST_DELIM));
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
		if (!empty($Data['Format'])) {
			$Info[] = $Data['Format'];
		}
		if (!empty($Data['Encoding'])) {
			$Info[] = $Data['Encoding'];
		}
		if (!empty($Data['HasLog'])) {
			$Str = 'Log';
			if (!empty($Data['LogScore'])) {
				$Str .= ' ('.$Data['LogScore'].'%)';
			}
			$Info[] = $Str;
		}
		if (!empty($Data['HasCue'])) {
			$Info[] = 'Cue';
		}
		if ($ShowMedia && !empty($Data['Media'])) {
			$Info[] = $Data['Media'];
		}
		if (!empty($Data['Scene'])) {
			$Info[] = 'Scene';
		}
		if ($ShowEdition) {
			$EditionInfo = array();
			if (!empty($Data['RemasterYear'])) {
				$EditionInfo[] = $Data['RemasterYear'];
			}
			if (!empty($Data['RemasterTitle'])) {
				$EditionInfo[] = $Data['RemasterTitle'];
			}
			if (count($EditionInfo)) {
				$Info[] = implode(' ', $EditionInfo);
			}
		}
		if ($Data['IsSnatched']) {
			$Info[] = Format::torrent_label('Snatched!');
		}
		if ($Data['FreeTorrent'] == '1') {
			$Info[] = Format::torrent_label('Freeleech!');
		}
		if ($Data['FreeTorrent'] == '2') {
			$Info[] = Format::torrent_label('Neutral Leech!');
		}
		if ($Data['PersonalFL']) {
			$Info[] = Format::torrent_label('Personal Freeleech!');
		}
		return implode(' / ', $Info);
	}


	/**
	 * Will freeleech / neutral leech / normalise a set of torrents
	 *
	 * @param array $TorrentIDs An array of torrent IDs to iterate over
	 * @param int $FreeNeutral 0 = normal, 1 = fl, 2 = nl
	 * @param int $FreeLeechType 0 = Unknown, 1 = Staff picks, 2 = Perma-FL (Toolbox, etc.), 3 = Vanity House
	 */
	public static function freeleech_torrents($TorrentIDs, $FreeNeutral = 1, $FreeLeechType = 0) {
		if (!is_array($TorrentIDs)) {
			$TorrentIDs = array($TorrentIDs);
		}

		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			UPDATE torrents
			SET FreeTorrent = '$FreeNeutral', FreeLeechType = '$FreeLeechType'
			WHERE ID IN (".implode(', ', $TorrentIDs).')');

		G::$DB->query('
			SELECT ID, GroupID, info_hash
			FROM torrents
			WHERE ID IN ('.implode(', ', $TorrentIDs).')
			ORDER BY GroupID ASC');
		$Torrents = G::$DB->to_array(false, MYSQLI_NUM, false);
		$GroupIDs = G::$DB->collect('GroupID');
		G::$DB->set_query_id($QueryID);

		foreach ($Torrents as $Torrent) {
			list($TorrentID, $GroupID, $InfoHash) = $Torrent;
			Tracker::update_tracker('update_torrent', array('info_hash' => rawurlencode($InfoHash), 'freetorrent' => $FreeNeutral));
			G::$Cache->delete_value("torrent_download_$TorrentID");
			Misc::write_log(G::$LoggedUser['Username']." marked torrent $TorrentID freeleech type $FreeLeechType!");
			Torrents::write_group_log($GroupID, $TorrentID, G::$LoggedUser['ID'], "marked as freeleech type $FreeLeechType!", 0);
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
		$QueryID = G::$DB->get_query_id();

		if (!is_array($GroupIDs)) {
			$GroupIDs = array($GroupIDs);
		}

		G::$DB->query('
			SELECT ID
			FROM torrents
			WHERE GroupID IN ('.implode(', ', $GroupIDs).')');
		if (G::$DB->has_results()) {
			$TorrentIDs = G::$DB->collect('ID');
			Torrents::freeleech_torrents($TorrentIDs, $FreeNeutral, $FreeLeechType);
		}
		G::$DB->set_query_id($QueryID);
	}


	/**
	 * Check if the logged in user has an active freeleech token
	 *
	 * @param int $TorrentID
	 * @return true if an active token exists
	 */
	public static function has_token($TorrentID) {
		if (empty(G::$LoggedUser)) {
			return false;
		}

		static $TokenTorrents;
		$UserID = G::$LoggedUser['ID'];
		if (!isset($TokenTorrents)) {
			$TokenTorrents = G::$Cache->get_value("users_tokens_$UserID");
			if ($TokenTorrents === false) {
				$QueryID = G::$DB->get_query_id();
				G::$DB->query("
					SELECT TorrentID
					FROM users_freeleeches
					WHERE UserID = $UserID
						AND Expired = 0");
				$TokenTorrents = array_fill_keys(G::$DB->collect('TorrentID', false), true);
				G::$DB->set_query_id($QueryID);
				G::$Cache->cache_value("users_tokens_$UserID", $TokenTorrents);
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
		if (empty(G::$LoggedUser)) {
			return false;
		}
		return (G::$LoggedUser['FLTokens'] > 0
			&& $Torrent['Size'] < 1073741824
			&& !$Torrent['PersonalFL']
			&& empty($Torrent['FreeTorrent'])
			&& G::$LoggedUser['CanLeech'] == '1');
	}

	/**
	 * Build snatchlists and check if a torrent has been snatched
	 * if a user has the 'ShowSnatched' option enabled
	 * @param int $TorrentID
	 * @return bool
	 */
	public static function has_snatched($TorrentID) {
		if (empty(G::$LoggedUser) || !G::$LoggedUser['ShowSnatched']) {
			return false;
		}

		$UserID = G::$LoggedUser['ID'];
		$Buckets = 64;
		$LastBucket = $Buckets - 1;
		$BucketID = $TorrentID & $LastBucket;
		static $SnatchedTorrents = array(), $UpdateTime = array();

		if (empty($SnatchedTorrents)) {
			$SnatchedTorrents = array_fill(0, $Buckets, false);
			$UpdateTime = G::$Cache->get_value("users_snatched_{$UserID}_time");
			if ($UpdateTime === false) {
				$UpdateTime = array(
					'last' => 0,
					'next' => 0);
			}
		} elseif (isset($SnatchedTorrents[$BucketID][$TorrentID])) {
			return true;
		}

		// Torrent was not found in the previously inspected snatch lists
		$CurSnatchedTorrents =& $SnatchedTorrents[$BucketID];
		if ($CurSnatchedTorrents === false) {
			$CurTime = time();
			// This bucket hasn't been checked before
			$CurSnatchedTorrents = G::$Cache->get_value("users_snatched_{$UserID}_$BucketID", true);
			if ($CurSnatchedTorrents === false || $CurTime > $UpdateTime['next']) {
				$Updated = array();
				$QueryID = G::$DB->get_query_id();
				if ($CurSnatchedTorrents === false || $UpdateTime['last'] == 0) {
					for ($i = 0; $i < $Buckets; $i++) {
						$SnatchedTorrents[$i] = array();
					}
					// Not found in cache. Since we don't have a suitable index, it's faster to update everything
					G::$DB->query("
						SELECT fid
						FROM xbt_snatched
						WHERE uid = '$UserID'");
					while (list($ID) = G::$DB->next_record(MYSQLI_NUM, false)) {
						$SnatchedTorrents[$ID & $LastBucket][(int)$ID] = true;
					}
					$Updated = array_fill(0, $Buckets, true);
				} elseif (isset($CurSnatchedTorrents[$TorrentID])) {
					// Old cache, but torrent is snatched, so no need to update
					return true;
				} else {
					// Old cache, check if torrent has been snatched recently
					G::$DB->query("
						SELECT fid
						FROM xbt_snatched
						WHERE uid = '$UserID'
							AND tstamp >= $UpdateTime[last]");
					while (list($ID) = G::$DB->next_record(MYSQLI_NUM, false)) {
						$CurBucketID = $ID & $LastBucket;
						if ($SnatchedTorrents[$CurBucketID] === false) {
							$SnatchedTorrents[$CurBucketID] = G::$Cache->get_value("users_snatched_{$UserID}_$CurBucketID", true);
							if ($SnatchedTorrents[$CurBucketID] === false) {
								$SnatchedTorrents[$CurBucketID] = array();
							}
						}
						$SnatchedTorrents[$CurBucketID][(int)$ID] = true;
						$Updated[$CurBucketID] = true;
					}
				}
				G::$DB->set_query_id($QueryID);
				for ($i = 0; $i < $Buckets; $i++) {
					if (isset($Updated[$i])) {
						G::$Cache->cache_value("users_snatched_{$UserID}_$i", $SnatchedTorrents[$i], 0);
					}
				}
				$UpdateTime['last'] = $CurTime;
				$UpdateTime['next'] = $CurTime + self::SNATCHED_UPDATE_INTERVAL;
				G::$Cache->cache_value("users_snatched_{$UserID}_time", $UpdateTime, 0);
			}
		}
		return isset($CurSnatchedTorrents[$TorrentID]);
	}

	/**
	 * Change the schedule for when the next update to a user's cached snatch list should be performed.
	 * By default, the change will only be made if the new update would happen sooner than the current
	 * @param int $Time Seconds until the next update
	 * @param bool $Force Whether to accept changes that would push back the update
	 */
	public static function set_snatch_update_time($UserID, $Time, $Force = false) {
		if (!$UpdateTime = G::$Cache->get_value("users_snatched_{$UserID}_time")) {
			return;
		}
		$NextTime = time() + $Time;
		if ($Force || $NextTime < $UpdateTime['next']) {
			// Skip if the change would delay the next update
			$UpdateTime['next'] = $NextTime;
			G::$Cache->cache_value("users_snatched_{$UserID}_time", $UpdateTime, 0);
		}
	}

	// Some constants for self::display_string's $Mode parameter
	const DISPLAYSTRING_HTML = 1; // Whether or not to use HTML for the output (e.g. VH tooltip)
	const DISPLAYSTRING_ARTISTS = 2; // Whether or not to display artists
	const DISPLAYSTRING_YEAR = 4; // Whether or not to display the group's year
	const DISPLAYSTRING_VH = 8; // Whether or not to display the VH flag
	const DISPLAYSTRING_RELEASETYPE = 16; // Whether or not to display the release type
	const DISPLAYSTRING_LINKED = 33; // Whether or not to link artists and the group
	// The constant for linking is 32, but because linking only works with HTML, this constant is defined as 32|1 = 33, i.e. LINKED also includes HTML
	// Keep this in mind when defining presets below!

	// Presets to facilitate the use of $Mode
	const DISPLAYSTRING_DEFAULT = 63; // HTML|ARTISTS|YEAR|VH|RELEASETYPE|LINKED = 63
	const DISPLAYSTRING_SHORT = 6; // Very simple format, only artists and year, no linking (e.g. for forum thread titles)

	/**
	 * Return the display string for a given torrent group $GroupID.
	 * @param int $GroupID
	 * @return string
	 */
	public static function display_string($GroupID, $Mode = self::DISPLAYSTRING_DEFAULT) {
		global $ReleaseTypes; // I hate this

		$GroupInfo = self::get_groups(array($GroupID), true, true, false)[$GroupID];
		$ExtendedArtists = $GroupInfo['ExtendedArtists'];

		if ($Mode & self::DISPLAYSTRING_ARTISTS) {
			if (!empty($ExtendedArtists[1])
				|| !empty($ExtendedArtists[4])
				|| !empty($ExtendedArtists[5])
				|| !empty($ExtendedArtists[6])
			) {
				unset($ExtendedArtists[2], $ExtendedArtists[3]);
				$DisplayName = Artists::display_artists($ExtendedArtists, ($Mode & self::DISPLAYSTRING_LINKED));
			} else {
				$DisplayName = '';
			}
		}

		if ($Mode & self::DISPLAYSTRING_LINKED) {
			$DisplayName .= "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupInfo[Name]</a>";
		} else {
			$DisplayName .= $GroupInfo['Name'];
		}

		if (($Mode & self::DISPLAYSTRING_YEAR) && $GroupInfo['Year'] > 0) {
			$DisplayName .= " [$GroupInfo[Year]]";
		}

		if (($Mode & self::DISPLAYSTRING_VH) && $GroupInfo['VanityHouse']) {
			if ($Mode & self::DISPLAYSTRING_HTML) {
				$DisplayName .= ' [<abbr class="tooltip" title="This is a Vanity House release">VH</abbr>]';
			} else {
				$DisplayName .= ' [VH]';
			}
		}

		if (($Mode & self::DISPLAYSTRING_RELEASETYPE) && $GroupInfo['ReleaseType'] > 0) {
			$DisplayName .= ' ['.$ReleaseTypes[$GroupInfo['ReleaseType']].']';
		}

		return $DisplayName;
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
			$AddExtra = ' / ';
			if (!$Torrent['Remastered']) {
				$EditionName = 'Original Release';
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

	//Used to get reports info on a unison cache in both browsing pages and torrent pages.
	public static function get_reports($TorrentID) {
		$Reports = G::$Cache->get_value("reports_torrent_$TorrentID");
		if ($Reports === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT
					ID,
					ReporterID,
					Type,
					UserComment,
					ReportedTime
				FROM reportsv2
				WHERE TorrentID = $TorrentID
					AND Status != 'Resolved'");
			$Reports = G::$DB->to_array(false, MYSQLI_ASSOC, false);
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value("reports_torrent_$TorrentID", $Reports, 0);
		}
		if (!check_perms('admin_reports')) {
			$Return = array();
			foreach ($Reports as $Report) {
				if ($Report['Type'] !== 'edited') {
					$Return[] = $Report;
				}
			}
			return $Return;
		}
		return $Reports;
	}
}
?>
