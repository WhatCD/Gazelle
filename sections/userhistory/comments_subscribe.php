<?
// perform the back end of subscribing to topics
authorize();

if (!in_array($_GET['page'], array('artist', 'collages', 'requests', 'torrents')) || !is_number($_GET['pageid'])) {
	error(0);
}

Subscriptions::subscribe_comments($_GET['page'], $_GET['pageid']);