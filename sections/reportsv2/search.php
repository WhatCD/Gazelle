<?
/*
 * I'm not writing documentation for this page untill I write this page >.>
 */
if(!check_perms('admin_reports')){
	error(403);
}

show_header('Reports V2!', 'reportsv2');

?>
<div class="header">
	<h2>Search</h2>
<? include('header.php'); ?>
</div>
<br />
On hold until FZeroX fixes the main torrents search, then I will steal all his work and claim it as my own.
<?
show_footer();
?>