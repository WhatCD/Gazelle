<?
class Votes {
	/**
	 * Generate voting links for torrent pages, etc.
	 * @global $LoggedUser
	 * @param $GroupID
	 * @param $Vote The pre-existing vote, if it exists 'Up'|'Down'
	 */
	public static function vote_link($GroupID, $Vote = '') {
		global $LoggedUser;
		if (!$LoggedUser['NoVoteLinks'] && check_perms('site_album_votes')) { ?>
			<span class="votespan brackets" style="white-space: nowrap;">
				Vote:
				<a href="#" onclick="UpVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;" class="small_upvote vote_link_<?=$GroupID?><?=(!empty($Vote) ? ' hidden' : '')?>" title="Upvote">↑</a>
				<span class="voted_type small_upvoted voted_up_<?=$GroupID?><?=(($Vote == 'Down' || empty($Vote)) ? ' hidden' : '')?>" title="Upvoted">↑</span>
				<a href="#" onclick="DownVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;" class="small_downvote vote_link_<?=$GroupID?><?=(!empty($Vote) ? ' hidden' : '')?>" title="Downvote">↓</a>
				<span class="voted_type small_downvoted voted_down_<?=$GroupID?><?=(($Vote == 'Up' || empty($Vote)) ? ' hidden' : '')?>" title="Downvoted">↓</span>
				<a href="#" onclick="UnvoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;" class="small_clearvote vote_clear_<?=$GroupID?><?=(empty($Vote) ? ' hidden' : '')?>" title="Clear your vote">x</a>
			</span>
<?		}
	}

