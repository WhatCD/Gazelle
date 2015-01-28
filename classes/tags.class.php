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
 * overall total amount of tags to provide a Top Tags list. Merging is optional.
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
	 * @param string $Link  Page query where more items of this tag type can be found
	 * @param string $ArtistName Optional artist
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

	/**
	 * General purpose method to get all tag aliases from the DB
	 * @return array
	 */
	public static function get_aliases() {
		$TagAliases = G::$Cache->get_value('tag_aliases_search');
		if ($TagAliases === false) {
			G::$DB->query('
			SELECT ID, BadTag, AliasTag
			FROM tag_aliases
			ORDER BY BadTag');
			$TagAliases = G::$DB->to_array(false, MYSQLI_ASSOC, false);
			// Unify tag aliases to be in_this_format as tags not in.this.format
			array_walk_recursive($TagAliases, create_function('&$val', '$val = preg_replace("/\./","_", $val);'));
			// Clean up the array for smaller cache size
			foreach ($TagAliases as &$TagAlias) {
				foreach (array_keys($TagAlias) as $Key) {
					if (is_numeric($Key)) {
						unset($TagAlias[$Key]);
					}
				}
			}
			G::$Cache->cache_value('tag_aliases_search', $TagAliases, 3600 * 24 * 7); // cache for 7 days
		}
		return $TagAliases;
	}

	/**
	 * Replace bad tags with tag aliases
	 * @param array $Tags Array with sub-arrays 'include' and 'exclude'
	 * @return array
	 */
	public static function remove_aliases($Tags) {
		$TagAliases = self::get_aliases();

		if (isset($Tags['include'])) {
			$End = count($Tags['include']);
			for ($i = 0; $i < $End; $i++) {
				foreach ($TagAliases as $TagAlias) {
					if ($Tags['include'][$i] === $TagAlias['BadTag']) {
						$Tags['include'][$i] = $TagAlias['AliasTag'];
						break;
					}
				}
			}
			// Only keep unique entries after unifying tag standard
			$Tags['include'] = array_unique($Tags['include']);
		}

		if (isset($Tags['exclude'])) {
			$End = count($Tags['exclude']);
			for ($i = 0; $i < $End; $i++) {
				foreach ($TagAliases as $TagAlias) {
					if (substr($Tags['exclude'][$i], 1) === $TagAlias['BadTag']) {
						$Tags['exclude'][$i] = '!'.$TagAlias['AliasTag'];
						break;
					}
				}
			}
			// Only keep unique entries after unifying tag standard
			$Tags['exclude'] = array_unique($Tags['exclude']);
		}

		return $Tags;
	}

	/**
	 * Filters a list of include and exclude tags to be used in a Sphinx search
	 * @param array $Tags An array of tags with sub-arrays 'include' and 'exclude'
	 * @param boolean $EnableNegation Sphinx needs at least one positive search condition to support the NOT operator
	 * @param integer $TagType Search for Any or All of these tags.
	 * @return array Array keys predicate and input
	 *               Predicate for a Sphinx 'taglist' query
	 *               Input contains clean, aliased tags. Use it in a form instead of the user submitted string
	 */
	public static function tag_filter_sph($Tags, $EnableNegation, $TagType) {
		$QueryParts = [];
		$Tags = Tags::remove_aliases($Tags);
		$TagList = str_replace('_', '.', implode(', ', array_merge($Tags['include'], $Tags['exclude'])));

		if (!$EnableNegation && !empty($Tags['exclude'])) {
			$Tags['include'] = array_merge($Tags['include'], $Tags['exclude']);
			unset($Tags['exclude']);
		}

		foreach ($Tags['include'] as &$Tag) {
			$Tag = Sphinxql::sph_escape_string($Tag);
		}

		if (!empty($Tags['exclude'])) {
			foreach ($Tags['exclude'] as &$Tag) {
				$Tag = '!' . Sphinxql::sph_escape_string(substr($Tag, 1));
			}
		}

		// 'All' tags
		if (!isset($TagType) || $TagType == 1) {
			$SearchWords = array_merge($Tags['include'], $Tags['exclude']);
			if (!empty($Tags)) {
				$QueryParts[] = implode(' ', $SearchWords);
			}
		}
		// 'Any' tags
		else {
			if (!empty($Tags['include'])) {
				$QueryParts[] = '( ' . implode(' | ', $Tags['include']) . ' )';
			}
			if (!empty($Tags['exclude'])) {
				$QueryParts[] = implode(' ', $Tags['exclude']);
			}
		}

		return ['input' => $TagList, 'predicate' => implode(' ', $QueryParts)];
	}
}
