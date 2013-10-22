<?
View::show_header("Ask the Staff");
?>
<div class="thin">
	<div class="header">
		<h2>Ask Staff Anything</h2>
	</div>
	<div class="linkbox">
		<a class="brackets" href="questions.php?action=answers">View staff answers</a>
	</div>
	<div class="center box pad">
		<form method="post">
			<input type="hidden" name="action" value="take_ask_question" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<textarea id="question" class="required" onkeyup="resize('question');" name="question" cols="90" rows="8"></textarea>
			<div id="buttons" class="center">
				<input type="submit" class="submit" id="submit_button" value="Ask question" />
			</div>
		</form>
	</div>
</div>
<?
View::show_footer();
