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
		$FLS = $DB->to_array(false, MYSQLI_BOTH, array(4));
		$Cache->cache_value('fls', $FLS, 180);
	}
	return $FLS;
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
			ORDER BY p.Level, m.LastAccess ASC");
		$Staff = $DB->to_array(false, MYSQLI_BOTH, array(4));
		$Cache->cache_value('staff', $Staff, 180);
	}
	return $Staff;
}

function get_support() {
	return array(
		0 => get_fls(),
		1 => get_staff(),
		'fls' => get_fls(),
		'staff' => get_staff()
	);
}
