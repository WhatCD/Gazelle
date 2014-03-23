<?View::show_header('Wiki');?>
<div class="thin">
	<div class="header">
		<h2>Wiki</h2>
	</div>
	<div class="box pad center">
		<p>Search the wiki for user created tutorials and information.</p>
		<form class="search_form" name="wiki" action="wiki.php" method="get">
			<input type="hidden" name="action" value="search" />
			<input type="search" name="search" size="80" />
			<input value="Search" type="submit" class="hidden" />
		</form>
		<br />
		<p>Additionally, you can manually browse through the articles by their first letter.</p>
		<span>
			<a href="wiki.php?action=browse&amp;letter=a">A</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=b">B</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=c">C</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=d">D</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=e">E</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=f">F</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=g">G</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=h">H</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=i">I</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=j">J</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=k">K</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=l">L</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=m">M</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=n">N</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=o">O</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=p">P</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=q">Q</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=r">R</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=s">S</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=t">T</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=u">U</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=v">V</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=w">W</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=x">X</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=y">Y</a>&nbsp;&nbsp;
			<a href="wiki.php?action=browse&amp;letter=z">Z</a>&nbsp;&nbsp;
		</span>
	</div>
</div>
<? View::show_footer(); ?>
