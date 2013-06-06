<?
enforce_login();
if (!check_perms('users_mod')) {
	error(403);
}


if (!empty($_POST)) {
	if (empty($_POST['productid']) || !is_number($_POST['productid'])) {
		error('ProductID should be a number...');
		header('Location: tools.php?action=featured_merch');
		die();
	}

	$ProductID = (int)$_POST['productid'];
	$Title = db_string($_POST['title']);
	$Image = db_string($_POST['image']);
	$AritstID = ((int)$_POST['artistid'] > 0) ? (int)$_POST['artistid'] : 0;

	if (!$Title) {
		$Title = db_string('Featured Product');
	}

	$DB->query("
		UPDATE featured_merch
		SET Ended = '".sqltime()."'
		WHERE Ended = 0");
	$DB->query("
		INSERT INTO featured_merch (ProductID, Title, Image, ArtistID, Started)
		VALUES ($ProductID, '$Title', '$Image', '$ArtistID', '".sqltime()."')");
	$Cache->delete_value('featured_merch');
	header('Location: index.php');
	die();
}

View::show_header();
?>
<h2>Change the featured merchandise</h2>
<div class="thin box pad">
	<form action="" method="post">
		<input type="hidden" name="action" value="featured_merch" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table align="center">
			<tr>
				<td class="label">Product ID:</td>
				<td>
					<input type="text" name="productid" size="10" />
				</td>
			</tr>
			<tr>
				<td class="label">Title:</td>
				<td>
					<input type="text" name="title" size="30" />
				</td>
			</tr>
			<tr>
				<td class="label">Image:</td>
				<td>
					<input type="text" name="image" size="30" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Submit" />
				</td>
			</tr>
			<tr>
				<td class="label">Artist ID:</td>
				<td>
					<input type="text" name="artistid" size="10" />
				</td>
			</tr>
		</table>
	</form>
</div>
<?
View::show_footer();
?>
