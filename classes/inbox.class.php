<?php

class Inbox {
	/*
	 * Get the link to a user's inbox.
	 * This is what handles the ListUnreadPMsFirst setting
	 *
	 * @param string - whether the inbox or sentbox should be loaded
	 * @return string - the URL to a user's inbox
	 */
	public static function get_inbox_link($WhichBox = 'inbox') {

		$ListFirst = G::$LoggedUser['ListUnreadPMsFirst'];

		if ($WhichBox == 'inbox') {
			if ($ListFirst) {
				$InboxURL = 'inbox.php?sort=unread';
			} else {
				$InboxURL = 'inbox.php';
			}
		} else {
			if ($ListFirst) {
				$InboxURL = 'inbox.php?action=sentbox&amp;sort=unread';
			} else {
				$InboxURL = 'inbox.php?action=sentbox';
			}
		}
		return $InboxURL;
	}
}
?>
