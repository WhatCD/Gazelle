<?
View::show_header('Connectability Checker');
?>
<div class="thin">
	<div class="header">
		<h2><a href="user.php?id=<?=$LoggedUser['ID']?>"><?=$LoggedUser['Username']?></a> &gt; Connectability Checker</h2>
	</div>
	<div class="linkbox"></div>
	<div class="box pad">This page has been disabled because the results have been inaccurate. Try a smarter and more reliable service, like <a href="http://www.canyouseeme.org">http://www.canyouseeme.org</a>.</div>
</div>
<? View::show_footer(); ?>
