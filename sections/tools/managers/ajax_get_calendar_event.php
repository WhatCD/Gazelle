<?

if (!Calendar::can_view()) {
	error(404);
}

if ($_GET['id']) {
	$Event = Calendar::get_event($_GET['id']);
} else {
	$Year = $_GET['year'];
	$Month = $_GET['month'];
	$Day = $_GET['day'];
	if ($Month < 10) {
		$Month = "0$Month";
	}
	if ($Day < 10) {
		$Day = "0$Day";
	}
	$StartDate = $EndDate = "$Year-$Month-$Day";
}
?>
<form id="event_form" name="event_form" method="post" action="">
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
<?	if ($Event) { ?>
	<input type="hidden" name="id" value="<?=$Event['ID']?>" />
<?	} ?>
	<input type="hidden" name="action" value="take_calendar_event" />
	<table class="event_form_table">
	<tr>
		<tr>
			<td class="label small_label">Title:</td>
			<td>
				<input type="text" id="title" name="title" class="required" value="<?=$Event['Title']?>" />
			</td>
		</tr>
		<tr>
			<td class="label small_label">Category:</td>
			<td>
				<select id="category" name="category" class="required">
<?
	$Categories = Calendar::$Categories;
	foreach ($Categories as $Key => $Value) {
?>
					<option	value="<?=$Key?>"<?=$Key == $Event['Category'] ? ' selected="selected"' : ''?>><?=$Value?></option>
<?	} ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="label small_label">Importance:</td>
			<td>
				<select id="importance" name="importance" class="required">
<?
	$Importances = Calendar::$Importances;
	foreach ($Importances as $Key => $Value) {
?>
					<option	value="<?=$Key?>"<?=$Key == $Event['Importance'] ? ' selected="selected"' : ''?>><?=$Value?></option>
<?	} ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="label small_label">Team:</td>
			<td>
				<select id="team" name="team" class="required">
<?
	$Teams = Calendar::$Teams;
	foreach ($Teams as $Key => $Value) {
?>
					<option	value="<?=$Key?>"<?=$Key == $Event['Team'] ? ' selected="selected"' : ''?>><?=$Value?></option>
<?	} ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="label small_label">Start date:</td>
			<td>
				<input type="date" id="start_date" name="start_date" class="required"
<?	if ($Event) { ?>
						value="<?=date('Y-m-d', strtotime($Event['StartDate']))?>" />
<?	} else { ?>
						value="<?=$StartDate?>" />
<?	} ?>
			</td>
		</tr>
		<tr>
			<td class="label small_label">End date:</td>
			<td>
				<input type="date" id="end_date" name="end_date" class="required"
<?	if ($Event) { ?>
						value="<?=date('Y-m-d', strtotime($Event['EndDate']))?>" />
<?	} else { ?>
						value="<?=$EndDate?>" />
<?	} ?>
			</td>
		</tr>
		<tr>
			<td class="label small_label">Created by:</td>
			<td>
				<?=$Event ? Users::format_username($Event['AddedBy']) : Users::format_username($LoggedUser['ID'])?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<textarea id="body" name="body" class="required"><?=$Event['Body']?></textarea>
			</td>
		</tr>
		<tr>
<?
	if (check_perms('users_mod')) {
		if ($Event) {
?>
			<td>
				<input type="submit" id="update" name="update" value="Update" />
			</td>
			<td>
				<input type="submit" id="delete" name="delete" value="Delete" />
			</td>
<?		} else { ?>
			<td>
				<input type="submit" id="create" name="create" value="Create" />
			</td>
<?
		}
	}
?>
		</tr>
	</tr>
</table>
</form>
