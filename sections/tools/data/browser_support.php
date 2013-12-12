<?
$Campaign = 'forumaudio';
if (!$Votes = $Cache->get_value("support_$Campaign")) {
	$Votes = array(0, 0);
}
if (!isset($_GET['support'])) {
?>
<h1>Browser Support Campaign: <?=$Campaign?></h1>
<ul>
	<li><?=number_format($Votes[0])?> +</li>
	<li><?=number_format($Votes[1])?> -</li>
	<li><?=number_format(($Votes[0] / ($Votes[0] + $Votes[1])) * 100, 3)?>%</li>
</ul>
<?
} elseif ($_GET['support'] === 'true') {
	$Votes[0]++;
} elseif ($_GET['support'] === 'false') {
	$Votes[1]++;
}
$Cache->cache_value("support_$Campaign", $Votes, 0);
?>
