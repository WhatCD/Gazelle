<?
if(in_array($_GET['stat'], array('inbox', 'uploads', 'bookmarks', 'notifications', 'subscriptions', 'comments', 'friends'))) {
	$Cache->begin_transaction('stats_links');
	$Cache->update_row(false, array($_GET['stat'] => '+1'));
	$Cache->commit_transaction(0);
}
?>
