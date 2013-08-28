<?
if (check_perms('users_mod') || $OwnProfile || Donations::is_visible($UserID)) { ?>
	<div class="box box_info box_userinfo_donor_stats">
		<div class="head colhead_dark">Donor Statistics</div>
		<ul class="stats nobullet">
			<li>
				Total donor points: <?=Donations::get_total_rank($UserID)?>
			</li>
			<li>
				Current donor rank: <?=Donations::render_rank(Donations::get_rank($UserID), true)?>
			</li>
			<li>
				Last donated: <?=time_diff(Donations::get_donation_time($UserID))?>
			</li>
		</ul>
	</div>
<?
}
