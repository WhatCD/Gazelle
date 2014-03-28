<?

class DonationsView {
	public static function render_mod_donations($UserID) {
?>
		<table class="layout" id="donation_box">
			<tr class="colhead">
				<td colspan="2">
					Donor System (add points)
				</td>
			</tr>
			<tr>
				<td class="label">Value:</td>
				<td>
					<input type="text" name="donation_value" onkeypress="return isNumberKey(event);" />
					<select name="donation_currency">
						<option value="EUR">EUR</option>
						<option value="USD">USD</option>
						<option value="BTC">BTC</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Reason:</td>
				<td><input type="text" class="wide_input_text" name="donation_reason" /></td>
			</tr>
			<tr>
				<td align="right" colspan="2">
					<input type="submit" name="donor_points_submit" value="Add donor points" />
				</td>
			</tr>
		</table>

		<table class="layout" id="donor_points_box">
			<tr class="colhead">
				<td colspan="3" class="tooltip" title='Use this tool only when manually correcting values. If crediting donations normally, use the "Donor System (add points)" tool'>
					Donor System (modify values)
				</td>
			</tr>
			<tr>
				<td class="label tooltip" title="Active points determine a user's Donor Rank and do expire.">Active points:</td>
				<td><input type="text" name="donor_rank" onkeypress="return isNumberKey(event);" value="<?=Donations::get_rank($UserID)?>" /></td>
			</tr>
			<tr>
				<td class="label tooltip" title="Total points represent a user's overall total and never expire. Total points determines a user's Special Rank and Donor Leaderboard placement.">Total points:</td>
				<td><input type="text" name="total_donor_rank" onkeypress="return isNumberKey(event);" value="<?=Donations::get_total_rank($UserID)?>" /></td>
			</tr>
			<tr>
				<td class="label">Reason:</td>
				<td><input type="text" class="wide_input_text" name="reason" /></td>
			</tr>
			<tr>
				<td align="right" colspan="2">
					<input type="submit" name="donor_values_submit" value="Change point values" />
				</td>
			</tr>
		</table>
<?
	}

	public static function render_donor_stats($UserID) {
		$OwnProfile = G::$LoggedUser['ID'] == $UserID;
		if (check_perms("users_mod") || $OwnProfile || Donations::is_visible($UserID)) {
?>
			<div class="box box_info box_userinfo_donor_stats">
				<div class="head colhead_dark">Donor Statistics</div>
				<ul class="stats nobullet">
<?
			if (Donations::is_donor($UserID)) {
				if (check_perms('users_mod') || $OwnProfile) {
?>
					<li>
						Total donor points: <?=Donations::get_total_rank($UserID)?>
					</li>
<?				} ?>
					<li>
						Current donor rank: <?=self::render_rank(Donations::get_rank($UserID), Donations::get_special_rank($UserID), true)?>
					</li>
					<li>
						Leaderboard position: <?=Donations::get_leaderboard_position($UserID)?>
					</li>
					<li>
						Last donated: <?=time_diff(Donations::get_donation_time($UserID))?>
					</li>
					<li>
						Rank expires: <?=(Donations::get_rank_expiration($UserID))?>
					</li>
<?			} else { ?>
					<li>
						This user hasn't donated.
					</li>
<?			} ?>
				</ul>
			</div>
<?
		}
	}

	public static function render_profile_rewards($EnabledRewards, $ProfileRewards) {
		for ($i = 1; $i <= 4; $i++) {
			if ($EnabledRewards['HasProfileInfo' . $i] && $ProfileRewards['ProfileInfo' . $i]) {
?>
			<div class="box">
				<div class="head" style="height: 13px;">
					<span style="float: left;"><?=!empty($ProfileRewards['ProfileInfoTitle' . $i]) ? display_str($ProfileRewards['ProfileInfoTitle' . $i]) : "Extra Profile " . ($i + 1)?></span>
					<span style="float: right;"><a href="#" onclick="$('#profilediv_<?=$i?>').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); return false;" class="brackets">Hide</a></span>
				</div>
				<div class="pad profileinfo" id="profilediv_<?=$i?>">
<?					echo Text::full_format($ProfileRewards['ProfileInfo' . $i]); ?>
				</div>
			</div>
<?
			}
		}
	}

	public static function render_donation_history($DonationHistory) {
		if (empty($DonationHistory)) {
			return;
		}
?>
		<div class="box box2" id="donation_history_box">
			<div class="head">
				Donation History <a href="#" onclick="$('#donation_history').gtoggle(); return false;" class="brackets">View</a>
			</div>
<?		$Row = 'b'; ?>
			<div class="hidden" id="donation_history">
				<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
					<tbody>
					<tr class="colhead_dark">
						<td>
							<strong>Source</strong>
						</td>
						<td>
							<strong>Date</strong>
						</td>
						<td>
							<strong>Amount (EUR)</strong>
						</td>
						<td>
							<strong>Added Points</strong>
						</td>
						<td>
							<strong>Total Points</strong>
						</td>
						<td>
							<strong>Email</strong>
						</td>
						<td style="width: 30%;">
							<strong>Reason</strong>
						</td>
					</tr>
<?		foreach ($DonationHistory as $Donation) { ?>
					<tr class="row<?=$Row?>">
						<td>
							<?=display_str($Donation['Source'])?> (<?=Users::format_username($Donation['AddedBy'])?>)
						</td>
						<td>
							<?=$Donation['Time']?>
						</td>
						<td>
							<?=$Donation['Amount']?>
						</td>
						<td>
							<?=$Donation['Rank']?>
						</td>
						<td>
							<?=$Donation['TotalRank']?>
						</td>
						<td>
							<?=display_str($Donation['Email'])?>
						</td>
						<td>
							<?=display_str($Donation['Reason'])?>
						</td>
					</tr>
<?
			$Row = $Row === 'b' ? 'a' : 'b';
		}
?>
					</tbody>
				</table>
			</div>
		</div>
<?
	}

	public static function render_rank($Rank, $SpecialRank, $ShowOverflow = false) {
		if ($SpecialRank == 3) {
			$Display = '∞ [Diamond]';
		} else {
			$CurrentRank = $Rank >= MAX_RANK ? MAX_RANK : $Rank;
			$Overflow = $Rank - $CurrentRank;
			$Display = $CurrentRank;
			if ($Display == 5 || $Display == 6) {
				$Display--;
			}
			if ($ShowOverflow && $Overflow) {
				$Display .= " (+$Overflow)";
			}
			if ($Rank >= 6) {
				$Display .= ' [Gold]';
			} elseif ($Rank >= 4) {
				$Display .= ' [Silver]';
			} elseif ($Rank >= 3) {
				$Display .= ' [Bronze]';
			} elseif ($Rank >= 2) {
				$Display .= ' [Copper]';
			} elseif ($Rank >= 1) {
				$Display .= ' [Red]';
			}
		}
		echo $Display;
	}

}
