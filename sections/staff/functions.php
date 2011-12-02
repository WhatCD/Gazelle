<?
function get_fls() {
	global $Cache, $DB;
	static $FLS;
	if(is_array($FLS)) {
		return $FLS;
	}
	if(($FLS = $Cache->get_value('fls')) === false) {
		$DB->query("SELECT
			m.ID,
			p.Level,
			m.Username,
			m.Paranoia,
			m.LastAccess,
			i.SupportFor
			FROM users_info AS i
			JOIN users_main AS m ON m.ID=i.UserID
			JOIN permissions AS p ON p.ID=m.PermissionID
			WHERE p.DisplayStaff!='1' AND i.SupportFor!=''");
		$FLS = $DB->to_array(false, MYSQLI_BOTH, array(3,'Paranoia'));
		$Cache->cache_value('fls', $FLS, 180);
	}
	return $FLS;
}

function get_forum_staff() {
	global $Cache, $DB;
	static $ForumStaff;
	if(is_array($ForumStaff)) {
		return $ForumStaff;
	}
	if(($ForumStaff = $Cache->get_value('forum_staff')) === false) {
		$DB->query("SELECT
			m.ID,
			p.Level,
			m.Username,
			m.Paranoia,
			m.LastAccess,
			i.SupportFor
			FROM users_main AS m
			JOIN users_info AS i ON m.ID=i.UserID
			JOIN permissions AS p ON p.ID=m.PermissionID
			WHERE p.DisplayStaff='1'
				AND p.Level < 700
			ORDER BY p.Level, m.LastAccess ASC");
		$ForumStaff = $DB->to_array(false, MYSQLI_BOTH, array(3,'Paranoia'));
		$Cache->cache_value('forum_staff', $ForumStaff, 180);
	}
	return $ForumStaff;
}

function get_staff() {
	global $Cache, $DB;
	static $Staff;
	if(is_array($Staff)) {
		return $Staff;
	}
	if(($Staff = $Cache->get_value('staff')) === false) {
		$DB->query("SELECT
			m.ID,
			p.Level,
			p.Name,
			m.Username,
			m.Paranoia,
			m.LastAccess,
			i.SupportFor
			FROM users_main AS m
			JOIN users_info AS i ON m.ID=i.UserID
			JOIN permissions AS p ON p.ID=m.PermissionID
			WHERE p.DisplayStaff='1'
				AND p.Level >= 700
			ORDER BY p.Level, m.LastAccess ASC");
		$Staff = $DB->to_array(false, MYSQLI_BOTH, array(4,'Paranoia'));
		$Cache->cache_value('staff', $Staff, 180);
	}
	return $Staff;
}

function get_support() {
	return array(
		get_fls(),
		get_forum_staff(),
		get_staff(),
		'fls' => get_fls(),
		'forum_staff' => get_forum_staff(),
		'staff' => get_staff()
	);
}
