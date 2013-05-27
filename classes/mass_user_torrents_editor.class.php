<?php

/**
 * Abstract class
 * Mass User-Torrents Editor
 *
 * A class that deals with mass editing a user's torrents.
 *
 * This abstract class is used by sub-classes as a way to access the Cache/DB.
 *
 * It is intended to streamline the process of processing data sent by the
 * MASS_USER_TORRENT_TABLE_VIEW class.
 *
 * It could also be used for other types like collages.
 */
abstract class MASS_USER_TORRENTS_EDITOR
{
	/**
	 * Internal access to the Cache
	 * @var Cache $Cache
	 */
	protected $Cache;

	/**
	 * Internal access to the Database
	 * @var DB_MYSQL $DB
	 */
	protected $DB;

	/**
	 * Current User's ID
	 * @var str|int $UserID
	 */
	protected $UserID;

	/**
	 * The affected DB table
	 * @var string $Table
	 */
	protected $Table;

	/**
	 * Extended classes should call this method.
	 * @example parent::__construct()
	 * @global CACHE $Cache
	 * @global DB_MYSQL $DB
	 * @global string|int $UserID
	 */
	public function __construct (/*CACHE &$Cache, DB_MYSQL &$DB, $UserID*/)
	{
		global $Cache, $DB, $UserID;
		$this->Cache = $Cache;
		$this->DB = $DB;
		$this->UserID = (int) $UserID;
		if ($this->UserID < 1) error(403);
	}

	/**
	 * Set the Table
	 * @param string $Table
	 */
	final public function set_table ($Table)
	{
		$this->Table = db_string($Table);
	}

	/**
	 * Get the Table
	 * @param sting $Table
	 * @return string $Table
	 */
	final public function get_table ()
	{
		return $this->Table;
	}

	/**
	 * The extending class must provide a method to send a query and clear the cache
	 */
	abstract protected function query_and_clear_cache ($sql);

	/**
	 * A method to insert many rows into a single table
	 * Not required in subsequent classes
	 */
	public function mass_add () {}

	/**
	 * A method to remove many rows from a table
	 * The extending class must have a mass_remove method
	 */
	abstract public function mass_remove ();

	/**
	 * A method to update many rows in a table
	 * The extending class must have a mass_update method
	 */
	abstract public function mass_update ();
}