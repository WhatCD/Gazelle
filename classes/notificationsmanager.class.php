<?

class NotificationsManager {
	// Option types
	const OPT_DISABLED = 0;
	const OPT_POPUP = 1;
	const OPT_TRADITIONAL = 2;
	const OPT_PUSH = 3;
	const OPT_POPUP_PUSH = 4;
	const OPT_TRADITIONAL_PUSH = 5;

	// Importances
	const IMPORTANT = 'information';
	const CRITICAL = 'error';
	const WARNING = 'warning';
	const INFO = 'confirmation';

	public static $Importances = array(
		'important' => self::IMPORTANT,
		'critical' => self::CRITICAL,
		'warning' => self::WARNING,
		'info' => self::INFO);

	// Types. These names must correspond to column names in users_notifications_settings
	const NEWS = 'News';
	const BLOG = 'Blog';
	const STAFFBLOG = 'StaffBlog';
	const STAFFPM = 'StaffPM';
	const INBOX = 'Inbox';
	const QUOTES = 'Quotes';
	const SUBSCRIPTIONS = 'Subscriptions';
	const TORRENTS = 'Torrents';
	const COLLAGES = 'Collages';
	const SITEALERTS = 'SiteAlerts';
	const FORUMALERTS = 'ForumAlerts';
	const REQUESTALERTS = 'RequestAlerts';
	const COLLAGEALERTS = 'CollageAlerts';
	const TORRENTALERTS = 'TorrentAlerts';
	const GLOBALNOTICE = 'Global';

	public static $Types = array(
		'News',
		'Blog',
		'StaffPM',
		'Inbox',
		'Quotes',
		'Subscriptions',
		'Torrents',
		'Collages',
		'SiteAlerts',
		'ForumAlerts',
		'RequestAlerts',
		'CollageAlerts',
		'TorrentAlerts');

	private $UserID;
	private $Notifications;
	private $Settings;
	private $Skipped;

	function __construct($UserID, $Skip = array(), $Load = true, $AutoSkip = true) {
		$this->UserID = $UserID;
		$this->Notifications = array();
		$this->Settings = self::get_settings($UserID);
		$this->Skipped = $Skip;
		if ($AutoSkip) {
			foreach ($this->Settings as $Key => $Value) {
				// Skip disabled and traditional settings
				if ($Value == self::OPT_DISABLED || $this->is_traditional($Key)) {
					$this->Skipped[$Key] = true;
				}
			}
		}
		if ($Load) {
			$this->load_global_notification();
			if (!isset($this->Skipped[self::NEWS])) {
				$this->load_news();
			}
			if (!isset($this->Skipped[self::BLOG])) {
				$this->load_blog();
			}
			// if (!isset($this->Skipped[self::STAFFBLOG])) {
			// 	$this->load_staff_blog();
			// }
			if (!isset($this->Skipped[self::STAFFPM])) {
				$this->load_staff_pms();
			}
			if (!isset($this->Skipped[self::INBOX])) {
				$this->load_inbox();
			}
			if (!isset($this->Skipped[self::TORRENTS])) {
				$this->load_torrent_notifications();
			}
			if (!isset($this->Skipped[self::COLLAGES])) {
				$this->load_collage_subscriptions();
			}
			if (!isset($this->Skipped[self::QUOTES])) {
				$this->load_quote_notifications();
			}
			if (!isset($this->Skipped[self::SUBSCRIPTIONS])) {
				$this->load_subscriptions();
			}
			// $this->load_one_reads(); // The code that sets these notices is commented out.
		}
	}

	public function get_notifications() {
		return $this->Notifications;
	}

	public function clear_notifications_array() {
		unset($this->Notifications);
		$this->Notifications = array();
	}

	private function create_notification($Type, $ID, $Message, $URL, $Importance) {
		$this->Notifications[$Type] = array(
			'id' => (int)$ID,
			'message' => $Message,
			'url' => $URL,
			'importance' => $Importance);
	}

	public static function notify_user($UserID, $Type, $Message, $URL, $Importance) {
		self::notify_users(array($UserID), $Type, $Message, $URL, $Importance);
	}

