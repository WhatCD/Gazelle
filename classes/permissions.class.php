<?
class Permissions {
	/* Check to see if a user has the permission to perform an action
	 * This is called by check_perms in util.php, for convenience.
	 *
	 * @param string PermissionName
	 * @param string $MinClass Return false if the user's class level is below this.
	 */
	public static function check_perms($PermissionName, $MinClass = 0) {
		return (
			isset(G::$LoggedUser['Permissions'][$PermissionName])
			&& G::$LoggedUser['Permissions'][$PermissionName]
			&& (G::$LoggedUser['Class'] >= $MinClass
				|| G::$LoggedUser['EffectiveClass'] >= $MinClass)
			) ? true : false;
	}

	/**
	 * Gets the permissions associated with a certain permissionid
	 *
	 * @param int $PermissionID the kind of permissions to fetch
	 * @return array permissions
	 */
	public static function get_permissions($PermissionID) {
		$Permission = G::$Cache->get_value("perm_$PermissionID");
		if (empty($Permission)) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT Level AS Class, `Values` AS Permissions, Secondary, PermittedForums
				FROM permissions
				WHERE ID = '$PermissionID'");
			$Permission = G::$DB->next_record(MYSQLI_ASSOC, array('Permissions'));
			G::$DB->set_query_id($QueryID);
			$Permission['Permissions'] = unserialize($Permission['Permissions']);
			G::$Cache->cache_value("perm_$PermissionID", $Permission, 2592000);
		}
		return $Permission;
	}

	/**
	 * Get a user's permissions.
	 *
	 * @param $UserID
	 * @param array|false $CustomPermissions
	 *	Pass in the user's custom permissions if you already have them.
	 *	Leave false if you don't have their permissions. The function will fetch them.
	 * @return array Mapping of PermissionName=>bool/int
	 */
	public static function get_permissions_for_user($UserID, $CustomPermissions = false) {
		$UserInfo = Users::user_info($UserID);

		// Fetch custom permissions if they weren't passed in.
		if ($CustomPermissions === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query('
				SELECT CustomPermissions
				FROM users_main
				WHERE ID = ' . (int)$UserID);
			list($CustomPermissions) = G::$DB->next_record(MYSQLI_NUM, false);
			G::$DB->set_query_id($QueryID);
		}

		if (!empty($CustomPermissions) && !is_array($CustomPermissions)) {
			$CustomPermissions = unserialize($CustomPermissions);
		}

		$Permissions = self::get_permissions($UserInfo['PermissionID']);

		// Manage 'special' inherited permissions
		$BonusPerms = array();
		$BonusCollages = 0;
		foreach ($UserInfo['ExtraClasses'] as $PermID => $Value) {
			$ClassPerms = self::get_permissions($PermID);
			$BonusCollages += $ClassPerms['Permissions']['MaxCollages'];
			unset($ClassPerms['Permissions']['MaxCollages']);
			$BonusPerms = array_merge($BonusPerms, $ClassPerms['Permissions']);
		}

		if (empty($CustomPermissions)) {
			$CustomPermissions = array();
		}

		// This is legacy donor cruft
		if ($UserInfo['Donor']) {
			$DonorPerms = self::get_permissions(DONOR);
			unset($DonorPerms['Permissions']['MaxCollages']);
		} else {
			$DonorPerms = array('Permissions' => array());
		}
		$MaxCollages = $Permissions['Permissions']['MaxCollages'] + $BonusCollages;
		if (isset($CustomPermissions['MaxCollages'])) {
			$MaxCollages += $CustomPermissions['MaxCollages'];
			unset($CustomPermissions['MaxCollages']);
		}
		$Permissions['Permissions']['MaxCollages'] = $MaxCollages;
		// Combine the permissions
		return array_merge(
				$Permissions['Permissions'],
				$BonusPerms,
				$CustomPermissions,
				$DonorPerms['Permissions']);
	}

	public static function is_mod($UserID) {
		$Permissions = self::get_permissions_for_user($UserID);
		return isset($Permissions['users_mod']) && $Permissions['users_mod'];
	}
}
?>
