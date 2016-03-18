<?

if (!check_perms('users_mod')) {
    error(403);
}

if (!FEATURE_EMAIL_REENABLE) {
    // This feature is disabled
    header("Location: tools.php");
    die();
}

View::show_header("Enable Requests", 'enable_requests');

// Pagination
$RequestsPerPage = 25;
list($Page, $Limit) = Format::page_limit($RequestsPerPage);

// How can things be ordered?
$OrderBys = array(
    'submitted_timestamp' => 'uer.Timestamp',
    'outcome' => 'uer.Outcome',
    'handled_timestamp' => 'uer.HandledTimestamp');

$Where = [];
$Joins = [];

// Default orderings
$OrderBy = "uer.Timestamp";
$OrderWay = "DESC";

// Build query for different views
if ($_GET['view'] == 'perfect') {
    $Where[] = "um.Email = uer.Email";
    $Joins[] = "JOIN users_main um ON um.ID = uer.UserID";
    $Where[] = "uer.IP = (SELECT IP FROM users_history_ips uhi1 WHERE uhi1.StartTime = (SELECT MAX(StartTime) FROM users_history_ips uhi2 WHERE uhi2.UserID = uer.UserID ORDER BY StartTime DESC LIMIT 1))";
    $Where[] = "(SELECT 1 FROM users_history_ips uhi WHERE uhi.IP = uer.IP AND uhi.UserID != uer.UserID) IS NULL";
    $Where[] = "ui.BanReason = '3'";
} else if ($_GET['view'] == 'minus_ip') {
    $Where[] = "um.Email = uer.Email";
    $Joins[] = "JOIN users_main um ON um.ID = uer.UserID";
    $Where[] = "ui.BanReason = '3'";
} else if ($_GET['view'] == 'invalid_email') {
    $Joins[] = "JOIN users_main um ON um.ID = uer.UserID";
    $Where[] = "um.Email != uer.Email";
} else if ($_GET['view'] == 'ip_overlap') {
    $Joins[] = "JOIN users_history_ips uhi ON uhi.IP = uer.IP AND uhi.UserID != uer.UserID";
} else if ($_GET['view'] == 'manual_disable') {
    $Where[] = "ui.BanReason != '3'";
} else {
    $Joins[] = '';
}
// End views

// Build query further based on search
if (isset($_GET['search'])) {
    $Username = db_string($_GET['username']);
    $IP = db_string($_GET['ip']);
    $SubmittedBetween = db_string($_GET['submitted_between']);
    $SubmittedTimestamp1 = db_string($_GET['submitted_timestamp1']);
    $SubmittedTimestamp2 = db_string($_GET['submitted_timestamp2']);
    $HandledUsername = db_string($_GET['handled_username']);
    $HandledBetween = db_string($_GET['handled_between']);
    $HandledTimestamp1 = db_string($_GET['handled_timestamp1']);
    $HandledTimestamp2 = db_string($_GET['handled_timestamp2']);
    $OutcomeSearch = (int) $_GET['outcome_search'];
    $Checked = (isset($_GET['show_checked']));

    if (array_key_exists($_GET['order'], $OrderBys)) {
        $OrderBy = $OrderBys[$_GET['order']];
    }

    if ($_GET['way'] == "asc" || $_GET['way'] == "desc") {
        $OrderWay = $_GET['way'];
    }

    if (!empty($Username)) {
        $Joins[] = "JOIN users_main um1 ON um1.ID = uer.UserID";
    }

    if (!empty($HandledUsername)) {
        $Joins[] = "JOIN users_main um2 ON um2.ID = uer.CheckedBy";
    }

    $Where = array_merge($Where, AutoEnable::build_search_query($Username,
        $IP, $SubmittedBetween, $SubmittedTimestamp1, $SubmittedTimestamp2, $HandledUsername,
        $HandledBetween, $HandledTimestamp1, $HandledTimestamp2, $OutcomeSearch, $Checked));
}
// End search queries

$ShowChecked = $Checked || !empty($HandledUsername) || !empty($HandledTimestamp1) || !empty($OutcomeSearch);

if (!$ShowChecked || count($Where) == 0) {
    // If no search is entered, add this to the query to only show unchecked requests
    $Where[] = 'Outcome IS NULL';
}

