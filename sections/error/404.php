<h1>Error: 404</h1> Not Found.
<?
//Hide alerts for missing images and static requests
if (!preg_match("/\.(ico|jpg|jpeg|gif|png)$/", $_SERVER['REQUEST_URI']) && substr($_SERVER['REQUEST_URI'],0,9) !== '/static/') {
	notify(STATUS_CHAN,'404');
}
