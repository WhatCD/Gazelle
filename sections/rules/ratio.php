<?
//Include the header
View::show_header('Ratio Requirements');
?>
<div class="thin">
	<div class="header">
		<h2 class="center">Ratio Rules</h2>
	</div>
	<div class="box pad rule_summary">
		<br />
		<strong>Ratio System Overview:</strong>
		<br />
		<ul>
			<li>Your <strong>ratio</strong> is calculated by dividing the amount of data you&apos;ve uploaded by the amount of data you&apos;ve downloaded. You can view your ratio in the site header or in the &quot;stats&quot; section of your user profile.
			</li>
			<li>To maintain <strong>leeching privileges</strong>, your ratio must remain above a minimum value. This minimum value is your <strong>required ratio</strong>.</li>
			<li>If your ratio falls below your required ratio, you will be given two weeks to raise your ratio back above your required ratio. During this period, you are on <strong>ratio watch</strong>.
			</li>
			<li>If you fail to raise your ratio above your required ratio in the allotted time, your leeching privileges will be revoked. You will be unable to download more data. Your account will remain enabled.
			</li>
		</ul>
		<br />
		<br />
		<strong>Required Ratio Overview:</strong>
		<br />
		<ul>
			<li>Your required ratio represents the minimum ratio you must maintain to avoid ratio watch. You can view your required ratio in the site header after the word &quot;required&quot; or in the &quot;stats&quot; section of your user profile.
			</li>
			<li>Your required ratio is unique; each person&apos;s required ratio is calculated for their account specifically.</li>
			<li>Your required ratio is calculated using (1) the total amount of data you&apos;ve downloaded and (2) the total number of torrents you&apos;re seeding. The seeding total is not limited to snatched torrents (completed downloads)&#8202;&mdash;&#8202;the total includes, but is not limited to, your uploaded torrents.
			</li>
			<li>The required ratio system lowers your required ratio when you seed a greater number of torrents. The more torrents you seed, the lower your required ratio will be. The lower your required ratio is, the less likely it is that you&apos;ll enter ratio watch.
			</li>
		</ul>
		<br />
		<br />
		<div style="text-align: center;">
			<strong>Required Ratio Table</strong>
			<br />
			<br />
			<table class="ratio_table">
				<tr class="colhead">
					<td class="tooltip" title="These units are actually in base 2, not base 10. For example, there are 1,024 MB in 1 GB.">Amount Downloaded</td>
					<td>Required Ratio (0% seeded)</td>
					<td>Required Ratio (100% seeded)</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] < 5 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>0&ndash;5 GB</td>
					<td>0.00</td>
					<td>0.00</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 5 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 10 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>5&ndash;10 GB</td>
					<td>0.15</td>
					<td>0.00</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 10 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 20 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>10&ndash;20 GB</td>
					<td>0.20</td>
					<td>0.00</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 20 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 30 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>20&ndash;30 GB</td>
					<td>0.30</td>
					<td>0.05</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 30 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 40 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>30&ndash;40 GB</td>
					<td>0.40</td>
					<td>0.10</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 40 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 50 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>40&ndash;50 GB</td>
					<td>0.50</td>
					<td>0.20</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 50 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 60 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>50&ndash;60 GB</td>
					<td>0.60</td>
					<td>0.30</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 60 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 80 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>60&ndash;80 GB</td>
					<td>0.60</td>
					<td>0.40</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 80 * 1024 * 1024 * 1024 && $LoggedUser['BytesDownloaded'] < 100 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>80&ndash;100 GB</td>
					<td>0.60</td>
					<td>0.50</td>
				</tr>
				<tr class="row<?=($LoggedUser['BytesDownloaded'] >= 100 * 1024 * 1024 * 1024) ? 'a' : 'b' ?>">
					<td>100+ GB</td>
					<td>0.60</td>
					<td>0.60</td>
				</tr>
			</table>
		</div>
		<br />
		<br />
		<strong>Required Ratio Calculation:</strong>
		<br />
		<ul>
			<li>
				<strong>1: Determine the maximum and minimum possible values of your required ratio.</strong> Using the table above, determine your amount downloaded bracket from the first column.
				Next, locate the values in the adjacent columns. The second column lists the maximum required ratio for each bracket, and the third column lists the minimum required ratio for each
				bracket. The maximum and minimum required ratios are also referred to as the <strong>0% seeded</strong> and <strong>100% seeded</strong> required ratios, respectively.
			</li>
			<li>
				<strong>2: Determine the actual required ratio.</strong> Your actual required ratio will be a number that falls between the maximum and minimum required ratio values determined in the
				previous step. To determine your actual required ratio, the system first uses the maximum required ratio (0% seeded) and multiplies it by the value [1 &minus; (<var>seeding</var> / <var>snatched</var>)]. Formatted
				differently, the calculation performed by the system looks like this:
				<br />
				<br />
				<div style="text-align: center;">
					<img style="vertical-align: middle;" src="static/blank.gif" alt="required ratio = (maximum required ratio) * (1 - (seeding / snatched))"
							onload="if (this.src.substr(this.src.length - 9, this.src.length) == 'blank.gif') { this.src = 'https://chart.googleapis.com/chart?cht=tx&amp;chf=bg,s,FFFFFF00&amp;chl=%5Ctextrm%7B%28maximum+required+ratio%29+%2A+%281-%5Cfrac%7Bseeding%7D%7Bsnatched%7D%29%7D&amp;chco=' + hexify(getComputedStyle(this.parentNode, null).color); }" />
				</div>
				<br />
				<br />
				<ul>
					<li>In this formula, <var>snatched</var> is the number of non-deleted unique snatches you have made. If you snatch a torrent twice, it only counts once. If a snatched torrent is
						deleted from the site, it is not counted at all.
					</li>
					<li>In this formula, <var>seeding</var> is the average number of torrents you&apos;ve seeded over a 72 hour period within the last week. If you&apos;ve seeded a torrent for less than
						72 hours within the last week, it will not raise your seeding total. Please note that while it is possible to seed more torrents than you have snatched, the system effectively caps the
						value at 100% of your snatched amount.
					</li>
				</ul>
			</li>
			<li><strong>3: Round, if necessary.</strong> The value determined in the previous step is rounded up to your minimum required ratio (100% seeded) if necessary. This step is required because
				most amount downloaded brackets have a minimum required ratio (100% seeded) greater than zero, and the value returned by the above calculation is zero when seeding equals snatched.
			</li>
		</ul>
		<br />
		<br />
		<strong>Required Ratio Details:</strong>
		<br />
		<ul>
			<li>If you stop seeding for one week, your required ratio will become the maximum required ratio (0% seeded) for your amount downloaded bracket. Once you have resumed seeding for a 72 hour
				period, your required ratio will decrease according to the above calculations.
			</li>
			<li>If your download total is less than 5 GB, you won&apos;t be eligible for ratio watch, and you will not need a required ratio. In this circumstance, your required ratio will be zero
				regardless of your seeding percentage.
			</li>
			<li>If your download total is less than 20 GB and you are seeding a number of torrents equal to 100% of your snatches, your required ratio will be zero.</li>
			<li>As your download total increases, your minimum (100% seeded) and maximum (0% seeded) required ratios taper together. After you have downloaded 100 GB, those values become equal to each
				other. This means that users with download totals greater than or equal to 100 GB have a minimum required ratio (100% seeded) of 0.60 from that point forward.
			</li>
		</ul>
		<br />
		<br />
		<strong>Required Ratio Example:</strong>
		<br />
		<ul>
			<li>In this example, Rippy has downloaded 25 GB. Rippy falls into the 20&ndash;30 GB amount downloaded bracket in the table above. Rippy&apos;s maximum required ratio (0% seeded) is 0.30, and his minimum required ratio (100% seeded) is 0.05.
			</li>
			<li>In this example, Rippy has snatched 90 torrents, and is currently seeding 45 torrents.</li>
			<li>To calculate Rippy&apos;s actual required ratio, we take his maximum required ratio (0% seeded), which is 0.30, and multiply it by [1 &minus; (<var>seeding</var> / <var>snatched</var>)] (which is 0.50). Written out:
				<samp>0.30 * [1 &minus; (45 / 90)] = 0.15</samp>
			</li>
			<li>The resulting required ratio is 0.15, which falls between the maximum required ratio of 0.30 and the minimum required ratio of 0.05 for his amount downloaded bracket.</li>
			<li>If Rippy&apos;s on-site required ratio was listed as a value greater than the calculated value, this would be because he hadn&apos;t seeded those 45 torrents for a 72 hour period in the
				last week. In this case, the system would not be counting all 45 torrents as seeded.
			</li>
		</ul>
		<br />
		<br />
		<strong>Ratio Watch Overview:</strong>
		<br />
		<ul>
			<li>Everyone gets to download their first 5 GB before ratio watch eligibility begins.</li>
			<li>If you&apos;ve downloaded more than 5 GB and your ratio does not meet or surpass your required ratio, you will be put on ratio watch and have <strong>two weeks</strong> to raise your
				ratio above your required ratio.
			</li>
			<li>If you download 10 GB while on ratio watch, your leeching privileges will automatically be disabled.</li>
			<li>If you fail to leave ratio watch within a two week period, you will lose leeching privileges. After losing leeching privileges, you will be unable to download more data. Your account
				will remain enabled.
			</li>
			<li>The ratio watch system is automated and cannot be interrupted by staff.</li>
		</ul>
		<br />
		<br />
		<strong>Leaving Ratio Watch:</strong>
		<br />
		<ul>
			<li>To leave ratio watch, you must either raise your ratio by uploading more, or lower your required ratio by seeding more. Your ratio must be equal to or above your required ratio in
				order for ratio watch to end.
			</li>
			<li>If you fail to improve your ratio by the time ratio watch expires and lose leeching privileges, your required ratio will be temporarily set to the maximum possible requirement (as if 0% of snatched torrents were being seeded).
			</li>
			<li>After losing leeching privileges, in order to adjust the required ratio so that it reflects the actual number of torrents being seeded, you must seed for a combined 72 hours within a weeklong period. After 72
				hours of seeding occur, the required ratio will update to reflect your current seeding total, just as it would for a leech-enabled user.
			</li>
			<li>Leeching privileges will be restored once your ratio has become greater than or equal to your required ratio.</li>
		</ul>
		<br />
		<br />
	</div>
<? include('jump.php'); ?>
</div>
<?
	View::show_footer();
?>
