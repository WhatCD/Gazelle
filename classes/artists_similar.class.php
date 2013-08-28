<?
class ARTIST {
	var $ID = 0;
	var $Name = 0;
	var $NameLength = 0;
	var $SimilarID = 0;
	var $Displayed = false;
	var $x = 0;
	var $y = 0;
	var $Similar = array();

	function ARTIST($ID = '', $Name = '') {
		$this->ID = $ID;
		$this->NameLength = mb_strlen($Name, 'utf8');
		$this->Name = display_str($Name);
	}
}

class ARTISTS_SIMILAR extends ARTIST{
	var $Artists = array();
	var $TotalScore = 0;

	var $xValues = array(WIDTH=>1);
	var $yValues = array(HEIGHT=>1);

	var $LargestDecimal = 0;
	var $LowestDecimal = 1;



	function dump_data() {
		return serialize(array(time(), $this->Name, $this->x, $this->y, serialize($this->Artists), serialize($this->Similar)));
	}

	function load_data($Data) {
		list($LastUpdated, $this->Name, $this->x, $this->y, $this->Artists, $this->Similar) = unserialize($Data);
		$this->Artists = unserialize($this->Artists);
		$this->Similar = unserialize($this->Similar);
	}

	function set_up() {
		$QueryID = G::$DB->get_query_id();

		$this->x = ceil(WIDTH / 2);
		$this->y = ceil(HEIGHT / 2);

		$this->xValues[$this->x] = $this->ID;
		$this->yValues[$this->y] = $this->ID;


		// Get artists that are directly similar to the artist
		$ArtistIDs = array();
		G::$DB->query("
			SELECT
				s2.ArtistID,
				ag.Name,
				ass.Score
			FROM artists_similar AS s1
				JOIN artists_similar AS s2 ON s1.SimilarID=s2.SimilarID AND s1.ArtistID!=s2.ArtistID
				JOIN artists_similar_scores AS ass ON ass.SimilarID=s1.SimilarID
				JOIN artists_group AS ag ON ag.ArtistID=s2.ArtistID
			WHERE s1.ArtistID=".$this->ID."
			ORDER BY ass.Score DESC
			LIMIT 14");

		if (!G::$DB->has_results()) {
			return;
		}

		// Build into array. Each artist is its own object in $this->Artists
		while (list($ArtistID, $Name, $Score) = G::$DB->next_record(MYSQLI_NUM, false)) {
			if ($Score < 0) {
				continue;
			}
			$this->Artists[$ArtistID] = new ARTIST($ArtistID, $Name);
			$this->Similar[$ArtistID] = array('ID' => $ArtistID, 'Score' => $Score);
			$this->TotalScore += $Score;
			$ArtistIDs[] = $ArtistID;
		}

		// Get similarities between artists on the map
		G::$DB->query("
			SELECT
				s1.ArtistID,
				s2.ArtistID
			FROM artists_similar AS s1
				JOIN artists_similar AS s2 ON s1.SimilarID=s2.SimilarID AND s1.ArtistID!=s2.ArtistID
				JOIN artists_similar_scores AS ass ON ass.SimilarID=s1.SimilarID
				JOIN artists_group AS a ON a.ArtistID=s2.ArtistID
			WHERE s1.ArtistID IN(".implode(',', $ArtistIDs).')
				AND s2.ArtistID IN('.implode(',', $ArtistIDs).')');

		// Build into array
		while (list($Artist1ID, $Artist2ID) = G::$DB->next_record()) {
			$this->Artists[$Artist1ID]->Similar[$Artist2ID] = array('ID'=>$Artist2ID);
		}

		// Calculate decimal point scores between artists
		foreach ($this->Similar as $SimilarArtist) {
			list($ArtistID, $Similar) = array_values($SimilarArtist);
			$this->Similar[$ArtistID]['Decimal'] =  $this->similarity($Similar['Score'], $this->TotalScore);

			if ($this->Similar[$ArtistID]['Decimal'] < $this->LowestDecimal) {
				$this->LowestDecimal = $this->Similar[$ArtistID]['Decimal'];
			}
			if ($this->Similar[$ArtistID]['Decimal'] > $this->LargestDecimal) {
				$this->LargestDecimal = $this->Similar[$ArtistID]['Decimal'];
			}
		}
		reset($this->Artists);

		G::$DB->set_query_id($QueryID);
	}

	function set_positions() {
		$xValues = array(); // Possible x values
		$Root = ceil(WIDTH / 4); // Half-way into half of the image
		$Offset = 4; // Distance from the root (a quarter of the way into the image) to the x value

		// The number of artists placed in the top or the bottom
		$NumTop = 0;
		$NumBottom = 0;

		// The number of artists placed in the left or the right
		$NumLeft = 0;
		$NumRight = 0;

		$Multiplier = 0;

		// Build up an impressive list of possible x values
		// We later iterate through these, and pick out the ones we want

		// These x values are all below WIDTH/2 (all on the left)
		// The script later chooses which side to put them on

		// We create more very low x values because they're more likely to be skipped
		for ($i = 0; $i <= count($this->Artists) * 4; $i++) {
			if ($Offset >= ((WIDTH / 4))) {
				$Offset = $Offset % (WIDTH / 4);
			}
			$Plus = $Root + $Offset; // Point on the right of the root
			$Minus = abs($Root - $Offset); // Point on the left of the root

			$xValues[$Plus] = $Plus;

			$xValues[$Minus] = $Minus;

			// Throw in an extra x value closer to the edge, because they're more likely to be skipped

			if ($Minus > 30) {
			//	$xValues[$Minus - 30] = $Minus - 30;
			}

			$Offset = $Offset + rand(5, 20); // Increase offset, and go again
		}

		foreach ($this->Artists as $Artist) {
			$ArtistID = $Artist->ID;
			if ($Artist->Displayed == true) {
				continue;
			}
			$this->Similar[$ArtistID]['Decimal'] = $this->Similar[$ArtistID]['Decimal'] * (1 / ($this->LargestDecimal)) - 0.1;
			// Calculate the distance away from the center, based on similarity
			$IdealDistance =  $this->calculate_distance($this->Similar[$ArtistID]['Decimal'], $this->x, $this->y);

			$this->Similar[$ArtistID]['Distance'] = $IdealDistance;

			// 1 = left, 2 = right
			$Horizontal = 0;
			$Vertical = 0;

			// See if any similar artists have been placed yet. If so, place artist in that half
			// (provided that there are enough in the other half to visually balance out)
			reset($Artist->Similar);
			foreach ($Artist->Similar as $SimilarArtist) {
				list($Artist2ID) = array_values($SimilarArtist);
				if ($this->Artists[$Artist2ID]) {
					if ($this->Artists[$Artist2ID]->x > (WIDTH / 2) && ($NumRight-$NumLeft) < 1) {
						$Horizontal = 2;
					} elseif ($NumLeft - $NumRight < 1) {
						$Horizontal = 1;
					}
					break;
				}
			}

			shuffle($xValues);

			while ($xValue = array_shift($xValues)) {
				if (abs($this->x - $xValue) <= $IdealDistance) {
					if (hypot(abs($this->x - $xValue), ($this->y - 50)) > $IdealDistance
						|| ceil(sqrt(pow($IdealDistance, 2) - pow($this->x - $xValue, 2))) > (HEIGHT / 2)) {
						$xValue = $this->x - ceil(sqrt(pow($IdealDistance, 2) - pow($IdealDistance * 0.1 * rand(5,9), 2)));
						//echo "Had to change x value for ".$Artist->Name." to ".$xValue."\n";
					}
					// Found a match (Is close enough to the center to satisfy $IdealDistance),
					// Now it's time to choose which half to put it on
					if (!$Horizontal) {
						// No similar artists displayed
						$Horizontal = ($NumLeft < $NumRight) ? 1 : 2;
					}
					if ($Horizontal == 2) {
						$xValue = WIDTH - $xValue;
						$NumRight++;
					} else {
						$NumLeft++;
					}

					$Artist->x = $xValue;
					$this->xValues[$xValue] = $ArtistID;
					unset($xValues[$xValue]);

					break;
				}
			}
			if (!$xValue) { // Uh-oh, we were unable to choose an x value.
				$xValue = ceil(sqrt(pow($IdealDistance, 2) / 2));
				$xValue = (WIDTH / 2) - $xValue;
				$Artist->x = $xValue;
				$this->xValues[$xValue] = $ArtistID;
				unset($xValues[$xValue]);
			}


			// Pythagoras. $yValue is the vertical distance from the center to the y value
			$yValue = sqrt(pow($IdealDistance, 2) - pow(abs($this->x - $Artist->x), 2));


			// Now we pick if it should go on the top or bottom

			if ($NumTop > $NumBottom) { // Send it to the bottom half
				$yValue = (HEIGHT / 2) + $yValue;
				$NumBottom++;
			} else {
				$yValue=(HEIGHT / 2) - $yValue;
				$NumTop++;
			}

			$yValue = ceil($yValue);

			// $yValue is now a proper y coordinate
			// Now time to do some spacing out

			if ($yValue < 10) {
				$yValue += (10 + abs($yValue)) + rand(10,20);
			}

			if ($yValue > (HEIGHT - 10)) {
				$yValue -= ((HEIGHT / 2) - rand(10,20));
			}

			$i = 1;
			while ($Conflict = $this->scan_array_range($this->yValues, abs($yValue - 13), $yValue + 13)) {
				if ($i > 10) {
					break;
				}
				if (!$this->scan_array_range($this->yValues, abs($yValue - 5), $yValue - 20)) {
					$yValue -= 20;
				}

				$yValue = $Conflict + rand(10, 20);
				if ($yValue > HEIGHT - 10) {
					$yValue -= ceil(HEIGHT / 2.5);
				} elseif ($yValue < 10) {
					$yValue += ceil(HEIGHT / 2.5);
				}
				$i++;
			}

			$Artist->y = $yValue;
			$this->yValues[$yValue] = $ArtistID;
		}
		reset($this->Artists);
		reset($this->xValues);
		reset($this->yValues);

	}

	// Calculate the ideal distance from the center point ($Rootx, $Rooty) to the artist's point on the board
	// Pythagoras as fun!
	function calculate_distance($SimilarityCoefficient, $Rootx, $Rooty) {
		$MaxWidth = WIDTH - $Rootx;
		$MaxHeight = HEIGHT - $Rooty;
		$x = $MaxWidth - ($SimilarityCoefficient * $MaxWidth * 0.01); // Possible x value
		$y = $MaxHeight - ($SimilarityCoefficient * $MaxHeight); // Possible y value
		$Hypot = hypot($Rootx - $x, $Rooty - $y);
		return $MaxWidth - $Hypot;

	}

	function similarity($Score, $TotalArtistScore) {
		return (pow(($Score / ($TotalArtistScore + 1)), (1 / 1)));
	}

	function scan_array_range($Array, $Start, $Finish) {
		if ($Start < 0) {
			die($Start);
		}
		for ($i = $Start; $i <= $Finish; $i++) {
			if (isset($Array[$i])) {
				return $i;
			}
		}
		return false;
	}

	function write_artists() {
?>
		<div style="position: absolute; bottom: <?=($this->y - 10)?>px; left: <?=($this->x - $this->NameLength * 4)?>px; font-size: 13pt; white-space: nowrap;" class="similar_artist_header">
			<?=($this->Name)?>
		</div>
<?
		foreach ($this->Artists as $Artist) {
			if ($Artist->ID == $this->ID) {
				continue;
			}
			$xPosition = $Artist->x - $Artist->NameLength * 4;
			if ($xPosition < 0) {
				$xPosition = 3;
				$Artist->x = $xPosition;

			}
			$Decimal = $this->Similar[$Artist->ID]['Decimal'];

			if ($Decimal < 0.2) {
				$FontSize = 8;
			} elseif ($Decimal < 0.3) {
				$FontSize = 9;
			} elseif ($Decimal < 0.4) {
				$FontSize = 10;
			} else {
				$FontSize = 12;
			}
?>
		<div style="position: absolute; top: <?=($Artist->y - 5)?>px; left: <?=$xPosition?>px; font-size: <?=$FontSize?>pt; white-space: nowrap;">
			<a href="artist.php?id=<?=($Artist->ID)?>" class="similar_artist"><?=($Artist->Name)?></a>
		</div>
<?
		}
		reset($this->Artists);
	}

	function background_image() {
		global $Img;
		reset($this->Similar);
		foreach ($this->Similar as $SimilarArtist) {
			list($ArtistID, $Val) = array_values($SimilarArtist);
			$Artist = $this->Artists[$ArtistID];
			$Decimal = $this->Similar[$ArtistID]['Decimal'];
			$Width = ceil($Decimal * 4) + 1;

			$Img->line($this->x, $this->y, $Artist->x, $Artist->y, $Img->color(199, 218, 255), $Width);

			unset($Artist->Similar[$this->ID]);
			reset($Artist->Similar);
			foreach ($Artist->Similar as $SimilarArtist2) {
				list($Artist2ID) = array_values($SimilarArtist2);
				if ($this->Artists[$Artist2ID]) {
					$Artist2 = $this->Artists[$Artist2ID];
					$Img->line($Artist->x, $Artist->y, $Artist2->x, $Artist2->y, $Img->color(173, 201, 255));
					unset($Artist2->Similar[$ArtistID]);
				}
			}
			reset($this->xValues);
		}
		$Img->make_png(SERVER_ROOT.'/static/similar/'.$this->ID.'.png');
	}

	function dump() {
		echo "Similarities:\n";
		foreach ($this->Artists as $Artist) {
			echo $Artist->ID;
			echo ' - ';
			echo $Artist->Name;
			echo "\n";
			echo 'x - ' . $Artist->x . "\n";
			echo 'y - ' . $Artist->y . "\n";
			print_r($this->Similar[$Artist->ID]);
			//print_r($Artist->Similar);
			echo "\n\n---\n\n";
		}

	}
}
?>
