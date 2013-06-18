<?
class Permissions {
	/* Check to see if a user has the permission to perform an action
	 * This is called by check_perms in util.php, for convenience.
	 *
	 * @param string PermissionName
	 * @param string $MinClass Return false if the user's class level is below this.
	 */
	public static function check_perms($PermissionName, $MinClass = 0) {
		global $LoggedUser;
		return (
			isset($LoggedUser['Permissions'][$PermissionName])
			&& $LoggedUser['Permissions'][$PermissionName]
			&& ($LoggedUser['Class'] >= $MinClass
				|| $LoggedUser['EffectiveClass'] >= $MinClass)
			) ? true : false;
	}

	/**
	 * Gets the permissions associated with a certain permissionid
	 *
	 * @param int $PermissionID the kind of permissions to fetch
	 * @return array permissions
	 */
	public static function get_permissions($PermissionID) {
		global $DB, $Cache;
		$Permission = $Cache->get_value('perm_'.$PermissionID);
		if (empty($Permission)) {
			$DB->query("
				SELECT p.Level AS Class, p.Values as Permissions, p.Secondary, p.PermittedForums
				FROM permissions AS p
				WHERE ID='$PermissionID'");
			$Permission = $DB->next_record(MYSQLI_ASSOC, array('Permissions'));
			$Permission['Permissions'] = unserialize($Permission['Permissions']);
			$Cache->cache_value('perm_'.$PermissionID, $Permission, 2592000);
		}
		return $Permission;
	}

	/**
	 * Get a user's permissions.
	 *
	 * @param $UserID
	 * @param array|false $CustomPermissions
	 *	Pass in the user's custom permissions if you already have them.
	 *	Leave false if you don't have their permissions, the function will fetch them.
	 * @return array Mapping of PermissionName=>bool/int
	 */
	public static function get_permissions_for_user($UserID, $CustomPermissions = false) {
		global $DB;

		$UserInfo = Users::user_info($UserID);

		// Fetch custom permissions if they weren't passed in.
		if ($CustomPermissions === false) {
			$DB->query('
				SELECT um.CustomPermissions
				FROM users_main AS um
				WHERE um.ID = '.((int)$UserID));
			list($CustomPermissions) = $DB->next_record(MYSQLI_NUM, false);
		}

		if (!empty($CustomPermissions) && !is_array($CustomPermissions)) {
			$CustomPermissions = unserialize($CustomPermissions);
		}

		$Permissions = Permissions::get_permissions($UserInfo['PermissionID']);

		// Manage 'special' inherited permissions
		$BonusPerms = array();
		$BonusCollages = 0;
		foreach ($UserInfo['ExtraClasses'] as $PermID => $Value) {
			$ClassPerms = Permissions::get_permissions($PermID);
			$BonusCollages += $ClassPerms['Permissions']['MaxCollages'];
			unset($ClassPerms['Permissions']['MaxCollages']);
			$BonusPerms = array_merge($BonusPerms, $ClassPerms['Permissions']);
		}

		if (!empty($CustomPermissions)) {
			$CustomPerms = $CustomPermissions;
		} else {
			$CustomPerms = array();
		}

		// This is legacy donor cruft
		if ($UserInfo['Donor']) {
			$DonorPerms = Permissions::get_permissions(DONOR);
		} else {
			$DonorPerms = array('Permissions' => array());
		}

		$MaxCollages = $Permissions['Permissions']['MaxCollages']
				+ $BonusCollages
				+ $CustomPerms['MaxCollages']
				+ $DonorPerms['Permissions']['MaxCollages'];

		//Combine the permissions
		return array_merge(
				$Permissions['Permissions'],
				$BonusPerms,
				$CustomPerms,
				$DonorPerms['Permissions'],
				array('MaxCollages' => $MaxCollages));
	}
}
?>
