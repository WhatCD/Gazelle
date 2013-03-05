<?
if (!empty($_GET['action']) && $_GET['action'] === 'autocomplete') {
	require('sections/artist/autocomplete.php');
} else {
	define('ERROR_EXCEPTION', true);
	require('classes/script_start.php');
}
