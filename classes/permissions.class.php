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
		$Permission = G::$Cache->get_value('perm_'.$PermissionID);
		if (empty($Permission)) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT p.Level AS Class, p.Values as Permissions, p.Secondary, p.PermittedForums
				FROM permissions AS p
				WHERE ID='$PermissionID'");
			$Permission = G::$DB->next_record(MYSQLI_ASSOC, array('Permissions'));
			G::$DB->set_query_id($QueryID);
			$Permission['Permissions'] = unserialize($Permission['Permissions']);
			G::$Cache->cache_value('perm_'.$PermissionID, $Permission, 2592000);
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

		$UserInfo = Users::user_info($UserID);

		// Fetch custom permissions if they weren't passed in.
		if ($CustomPermissions === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query('
				SELECT um.CustomPermissions
				FROM users_main AS um
				WHERE um.ID = '.((int)$UserID));
			list($CustomPermissions) = G::$DB->next_record(MYSQLI_NUM, false);
			G::$DB->set_query_id($QueryID);
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

		if (empty($CustomPermissions)) {
			$CustomPermissions = array();
		}

		// This is legacy donor cruft
		if ($UserInfo['Donor']) {
			$DonorPerms = Permissions::get_permissions(DONOR);
		} else {
			$DonorPerms = array('Permissions' => array());
		}

		$IsMod = isset($Permissions['Permissions']['users_mod']) && $Permissions['Permissions']['users_mod'];
		$DonorCollages = self::get_personal_collages($UserID, $IsMod);

		$MaxCollages = $Permissions['Permissions']['MaxCollages'] + $BonusCollages + $DonorCollages;

		if (isset($CustomPermissions['MaxCollages'])) {
			$MaxCollages += $CustomPermissions['MaxCollages'];
		}

		//Combine the permissions
		return array_merge(
				$Permissions['Permissions'],
				$BonusPerms,
				$CustomPermissions,
				$DonorPerms['Permissions'],
				array('MaxCollages' => $MaxCollages));
	}

	private static function get_personal_collages($UserID, $HasAll) {
		$QueryID = G::$DB->get_query_id();
		if (!$HasAll) {
			$SpecialRank = G::$Cache->get_value("donor_special_rank_$UserID");
			if ($SpecialRank === false) {
				G::$DB->query("SELECT SpecialRank FROM users_donor_ranks WHERE UserID = '$UserID'");
				list($SpecialRank) = G::$DB->next_record();
				$HasAll = $SpecialRank == MAX_SPECIAL_RANK ? true : false;
				G::$Cache->cache_value("donor_special_rank_$UserID", $SpecialRank, 0);
			}
		} else {
			G::$Cache->cache_value("donor_special_rank_$UserID", MAX_SPECIAL_RANK, 0);
		}

		if ($HasAll) {
			$Collages = 5;
		} else {
			$Collages = 0;
			$Rank = G::$Cache->get_value("donor_rank_$UserID");
			if ($Rank === false) {
				G::$DB->query("SELECT Rank FROM users_donor_ranks WHERE UserID = '$UserID'");
				list($Rank) = G::$DB->next_record();
				G::$Cache->cache_value("donor_rank_$UserID", $Rank, 0);
			}

			$Rank = min($Rank, 5);
			for ($i = 1; $i <= $Rank; $i++) {
				$Collages++;
			}
		}
		G::$DB->set_query_id($QueryID);
		return $Collages;
	}
}
?>
