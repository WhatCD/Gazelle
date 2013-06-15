<?
//Include the header
View::show_header('Request Rules');
?>
<div class="thin">
	<div class="header">
		<h2 class="center">Requests</h2>
	</div>
	<div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
		<ul>
			<li>
				<strong>Do not make requests for torrents that break the rules.</strong> It is your responsibility that the request follows the rules. Your request will be deleted, and you will not get your bounty back. Requests cannot be more specific than the upload (and trumping) rules. For example, requesting an MP3 torrent with a log when the rules prohibit replacing an MP3 torrent without a log. Such a request asks for a duplicate to be uploaded. Exceptions: Requests made before the November 2008 rule change are not subject to deletion. However, you are advised to edit such older requests to comply with the rules.
			</li>
			<li>
				<strong>Only one title (application, album, etc.) per request.</strong> No requests for multiple albums (e.g. discographies) or vague requirements. You may ask for multiple formats, but you cannot specify all of them. For example, you may ask for either a FLAC or V0 but not both formats. You may also make a list of albums by one artist that satisfies your request, but the request can be filled with only one album. Application requests can consist of only one application, but may span a range of different versions. However, such requests can be filled with only one version of that title.
			</li>
			<li>
				<strong>Do not unfill requests for trivial reasons.</strong> If you did not specify in your request what you wanted (such as bitrates or a particular edition), do not unfill and later change the description. Do not unfill requests if you are unsure of what you are doing (e.g. the filled torrent may be a transcode, but you don't know how to tell). Ask for help from <a href="/staff.php">first-line support or staff</a> in that case. You may unfill the request if the torrent does not fit your specifications stated clearly in the request.
			</li>
			<li>
				<strong>All users must have an equal chance to fill a request.</strong> Trading upload credit is not allowed. Abusing the request system to exchange favors for other users is not tolerated. That includes making specific requests for certain users (whether explicitly named or not). Making requests for releases, and then unfilling so that one particular user can fill the request is not allowed. If reported, both the requester and user filling the request will receive a warning and lose the request bounty.
			</li>
			<li>
				<strong>No manipulation of the requester for bounty.</strong> The bounty is a reward for helping other users&#8202;&mdash;&#8202;it should not be a ransom. Any user who refuses to fill a request unless the bounty is increased will face harsh punishment.
			</li>
		</ul>
	</div>
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
