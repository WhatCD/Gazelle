<?
authorize();

if (!isset($_POST['postid']) || !is_number($_POST['postid']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
	error(0);
}

if ($LoggedUser['DisablePosting']) {
	error('Your posting privileges have been removed.');
}

$SendPM = isset($_POST['pm']) && $_POST['pm'];
Comments::edit((int)$_POST['postid'], $_POST['body'], $SendPM);

// This gets sent to the browser, which echoes it in place of the old body
echo Text::full_format($_POST['body']);
