<?
if (!($IsFLS)) {
	// Logged in user is not FLS or Staff
	error(403);
}

show_header('Staff PMs', 'staffpm');

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

?>
<div class="thin">
	<h2>Staff PMs - Manage common responses</h2>
	<div class="linkbox">
<? 	if ($IsStaff) { ?>
		<a href="staffpm.php">[My unanswered]</a>
<? 	} ?>
		<a href="staffpm.php?view=unanswered">[All unanswered]</a>
		<a href="staffpm.php?view=open">[Open]</a>
		<a href="staffpm.php?view=resolved">[Resolved]</a>
<?	if ($ConvID = (int)$_GET['convid']) { ?>
		<a href="staffpm.php?action=viewconv&id=<?=$ConvID?>">[Back to conversation]</a>
<?	} ?>
		<br />
		<br />
	</div>
	<div id="commonresponses" class="center">
		<br />
		<div id="ajax_message_0" class="hidden center alertbar"></div>
		<br />
		<div class="center">
			<h3>Create new response:</h3>
		</div>
		<div id="response_new" class="box">
			<form id="response_form_0" action="">
				<div class="head">
					<strong>Name:</strong> 
					<input onfocus="if (this.value == 'New name') this.value='';" 
						   onblur="if (this.value == '') this.value='New name';" 
						   type="text" id="response_name_0" size="87" value="New name" 
					/>
				</div>
				<div class="pad">
					<textarea onfocus="if (this.value == 'New message') this.value='';" 
							  onblur="if (this.value == '') this.value='New message';" 
							  rows="10" cols="87"
							  id="response_message_0">New message</textarea>
					<br />
					<input type="button" value="Save" id="save_0" onClick="SaveMessage(0);" />
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
$DB->query("SELECT ID, Message, Name FROM staff_pm_responses ORDER BY ID DESC");
while(list($ID, $Message, $Name) = $DB->next_record()) {
	
?>
		<br />
		<div id="ajax_message_<?=$ID?>" class="hidden center alertbar"></div>
		<br />
		<div id="response_<?=$ID?>" class="box">
			<form id="response_form_<?=$ID?>" action="">
				<div class="head">
					<strong>Name:</strong> 
					<input type="hidden" name="id" value="<?=$ID?>" />
					<input type="text" name="name" id="response_name_<?=$ID?>" size="87" value="<?=display_str($Name)?>" />
				</div>
				<div class="pad">
					<div class="box pad hidden" id="response_div_<?=$ID?>">
						<?=$Text->full_format($Message)?>
					</div>
					<textarea rows="10" cols="87" id="response_message_<?=$ID?>" name="message"><?=display_str($Message)?></textarea>
					<br />
					<input type="button" value="Toggle preview" onClick="PreviewResponse(<?=$ID?>);" />
					<input type="button" value="Delete" onClick="DeleteMessage(<?=$ID?>);" />
					<input type="button" value="Save" id="save_<?=$ID?>" onClick="SaveMessage(<?=$ID?>);" />
				</div>
			</form>
		</div>
<?
	
}

?>
	</div>
</div>
<?

show_footer();

?>
