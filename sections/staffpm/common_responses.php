<?
if (!($IsFLS)) {
	// Logged in user is not FLS or Staff
	error(403);
}

View::show_header('Staff PMs', 'staffpm');

?>
<div class="thin">
	<div class="header">
		<h2>Staff PMs - Manage common responses</h2>
		<div class="linkbox">
<? 	if ($IsStaff) { ?>
			<a href="staffpm.php" class="brackets">View your unanswered</a>
<? 	} ?>
			<a href="staffpm.php?view=unanswered" class="brackets">View all unanswered</a>
			<a href="staffpm.php?view=open" class="brackets">View unresolved</a>
			<a href="staffpm.php?view=resolved" class="brackets">View resolved</a>
<?	if ($ConvID = (int)$_GET['convid']) { ?>
			<a href="staffpm.php?action=viewconv&amp;id=<?=$ConvID?>" class="brackets">Back to conversation</a>
<?	} ?>
		</div>
	</div>
	<br />
	<br />
	<div id="commonresponses" class="center">
		<br />
		<div id="ajax_message_0" class="hidden center alertbar"></div>
		<br />
		<div class="center">
			<h3>Create new response:</h3>
		</div>
		<div id="response_new" class="box">
			<form class="send_form" name="response" id="response_form_0" action="">
				<div class="head">
					<strong>Name:</strong>
					<input onfocus="if (this.value == 'New name') { this.value = ''; }"
						onblur="if (this.value == '') { this.value = 'New name'; }"
						type="text" id="response_name_0" size="87" value="New name"
					/>
				</div>
				<div class="pad">
					<textarea onfocus="if (this.value == 'New message') { this.value = ''; }"
						onblur="if (this.value == '') { this.value = 'New message'; }"
						rows="10" cols="87"
						id="response_message_0">New message</textarea>
					<br />
					<input type="button" value="Save" id="save_0" onclick="SaveMessage(0);" />
				</div>
			</form>
		</div>
		<br />
		<br />
		<div class="center">
			<h3>Edit old responses:</h3>
		</div>
<?

// List common responses
$DB->query("
	SELECT ID, Message, Name
	FROM staff_pm_responses
	ORDER BY ID DESC");
while (list($ID, $Message, $Name) = $DB->next_record()) {

?>
		<br />
		<div id="ajax_message_<?=$ID?>" class="hidden center alertbar"></div>
		<br />
		<div id="response_<?=$ID?>" class="box">
			<form class="send_form" name="response" id="response_form_<?=$ID?>" action="">
				<div class="head">
					<strong>Name:</strong>
					<input type="hidden" name="id" value="<?=$ID?>" />
					<input type="text" name="name" id="response_name_<?=$ID?>" size="87" value="<?=display_str($Name)?>" />
				</div>
				<div class="pad">
					<div class="box pad hidden" id="response_div_<?=$ID?>">
						<?=Text::full_format($Message)?>
					</div>
					<textarea rows="10" cols="87" id="response_message_<?=$ID?>" name="message"><?=display_str($Message)?></textarea>
					<br />
					<input type="button" value="Toggle preview" onclick="PreviewResponse(<?=$ID?>);" />
					<input type="button" value="Delete" onclick="DeleteMessage(<?=$ID?>);" />
					<input type="button" value="Save" id="save_<?=$ID?>" onclick="SaveMessage(<?=$ID?>);" />
				</div>
			</form>
		</div>
<?
}
?>
	</div>
</div>
<? View::show_footer(); ?>