	public static function notify_users($UserIDs, $Type, $Message, $URL, $Importance) {
		/**
		if (!isset($Importance)) {
			$Importance = self::INFO;
		}
		$Type = db_string($Type);
		if (!empty($UserIDs)) {
			$UserIDs = implode(',', $UserIDs);
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT UserID
				FROM users_notifications_settings
				WHERE $Type != 0
					AND UserID IN ($UserIDs)");
			$UserIDs = array();
			while (list($ID) = G::$DB->next_record()) {
				$UserIDs[] = $ID;
			}
			G::$DB->set_query_id($QueryID);
			foreach ($UserIDs as $UserID) {
				$OneReads = G::$Cache->get_value("notifications_one_reads_$UserID");
				if (!$OneReads) {
					$OneReads = array();
				}
				array_unshift($OneReads, $this->create_notification($OneReads, "oneread_" . uniqid(), null, $Message, $URL, $Importance));
				$OneReads = array_filter($OneReads);
				G::$Cache->cache_value("notifications_one_reads_$UserID", $OneReads, 0);
			}
		}
		**/
	}

	public static function get_notification_enabled_users($Type, $UserID) {
		$Type = db_string($Type);
		$UserWhere = '';
		if (isset($UserID)) {
			$UserID = (int)$UserID;
			$UserWhere = " AND UserID = '$UserID'";
		}
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT UserID
			FROM users_notifications_settings
			WHERE $Type != 0
				$UserWhere");
		$IDs = array();
		while (list($ID) = G::$DB->next_record()) {
			$IDs[] = $ID;
		}
		G::$DB->set_query_id($QueryID);
		return $IDs;
	}

	public function load_one_reads() {
		$OneReads = G::$Cache->get_value('notifications_one_reads_' . G::$LoggedUser['ID']);
		if (is_array($OneReads)) {
			$this->Notifications = $this->Notifications + $OneReads;
		}
	}

	public static function clear_one_read($ID) {
		$OneReads = G::$Cache->get_value('notifications_one_reads_' . G::$LoggedUser['ID']);
		if ($OneReads) {
			unset($OneReads[$ID]);
			if (count($OneReads) > 0) {
				G::$Cache->cache_value('notifications_one_reads_' . G::$LoggedUser['ID'], $OneReads, 0);
			} else {
				G::$Cache->delete_value('notifications_one_reads_' . G::$LoggedUser['ID']);
			}
		}

	}

	public function load_global_notification() {
		$GlobalNotification = G::$Cache->get_value('global_notification');
		if ($GlobalNotification) {
			$Read = G::$Cache->get_value('user_read_global_' . G::$LoggedUser['ID']);
			if (!$Read) {
				$this->create_notification(self::GLOBALNOTICE, 0,  $GlobalNotification['Message'], $GlobalNotification['URL'], $GlobalNotification['Importance']);
			}
		}
	}

	public static function get_global_notification() {
		return G::$Cache->get_value('global_notification');
	}

	public static function set_global_notification($Message, $URL, $Importance, $Expiration) {
		if (empty($Message) || empty($Expiration)) {
			error('Error setting notification');
		}
		G::$Cache->cache_value('global_notification', array("Message" => $Message, "URL" => $URL, "Importance" => $Importance, "Expiration" => $Expiration), $Expiration);
	}

	public static function delete_global_notification() {
		G::$Cache->delete_value('global_notification');
	}

	public static function clear_global_notification() {
		$GlobalNotification = G::$Cache->get_value('global_notification');
		if ($GlobalNotification) {
			// This is some trickery
			// since we can't know which users have the read cache key set
			// we set the expiration time of their cache key to that of the length of the notification
			// this gaurantees that their cache key will expire after the notification expires
			G::$Cache->cache_value('user_read_global_' . G::$LoggedUser['ID'], true, $GlobalNotification['Expiration']);
		}
	}

