<?
enforce_login();


define('INDEX_ARTICLE', '1');


function class_list($Selected = 0) {
	global $Classes, $LoggedUser;
	$Return = '';
	foreach ($Classes as $ID => $Class) {
		if ($Class['Level'] <= $LoggedUser['EffectiveClass']) {
			$Return.='<option value="'.$Class['Level'].'"';
			if ($Selected == $Class['Level']) {
				$Return.=' selected="selected"';
			}
			$Return.='>'.Format::cut_string($Class['Name'], 20, 1).'</option>'."\n";
		}
	}
	reset($Classes);
	return $Return;
}

if (!empty($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'create':
			if ($_POST['action']) {
				include('takecreate.php');
			} else {
				include('create.php');
			}
			break;
		case 'edit':
			if ($_POST['action']) {
				include('takeedit.php');
			} else {
				include('edit.php');
			}
			break;
		case 'delete':
			if ($_POST['action']) {
				include('takedelete.php');
			} else {
				include('delete.php');
			}
			break;
		case 'revisions':
			include('revisions.php');
			break;
		case 'compare':
			include('compare.php');
			break;
		case 'add_alias':
			include('add_alias.php');
			break;
		case 'delete_alias':
			include('delete_alias.php');
			break;
		case 'browse':
			include('wiki_browse.php');
			break;
		case 'article':
			include('article.php');
			break;
		case 'search':
			include('search.php');
			break;
	}
} else {
	$_GET['id'] = INDEX_ARTICLE;
	include('article.php');
	//include('splash.php');
}
?>
