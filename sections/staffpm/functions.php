<?
function print_compose_staff_pm($Hidden = true) { ?>
		<div id="compose" class="<?=($Hidden ? 'hidden' : '')?>">
			<form action="staffpm.php" method="post">
				<input type="hidden" name="action" value="takepost" />
				<h3><label for="subject">Subject</label></h3>
				<input size="95" type="text" name="subject" id="subject" />
				<br />
				
				<h3><label for="message">Message</label></h3>
				<textarea rows="10" cols="95" name="message" id="message"></textarea>
				<br />
				
				<strong>Send to: </strong>
				<select name="level">
					<option value="0" selected="selected">First Line Support</option>
					<option value="650">Forum Moderators</option>
					<option value="700">Staff</option>
				</select>
				
				<input type="submit" value="Send message" />
				<input type="button" value="Hide" onclick="$('#compose').toggle();return false;" />
			</form>
		</div>
<? } ?>
