<?
class Misc {
        /**
         * Send an email.
         *
         * We can do this one of two ways - either using MailGun or with PHP's mail function.
         * Checks for EMAIL_DELIVERY_TYPE and then proceeds as directed to send e-mail.
         *
         * @param string $To the email address to send it to.
         * @param string $Subject
         * @param string $Body
         * @param string $From The user part of the user@NONSSL_SITE_URL email address.
         * @param string $ContentType text/plain or text/html
         */

        public static function send_email($To, $Subject, $Body, $From, $ContentType) {

                switch (EMAIL_DELIVERY_TYPE) {
                        case 'local':
                                // remove the next line if you want to send HTML email from some places...
                                $ContentType='text/plain';
                                $Headers = 'MIME-Version: 1.0'."\r\n";
                                $Headers .= 'Content-type: '.$ContentType.'; charset=iso-8859-1'."\r\n";
                                $Headers .= 'From: '.SITE_NAME.' <'.$From.'@'.NONSSL_SITE_URL.'>'."\r\n";
                                $Headers .= 'Reply-To: '.$From.'@'.NONSSL_SITE_URL."\r\n";
                                $Headers .= 'X-Mailer: Project Gazelle'."\r\n";
                                $Headers .= 'Message-Id: <'.Users::make_secret().'@'.NONSSL_SITE_URL.">\r\n";
                                $Headers .= 'X-Priority: 3'."\r\n";
                                mail($To, $Subject, $Body, $Headers, "-f $From@".NONSSL_SITE_URL);
                                break;

                        case 'mailgun':
                                // set up our message first
                                $From .= '@'.NONSSL_SITE_URL;
                                $OutgoingEmail = array(
                                        'from'          => $From,
                                        'to'            => $To,
                                        'h:Reply-To'    => $From,
                                        'subject'       => $Subject,
                                        'text'          => $Body);
                                // now let's POST it to mailgun
                                $Curl = curl_init();
                                curl_setopt($Curl, CURLOPT_URL, MAILGUN_API_URL);
                                curl_setopt($Curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                                curl_setopt($Curl, CURLOPT_USERPWD, 'api:'.MAILGUN_API_KEY);
                                curl_setopt($Curl, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
                                curl_setopt($Curl, CURLOPT_POST, true);
                                curl_setopt($Curl, CURLOPT_POSTFIELDS, $OutgoingEmail);

                                $RequestResult = curl_exec($Curl);
                                $RequestStatusCode = curl_getinfo($Curl, CURLINFO_HTTP_CODE);
                                curl_close($Curl);
                                // alert on failed emails
                                if ($RequestStatusCode != 200) {
                                        send_irc('PRIVMSG '.STATUS_CHAN." !dev email failed to $To with error message $RequestResult");
                                        }
                                break;

                        default:
                                die('You have either not configured an email delivery method in config.php or your value is incorrect.');
                                break;
                }
        }


	/**
	 * Sanitize a string to be allowed as a filename.
	 *
	 * @param string $EscapeStr the string to escape
	 * @return the string with all banned characters removed.
	 */
	public static function file_string($EscapeStr) {
		return str_replace(array('"', '*', '/', ':', '<', '>', '?', '\\', '|'), '', $EscapeStr);
	}


	/**
	 * Sends a PM from $FromId to $ToId.
	 *
	 * @param string $ToID ID of user to send PM to. If $ToID is an array and $ConvID is empty, a message will be sent to multiple users.
	 * @param string $FromID ID of user to send PM from, 0 to send from system
	 * @param string $Subject
	 * @param string $Body
	 * @param int $ConvID The conversation the message goes in. Leave blank to start a new conversation.
	 * @return
	 */
	public static function send_pm($ToID, $FromID, $Subject, $Body, $ConvID = '') {
		global $Time;
		$UnescapedSubject = $Subject;
		$UnescapedBody = $Body;
		$Subject = db_string($Subject);
		$Body = db_string($Body);
		if ($ToID == 0 || $ToID == $FromID) {
			// Don't allow users to send messages to the system or themselves
			return;
		}

		$QueryID = G::$DB->get_query_id();

		if ($ConvID == '') {
			// Create a new conversation.
			G::$DB->query("
				INSERT INTO pm_conversations (Subject)
				VALUES ('$Subject')");
			$ConvID = G::$DB->inserted_id();
			G::$DB->query("
				INSERT INTO pm_conversations_users
					(UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead)
				VALUES
					('$ToID', '$ConvID', '1','0','".sqltime()."', '".sqltime()."', '1')");
			if ($FromID != 0) {
				G::$DB->query("
					INSERT INTO pm_conversations_users
						(UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead)
					VALUES
						('$FromID', '$ConvID', '0','1','".sqltime()."', '".sqltime()."', '0')");
			}
			$ToID = array($ToID);
		} else {
			// Update the pre-existing conversations.
			G::$DB->query("
				UPDATE pm_conversations_users
				SET
					InInbox = '1',
					UnRead = '1',
					ReceivedDate = '".sqltime()."'
				WHERE UserID IN (".implode(',', $ToID).")
					AND ConvID = '$ConvID'");

			G::$DB->query("
				UPDATE pm_conversations_users
				SET
					InSentbox = '1',
					SentDate = '".sqltime()."'
				WHERE UserID = '$FromID'
					AND ConvID = '$ConvID'");
		}

		// Now that we have a $ConvID for sure, send the message.
		G::$DB->query("
			INSERT INTO pm_messages
				(SenderID, ConvID, SentDate, Body)
			VALUES
				('$FromID', '$ConvID', '".sqltime()."', '$Body')");

		// Update the cached new message count.
		foreach ($ToID as $ID) {
			G::$DB->query("
				SELECT COUNT(ConvID)
				FROM pm_conversations_users
				WHERE UnRead = '1'
					AND UserID = '$ID'
					AND InInbox = '1'");
			list($UnRead) = G::$DB->next_record();
			G::$Cache->cache_value("inbox_new_$ID", $UnRead);
		}

		G::$DB->query("
			SELECT Username
			FROM users_main
			WHERE ID = '$FromID'");
		list($SenderName) = G::$DB->next_record();
		foreach ($ToID as $ID) {
			G::$DB->query("
				SELECT COUNT(ConvID)
				FROM pm_conversations_users
				WHERE UnRead = '1'
					AND UserID = '$ID'
					AND InInbox = '1'");
			list($UnRead) = G::$DB->next_record();
			G::$Cache->cache_value("inbox_new_$ID", $UnRead);

			NotificationsManager::send_push($ID, "Message from $SenderName, Subject: $UnescapedSubject", $UnescapedBody, site_url() . 'inbox.php', NotificationsManager::INBOX);
		}

		G::$DB->set_query_id($QueryID);

		return $ConvID;
	}

	/**
	 * Create thread function, things should already be escaped when sent here.
	 *
	 * @param int $ForumID
	 * @param int $AuthorID ID of the user creating the post.
	 * @param string $Title
	 * @param string $PostBody
	 * @return -1 on error, -2 on user not existing, thread id on success.
	 */
	public static function create_thread($ForumID, $AuthorID, $Title, $PostBody) {
		global $Time;
		if (!$ForumID || !$AuthorID || !is_number($AuthorID) || !$Title || !$PostBody) {
			return -1;
		}

		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			SELECT Username
			FROM users_main
			WHERE ID = $AuthorID");
		if (!G::$DB->has_results()) {
			G::$DB->set_query_id($QueryID);
			return -2;
		}
		list($AuthorName) = G::$DB->next_record();

		$ThreadInfo = array();
		$ThreadInfo['IsLocked'] = 0;
		$ThreadInfo['IsSticky'] = 0;

		G::$DB->query("
			INSERT INTO forums_topics
				(Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID, CreatedTime)
			VALUES
				('$Title', '$AuthorID', '$ForumID', '".sqltime()."', '$AuthorID', '".sqltime()."')");
		$TopicID = G::$DB->inserted_id();
		$Posts = 1;

		G::$DB->query("
			INSERT INTO forums_posts
				(TopicID, AuthorID, AddedTime, Body)
			VALUES
				('$TopicID', '$AuthorID', '".sqltime()."', '$PostBody')");
		$PostID = G::$DB->inserted_id();

		G::$DB->query("
			UPDATE forums
			SET
				NumPosts  = NumPosts + 1,
				NumTopics = NumTopics + 1,
				LastPostID = '$PostID',
				LastPostAuthorID = '$AuthorID',
				LastPostTopicID = '$TopicID',
				LastPostTime = '".sqltime()."'
			WHERE ID = '$ForumID'");

		G::$DB->query("
			UPDATE forums_topics
			SET
				NumPosts = NumPosts + 1,
				LastPostID = '$PostID',
				LastPostAuthorID = '$AuthorID',
				LastPostTime = '".sqltime()."'
			WHERE ID = '$TopicID'");

		// Bump this topic to head of the cache
		list($Forum,,, $Stickies) = G::$Cache->get_value("forums_$ForumID");
		if (!empty($Forum)) {
			if (count($Forum) == TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
				array_pop($Forum);
			}
			G::$DB->query("
				SELECT IsLocked, IsSticky, NumPosts
				FROM forums_topics
				WHERE ID ='$TopicID'");
			list($IsLocked, $IsSticky, $NumPosts) = G::$DB->next_record();
			$Part1 = array_slice($Forum, 0, $Stickies, true); //Stickys
			$Part2 = array(
				$TopicID => array(
					'ID' => $TopicID,
					'Title' => $Title,
					'AuthorID' => $AuthorID,
					'IsLocked' => $IsLocked,
					'IsSticky' => $IsSticky,
					'NumPosts' => $NumPosts,
					'LastPostID' => $PostID,
					'LastPostTime' => sqltime(),
					'LastPostAuthorID' => $AuthorID,
					)
				); //Bumped thread
			$Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE, true); //Rest of page
			if ($Stickies > 0) {
				$Part1 = array_slice($Forum, 0, $Stickies, true); //Stickies
				$Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); //Rest of page
			} else {
				$Part1 = array();
				$Part3 = $Forum;
			}
			if (is_null($Part1)) {
				$Part1 = array();
			}
			if (is_null($Part3)) {
				$Part3 = array();
			}
			$Forum = $Part1 + $Part2 + $Part3;
			G::$Cache->cache_value("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);
		}

		//Update the forum root
		G::$Cache->begin_transaction('forums_list');
		$UpdateArray = array(
			'NumPosts' => '+1',
			'NumTopics' => '+1',
			'LastPostID' => $PostID,
			'LastPostAuthorID' => $AuthorID,
			'LastPostTopicID' => $TopicID,
			'LastPostTime' => sqltime(),
			'Title' => $Title,
			'IsLocked' => $ThreadInfo['IsLocked'],
			'IsSticky' => $ThreadInfo['IsSticky']
			);

		$UpdateArray['NumTopics'] = '+1';

		G::$Cache->update_row($ForumID, $UpdateArray);
		G::$Cache->commit_transaction(0);

		$CatalogueID = floor((POSTS_PER_PAGE * ceil($Posts / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);
		G::$Cache->begin_transaction('thread_'.$TopicID.'_catalogue_'.$CatalogueID);
		$Post = array(
			'ID' => $PostID,
			'AuthorID' => G::$LoggedUser['ID'],
			'AddedTime' => sqltime(),
			'Body' => $PostBody,
			'EditedUserID' => 0,
			'EditedTime' => '0000-00-00 00:00:00',
			'Username' => ''
			);
		G::$Cache->insert('', $Post);
		G::$Cache->commit_transaction(0);

		G::$Cache->begin_transaction('thread_'.$TopicID.'_info');
		G::$Cache->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $AuthorID));
		G::$Cache->commit_transaction(0);

		G::$DB->set_query_id($QueryID);

		return $TopicID;
	}

	/**
	 * If the suffix of $Haystack is $Needle
	 *
	 * @param string $Haystack String to search in
	 * @param string $Needle String to search for
	 * @return boolean True if $Needle is a suffix of $Haystack
	 */
	public static function ends_with($Haystack, $Needle) {
		return substr($Haystack, strlen($Needle) * -1) == $Needle;
	}


	/**
	 * If the prefix of $Haystack is $Needle
	 *
	 * @param string $Haystack String to search in
	 * @param string $Needle String to search for
	 * @return boolean True if $Needle is a prefix of $Haystack
	 */
	public static function starts_with($Haystack, $Needle) {
		return strpos($Haystack, $Needle) === 0;
	}

	/**
	 * Variant of in_array() with trailing wildcard support
	 *
	 * @param string $Needle, array $Haystack
	 * @return boolean true if (substring of) $Needle exists in $Haystack
	 */
	public static function in_array_partial($Needle, $Haystack) {
		static $Searches = array();
		if (array_key_exists($Needle, $Searches)) {
			return $Searches[$Needle];
		}
		foreach ($Haystack as $String) {
			if (substr($String, -1) == '*') {
				if (!strncmp($Needle, $String, strlen($String) - 1)) {
					$Searches[$Needle] = true;
					return true;
				}
			} elseif (!strcmp($Needle, $String)) {
				$Searches[$Needle] = true;
				return true;
			}
		}
		$Searches[$Needle] = false;
		return false;
	}

	/**
	 * Used to check if keys in $_POST and $_GET are all set, and throws an error if not.
	 * This reduces 'if' statement redundancy for a lot of variables
	 *
	 * @param array $Request Either $_POST or $_GET, or whatever other array you want to check.
	 * @param array $Keys The keys to ensure are set.
	 * @param boolean $AllowEmpty If set to true, a key that is in the request but blank will not throw an error.
	 * @param int $Error The error code to throw if one of the keys isn't in the array.
	 */
	public static function assert_isset_request($Request, $Keys = null, $AllowEmpty = false, $Error = 0) {
		if (isset($Keys)) {
			foreach ($Keys as $K) {
				if (!isset($Request[$K]) || ($AllowEmpty == false && $Request[$K] == '')) {
					error($Error);
					break;
				}
			}
		} else {
			foreach ($Request as $R) {
				if (!isset($R) || ($AllowEmpty == false && $R == '')) {
					error($Error);
					break;
				}
			}
		}
	}


	/**
	 * Given an array of tags, return an array of their IDs.
	 *
	 * @param array $TagNames
	 * @return array IDs
	 */
	public static function get_tags($TagNames) {
		$TagIDs = array();
		foreach ($TagNames as $Index => $TagName) {
			$Tag = G::$Cache->get_value("tag_id_$TagName");
			if (is_array($Tag)) {
				unset($TagNames[$Index]);
				$TagIDs[$Tag['ID']] = $Tag['Name'];
			}
		}
		if (count($TagNames) > 0) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT ID, Name
				FROM tags
				WHERE Name IN ('".implode("', '", $TagNames)."')");
			$SQLTagIDs = G::$DB->to_array();
			G::$DB->set_query_id($QueryID);
			foreach ($SQLTagIDs as $Tag) {
				$TagIDs[$Tag['ID']] = $Tag['Name'];
				G::$Cache->cache_value('tag_id_'.$Tag['Name'], $Tag, 0);
			}
		}

		return($TagIDs);
	}


	/**
	 * Gets the alias of the tag; if there is no alias, silently returns the original tag.
	 *
	 * @param string $BadTag the tag we want to alias
	 * @return string The aliased tag.
	 */
	public static function get_alias_tag($BadTag) {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT AliasTag
			FROM tag_aliases
			WHERE BadTag = '$BadTag'
			LIMIT 1");
		if (G::$DB->has_results()) {
			list($AliasTag) = G::$DB->next_record();
		} else {
			$AliasTag = $BadTag;
		}
		G::$DB->set_query_id($QueryID);
		return $AliasTag;
	}


	/*
	 * Write a message to the system log.
	 *
	 * @param string $Message the message to write.
	 */
	public static function write_log($Message) {
		global $Time;
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			INSERT INTO log (Message, Time)
			VALUES ('" . db_string($Message) . "', '" . sqltime() . "')");
		G::$DB->set_query_id($QueryID);
	}


	/**
	 * Get a tag ready for database input and display.
	 *
	 * @param string $Str
	 * @return sanitized version of $Str
	 */
	public static function sanitize_tag($Str) {
		$Str = strtolower($Str);
		$Str = preg_replace('/[^a-z0-9.]/', '', $Str);
		$Str = preg_replace('/(^[.,]*)|([.,]*$)/', '', $Str);
		$Str = htmlspecialchars($Str);
		$Str = db_string(trim($Str));
		return $Str;
	}

	/**
	 * HTML escape an entire array for output.
	 * @param array $Array, what we want to escape
	 * @param boolean/array $Escape
	 *	if true, all keys escaped
	 *	if false, no escaping.
	 *	If array, it's a list of array keys not to escape.
	 * @return mutated version of $Array with values escaped.
	 */
	public static function display_array($Array, $Escape = array()) {
		foreach ($Array as $Key => $Val) {
			if ((!is_array($Escape) && $Escape == true) || !in_array($Key, $Escape)) {
				$Array[$Key] = display_str($Val);
			}
		}
		return $Array;
	}

	/**
	 * Searches for a key/value pair in an array.
	 *
	 * @return array of results
	 */
	public static function search_array($Array, $Key, $Value) {
		$Results = array();
		if (is_array($Array))
		{
			if (isset($Array[$Key]) && $Array[$Key] == $Value) {
				$Results[] = $Array;
			}

			foreach ($Array as $subarray) {
				$Results = array_merge($Results, self::search_array($subarray, $Key, $Value));
			}
		}
		return $Results;
	}

	/**
	 * Search for $Needle in the string $Haystack which is a list of values separated by $Separator.
	 * @param string $Haystack
	 * @param string $Needle
	 * @param string $Separator
	 * @param boolean $Strict
	 * @return boolean
	 */
	public static function search_joined_string($Haystack, $Needle, $Separator = '|', $Strict = true) {
		return (array_search($Needle, explode($Separator, $Haystack), $Strict) !== false);
	}

	/**
	 * Check for a ":" in the beginning of a torrent meta data string
	 * to see if it's stored in the old base64-encoded format
	 *
	 * @param string $Torrent the torrent data
	 * @return true if the torrent is stored in binary format
	 */
	public static function is_new_torrent(&$Data) {
		return strpos(substr($Data, 0, 10), ':') !== false;
	}

	public static function display_recommend($ID, $Type, $Hide = true) {
		if ($Hide) {
			$Hide = ' style="display: none;"';
		}
		?>
		<div id="recommendation_div" data-id="<?=$ID?>" data-type="<?=$Type?>"<?=$Hide?> class="center">
			<div style="display: inline-block;">
				<strong>Recommend to:</strong>
				<select id="friend" name="friend">
					<option value="0" selected="selected">Choose friend</option>
				</select>
				<input type="text" id="recommendation_note" placeholder="Add note..." />
				<button id="send_recommendation" disabled="disabled">Send</button>
			</div>
			<div class="new" id="recommendation_status"><br /></div>
		</div>
<?
	}

	public static function is_valid_url($URL) {
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $URL);
	}
}
?>
