<?
if (!check_perms('site_debug')) {
	error(403);
}

//View schemas
if (!empty($_GET['table'])) {
	$DB->query('SHOW TABLES');
	$Tables =$DB->collect('Tables_in_'.SQLDB);
	if (!in_array($_GET['table'], $Tables)) {
		error(0);
	}
	$DB->query('SHOW CREATE TABLE '.db_string($_GET['table']));
	list(,$Schema) = $DB->next_record(MYSQLI_NUM, false);
	header('Content-type: text/plain');
	die($Schema);
}

//Cache the tables for 4 hours, makes sorting faster
if (!$Tables = $Cache->get_value('database_table_stats')) {
	$DB->query('SHOW TABLE STATUS');
	$Tables =$DB->to_array();
	$Cache->cache_value('database_table_stats', $Tables, 3600 * 4);
}

require(SERVER_ROOT.'/classes/charts.class.php');
$Pie = new PIE_CHART(750,400,array('Other'=>1,'Percentage'=>1,'Sort'=>1));

//Begin sorting
$Sort = array();
switch (empty($_GET['order_by']) ? '' : $_GET['order_by']) {
	case 'name':
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[6] + $Value[8]);
			$Sort[$Key] = $Value[0];
		}
		break;
	case 'engine':
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[6] + $Value[8]);
			$Sort[$Key] = $Value[1];
		}
		break;
	case 'rows':
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[4]);
			$Sort[$Key] = $Value[4];
		}
		break;
	case 'rowsize':
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[5]);
			$Sort[$Key] = $Value[5];
		}
		break;
	case 'datasize':
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[6]);
			$Sort[$Key] = $Value[6];
		}
		break;
	case 'indexsize':
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[8]);
			$Sort[$Key] = $Value[8];
		}
		break;
	case 'totalsize':
	default:
		foreach ($Tables as $Key => $Value) {
			$Pie->add($Value[0], $Value[6] + $Value[8]);
			$Sort[$Key] = $Value[6] + $Value[8];
		}
}
$Pie->generate();

if (!empty ($_GET['order_way']) && $_GET['order_way'] == 'asc') {
	$SortWay = SORT_ASC;
} else {
	$SortWay = SORT_DESC;
}

array_multisort($Sort, $SortWay, $Tables);
//End sorting

View::show_header('Database Specifics');
?>
<h3>Breakdown</h3>
<div class="box pad center">
	<img src="<?=$Pie->url()?>" />
</div>
<br />
<table>
	<tr class="colhead">
		<td><a href="tools.php?action=database_specifics&amp;order_by=name&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'name' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Name</a></td>
		<td><a href="tools.php?action=database_specifics&amp;order_by=engine&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'engine' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Engine</a></td>
		<td><a href="tools.php?action=database_specifics&amp;order_by=rows&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'rows' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Rows</td>
		<td><a href="tools.php?action=database_specifics&amp;order_by=rowsize&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'rowsize' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Row Size</a></td>
		<td><a href="tools.php?action=database_specifics&amp;order_by=datasize&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'datasize' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Data Size</a></td>
		<td><a href="tools.php?action=database_specifics&amp;order_by=indexsize&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'indexsize' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Index Size</a></td>
		<td><a href="tools.php?action=database_specifics&amp;order_by=totalsize&amp;order_way=<?=(!empty($_GET['order_by']) && $_GET['order_by'] == 'totalsize' && !empty($_GET['order_way']) && $_GET['order_way'] == 'desc') ? 'asc' : 'desc'?>">Total Size</td>
		<td>Tools</td>
	</tr>
<?
$TotalRows = 0;
$TotalDataSize = 0;
$TotalIndexSize = 0;
$Row = 'a';
foreach ($Tables as $Table) {
	list($Name,$Engine,,,$Rows,$RowSize,$DataSize,,$IndexSize) = $Table;
	$Row = $Row === 'a' ? 'b' : 'a';

	$TotalRows += $Rows;
	$TotalDataSize += $DataSize;
	$TotalIndexSize += $IndexSize;
?>
	<tr class="row<?=$Row?>">
		<td><?=display_str($Name)?></td>
		<td><?=display_str($Engine)?></td>
		<td><?=number_format($Rows)?></td>
		<td><?=Format::get_size($RowSize)?></td>
		<td><?=Format::get_size($DataSize)?></td>
		<td><?=Format::get_size($IndexSize)?></td>
		<td><?=Format::get_size($DataSize + $IndexSize)?></td>
		<td><a href="tools.php?action=database_specifics&amp;table=<?=display_str($Name)?>" class="brackets">Schema</a></td>
	</tr>
<?
}
?>
	<tr>
		<td></td>
		<td></td>
		<td><?=number_format($TotalRows)?></td>
		<td></td>
		<td><?=Format::get_size($TotalDataSize)?></td>
		<td><?=Format::get_size($TotalIndexSize)?></td>
		<td><?=Format::get_size($TotalDataSize + $TotalIndexSize)?></td>
		<td></td>
	</tr>
</table>
<?
View::show_footer();
