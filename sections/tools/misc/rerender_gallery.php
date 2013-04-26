<?
if (!(check_perms('users_mod') || check_perms('admin_clear_cache'))) {
	error(403);
}

// If this is accessed by itself through AJAX (e.g. when already rerendering images)
if (isset($_POST['ajax']) && isset($_POST['image'])) {
	if (!isset($_POST['stylesheet']) ) {
		echo json_encode(array('status' => "-2"));
		die();
	}
	//Get the actual image data from the sent data string.
	$FullData = $_POST['image'];
	list($type, $ImageData) = explode(';', $FullData);
	list(, $Base64Data) = explode(',', $ImageData);
	$Image = base64_decode($Base64Data);
	//Save the image to a file
	file_put_contents(STATIC_SERVER.'thumb_'.$_POST['stylesheet'].'.png', $Image);
	//Check if the file got saved properly, return status message.
	if (!file_exists(STATIC_SERVER.'thumb_'.$_POST['stylesheet'].'.png')) {
		echo json_encode(array('status' => "-1"));
		die();
	} else {
		echo json_encode(array('status' => "0"));
		die();
	}
} elseif (!isset($_POST['ajax'])) {
// If this is accessed by the administrating user, display the page (creates calls to itself through AJAX).
View::show_header('Rerender stylesheet gallery images', 'jquery,stylesheetgallery_rerender_queue');
?>
<style>
	#protected {
		position: relative;
	}
	#protecting_overlay {
		display: block;
		opacity: 0.01;
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
	}
	.statusbutton > iframe { border: none; }
	.statusbutton > iframe[src^="queue_"] { background: none repeat scroll 0 0 gray !important; }
	.statusbutton > iframe.finished { background: none repeat scroll 0 0 yellowgreen !important; }
	.statusbutton {
		overflow: hidden;
		width: 10px;
		height: 10px;
		border-radius: 10px;
		display: inline-block;
		border: 0px none;
		background: red;
		box-shadow: 0 0 0 1px rgba(0,0,0,0.4);
		margin: 0 2px -1px 0;
	}
</style>
<div class="thin">
	<h2>Rerender stylesheet gallery images</h2>
	<div class="sidebar">
		<div class="box box_info box_userinfo_stats">
			<div class="head colhead_dark">Color codes</div>
			<ul class="stats nobullet">
				<li><div class="statusbutton" style="background: gray;"></div> <span>  - Queued</span></li>
				<li><div class="statusbutton" style="background: yellow;"></div> <span>  - Currently encoding render</span></li>
				<li><div class="statusbutton" style="background: yellowgreen;"></div> <span>  - Rendered successfully</span></li>
				<li><div class="statusbutton" style="background: red;"></div> <span>  - Rendering returned an error, check console</span></li>
				<li><div class="statusbutton" style="background: blue;"></div> <span>  - Storage returned an error</span></li>
				<li><div class="statusbutton" style="background: purple;"></div> <span>  - Incomplete data error</span></li>
			</ul>
		</div>
	</div>
	<div class="main_column">
		<div class="box">
			<div class="head">
				<span>About rendering</span>
			</div>
			<div class="pad">
				<p>You are currently rerendering the stylesheet gallery images. Please don't close this page until rendering is finished or some images may be left unrendered.</p>
				<p>This is a processing-intensive operation; you're likely to see a spike in your CPU &amp; RAM usage.</p>
				<p>Tested and debugged on up-to-date Webkit and Gecko browsers; Opera will result in undefined behavior.</p>
				<p><strong>Important:</strong> Be sure to double-check the gallery once all rendering is finished.</p>
				<br />
				<a href="#" class="brackets" id="start_rerendering">Begin rendering</a>
			</div>
		</div>
		<div class="box">
			<div class="head">
				<span>Rendering status</span>
			</div>
			<div class="pad" id="protected">
				<div id="protecting_overlay"></div>
<?			foreach ($Stylesheets as $Style) { ?>
				<p><span class="statusbutton" style="background: gray;"><iframe src="#" data-src="user.php?action=stylesheetgallery&amp;name=<?= $Style['Name'] ?>&amp;save=true" width="100%" height="100%"></iframe></span> - <?=$Style['Name']?></p>
<?			} ?>
			</div>
		</div>
	</div>
</div>
<? View::show_footer();
} else {
	// Faulty operation, too many parameters or too few, error out.
	error(500);
}
?>
