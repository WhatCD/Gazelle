<?php

/**
 * Tags Class
 *
 * Formatting and sorting methods for tags and tag lists
 *
 * Example:
 * <pre>&lt;?php
 * $Tags = new Tags('pop rock hip.hop');
 * $Tags->Format(); // returns a tag link list
 *
 * $Tags2 = new Tags('pop rock indie');
 *
 * // returns a tag link list of tags ordered by amount
 * Tags::format_top();
 * ?></pre>
 * e.g.:
 *	pop (2)
 *  rock (2)
 *  hip.hop (1)
 *  indie (1)
 *
 * Each time a new Tags object is instantiated, the tag list is merged with the
 * overall total amount of tags to provide the top tags. Merging is optional.
 */
class Tags {
	/**
	 * Collects all tags processed by the Tags Class
	 * @static
	 * @var array $All Class Tags
	 */
	private static $All = array();

	/**
	 * All tags in the current instance
	 * @var array $Tags Instance Tags
	 */
	private $Tags = null;

	/**
	 * @var array $TagLink Tag link list
	 */
	private $TagLink = array();

	/**
	 * @var string $Primary The primary tag
	 */
	private $Primary = '';

	/**
	 * Filter tags array to remove empty spaces.
	 *
	 * @param string $TagList A string of tags separated by a space
	 * @param boolean $Merge Merge the tag list with the Class' tags
	 *				E.g., compilations and soundtracks are skipped, so false
	 */
	public function __construct($TagList, $Merge = true) {
		if ($TagList) {
			$this->Tags = array_filter(explode(' ', str_replace('_', '.', $TagList)));

			if ($Merge) {
				self::$All = array_merge(self::$All, $this->Tags);
			}

			$this->Primary = $this->Tags[0];
		} else {
			$this->Tags = array();
		}
	}

	/**
	 * @return string Primary Tag
	 */
	public function get_primary() {
		return $this->Primary;
	}

	/**
	 * Set the primary tag
	 * @param string $Primary
	 */
	public function set_primary($Primary) {
		$this->Primary = (string)$Primary;
	}

	/**
	 * Formats primary tag as a title
	 * @return string Title
	 */
	public function title() {
		return ucwords(str_replace('.', ' ', $this->Primary));
	}

	/**
	 * Formats primary tag as a CSS class
	 * @return string CSS Class Name
	 */
	public function css_name() {
		return 'tags_' . str_replace('.', '_', $this->Primary);
	}

	/**
	 * @return array Tags
	 */
	public function get_tags() {
		return $this->Tags;
	}

	/**
	 * @return array All tags
	 */
	public static function all() {
		return self::$All;
	}

	/**
	 * Counts and sorts All tags
	 * @return array All tags sorted
	 */
	public static function sorted() {
		$Sorted = array_count_values(self::$All);
		arsort($Sorted);
		return $Sorted;
	}

	/**
	 * Formats tags
	 * @param string $Link Link to a taglist page
	 * @param string $ArtistName Restrict tag search by this artist
	 * @return string List of tag links
	 */
	public function format($Link = 'torrents.php?taglist=', $ArtistName = '') {
		if (!empty($ArtistName)) {
			$ArtistName = "&amp;artistname=" . urlencode($ArtistName) . "&amp;action=advanced&amp;searchsubmit=1";
		}
		foreach ($this->Tags as $Tag) {
			if (empty($this->TagLink[$Tag])) {
				$this->TagLink[$Tag] = '<a href="' . $Link . $Tag . $ArtistName . '">' . $Tag . '</a>';
			}
		}
		return implode(', ', $this->TagLink);
	}

	/**
	 * Format a list of top tags
	 * @param int $Max Max number of items to get
	 */
	public static function format_top($Max = 5, $Link = 'torrents.php?taglist=', $ArtistName = '') {
		if (empty(self::$All)) { ?>
			<li>No torrent tags</li>
<?
			return;
		}
		if (!empty($ArtistName)) {
			$ArtistName = '&amp;artistname=' . urlencode($ArtistName) . '&amp;action=advanced&amp;searchsubmit=1';
		}
		foreach (array_slice(self::sorted(), 0, $Max) as $TagName => $Total) { ?>
			<li><a href="<?=$Link . display_str($TagName) . $ArtistName?>"><?=display_str($TagName)?></a> (<?=$Total?>)</li>
<?		}
	}
}
