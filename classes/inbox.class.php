<?php

class Inbox {
	/*
	 * Get the link to a user's inbox.
	 * This is what handles the ListUnreadPMsFirst setting
	 *
	 * @param boolean - the value of $LoggedUser['ListUnreadPMsFirst']
	 * @param string - whether the inbox or sentbox should be loaded
	 * @return string - the URL to a user's inbox
	 */
	public static function get_inbox_link($ListFirst = 0, $WhichBox = 'inbox') {
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
