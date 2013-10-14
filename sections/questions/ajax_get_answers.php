<?

include(SERVER_ROOT.'/classes/text.class.php');
$Text = new TEXT(true);

$ID = (int) $_POST['id'];

if (empty($ID)) {
	die();
}

$UserID = (int) $_POST['userid'];
$UserIDSQL = "";
if (!empty($UserID)) {
	$UserIDSQL = " AND UserID != '$UserID' ";
}

G::$DB->query("SELECT UserID, Answer, Date FROM staff_answers WHERE QuestionID = '$ID' $UserIDSQL ORDER BY DATE DESC");

$Answers = G::$DB->to_array();
?>

<div id="responses_for_<?=$ID?>" style="margin-left: 20px;">
<?	foreach($Answers as $Answer) { ?>
		<div class="box box2" >
			<div class="head">
				<span>
					Answer by <?=Users::format_username($Answer['UserID'])?> - <?=time_diff($Answer['Date'])?>
				</span>
			</div>
			<div class="pad">
				<?=$Text->full_format($Answer['Answer'])?>
			</div>
		</div>
	<?	} ?>
</div>
