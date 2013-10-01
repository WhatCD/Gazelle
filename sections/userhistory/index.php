<?
/*****************************************************************
User history switch center


This page acts as a switch that includes the real user history pages (to keep
the root less cluttered).

enforce_login() is run here - the entire user history pages are off limits for
non members.
*****************************************************************/

//Include all the basic stuff...
enforce_login();

if ($_GET['action']) {
	switch ($_GET['action']) {
		case 'ips':
			//Load IP history page
			include('ip_history.php');
			break;
		case 'tracker_ips':
			include('ip_tracker_history.php');
			break;
		case 'passwords':
			//Load Password history page
			include('password_history.php');
			break;
		case 'email':
			//Load email history page
			include('email_history.php');
			break;
		case 'email2':
			//Load email history page
			include('email_history2.php');
			break;
		case 'passkeys':
			//Load passkey history page
			include('passkey_history.php');
			break;
		case 'posts':
			//Load ratio history page
			include('post_history.php');
			break;
		case 'subscriptions':
			// View subscriptions
			require('subscriptions.php');
			break;
		case 'thread_subscribe':
			require('thread_subscribe.php');
			break;
		case 'comments_subscribe':
			require('comments_subscribe.php');
			break;
		case 'catchup':
			require('catchup.php');
			break;
		case 'collage_subscribe':
			require('collage_subscribe.php');
			break;
		case 'subscribed_collages':
			require('subscribed_collages.php');
			break;
		case 'catchup_collages':
			require('catchup_collages.php');
			break;
		case 'token_history':
			require('token_history.php');
			break;
		case 'quote_notifications':
			require('quote_notifications.php');
			break;
		default:
			//You trying to mess with me query string? To the home page with you!
			header('Location: index.php');
	}
}

/* Database Information Regarding This Page

users_history_ips:
	id (auto_increment, index)
	userid (index)
	ip (stored using ip2long())
	timestamp

users_history_passwd:
	id (auto_increment, index)
	userid (index)
	changed_by (index)
	old_pass
	new_pass
	timestamp

users_history_email:
	id (auto_increment, index)
	userid (index)
	changed_by (index)
	old_email
	new_email
	timestamp

users_history_passkey:
	id (auto_increment, index)
	userid (index)
	changed_by (index)
	old_passkey
	new_passkey
	timestamp

users_history_stats:
	id (auto_increment, index)
	userid (index)
	uploaded
	downloaded
	ratio
	timestamp

*/
?>
