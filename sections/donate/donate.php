<?
enforce_login();

//Include the header
if (!$UserCount = $Cache->get_value('stats_user_count')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'");
	list($UserCount) = $DB->next_record();
	$Cache->cache_value('stats_user_count', $UserCount, 0); //inf cache
}

$DonorPerms = Permissions::get_permissions(DONOR);

View::show_header('Donate');
?>
<div class="thin">
	<span class="donation_info_title">Why donate?</span>
	<div class="box pad donation_info">
		<?=SITE_NAME?> has no advertisements, is not sponsored, and provides its services free of charge. For these reasons, <?=SITE_NAME?>'s financial obligations can only be met with the help of voluntary user donations. Supporting <?=SITE_NAME?> is and will always remain voluntary. If you are financially able, help pay <?=SITE_NAME?>'s bills by donating. <?=SITE_NAME?>'s survival is up to you.<br>
		<br>
		<?=SITE_NAME?> uses all voluntary donations to cover the costs of running the site, tracker, and IRC network. These costs represent the hardware the site runs on (e.g., servers, upgrades, fixes, etc.), and recurring operating expenses (e.g., hosting, bandwidth, power, etc.).<br>
		<br>
		Please note that <?=SITE_NAME?> is a nonprofit organization. No staff member or other individual responsible for the site's operation personally profits from user donations. As a donor, your financial support is exclusively applied to operating costs. When you donate you aren't paying the <?=SITE_NAME?> Staff, purchasing upload credit, or buying the ability to download. When you donate you are paying <?=SITE_NAME?>'s bills. <br>
		<br>
		<?=SITE_NAME?>'s Donor Rank system is currently available to all credited donors. This system provides donors with perks. Some of these perks are cosmetic (e.g., a donor icon added to your account), some are one-time benefits (e.g., additional invites), and others modify specific site options (e.g., additional profile information boxes, or personal collages). Please see the <a href="/forums.php?action=viewthread&amp;threadid=178640">Donor Rank System FAQ Document</a> for more information about these benefits.
	</div>

	<span class="donation_info_title">What you will receive for donating</span>
	<div class="box pad donation_info">
		Any donation or contribution option listed above gives you the opportunity to receive Donor Points. After acquiring your first Donor Point, your account will unlock Donor Rank #1. This rank will last forever, and you'll receive the following perks upon unlocking it:<br>
		<br>
		<ul><li>Our eternal love, as represented by the red heart you get next to your name</li><li><a href="/wiki.php?action=article&amp;id=8">Inactivity</a> timer immunity</li><li>Access to the <a href="/user.php?action=notify">notifications system</a></li><li>Two <a href="/user.php?action=invite">invites</a></li><li><a href="/collages.php">Collage</a> creation privileges</li><li>Personal collage creation privileges</li><li>One additional personal collage</li><li>A warm, fuzzy feeling</li></ul><br>
		There are a number of additional perks waiting for you when you unlock additional Donor Ranks. View the FAQ or infographic below for more information about these perks.<br>
		<br>
		<div style="text-align: center;">[<a href="/forums.php?action=viewthread&amp;threadid=178640">View Donor Rank System FAQ Document</a>]<br>
		<br>
		[<a rel="noreferrer" target="_blank" href="static/common/banners/donorinfographic.jpg">View Donor Rank Perks Infographic</a>]<br>
		(Last Updated: July 2013)</div><br>
		<br>
		Be reminded that when you make a donation, you aren't "purchasing" Donor Ranks, invites, or any <?=SITE_NAME?>-specific benefit. When donating, you are helping <?=SITE_NAME?> pay its bills, and your donation should be made in this spirit. The <?=SITE_NAME?> Staff does its best to recognize <?=SITE_NAME?>'s financial supporters in a fair and fun way, but all Donor Perks are subject to change or cancellation at any time, without notice.
	</div>

	<span class="donation_info_title">What you won't receive for donating</span>
	<div class="box pad donation_info">
		<ul><li>Immunity from the rules</li><li>Additional upload credit</li></ul>
	</div>
</div>
<!-- END Donate -->
<? View::show_footer(); ?>
