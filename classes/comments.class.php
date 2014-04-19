<?
class Comments {
	/*
	 * For all functions:
	 * $Page = 'artist', 'collages', 'requests' or 'torrents'
	 * $PageID = ArtistID, CollageID, RequestID or GroupID, respectively
	 */

	/**
	 * Post a comment on an artist, request or torrent page.
	 * @param string $Page
	 * @param int $PageID
	 * @param string $Body
	 * @return int ID of the new comment
	 */
	public static function post($Page, $PageID, $Body) {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT
				CEIL(
					(
						SELECT COUNT(ID) + 1
						FROM comments
						WHERE Page = '$Page'
							AND PageID = $PageID
					) / " . TORRENT_COMMENTS_PER_PAGE . "
				) AS Pages");
		list($Pages) = G::$DB->next_record();

		G::$DB->query("
			INSERT INTO comments (Page, PageID, AuthorID, AddedTime, Body)
			VALUES ('$Page', $PageID, " . G::$LoggedUser['ID'] . ", '" . sqltime() . "', '" . db_string($Body) . "')");
		$PostID = G::$DB->inserted_id();

		$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Pages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
		G::$Cache->delete_value($Page.'_comments_'.$PageID.'_catalogue_'.$CatalogueID);
		G::$Cache->delete_value($Page.'_comments_'.$PageID);

		Subscriptions::flush_subscriptions($Page, $PageID);
		Subscriptions::quote_notify($Body, $PostID, $Page, $PageID);

		G::$DB->set_query_id($QueryID);

		return $PostID;
	}

	/**
	 * Edit a comment
	 * @param int $PostID
	 * @param string $NewBody
	 * @param bool $SendPM If true, send a PM to the author of the comment informing him about the edit
	 * @todo move permission check out of here/remove hardcoded error(404)
	 */
	public static function edit($PostID, $NewBody, $SendPM = false) {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			SELECT
				Body,
				AuthorID,
				Page,
				PageID,
				AddedTime
			FROM comments
			WHERE ID = $PostID");
		if (!G::$DB->has_results()) {
			return false;
		}
		list($OldBody, $AuthorID, $Page, $PageID, $AddedTime) = G::$DB->next_record();

		if (G::$LoggedUser['ID'] != $AuthorID && !check_perms('site_moderate_forums')) {
			return false;
		}

		G::$DB->query("
			SELECT CEIL(COUNT(ID) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Page
			FROM comments
			WHERE Page = '$Page'
				AND PageID = $PageID
				AND ID <= $PostID");
		list($CommPage) = G::$DB->next_record();

		// Perform the update
		G::$DB->query("
			UPDATE comments
			SET
				Body = '" . db_string($NewBody) . "',
				EditedUserID = " . G::$LoggedUser['ID'] . ",
				EditedTime = '" . sqltime() . "'
			WHERE ID = $PostID");

		// Update the cache
		$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
		G::$Cache->delete_value($Page . '_comments_' . $PageID . '_catalogue_' . $CatalogueID);

		if ($Page == 'collages') {
			// On collages, we also need to clear the collage key (collage_$CollageID), because it has the comments in it... (why??)
			G::$Cache->delete_value('collage_' . $PageID);
		}

		G::$DB->query("
			INSERT INTO comments_edits (Page, PostID, EditUser, EditTime, Body)
			VALUES ('$Page', $PostID, " . G::$LoggedUser['ID'] . ", '" . sqltime() . "', '" . db_string($OldBody) . "')");

		G::$DB->set_query_id($QueryID);

		if ($SendPM && G::$LoggedUser['ID'] != $AuthorID) {
			// Send a PM to the user to notify them of the edit
			$PMSubject = "Your comment #$PostID has been edited";
			$PMurl = site_url()."comments.php?action=jump&postid=$PostID";
			$ProfLink = '[url='.site_url().'user.php?id='.G::$LoggedUser['ID'].']'.G::$LoggedUser['Username'].'[/url]';
			$PMBody = "One of your comments has been edited by $ProfLink: [url]{$PMurl}[/url]";
			Misc::send_pm($AuthorID, 0, $PMSubject, $PMBody);
		}

		return true; // TODO: this should reflect whether or not the update was actually successful, e.g. by checking G::$DB->affected_rows after the UPDATE query
	}

	/**
	 * Delete a comment
	 * @param int $PostID
	 */
	public static function delete($PostID) {
		$QueryID = G::$DB->get_query_id();
		// Get page, pageid
		G::$DB->query("SELECT Page, PageID FROM comments WHERE ID = $PostID");
		if (!G::$DB->has_results()) {
			// no such comment?
			G::$DB->set_query_id($QueryID);
			return false;
		}
		list ($Page, $PageID) = G::$DB->next_record();
		// get number of pages
		G::$DB->query("
			SELECT
				CEIL(COUNT(ID) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Pages,
				CEIL(SUM(IF(ID <= $PostID, 1, 0)) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Page
			FROM comments
			WHERE Page = '$Page'
				AND PageID = $PageID
			GROUP BY PageID");
		if (!G::$DB->has_results()) {
			// the comment $PostID was probably not posted on $Page
			G::$DB->set_query_id($QueryID);
			return false;
		}
		list($CommPages, $CommPage) = G::$DB->next_record();

		// $CommPages = number of pages in the thread
		// $CommPage = which page the post is on
		// These are set for cache clearing.

		G::$DB->query("
			DELETE FROM comments
			WHERE ID = $PostID");
		G::$DB->query("
			DELETE FROM comments_edits
			WHERE Page = '$Page'
				AND PostID = $PostID");

		G::$DB->query("
			DELETE FROM users_notify_quoted
			WHERE Page = '$Page'
				AND PostID = $PostID");

		Subscriptions::flush_subscriptions($Page, $PageID);
		Subscriptions::flush_quote_notifications($Page, $PageID);

		//We need to clear all subsequential catalogues as they've all been bumped with the absence of this post
		$ThisCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
		$LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
		for ($i = $ThisCatalogue; $i <= $LastCatalogue; ++$i) {
			G::$Cache->delete_value($Page . '_comments_' . $PageID . '_catalogue_' . $i);
		}

		G::$Cache->delete_value($Page . '_comments_' . $PageID);

		if ($Page == 'collages') {
			// On collages, we also need to clear the collage key (collage_$CollageID), because it has the comments in it... (why??)
			G::$Cache->delete_value("collage_$PageID");
		}

		G::$DB->set_query_id($QueryID);

		return true;
	}

	/**
	 * Get the URL to a comment, already knowing the Page and PostID
	 * @param string $Page
	 * @param int $PageID
	 * @param int $PostID
	 * @return string|bool The URL to the comment or false on error
	 */
	public static function get_url($Page, $PageID, $PostID = null) {
		$Post = (!empty($PostID) ? "&postid=$PostID#post$PostID" : '');
		switch ($Page) {
			case 'artist':
				return "artist.php?id=$PageID$Post";
			case 'collages':
				return "collages.php?action=comments&collageid=$PageID$Post";
			case 'requests':
				return "requests.php?action=view&id=$PageID$Post";
			case 'torrents':
				return "torrents.php?id=$PageID$Post";
			default:
				return false;
		}
	}

	/**
	 * Get the URL to a comment
	 * @param int $PostID
	 * @return string|bool The URL to the comment or false on error
	 */
	public static function get_url_query($PostID) {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			SELECT Page, PageID
			FROM comments
			WHERE ID = $PostID");
		if (!G::$DB->has_results()) {
			error(404);
		}
		list($Page, $PageID) = G::$DB->next_record();

		G::$DB->set_query_id($QueryID);

		return self::get_url($Page, $PageID, $PostID);
	}

	/**
	 * Load a page's comments. This takes care of `postid` and (indirectly) `page` parameters passed in $_GET.
	 * Quote notifications and last read are also handled here, unless $HandleSubscriptions = false is passed.
	 * @param string $Page
	 * @param int $PageID
	 * @param bool $HandleSubscriptions Whether or not to handle subscriptions (last read & quote notifications)
	 * @return array ($NumComments, $Page, $Thread, $LastRead)
	 *     $NumComments: the total number of comments on this artist/request/torrent group
	 *     $Page: the page we're currently on
	 *     $Thread: an array of all posts on this page
	 *     $LastRead: ID of the last comment read by the current user in this thread;
	 *                will be false if $HandleSubscriptions == false or if there are no comments on this page
	 */
	public static function load($Page, $PageID, $HandleSubscriptions = true) {
		$QueryID = G::$DB->get_query_id();

		// Get the total number of comments
		$NumComments = G::$Cache->get_value($Page."_comments_$PageID");
		if ($NumComments === false) {
			G::$DB->query("
				SELECT COUNT(ID)
				FROM comments
				WHERE Page = '$Page'
					AND PageID = $PageID");
			list($NumComments) = G::$DB->next_record();
			G::$Cache->cache_value($Page."_comments_$PageID", $NumComments, 0);
		}

		// If a postid was passed, we need to determine which page that comment is on.
		// Format::page_limit handles a potential $_GET['page']
		if (isset($_GET['postid']) && is_number($_GET['postid']) && $NumComments > TORRENT_COMMENTS_PER_PAGE) {
			G::$DB->query("
				SELECT COUNT(ID)
				FROM comments
				WHERE Page = '$Page'
					AND PageID = $PageID
					AND ID <= $_GET[postid]");
			list($PostNum) = G::$DB->next_record();
			list($CommPage, $Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $PostNum);
		} else {
			list($CommPage, $Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $NumComments);
		}

		// Get the cache catalogue
		$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);

		// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
		$Catalogue = G::$Cache->get_value($Page.'_comments_'.$PageID.'_catalogue_'.$CatalogueID);
		if ($Catalogue === false) {
			$CatalogueLimit = $CatalogueID * THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;
			G::$DB->query("
				SELECT
					c.ID,
					c.AuthorID,
					c.AddedTime,
					c.Body,
					c.EditedUserID,
					c.EditedTime,
					u.Username
				FROM comments AS c
					LEFT JOIN users_main AS u ON u.ID = c.EditedUserID
				WHERE c.Page = '$Page'
					AND c.PageID = $PageID
				ORDER BY c.ID
				LIMIT $CatalogueLimit");
			$Catalogue = G::$DB->to_array(false, MYSQLI_ASSOC);
			G::$Cache->cache_value($Page.'_comments_'.$PageID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
		}

		//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
		$Thread = array_slice($Catalogue, ((TORRENT_COMMENTS_PER_PAGE * $CommPage - TORRENT_COMMENTS_PER_PAGE) % THREAD_CATALOGUE), TORRENT_COMMENTS_PER_PAGE, true);

		if ($HandleSubscriptions && count($Thread) > 0) {
			// quote notifications
			$LastPost = end($Thread);
			$LastPost = $LastPost['ID'];
			$FirstPost = reset($Thread);
			$FirstPost = $FirstPost['ID'];
			G::$DB->query("
				UPDATE users_notify_quoted
				SET UnRead = false
				WHERE UserID = " . G::$LoggedUser['ID'] . "
					AND Page = '$Page'
					AND PageID = $PageID
					AND PostID >= $FirstPost
					AND PostID <= $LastPost");
			if (G::$DB->affected_rows()) {
				G::$Cache->delete_value('notify_quoted_' . G::$LoggedUser['ID']);
			}

			// last read
			G::$DB->query("
				SELECT PostID
				FROM users_comments_last_read
				WHERE UserID = " . G::$LoggedUser['ID'] . "
					AND Page = '$Page'
					AND PageID = $PageID");
			list($LastRead) = G::$DB->next_record();
			if ($LastRead < $LastPost) {
				G::$DB->query("
					INSERT INTO users_comments_last_read
						(UserID, Page, PageID, PostID)
					VALUES
						(" . G::$LoggedUser['ID'] . ", '$Page', $PageID, $LastPost)
					ON DUPLICATE KEY UPDATE
						PostID = $LastPost");
				G::$Cache->delete_value('subscriptions_user_new_' . G::$LoggedUser['ID']);
			}
		} else {
			$LastRead = false;
		}

		G::$DB->set_query_id($QueryID);

		return array($NumComments, $CommPage, $Thread, $LastRead);
	}

	/**
	 * Merges all comments from $Page/$PageID into $Page/$TargetPageID. This also takes care of quote notifications, subscriptions and cache.
	 * @param type $Page
	 * @param type $PageID
	 * @param type $TargetPageID
	 */
	public static function merge($Page, $PageID, $TargetPageID) {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			UPDATE comments
			SET PageID = $TargetPageID
			WHERE Page = '$Page'
				AND PageID = $PageID");

		// quote notifications
		G::$DB->query("
			UPDATE users_notify_quoted
			SET PageID = $TargetPageID
			WHERE Page = '$Page'
				AND PageID = $PageID");

		// comment subscriptions
		Subscriptions::move_subscriptions($Page, $PageID, $TargetPageID);

		// cache (we need to clear all comment catalogues)
		G::$DB->query("
			SELECT
				CEIL(COUNT(ID) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Pages
			FROM comments
			WHERE Page = '$Page'
				AND PageID = $TargetPageID
			GROUP BY PageID");
		list($CommPages) = G::$DB->next_record();
		$LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
		for ($i = 0; $i <= $LastCatalogue; ++$i) {
			G::$Cache->delete_value($Page . "_comments_$TargetPageID" . "_catalogue_$i");
		}
		G::$Cache->delete_value($Page."_comments_$TargetPageID");
		G::$DB->set_query_id($QueryID);
	}

	/**
	 * Delete all comments on $Page/$PageID (deals with quote notifications and subscriptions as well)
	 * @param string $Page
	 * @param int $PageID
	 * @return boolean
	 */
	public static function delete_page($Page, $PageID) {
		$QueryID = G::$DB->get_query_id();

		// get number of pages
		G::$DB->query("
			SELECT
				CEIL(COUNT(ID) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Pages
			FROM comments
			WHERE Page = '$Page'
				AND PageID = $PageID
			GROUP BY PageID");
		if (!G::$DB->has_results()) {
			return false;
		}
		list($CommPages) = G::$DB->next_record();

		// Delete comments
		G::$DB->query("
			DELETE FROM comments
			WHERE Page = '$Page'
				AND PageID = $PageID");

		// Delete quote notifications
		Subscriptions::flush_quote_notifications($Page, $PageID);
		G::$DB->query("
			DELETE FROM users_notify_quoted
			WHERE Page = '$Page'
				AND PageID = $PageID");

		// Deal with subscriptions
		Subscriptions::move_subscriptions($Page, $PageID, null);

		// Clear cache
		$LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $CommPages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
		for ($i = 0; $i <= $LastCatalogue; ++$i) {
			G::$Cache->delete_value($Page . '_comments_' . $PageID . '_catalogue_' . $i);
		}
		G::$Cache->delete_value($Page.'_comments_'.$PageID);

		G::$DB->set_query_id($QueryID);

		return true;
	}
}
