<?
enforce_login();
if (!check_perms('site_upload')) {
	error(403);
}
if ($LoggedUser['DisableUpload']) {
	error('Your upload privileges have been revoked.');
}
// build the page

if (!empty($_POST['submit'])) {
	include('upload_handle.php');
} else {
	include(SERVER_ROOT.'/sections/upload/upload.php');
}
?>