	public function load_news() {
		$MyNews = G::$LoggedUser['LastReadNews'];
		$CurrentNews = G::$Cache->get_value('news_latest_id');
		$Title = G::$Cache->get_value('news_latest_title');
		if ($CurrentNews === false || $Title === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query('
				SELECT ID, Title
				FROM news
				ORDER BY Time DESC
				LIMIT 1');
			if (G::$DB->has_results()) {
				list($CurrentNews, $Title) = G::$DB->next_record();
			} else {
				$CurrentNews = -1;
			}
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('news_latest_id', $CurrentNews, 0);
			G::$Cache->cache_value('news_latest_title', $Title, 0);
		}
		if ($MyNews < $CurrentNews) {
			$this->create_notification(self::NEWS, $CurrentNews, "Announcement: $Title", "index.php#news$CurrentNews", self::IMPORTANT);
		}
	}

	public function load_blog() {
		$MyBlog = G::$LoggedUser['LastReadBlog'];
		$CurrentBlog = G::$Cache->get_value('blog_latest_id');
		$Title = G::$Cache->get_value('blog_latest_title');
		if ($CurrentBlog === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query('
				SELECT ID, Title
				FROM blog
				WHERE Important = 1
				ORDER BY Time DESC
				LIMIT 1');
			if (G::$DB->has_results()) {
				list($CurrentBlog, $Title) = G::$DB->next_record();
			} else {
				$CurrentBlog = -1;
			}
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('blog_latest_id', $CurrentBlog, 0);
			G::$Cache->cache_value('blog_latest_title', $Title, 0);
		}
		if ($MyBlog < $CurrentBlog) {
			$this->create_notification(self::BLOG, $CurrentBlog, "Blog: $Title", "blog.php#blog$CurrentBlog", self::IMPORTANT);
		}
	}

	public function load_staff_blog() {
		if (check_perms('users_mod')) {
			global $SBlogReadTime, $LatestSBlogTime;
			if (!$SBlogReadTime && ($SBlogReadTime = G::$Cache->get_value('staff_blog_read_' . G::$LoggedUser['ID'])) === false) {
				$QueryID = G::$DB->get_query_id();
				G::$DB->query("
					SELECT Time
					FROM staff_blog_visits
					WHERE UserID = " . G::$LoggedUser['ID']);
				if (list($SBlogReadTime) = G::$DB->next_record()) {
					$SBlogReadTime = strtotime($SBlogReadTime);
				} else {
					$SBlogReadTime = 0;
				}
				G::$DB->set_query_id($QueryID);
				G::$Cache->cache_value('staff_blog_read_' . G::$LoggedUser['ID'], $SBlogReadTime, 1209600);
			}
			if (!$LatestSBlogTime && ($LatestSBlogTime = G::$Cache->get_value('staff_blog_latest_time')) === false) {
				$QueryID = G::$DB->get_query_id();
				G::$DB->query('
					SELECT MAX(Time)
					FROM staff_blog');
				if (list($LatestSBlogTime) = G::$DB->next_record()) {
					$LatestSBlogTime = strtotime($LatestSBlogTime);
				} else {
					$LatestSBlogTime = 0;
				}
				G::$DB->set_query_id($QueryID);
				G::$Cache->cache_value('staff_blog_latest_time', $LatestSBlogTime, 1209600);
			}
			if ($SBlogReadTime < $LatestSBlogTime) {
				$this->create_notification(self::STAFFBLOG, 0, 'New Staff Blog Post!', 'staffblog.php', self::IMPORTANT);
			}
		}
	}

	public function load_staff_pms() {
		$NewStaffPMs = G::$Cache->get_value('staff_pm_new_' . G::$LoggedUser['ID']);
		if ($NewStaffPMs === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT COUNT(ID)
				FROM staff_pm_conversations
				WHERE UserID = '" . G::$LoggedUser['ID'] . "'
					AND Unread = '1'");
			list($NewStaffPMs) = G::$DB->next_record();
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('staff_pm_new_' . G::$LoggedUser['ID'], $NewStaffPMs, 0);
		}

		if ($NewStaffPMs > 0) {
			$Title = 'You have ' . ($NewStaffPMs == 1 ? 'a' : $NewStaffPMs) . ' new Staff PM' . ($NewStaffPMs > 1 ? 's' : '');
			$this->create_notification(self::STAFFPM, 0, $Title, 'staffpm.php', self::INFO);
		}
	}

	public function load_inbox() {
		$NewMessages = G::$Cache->get_value('inbox_new_' . G::$LoggedUser['ID']);
		if ($NewMessages === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT COUNT(UnRead)
				FROM pm_conversations_users
				WHERE UserID = '" . G::$LoggedUser['ID'] . "'
					AND UnRead = '1'
					AND InInbox = '1'");
			list($NewMessages) = G::$DB->next_record();
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('inbox_new_' . G::$LoggedUser['ID'], $NewMessages, 0);
		}

		if ($NewMessages > 0) {
			$Title = 'You have ' . ($NewMessages == 1 ? 'a' : $NewMessages) . ' new message' . ($NewMessages > 1 ? 's' : '');
			$this->create_notification(self::INBOX, 0, $Title, Inbox::get_inbox_link(), self::INFO);
		}
	}

	public function load_torrent_notifications() {
		if (check_perms('site_torrents_notify')) {
			$NewNotifications = G::$Cache->get_value('notifications_new_' . G::$LoggedUser['ID']);
			if ($NewNotifications === false) {
				$QueryID = G::$DB->get_query_id();
				G::$DB->query("
					SELECT COUNT(UserID)
					FROM users_notify_torrents
					WHERE UserID = ' " . G::$LoggedUser['ID'] . "'
						AND UnRead = '1'");
				list($NewNotifications) = G::$DB->next_record();
				G::$DB->set_query_id($QueryID);
				G::$Cache->cache_value('notifications_new_' . G::$LoggedUser['ID'], $NewNotifications, 0);
			}
		}
		if ($NewNotifications > 0) {
			$Title = 'You have ' . ($NewNotifications == 1 ? 'a' : $NewNotifications) . ' new torrent notification' . ($NewNotifications > 1 ? 's' : '');
			$this->create_notification(self::TORRENTS, 0, $Title, 'torrents.php?action=notify', self::INFO);
		}
	}

	public function load_collage_subscriptions() {
		if (check_perms('site_collages_subscribe')) {
			$NewCollages = G::$Cache->get_value('collage_subs_user_new_' . G::$LoggedUser['ID']);
			if ($NewCollages === false) {
					$QueryID = G::$DB->get_query_id();
					G::$DB->query("
						SELECT COUNT(DISTINCT s.CollageID)
						FROM users_collage_subs AS s
							JOIN collages AS c ON s.CollageID = c.ID
							JOIN collages_torrents AS ct ON ct.CollageID = c.ID
						WHERE s.UserID = " . G::$LoggedUser['ID'] . "
							AND ct.AddedOn > s.LastVisit
							AND c.Deleted = '0'");
					list($NewCollages) = G::$DB->next_record();
					G::$DB->set_query_id($QueryID);
					G::$Cache->cache_value('collage_subs_user_new_' . G::$LoggedUser['ID'], $NewCollages, 0);
			}
			if ($NewCollages > 0) {
				$Title = 'You have ' . ($NewCollages == 1 ? 'a' : $NewCollages) . ' new collage update' . ($NewCollages > 1 ? 's' : '');
				$this->create_notification(self::COLLAGES, 0, $Title, 'userhistory.php?action=subscribed_collages', self::INFO);
			}
		}
	}

	public function load_quote_notifications() {
		if (G::$LoggedUser['NotifyOnQuote']) {
			$QuoteNotificationsCount = Subscriptions::has_new_quote_notifications();
			if ($QuoteNotificationsCount > 0) {
				$Title = 'New quote' . ($QuoteNotificationsCount > 1 ? 's' : '');
				$this->create_notification(self::QUOTES, 0, $Title, 'userhistory.php?action=quote_notifications', self::INFO);
			}
		}
	}

	public function load_subscriptions() {
		$SubscriptionsCount = Subscriptions::has_new_subscriptions();
		if ($SubscriptionsCount > 0) {
			$Title = 'New subscription' . ($SubscriptionsCount > 1 ? 's' : '');
			$this->create_notification(self::SUBSCRIPTIONS, 0, $Title, 'userhistory.php?action=subscriptions', self::INFO);
		}
	}

	public static function clear_news($News) {
		$QueryID = G::$DB->get_query_id();
		if (!$News) {
			if (!$News = G::$Cache->get_value('news')) {
				G::$DB->query('
					SELECT
						ID,
						Title,
						Body,
						Time
					FROM news
					ORDER BY Time DESC
					LIMIT 1');
				$News = G::$DB->to_array(false, MYSQLI_NUM, false);
				G::$Cache->cache_value('news_latest_id', $News[0][0], 0);
			}
		}

		if (G::$LoggedUser['LastReadNews'] != $News[0][0]) {
			G::$Cache->begin_transaction('user_info_heavy_' . G::$LoggedUser['ID']);
			G::$Cache->update_row(false, array('LastReadNews' => $News[0][0]));
			G::$Cache->commit_transaction(0);
			G::$DB->query("
				UPDATE users_info
				SET LastReadNews = '".$News[0][0]."'
				WHERE UserID = " . G::$LoggedUser['ID']);
			G::$LoggedUser['LastReadNews'] = $News[0][0];
		}
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_blog($Blog) {
		$QueryID = G::$DB->get_query_id();
		if (!$Blog) {
			if (!$Blog = G::$Cache->get_value('blog')) {
				G::$DB->query("
					SELECT
						b.ID,
						um.Username,
						b.UserID,
						b.Title,
						b.Body,
						b.Time,
						b.ThreadID
					FROM blog AS b
						LEFT JOIN users_main AS um ON b.UserID = um.ID
					ORDER BY Time DESC
					LIMIT 1");
				$Blog = G::$DB->to_array();
			}
		}
		if (G::$LoggedUser['LastReadBlog'] < $Blog[0][0]) {
			G::$Cache->begin_transaction('user_info_heavy_' . G::$LoggedUser['ID']);
			G::$Cache->update_row(false, array('LastReadBlog' => $Blog[0][0]));
			G::$Cache->commit_transaction(0);
			G::$DB->query("
				UPDATE users_info
				SET LastReadBlog = '". $Blog[0][0]."'
				WHERE UserID = " . G::$LoggedUser['ID']);
			G::$LoggedUser['LastReadBlog'] = $Blog[0][0];
		}
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_staff_pms() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT ID
			FROM staff_pm_conversations
			WHERE Unread = true
				AND UserID = " . G::$LoggedUser['ID']);
		$IDs = array();
		while (list($ID) = G::$DB->next_record()) {
			$IDs[] = $ID;
		}
		$IDs = implode(',', $IDs);
		if (!empty($IDs)) {
			G::$DB->query("
				UPDATE staff_pm_conversations
				SET Unread = false
				WHERE ID IN ($IDs)");
		}
		G::$Cache->delete_value('staff_pm_new_' . G::$LoggedUser['ID']);
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_inbox() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT ConvID
			FROM pm_conversations_users
			WHERE Unread = '1'
				AND UserID = " . G::$LoggedUser['ID']);
		$IDs = array();
		while (list($ID) = G::$DB->next_record()) {
			$IDs[] = $ID;
		}
		$IDs = implode(',', $IDs);
		if (!empty($IDs)) {
			G::$DB->query("
				UPDATE pm_conversations_users
				SET Unread = '0'
				WHERE ConvID IN ($IDs)
					AND UserID = " . G::$LoggedUser['ID']);
		}
		G::$Cache->delete_value('inbox_new_' . G::$LoggedUser['ID']);
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_torrents() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT TorrentID
			FROM users_notify_torrents
			WHERE UserID = ' " . G::$LoggedUser['ID'] . "'
				AND UnRead = '1'");
		$IDs = array();
		while (list($ID) = G::$DB->next_record()) {
			$IDs[] = $ID;
		}
		$IDs = implode(',', $IDs);
		if (!empty($IDs)) {
			G::$DB->query("
				UPDATE users_notify_torrents
				SET Unread = '0'
				WHERE TorrentID IN ($IDs)
					AND UserID = " . G::$LoggedUser['ID']);
		}
		G::$Cache->delete_value('notifications_new_' . G::$LoggedUser['ID']);
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_collages() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			UPDATE users_collage_subs
			SET LastVisit = NOW()
			WHERE UserID = " . G::$LoggedUser['ID']);
		G::$Cache->delete_value('collage_subs_user_new_' . G::$LoggedUser['ID']);
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_quotes() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			UPDATE users_notify_quoted
			SET UnRead = '0'
			WHERE UserID = " . G::$LoggedUser['ID']);
		G::$Cache->delete_value('notify_quoted_' . G::$LoggedUser['ID']);
		G::$DB->set_query_id($QueryID);
	}

	public static function clear_subscriptions() {
		$QueryID = G::$DB->get_query_id();
		if (($UserSubscriptions = G::$Cache->get_value('subscriptions_user_' . G::$LoggedUser['ID'])) === false) {
			G::$DB->query("
				SELECT TopicID
				FROM users_subscriptions
				WHERE UserID = " . G::$LoggedUser['ID']);
			if ($UserSubscriptions = G::$DB->collect(0)) {
				G::$Cache->cache_value('subscriptions_user_' . G::$LoggedUser['ID'], $UserSubscriptions, 0);
			}
		}
		if (!empty($UserSubscriptions)) {
			G::$DB->query("
				INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
					SELECT '" . G::$LoggedUser['ID'] . "', ID, LastPostID
					FROM forums_topics
					WHERE ID IN (".implode(',', $UserSubscriptions).')
				ON DUPLICATE KEY UPDATE
					PostID = LastPostID');
		}
		G::$Cache->delete_value('subscriptions_user_new_' . G::$LoggedUser['ID']);
		G::$DB->set_query_id($QueryID);
	}

/*
	// TODO: Figure out what these functions are supposed to do and fix them
	public static function send_notification($UserID, $ID, $Type, $Message, $URL, $Importance = 'alert', $AutoExpire = false) {
		$Notifications = G::$Cache->get_value("user_cache_notifications_$UserID");
		if (empty($Notifications)) {
			$Notifications = array();
		}
		array_unshift($Notifications, $this->create_notification($Type, $ID, $Message, $URL, $Importance, $AutoExpire));
		G::$Cache->cache_value("user_cache_notifications_$UserID", $Notifications, 0);
	}

	public static function clear_notification($UserID, $Index) {
		$Notifications = G::$Cache->get_value("user_cache_notifications_$UserID");
		if (count($Notifications)) {
			unset($Notifications[$Index]);
			$Notifications = array_values($Notifications);
			G::$Cache->cache_value("user_cache_notifications_$UserID", $Notifications, 0);
		}
	}
*/

	public static function get_settings($UserID) {
		$Results = G::$Cache->get_value("users_notifications_settings_$UserID");
		if (!$Results) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT *
				FROM users_notifications_settings AS n
				LEFT JOIN users_push_notifications AS p
				ON p.UserID = n.UserID
				WHERE n.UserID = '$UserID'");
			$Results = G::$DB->next_record(MYSQLI_ASSOC, false);
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value("users_notifications_settings_$UserID", $Results, 0);
		}
		return $Results;
	}

	public static function save_settings($UserID, $Settings) {
		if (!is_array($Settings)) {
			// A little cheat technique, gets all keys in the $_POST array starting with 'notifications_'
			$Settings = array_intersect_key($_POST, array_flip(preg_grep('/^notifications_/', array_keys($_POST))));
		}
		$Update = array();
		foreach (self::$Types as $Type) {
			$Popup = array_key_exists("notifications_{$Type}_popup", $Settings);
			$Traditional = array_key_exists("notifications_{$Type}_traditional", $Settings);
			$Push = array_key_exists("notifications_{$Type}_push", $Settings);
			$Result = self::OPT_DISABLED;
			if ($Popup) {
				$Result = $Push ? self::OPT_POPUP_PUSH : self::OPT_POPUP;
			} elseif ($Traditional) {
				$Result = $Push ? self::OPT_TRADITIONAL_PUSH : self::OPT_TRADITIONAL;
			} elseif ($Push) {
				$Result = self::OPT_PUSH;
			}
			$Update[] = "$Type = $Result";
		}
		$Update = implode(',', $Update);

		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			UPDATE users_notifications_settings
			SET $Update
			WHERE UserID = '$UserID'");

		$PushService = (int) $_POST['pushservice'];
		$PushOptionsArray = array("PushKey" => $_POST['pushkey']);
		if ($PushService === 6) { //pushbullet
			$PushOptionsArray['PushDevice'] = $_POST['pushdevice'];
		}
		$PushOptions = db_string(serialize($PushOptionsArray));

		if ($PushService != 0) {
			G::$DB->query("
					INSERT INTO users_push_notifications
						(UserID, PushService, PushOptions)
					VALUES
						('$UserID', '$PushService', '$PushOptions')
					ON DUPLICATE KEY UPDATE
						PushService = '$PushService',
						PushOptions = '$PushOptions'");
		} else {
			G::$DB->query("UPDATE users_push_notifications SET PushService = 0 WHERE UserID = '$UserID'");
		}

		G::$DB->set_query_id($QueryID);
		G::$Cache->delete_value("users_notifications_settings_$UserID");
	}

	public function is_traditional($Type) {
		return $this->Settings[$Type] == self::OPT_TRADITIONAL || $this->Settings[$Type] == self::OPT_TRADITIONAL_PUSH;
	}

	public function is_skipped($Type) {
		return isset($this->Skipped[$Type]);
	}

	public function use_noty() {
		return in_array(self::OPT_POPUP, $this->Settings) || in_array(self::OPT_POPUP_PUSH, $this->Settings);
	}

	/**
	 * Send a push notification to a user
	 *
	 * @param array $UserIDs integer or array of integers of UserIDs to push
	 * @param string $Title the title to be displayed in the push
	 * @param string $Body the body of the push
	 * @param string $URL url for the push notification to contain
	 * @param string $Type what sort of push is it? PM, Rippy, News, etc
	 */
	public static function send_push($UserIDs, $Title, $Body, $URL = '', $Type = self::GLOBALNOTICE) {
		if (!is_array($UserIDs)) {
			$UserIDs = array($UserIDs);
		}
		foreach($UserIDs as $UserID) {
			$UserID = (int) $UserID;
			$QueryID = G::$DB->get_query_id();
			$SQL = "
				SELECT
					p.PushService, p.PushOptions
				FROM users_notifications_settings AS n
					JOIN users_push_notifications AS p ON n.UserID = p.UserID
				WHERE n.UserID = '$UserID'
				AND p.PushService != 0";
			if ($Type != self::GLOBALNOTICE) {
				$SQL .= " AND n.$Type IN (" . self::OPT_PUSH . "," . self::OPT_POPUP_PUSH . "," . self::OPT_TRADITIONAL_PUSH . ")";
			}
			G::$DB->query($SQL);

			if (G::$DB->has_results()) {
				list($PushService, $PushOptions) = G::$DB->next_record(MYSQLI_NUM, false);
				$PushOptions = unserialize($PushOptions);
				switch ($PushService) {
					case '1':
						$Service = "NMA";
						break;
					case '2':
						$Service = "Prowl";
						break;
					// Case 3 is missing because notifo is dead.
					case '4':
						$Service = "Toasty";
						break;
					case '5':
						$Service = "Pushover";
						break;
					case '6':
						$Service = "PushBullet";
						break;
					default:
						break;
					}
					if (!empty($Service) && !empty($PushOptions['PushKey'])) {
						$Options = array("service" => strtolower($Service),
										"user" => array("key" => $PushOptions['PushKey']),
										"message" => array("title" => $Title, "body" => $Body, "url" => $URL));

						if ($Service === 'PushBullet') {
							$Options["user"]["device"] = $PushOptions['PushDevice'];

						}

						$JSON = json_encode($Options);
						G::$DB->query("
							INSERT INTO push_notifications_usage
								(PushService, TimesUsed)
							VALUES
								('$Service', 1)
							ON DUPLICATE KEY UPDATE
								TimesUsed = TimesUsed + 1");

						$PushServerSocket = fsockopen("127.0.0.1", 6789);
						fwrite($PushServerSocket, $JSON);
						fclose($PushServerSocket);
					}
			}
			G::$DB->set_query_id($QueryID);
		}
	}

	/**
	 * Gets users who have push notifications enabled
	 *
	 */
	public static function get_push_enabled_users() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT UserID
			FROM users_push_notifications
			WHERE PushService != 0
				AND UserID != '" . G::$LoggedUser['ID']. "'");
		$PushUsers = G::$DB->collect("UserID");
		G::$DB->set_query_id($QueryID);
		return $PushUsers;
	}
}
