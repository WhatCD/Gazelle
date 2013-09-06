<?php

//require_once 'mass_user_torrents_editor.class.php';

/**
 * This class helps with mass-editing bookmarked torrents.
 *
 * It can later be used for other bookmark tables.
 *
 */
class MASS_USER_BOOKMARKS_EDITOR extends MASS_USER_TORRENTS_EDITOR {
	public function __construct($Table = 'bookmarks_torrents') {
		$this->set_table($Table);
	}

	/**
	 * Runs a SQL query and clears the Cache key
	 *
	 * G::$Cache->delete_value didn't always work, but setting the key to null, did. (?)
	 *
	 * @param string $sql
	 */
	protected function query_and_clear_cache($sql) {
		$QueryID = G::$DB->get_query_id();
		if (is_string($sql) && G::$DB->query($sql)) {
			G::$Cache->delete_value('bookmarks_group_ids_' . G::$LoggedUser['ID']);
		}
		G::$DB->set_query_id($QueryID);
	}

	/**
	 * Uses (checkboxes) $_POST['remove'] to delete entries.
	 *
	 * Uses an IN() to match multiple items in one query.
	 */
	public function mass_remove() {
		$SQL = array();
		foreach ($_POST['remove'] as $GroupID => $K) {
			if (is_number($GroupID)) {
				$SQL[] = sprintf('%d', $GroupID);
			}
		}

		if (!empty($SQL)) {
			$SQL = sprintf('
					DELETE FROM %s
					WHERE UserID = %d
						AND GroupID IN (%s)',
				$this->Table,
				G::$LoggedUser['ID'],
				implode(', ', $SQL)
			);
			$this->query_and_clear_cache($SQL);
		}
	}

	/**
	 * Uses $_POST['sort'] values to update the DB.
	 */
	public function mass_update() {
		$SQL = array();
		foreach ($_POST['sort'] as $GroupID => $Sort) {
			if (is_number($Sort) && is_number($GroupID)) {
				$SQL[] = sprintf('(%d, %d, %d)', $GroupID, $Sort, G::$LoggedUser['ID']);
			}
		}

		if (!empty($SQL)) {
			$SQL = sprintf('
					INSERT INTO %s
						(GroupID, Sort, UserID)
					VALUES
						%s
					ON DUPLICATE KEY UPDATE
						Sort = VALUES (Sort)',
				$this->Table,
				implode(', ', $SQL));
			$this->query_and_clear_cache($SQL);
		}
	}
}
