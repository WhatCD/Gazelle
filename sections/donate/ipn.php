<?
// Paypal hits this page once a donation has gone through.
// This may appear to be light on the input validation, but the vast majority of that is handled through paypal confirmation
// $_POST['txn_id'] centains the unique identifier if anyone ever needs it
if (!is_number($_POST['custom'])) {
	die(); //Seems too stupid a mistake to bother banning
}

// Create request to return to paypal
$Request = 'cmd=_notify-validate';
foreach ($_POST as $Key => $Value) {
	$Value = urlencode(stripslashes($Value));
	$Request .= "&$Key=$Value";
}

// Headers
$Headers = "POST /cgi-bin/webscr HTTP/1.1\r\n";
$Headers .= "Host: www.paypal.com\r\n";
$Headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
$Headers .= "Content-Length: ".strlen($Request)."\r\n";
$Headers .= "Connection: close\r\n\r\n";

// Socket
$Socket = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);

// Send and process reply
fwrite ($Socket, $Headers.$Request);
$Result = '';
while (!feof($Socket)) {
	$Result .= fgets ($Socket, 1024);
}

if (strpos($Result, 'VERIFIED') !== false || check_perms('site_debug')) {
	if ($_POST['mc_gross'] >= PAYPAL_MINIMUM) {
		if ($_POST['mc_currency'] == PAYPAL_CURRENCY) {
			if ($_POST['business'] == PAYPAL_ADDRESS) {
				if (($_POST['payment_status'] == 'Completed') || ($_POST['payment_status'] == 'Pending')) {
					$DB->query('
						SELECT Donor
						FROM users_info
						WHERE UserID = \''.$_POST['custom'].'\'');
					list($Donor) = $DB->next_record();
					if ($Donor == 0) {
						//First time donor
						$DB->query('
							UPDATE users_main
							SET Invites = Invites + \''.DONOR_INVITES.'\'
							WHERE ID = \''.$_POST['custom'].'\'');
						$DB->query('
							UPDATE users_info
							SET Donor = \'1\'
							WHERE UserID = \''.$_POST['custom'].'\'');
						$DB->query('
							SELECT Invites
							FROM users_main
							WHERE ID = \''.$_POST['custom'].'\'');
						list($Invites) = $DB->next_record();
						$Cache->begin_transaction('user_info_'.$_POST['custom']);
						$Cache->update_row(false, array('Donor' => 1));
						$Cache->commit_transaction(0);
						$Cache->begin_transaction('user_info_heavy_'.$_POST['custom']);
						$Cache->update_row(false, array('Invites' => $Invites));
						$Cache->commit_transaction(0);
						Misc::send_pm($_POST['custom'], 0, 'Thank you for your donation', 'Your donation from '.$_POST['payer_email'].' of '.$_POST['mc_gross'].' '.PAYPAL_CURRENCY.' has been successfully processed. Because this is your first time donating, you have now been awarded Donor status as represented by the <3 found on your profile and next to your username where it appears. This has entitled you to a additional site features which you can now explore, and has granted you '.DONOR_INVITES.' invitations to share with others. Thank you for supporting '.SITE_NAME.'.');
					} else {
						//Repeat donor
						Misc::send_pm($_POST['custom'], 0, 'Thank you for your donation', 'Your donation from '.$_POST['payer_email'].' of '.$_POST['mc_gross'].' '.PAYPAL_CURRENCY.' has been successfully processed. Your continued support is highly appreciated and helps to make this place possible.');
					}


				}
			}
		}
	} else {
		if ($_POST['mc_gross'] > 0) {
			//Donation less than minimum
			Misc::send_pm($_POST['custom'], 0, 'Thank you for your donation', 'Your donation from '.$_POST['payer_email'].' of '.$_POST['mc_gross'].' '.PAYPAL_CURRENCY.' has been successfully processed. Unfortunately however this donation was less than the specified minimum donation of '.PAYPAL_MINIMUM.' '.PAYPAL_CURRENCY.' and while we are grateful, no special privileges have been awarded to you.');
		} else {
			//Failed pending donation
			$Message = "User ".site_url()."user.php?id=".$_POST['custom']." had donation of $TotalDonated ".PAYPAL_CURRENCY." at $DonationTime UTC from ".$_POST['payer_email'].' returned.';
			$DB->query('
				SELECT SUM(Amount), MIN(Time)
				FROM donations
				WHERE UserID = \''.$_POST['custom'].'\';');
			list($TotalDonated, $DonationTime) = $DB->next_record();
			if ($TotalDonated + $_POST['mc_gross'] == 0) {
				$DB->query("
					SELECT Invites
					FROM users_main
					WHERE ID = '".$_POST['custom']."'");
				list($Invites) = $DB->next_record();
				if (($Invites - DONOR_INVITES) >= 0) {
					$NewInvites = $Invites - DONOR_INVITES;
				} else {
					$NewInvites = 0;
					$Message .= ' They had already used at least one of their donation gained invites.';
				}
				$DB->query("
					UPDATE users_main
					SET Invites = $NewInvites
					WHERE ID = '".$_POST['custom']."'");
				$DB->query('
					UPDATE users_info
					SET Donor = \'0\'
					WHERE UserID = \''.$_POST['custom'].'\'');
				$Cache->begin_transaction('user_info_'.$_POST['custom']);
				$Cache->update_row(false, array('Donor' => 0));
				$Cache->commit_transaction(0);
				$Cache->begin_transaction('user_info_heavy_'.$_POST['custom']);
				$Cache->update_row(false, array('Invites' => $Invites));
				$Cache->commit_transaction(0);
				Misc::send_pm($_POST['custom'], 0, 'Notice of donation failure', 'PapPal has just notified us that the donation you sent from '.$_POST['payer_email'].' of '.$TotalDonated.' '.PAYPAL_CURRENCY.' at '.$DonationTime.' UTC has been revoked. Because of this your special privileges have been revoked, and your invites removed.');


				send_irc("PRIVMSG ".BOT_REPORT_CHAN." :$Message");
			}
		}
	}
	$DB->query("
		UPDATE users_info
		SET AdminComment = CONCAT('".sqltime()." - User donated ".db_string($_POST['mc_gross'])." ".db_string(PAYPAL_CURRENCY)." from ".db_string($_POST['payer_email']).".\n',AdminComment)
		WHERE UserID = '".$_POST['custom']."'");
	$DB->query("
		INSERT INTO donations
			(UserID, Amount, Email, Time)
		VALUES
			('".$_POST['custom']."', '".db_string($_POST['mc_gross'])."', '".db_string($_POST['payer_email'])."', '".sqltime()."')");
} else {
	$DB->query("
		INSERT INTO ip_bans
			(FromIP, ToIP, Reason)
		VALUES
			('".Tools::ip_to_unsigned($_SERVER['REMOTE_ADDR'])."', '".ip2long($_SERVER['REMOTE_ADDR'])."', 'Attempted to exploit donation system.')");
}
fclose ($Socket);
if (check_perms('site_debug')) {
	include(SERVER_ROOT.'/sections/donate/donate.php');
}
$Cache->cache_value('debug_donate', array($Result, $_POST), 0);
?>