	/**
	 * Returns an array with User Vote data: GroupID and vote type
	 * @global CACHE $Cache
	 * @global DB_MYSQL $DB
	 * @param string|int $UserID
	 * @return array GroupID=>(GroupID, 'Up'|'Down')
	 */
	public static function get_user_votes($UserID) {
		global $DB, $Cache;

		if ((int)$UserID == 0) {
			return array();
		}

		$UserVotes = $Cache->get_value('voted_albums_'.$UserID);
		if ($UserVotes === false) {
			$DB->query('
				SELECT GroupID, Type
				FROM users_votes
				WHERE UserID='.$UserID);
			$UserVotes = $DB->to_array('GroupID', MYSQL_ASSOC, false);
			$Cache->cache_value('voted_albums_'.$UserID, $UserVotes);
		}
		return $UserVotes;
	}

	/**
	 * Returns an array with torrent group vote data
	 * @global CACHE $Cache
	 * @global DB_MYSQL $DB
	 * @param string|int $GroupID
	 * @return array (Upvotes, Total Votes)
	 */
	public static function get_group_votes($GroupID) {
		global $DB, $Cache;

		$GroupVotes = $Cache->get_value('votes_'.$GroupID);
		if ($GroupVotes === false) {
			$DB->query("
				SELECT Ups AS Ups, Total AS Total
				FROM torrents_votes
				WHERE GroupID = $GroupID");
			if ($DB->record_count() == 0) {
				$GroupVotes = array('Ups' => 0, 'Total' => 0);
			} else {
				$GroupVotes = $DB->next_record(MYSQLI_ASSOC, false);
			}
			$Cache->cache_value('votes_'.$GroupID, $GroupVotes, 259200); // 3 days
		}
		return $GroupVotes;
	}

	/**
	 * Computes the inverse normal CDF of a p-value
	 * @param float $GroupID
	 * @return float Inverse Normal CDF
	 */
	private function inverse_ncdf($p) {
	/***************************************************************************
	 *																inverse_ncdf.php
	 *														-------------------
	 *	 begin								: Friday, January 16, 2004
	 *	 copyright						: (C) 2004 Michael Nickerson
	 *	 email								: nickersonm@yahoo.com
	 *
	 ***************************************************************************/

		//Inverse ncdf approximation by Peter John Acklam, implementation adapted to
		//PHP by Michael Nickerson, using Dr. Thomas Ziegler's C implementation as
		//a guide.	http://home.online.no/~pjacklam/notes/invnorm/index.html
		//I have not checked the accuracy of this implementation.	Be aware that PHP
		//will truncate the coeficcients to 14 digits.

		//You have permission to use and distribute this function freely for
		//whatever purpose you want, but please show common courtesy and give credit
		//where credit is due.

		//Input paramater is $p - probability - where 0 < p < 1.

		//Coefficients in rational approximations
		$a = array(1 => -3.969683028665376e+01, 2 => 2.209460984245205e+02,
				   3 => -2.759285104469687e+02, 4 => 1.383577518672690e+02,
				   5 => -3.066479806614716e+01, 6 => 2.506628277459239e+00);

		$b = array(1 => -5.447609879822406e+01, 2 => 1.615858368580409e+02,
				   3 => -1.556989798598866e+02, 4 => 6.680131188771972e+01,
				   5 => -1.328068155288572e+01);

		$c = array(1 => -7.784894002430293e-03, 2 => -3.223964580411365e-01,
				   3 => -2.400758277161838e+00, 4 => -2.549732539343734e+00,
				   5 => 4.374664141464968e+00,  6 => 2.938163982698783e+00);

		$d = array(1 => 7.784695709041462e-03, 2 => 3.224671290700398e-01,
				   3 => 2.445134137142996e+00, 4 => 3.754408661907416e+00);

		//Define break-points.
		$p_low  = 0.02425;									 //Use lower region approx. below this
		$p_high = 1 - $p_low;								 //Use upper region approx. above this

		//Define/list variables (doesn't really need a definition)
		//$p (probability), $sigma (std. deviation), and $mu (mean) are user inputs
		$q = NULL; $x = NULL; $y = NULL; $r = NULL;

		//Rational approximation for lower region.
		if (0 < $p && $p < $p_low) {
			$q = sqrt(-2 * log($p));
			$x = ((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) *
					 $q + $c[6]) / (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) *
					 $q + 1);
		}

		//Rational approximation for central region.
		elseif ($p_low <= $p && $p <= $p_high) {
			$q = $p - 0.5;
			$r = $q * $q;
			$x = ((((($a[1] * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) *
					 $r + $a[6]) * $q / ((((($b[1] * $r + $b[2]) * $r + $b[3]) * $r +
					 $b[4]) * $r + $b[5]) * $r + 1);
		}

		//Rational approximation for upper region.
		elseif ($p_high < $p && $p < 1) {
			$q = sqrt(-2 * log(1 - $p));
			$x = -((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q +
					 $c[5]) * $q + $c[6]) / (((($d[1] * $q + $d[2]) * $q + $d[3]) *
					 $q + $d[4]) * $q + 1);
		}

		//If 0 < p < 1, return a null value
		else {
			$x = NULL;
		}

		return $x;
		//END inverse ncdf implementation.
	}

	/**
	 * Implementation of the algorithm described at http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
	 * @param int $Ups Number of upvotes
	 * @param int $Total Number of total votes
	 * @return float Ranking score
	 */
	public static function binomial_score($Ups, $Total) {
		// Confidence level for binomial scoring (p-value .95)
		//define(Z_VAL, 1.645211440143815);
		// Confidence level for binomial scoring (p-value .90)
		define(Z_VAL, 1.281728756502709);

		if (($Total <= 0) || ($Ups < 0)) {
			return 0;
		}
		$phat = $Ups / $Total;
		$Numerator = ($phat + Z_VAL * Z_VAL / (2 * $Total) - Z_VAL * sqrt(($phat * (1 - $phat) + Z_VAL * Z_VAL / (4 * $Total)) / $Total));
		$Denominator = (1 + Z_VAL * Z_VAL / $Total);
		return ($Numerator / $Denominator);
	}

	/**
	 * Gets where this album ranks overall, for its year, and for its decade.  This is really just a wrapper.
	 * @param int $GroupID GroupID of the album
	 * @param int $Year Year it was released
	 * @return array ('overall'=><overall rank>, 'year'=><rank for its year>, 'decade'=><rank for its decade>)
	 */
	public static function get_ranking($GroupID, $Year) {
		$GroupID = (int)$GroupID;
		$Year = (int)$Year;
		if ($GroupID <= 0 || $Year <= 0) {
			return false;
		}

		return array('overall'=>Votes::get_rank_all($GroupID),
					 'year'   =>Votes::get_rank_year($GroupID, $Year),
					 'decade' =>Votes::get_rank_decade($GroupID, $Year));
	}

	/**
	 * Gets where this album ranks overall.
	 * @global CACHE $Cache
	 * @global DB_MYSQL $DB
	 * @param int $GroupID GroupID of the album
	 * @return int Rank
	 */
	public static function get_rank_all($GroupID) {
		global $Cache, $DB;

		$GroupID = (int)$GroupID;
		if ($GroupID <= 0) {
			return false;
		}

		$Rankings = $Cache->get_value('voting_ranks_overall');
		if ($Rankings === false) {
			$Rankings = array();
			$i = 0;
			$DB->query("
				SELECT GroupID
				FROM torrents_votes
				ORDER BY Score DESC
				LIMIT 100");
			while (list($GID) = $DB->next_record()) {
				$Rankings[$GID] = ++$i;
			}
			$Cache->cache_value('voting_ranks_overall', $Rankings, 259200); // 3 days
		}

		return (isset($Rankings[$GroupID]) ? $Rankings[$GroupID] : false);
	}

	/**
	 * Gets where this album ranks in its year.
	 * @global CACHE $Cache
	 * @global DB_MYSQL $DB
	 * @param int $GroupID GroupID of the album
	 * @param int $Year Year it was released
	 * @return int Rank for its year
	 */
	public static function get_rank_year($GroupID, $Year) {
		global $Cache, $DB;

		$GroupID = (int)$GroupID;
		$Year = (int)$Year;
		if ($GroupID <= 0 || $Year <= 0) {
			return false;
		}

		$Rankings = $Cache->get_value('voting_ranks_year_'.$Year);
		if ($Rankings === false) {
			$Rankings = array();
			$i = 0;
			$DB->query("
				SELECT GroupID
				FROM torrents_votes  AS v
					JOIN torrents_group AS g ON g.ID = v.GroupID
				WHERE g.Year = $Year
				ORDER BY Score DESC
				LIMIT 100");
			while (list($GID) = $DB->next_record()) {
				$Rankings[$GID] = ++$i;
			}
			$Cache->cache_value('voting_ranks_year_'.$Year , $Rankings, 259200); // 3 days
		}

		return (isset($Rankings[$GroupID]) ? $Rankings[$GroupID] : false);
	}

	/**
	 * Gets where this album ranks in its decade.
	 * @global CACHE $Cache
	 * @global DB_MYSQL $DB
	 * @param int $GroupID GroupID of the album
	 * @param int $Year Year it was released
	 * @return int Rank for its year
	 */
	public static function get_rank_decade($GroupID, $Year) {
		global $Cache, $DB;

		$GroupID = (int)$GroupID;
		$Year = (int)$Year;
		$Year = (int)$Year;
		if ((int)$GroupID <= 0 || (int)$Year <= 0) {
			return false;
		}

		// First year of the decade
		$Year = $Year - ($Year % 10);

		$Rankings = $Cache->get_value('voting_ranks_decade_'.$Year);
		if ($Rankings === false) {
			$Rankings = array();
			$i = 0;
			$DB->query("
				SELECT GroupID
				FROM torrents_votes  AS v
					JOIN torrents_group AS g ON g.ID = v.GroupID
				WHERE g.Year BETWEEN $Year AND " . ($Year + 9) . "
					  AND g.CategoryID = 1
				ORDER BY Score DESC
				LIMIT 100");
			while (list($GID) = $DB->next_record()) {
				$Rankings[$GID] = ++$i;
			}
			$Cache->cache_value('voting_ranks_decade_'.$Year , $Rankings, 259200); // 3 days
		}

		return (isset($Rankings[$GroupID]) ? $Rankings[$GroupID] : false);
	}
}
?>
