<?php

/*-- TODO ---------------------------//
Add the JavaScript validation into the display page using the class
//-----------------------------------*/

if (!empty($LoggedUser['ID'])) {
	header('Location: index.php');
	die();
}

if (BLOCK_OPERA_MINI && isset($_SERVER['HTTP_X_OPERAMINI_PHONE'])) {
	error('Opera Mini is banned. Please use another browser.');
}

// Check if IP is banned
if (Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
	error('Your IP address has been banned.');
}

require(SERVER_ROOT.'/classes/validate.class.php');
$Validate = NEW VALIDATE;

if (array_key_exists('action', $_GET) && $_GET['action'] == 'disabled') {
	require('disabled.php');
	die();
}

if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'recover') {
	// Recover password
	if (!empty($_REQUEST['key'])) {
		// User has entered a new password, use step 2

		$DB->query("
			SELECT
				m.ID,
				m.Email,
				m.ipcc,
				i.ResetExpires
			FROM users_main as m
				INNER JOIN users_info AS i ON i.UserID = m.ID
			WHERE i.ResetKey = '".db_string($_REQUEST['key'])."'
				AND i.ResetKey != ''
				AND m.Enabled = '1'");
		list($UserID, $Email, $Country, $Expires) = $DB->next_record();
		if ($UserID && strtotime($Expires) > time()) {

		// If the user has requested a password change, and his key has not expired
			$Validate->SetFields('password', '1', 'regex', 'You entered an invalid password. A strong password is 8 characters or longer, contains at least 1 lowercase and uppercase letter, contains at least a number or symbol', array('regex' => '/(?=^.{8,}$)(?=.*[^a-zA-Z])(?=.*[A-Z])(?=.*[a-z]).*$/'));
			$Validate->SetFields('verifypassword', '1', 'compare', 'Your passwords did not match.', array('comparefield' => 'password'));

			if (!empty($_REQUEST['password'])) {
				// If the user has entered a password.
				// If the user has not entered a password, $Reset is not set to 1, and the success message is not shown
				$Err = $Validate->ValidateForm($_REQUEST);
				if ($Err == '') {
					// Form validates without error, set new secret and password.
					$DB->query("
						UPDATE
							users_main AS m,
							users_info AS i
						SET
							m.PassHash = '".db_string(Users::make_crypt_hash($_REQUEST['password']))."',
							i.ResetKey = '',
							i.ResetExpires = '0000-00-00 00:00:00'
						WHERE m.ID = '$UserID'
							AND i.UserID = m.ID");
					$DB->query("
						INSERT INTO users_history_passwords
							(UserID, ChangerIP, ChangeTime)
						VALUES
							('$UserID', '$_SERVER[REMOTE_ADDR]', '".sqltime()."')");
					$Reset = true; // Past tense form of "to reset", meaning that password has now been reset


				}
			}

			// Either a form asking for them to enter the password
			// Or a success message if $Reset is 1
			require('recover_step2.php');

		} else {
			// Either his key has expired, or he hasn't requested a pass change at all

			if (strtotime($Expires) < time() && $UserID) {
				// If his key has expired, clear all the reset information
				$DB->query("
					UPDATE users_info
					SET ResetKey = '',
						ResetExpires = '0000-00-00 00:00:00'
					WHERE UserID = '$UserID'");
				$_SESSION['reseterr'] = 'The link you were given has expired.'; // Error message to display on form
			}
			// Show him the first form (enter email address)
			header('Location: login.php?act=recover');
		}

	} // End step 2

	// User has not clicked the link in his email, use step 1
	else {
		$Validate->SetFields('email', '1', 'email', 'You entered an invalid email address.');

		if (!empty($_REQUEST['email'])) {
			// User has entered email and submitted form
			$Err = $Validate->ValidateForm($_REQUEST);

			if (!$Err) {
				// Form validates correctly
				$DB->query("
					SELECT
						ID,
						Username,
						Email
					FROM users_main
					WHERE Email = '".db_string($_REQUEST['email'])."'
						AND Enabled = '1'");
				list($UserID, $Username, $Email) = $DB->next_record();

				if ($UserID) {
					// Email exists in the database
					// Set ResetKey, send out email, and set $Sent to 1 to show success page
					$ResetKey = Users::make_secret();
					$DB->query("
						UPDATE users_info
						SET
							ResetKey = '".db_string($ResetKey)."',
							ResetExpires = '".time_plus(60 * 60)."'
						WHERE UserID = '$UserID'");

					require(SERVER_ROOT.'/classes/templates.class.php');
					$TPL = NEW TEMPLATE;
					$TPL->open(SERVER_ROOT.'/templates/password_reset.tpl'); // Password reset template

					$TPL->set('Username', $Username);
					$TPL->set('ResetKey', $ResetKey);
					$TPL->set('IP', $_SERVER['REMOTE_ADDR']);
					$TPL->set('SITE_NAME', SITE_NAME);
					$TPL->set('SITE_URL', NONSSL_SITE_URL);

					Misc::send_email($Email, 'Password reset information for '.SITE_NAME, $TPL->get(),'noreply');
					$Sent = 1; // If $Sent is 1, recover_step1.php displays a success message

					//Log out all of the users current sessions
					$Cache->delete_value("user_info_$UserID");
					$Cache->delete_value("user_info_heavy_$UserID");
					$Cache->delete_value("user_stats_$UserID");
					$Cache->delete_value("enabled_$UserID");

					$DB->query("
						SELECT SessionID
						FROM users_sessions
						WHERE UserID = '$UserID'");
					while (list($SessionID) = $DB->next_record()) {
						$Cache->delete_value("session_$UserID"."_$SessionID");
					}
					$DB->query("
						UPDATE users_sessions
						SET Active = 0
						WHERE UserID = '$UserID'
							AND Active = 1");
				} else {
					$Err = 'There is no user with that email address.';
				}
			}

		} elseif (!empty($_SESSION['reseterr'])) {
			// User has not entered email address, and there is an error set in session data
			// This is typically because their key has expired.
			// Stick the error into $Err so recover_step1.php can take care of it
			$Err = $_SESSION['reseterr'];
			unset($_SESSION['reseterr']);
		}

		// Either a form for the user's email address, or a success message
		require('recover_step1.php');
	} // End if (step 1)

} // End password recovery

// Normal login
else {
	$Validate->SetFields('username', true, 'regex', 'You did not enter a valid username.', array('regex' => USERNAME_REGEX));
	$Validate->SetFields('password', '1', 'string', 'You entered an invalid password.', array('minlength' => '6', 'maxlength' => '150'));

	$DB->query("
		SELECT ID, Attempts, Bans, BannedUntil
		FROM login_attempts
		WHERE IP = '".db_string($_SERVER['REMOTE_ADDR'])."'");
	list($AttemptID, $Attempts, $Bans, $BannedUntil) = $DB->next_record();

	// Function to log a user's login attempt
	function log_attempt($UserID) {
		global $DB, $Cache, $AttemptID, $Attempts, $Bans, $BannedUntil;
		$IPStr = $_SERVER['REMOTE_ADDR'];
		$IPA = substr($IPStr, 0, strcspn($IPStr, '.'));
		$IP = Tools::ip_to_unsigned($IPStr);
		if ($AttemptID) { // User has attempted to log in recently
			$Attempts++;
			if ($Attempts > 5) { // Only 6 allowed login attempts, ban user's IP
				$BannedUntil = time_plus(60 * 60 * 6);
				$DB->query("
					UPDATE login_attempts
					SET
						LastAttempt = '".sqltime()."',
						Attempts = '".db_string($Attempts)."',
						BannedUntil = '".db_string($BannedUntil)."',
						Bans = Bans + 1
					WHERE ID = '".db_string($AttemptID)."'");

				if ($Bans > 9) { // Automated bruteforce prevention
					$DB->query("
						SELECT Reason
						FROM ip_bans
						WHERE $IP BETWEEN FromIP AND ToIP");
					if ($DB->has_results()) {
						//Ban exists already, only add new entry if not for same reason
						list($Reason) = $DB->next_record(MYSQLI_BOTH, false);
						if ($Reason != 'Automated ban per >60 failed login attempts') {
							$DB->query("
								UPDATE ip_bans
								SET Reason = CONCAT('Automated ban per >60 failed login attempts AND ', Reason)
								WHERE FromIP = $IP
									AND ToIP = $IP");
						}
					} else {
						//No ban
						$DB->query("
							INSERT IGNORE INTO ip_bans
								(FromIP, ToIP, Reason)
							VALUES
								('$IP','$IP', 'Automated ban per >60 failed login attempts')");
						$Cache->delete_value("ip_bans_$IPA");
					}
				}
			} else {
				// User has attempted fewer than 6 logins
				$DB->query("
					UPDATE login_attempts
					SET
						LastAttempt = '".sqltime()."',
						Attempts = '".db_string($Attempts)."',
						BannedUntil = '0000-00-00 00:00:00'
					WHERE ID = '".db_string($AttemptID)."'");
			}
		} else { // User has not attempted to log in recently
			$Attempts = 1;
			$DB->query("
				INSERT INTO login_attempts
					(UserID, IP, LastAttempt, Attempts)
				VALUES
					('".db_string($UserID)."', '".db_string($IPStr)."', '".sqltime()."', 1)");
		}
	} // end log_attempt function

	// If user has submitted form
	if (isset($_POST['username']) && !empty($_POST['username']) && isset($_POST['password']) && !empty($_POST['password'])) {
		if (strtotime($BannedUntil) > time()) {
			header("Location: login.php");
			die();
		}
		$Err = $Validate->ValidateForm($_POST);

		if (!$Err) {
			// Passes preliminary validation (username and password "look right")
			$DB->query("
				SELECT
					ID,
					PermissionID,
					CustomPermissions,
					PassHash,
					Secret,
					Enabled
				FROM users_main
				WHERE Username = '".db_string($_POST['username'])."'
					AND Username != ''");
			list($UserID, $PermissionID, $CustomPermissions, $PassHash, $Secret, $Enabled) = $DB->next_record(MYSQLI_NUM, array(2));
			if (strtotime($BannedUntil) < time()) {
				if ($UserID && Users::check_password($_POST['password'], $PassHash, $Secret)) {
					if (!Users::is_crypt_hash($PassHash)) {
						$CryptHash = Users::make_crypt_hash($_POST['password']);
						$DB->query("
							UPDATE users_main
							SET passhash = '".db_string($CryptHash)."'
							WHERE ID = $UserID");
					}
					if ($Enabled == 1) {
						$SessionID = Users::make_secret();
						$Cookie = $Enc->encrypt($Enc->encrypt($SessionID.'|~|'.$UserID));

						if (isset($_POST['keeplogged']) && $_POST['keeplogged']) {
							$KeepLogged = 1;
							setcookie('session', $Cookie, time() + 60 * 60 * 24 * 365, '/', '', $SSL, true);
						} else {
							$KeepLogged = 0;
							setcookie('session', $Cookie, 0, '/', '', $SSL, true);
						}

						//TODO: another tracker might enable this for donors, I think it's too stupid to bother adding that
						// Because we <3 our staff
						$Permissions = Permissions::get_permissions($PermissionID);
						$CustomPermissions = unserialize($CustomPermissions);
						if (isset($Permissions['Permissions']['site_disable_ip_history'])
							|| isset($CustomPermissions['site_disable_ip_history'])
						) {
							$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
						}

						$DB->query("
							INSERT INTO users_sessions
								(UserID, SessionID, KeepLogged, Browser, OperatingSystem, IP, LastUpdate, FullUA)
							VALUES
								('$UserID', '".db_string($SessionID)."', '$KeepLogged', '$Browser', '$OperatingSystem', '".db_string($_SERVER['REMOTE_ADDR'])."', '".sqltime()."', '".db_string($_SERVER['HTTP_USER_AGENT'])."')");

						$Cache->begin_transaction("users_sessions_$UserID");
						$Cache->insert_front($SessionID, array(
								'SessionID' => $SessionID,
								'Browser' => $Browser,
								'OperatingSystem' => $OperatingSystem,
								'IP' => $_SERVER['REMOTE_ADDR'],
								'LastUpdate' => sqltime()
								));
						$Cache->commit_transaction(0);

						$Sql = "
							UPDATE users_main
							SET
								LastLogin = '".sqltime()."',
								LastAccess = '".sqltime()."'
							WHERE ID = '".db_string($UserID)."'";

						$DB->query($Sql);

						if (!empty($_COOKIE['redirect'])) {
							$URL = $_COOKIE['redirect'];
							setcookie('redirect', '', time() - 60 * 60 * 24, '/', '', false);
							header("Location: $URL");
							die();
						} else {
							header('Location: index.php');
							die();
						}
					} else {
						log_attempt($UserID);
						if ($Enabled == 2) {

							header('location:login.php?action=disabled');
						} elseif ($Enabled == 0) {
							$Err = 'Your account has not been confirmed.<br />Please check your email.';
						}
						setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
					}
				} else {
					log_attempt($UserID);

					$Err = 'Your username or password was incorrect.';
					setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
				}

			} else {
				log_attempt($UserID);
				setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
			}

		} else {
			log_attempt('0');
			setcookie('keeplogged', '', time() + 60 * 60 * 24 * 365, '/', '', false);
		}
	}
	require('sections/login/login.php');
}
