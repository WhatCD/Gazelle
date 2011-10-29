<?
if (!check_perms('site_debug')) { error(403); }

if (!isset($_GET['case']) || !$Analysis = $Cache->get_value('analysis_'.$_GET['case'])) { error(404); }

show_header('Case Analysis');
?>
<h2>Case Analysis (<a href="<?=display_str($Analysis['url'])?>"><?=$_GET['case']?></a>)</h2>
<pre id="#debug_report"><?=display_str($Analysis['message'])?></pre>
<?
$Debug->flag_table($Analysis['flags']);
$Debug->include_table($Analysis['includes']);
$Debug->error_table($Analysis['errors']);
$Debug->query_table($Analysis['queries']);
$Debug->cache_table($Analysis['cache']);
$Debug->class_table();
$Debug->extension_table();
$Debug->constant_table();
$Debug->vars_table($Analysis['vars']);
show_footer();
?>