$QueryID = $DB->query("
    SELECT SQL_CALC_FOUND_ROWS
           uer.ID,
           uer.UserID,
           uer.Email,
           uer.IP,
           uer.UserAgent,
           uer.Timestamp,
           ui.BanReason,
           uer.CheckedBy,
           uer.HandledTimestamp,
           uer.Outcome
    FROM users_enable_requests AS uer
    JOIN users_info ui ON ui.UserID = uer.UserID
    ".implode(' ', $Joins)."
    WHERE
    ".implode(' AND ', $Where)."
    ORDER BY $OrderBy $OrderWay
    LIMIT $Limit");

$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$DB->set_query_id($QueryID);
?>

<div class="header">
    <h2>Auto-Enable Requests</h2>
</div>
<div align="center">
    <a class="brackets tooltip" href="tools.php?action=enable_requests" title="Default view">Main</a>
    <a class="brackets tooltip" href="tools.php?action=enable_requests&amp;view=perfect&amp;<?=Format::get_url(array('view', 'action'))?>" title="Valid username, matching email, current IP with no matches, and inactivity disabled">Perfect</a>
    <a class="brackets tooltip" href="tools.php?action=enable_requests&amp;view=minus_ip&amp;<?=Format::get_url(array('view', 'action'))?>" title="Valid username, matching email, and inactivity disabled">Perfect Minus IP</a>
    <a class="brackets tooltip" href="tools.php?action=enable_requests&amp;view=invalid_email&amp;<?=Format::get_url(array('view', 'action'))?>" title="Non-matching email address">Invalid Email</a>
    <a class="brackets tooltip" href="tools.php?action=enable_requests&amp;view=ip_overlap&amp;<?=Format::get_url(array('view', 'action'))?>" title="Requests with IP matches to other accounts">IP Overlap</a>
    <a class="brackets tooltip" href="tools.php?action=enable_requests&amp;view=manual_disable&amp;<?=Format::get_url(array('view', 'action'))?>" title="Requests for accounts that were not disabled for inactivity">Manual Disable</a>
    <a class="brackets tooltip" href="" title="Show/Hide Search" onclick="$('#search_form').gtoggle(); return false;">Search</a>
    <a class="brackets tooltip" href="" title="Show/Hide Search" onclick="$('#scores').gtoggle(); return false;">Scores</a>
</div><br />
<div class="thin">
    <table id="scores" class="hidden" style="width: 50%; margin: 0 auto;">
        <tr>
            <th>Username</th>
            <th>Checked</th>
        </tr>
<?  $DB->query("
        SELECT COUNT(CheckedBy), CheckedBy
        FROM users_enable_requests
        WHERE CheckedBy IS NOT NULL
        GROUP BY CheckedBy
        ORDER BY COUNT(CheckedBy) DESC
        LIMIT 50");
        while (list($Checked, $UserID) = $DB->next_record()) { ?>
            <tr>
                <td><?=Users::format_username($UserID)?></td>
                <td><?=$Checked?></td>
            </tr>
<?     }
    $DB->set_query_id($QueryID); ?>
    </table>
    <form action="" method="GET" id="search_form" <?=!isset($_GET['search']) ? 'class="hidden"' : ''?>>
        <input type="hidden" name="action" value="enable_requests" />
        <input type="hidden" name="view" value="<?=$_GET['view']?>" />
        <input type="hidden" name="search" value="1" />
        <table>
            <tr>
                <td class="label">Username</td>
                <td><input type="text" name="username" value="<?=$_GET['username']?>" /></td>
            </tr>
            <tr>
                <td class="label">IP Address</td>
                <td><input type="text" name="ip" value="<?=$_GET['ip']?>" /></td>
            </tr>
            <tr>
                <td class="label tooltip" title="This will search between the entered date and 24 hours after it">Submitted Timestamp</td>
                <td>
                    <select name="submitted_between" onchange="ChangeDateSearch(this.value, 'submitted_timestamp2');">
                        <option value="on" <?=$_GET['submitted_between'] == 'on' ? 'selected' : ''?>>On</option>
                        <option value="before" <?=$_GET['submitted_between'] == 'before' ? 'selected' : ''?>>Before</option>
                        <option value="after" <?=$_GET['submitted_between'] == 'after' ? 'selected' : ''?>>After</option>
                        <option value="between" <?=$_GET['submitted_between'] == 'between' ? 'selected' : ''?>>Between</option>
                    </select>&nbsp;
                    <input type="date" name="submitted_timestamp1" value="<?=$_GET['submitted_timestamp1']?>" />
                    <input type="date" id="submitted_timestamp2" name="submitted_timestamp2" value="<?=$_GET['submitted_timestamp2']?>" <?=$_GET['submitted_between'] != 'between' ? 'style="display: none;"' : ''?>/>
                </td>
            </tr>
            <tr>
                <td class="label">Handled By Username</td>
                <td><input type="text" name="handled_username" value="<?=$_GET['handled_username']?>" /></td>
            </tr>
            <tr>
                <td class="label tooltip" title="This will search between the entered date and 24 hours after it">Handled Timestamp</td>
                <td>
                    <select name="handled_between" onchange="ChangeDateSearch(this.value, 'handled_timestamp2');">
                        <option value="on" <?=$_GET['handled_between'] == 'on' ? 'selected' : ''?>>On</option>
                        <option value="before" <?=$_GET['handled_between'] == 'before' ? 'selected' : ''?>>Before</option>
                        <option value="after" <?=$_GET['handled_between'] == 'after' ? 'selected' : ''?>>After</option>
                        <option value="between" <?=$_GET['handled_between'] == 'between' ? 'selected' : ''?>>Between</option>
                    </select>&nbsp;
                <input type="date" name="handled_timestamp1" value="<?=$_GET['handled_timestamp1']?>" />
                <input type="date" id="handled_timestamp2" name="handled_timestamp2" value="<?=$_GET['handled_timestamp2']?>" <?=$_GET['handled_between'] != 'between' ? 'style="display: none;"' : ''?>/>
            </td>
            </tr>
            <tr>
                <td class="label">Outcome</td>
                <td>
                    <select name="outcome_search">
                        <option value="">---</option>
                        <option value="<?=AutoEnable::APPROVED?>" <?=$_GET['outcome_search'] == AutoEnable::APPROVED ? 'selected' : ''?>>Approved</option>
                        <option value="<?=AutoEnable::DENIED?>" <?=$_GET['outcome_search'] == AutoEnable::DENIED ? 'selected' : ''?>>Denied</option>
                        <option value="<?=AutoEnable::DISCARDED?>" <?=$_GET['outcome_search'] == AutoEnable::DISCARDED ? 'selected' : ''?>>Discarded</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label">Include Checked</td>
                <td><input type="checkbox" name="show_checked" <?=isset($_GET['show_checked']) ? 'checked' : ''?> /></td>
            </tr>
            <tr>
                <td class="label">Order By</td>
                <td>
                    <select name="order">
                        <option value="submitted_timestamp" <?=$_GET['order'] == 'submitted_timestamp' ? 'selected' : '' ?>>Submitted Timestamp</option>
                        <option value="outcome" <?=$_GET['order'] == 'outcome' ? 'selected' : '' ?>>Outcome</option>
                        <option value="handled_timestamp" <?=$_GET['order'] == 'handled_timestamp' ? 'selected' : '' ?>>Handled Timestamp</option>
                    </select>&nbsp;
                    <select name="way">
                        <option value="asc" <?=$_GET['way'] == 'asc' ? 'selected' : '' ?>>Ascending</option>
                        <option value="desc" <?=!isset($_GET['way']) || $_GET['way'] == 'desc' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan=2><input type="submit" value="Search" /></td>
            </tr>
        </table>
    </form>
</div>
<?
if ($NumResults > 0) { ?>
    <div class="linkbox">
<?
    $Pages = Format::get_pages($Page, $NumResults, $RequestsPerPage);
    echo $Pages;
?>
    </div>
    <table width="100%">
        <tr class="colhead">
            <td class="center"><input type="checkbox" id="check_all" /></td>
            <td>Username</td>
            <td>Email Address</td>
            <td>IP Address</td>
            <td>User Agent</td>
            <td>Age</td>
            <td>Ban Reason</td>
            <td>Comment<?=$ShowChecked ? '/Checked By' : ''?></td>
            <td>Submit<?=$ShowChecked ? '/Checked Date' : ''?></td>
<?      if ($ShowChecked) { ?>
            <td>Outcome</td>
<?      } ?>
        </tr>
    <?
    $Row = 'a';
    while (list($ID, $UserID, $Email, $IP, $UserAgent, $Timestamp, $BanReason, $CheckedBy, $HandledTimestamp, $Outcome) = $DB->next_record()) {
        $Row = $Row === 'a' ? 'b' : 'a';
?>
        <tr class="row<?=$Row?>" id="row_<?=$ID?>">
            <td class="center">
<?          if (!$HandledTimestamp) { ?>
                <input type="checkbox" id="multi" data-id="<?=$ID?>" />
<?          } ?>
            </td>
            <td><?=Users::format_username($UserID)?></td>
            <td><?=display_str($Email)?></td> 
            
            <td><?=display_str($IP)?></td>
            
            <td><?=display_str($UserAgent)?></td>
            <td><?=time_diff($Timestamp)?></td>
            <td><?=($BanReason == 3) ? '<b>Inactivity</b>' : 'Other'?></td>
<?      if (!$HandledTimestamp) { ?>
            <td><input class="inputtext" type="text" id="comment<?=$ID?>" placeholder="Comment" /></td>
            <td>
                <input type="submit" id="outcome" value="Approve" data-id="<?=$ID?>" />
                <input type="submit" id="outcome" value="Reject" data-id="<?=$ID?>" />
                <input type="submit" id="outcome" value="Discard" data-id="<?=$ID?>" />
            </td>
<?      } else { ?>
            <td><?=Users::format_username($CheckedBy);?></td>
            <td><?=$HandledTimestamp?></td>
<?      }

        if ($ShowChecked) { ?>
            <td><?=AutoEnable::get_outcome_string($Outcome)?>
<?          if ($Outcome == AutoEnable::DISCARDED) { ?>
                <a href="" id="unresolve" onclick="return false;" class="brackets" data-id="<?=$ID?>">Unresolve</a>
<?          } ?>
            </td>
<?      } ?>
        </tr>
    <?
    }
    ?>
    </table>
    <div class="linkbox">
<?
    $Pages = Format::get_pages($Page, $NumResults, $RequestsPerPage);
    echo $Pages;
?>
    </div>
<div style="padding-bottom: 11px;">
    <input type="submit" id="outcome" value="Approve Selected" />
    <input type="submit" id="outcome" value="Reject Selected" />
    <input type="submit" id="outcome" value="Discard Selected" />
</div>
<? } else { ?>
    <h2 align="center">No new pending auto enable requests<?=isset($_GET['view']) ? ' in this view' : ''?></h2>
<? }
View::show_footer();
