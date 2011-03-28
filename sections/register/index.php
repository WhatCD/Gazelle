<?

if(isset($LoggedUser)) {
	//Silly user, what are you doing here!
	header('Location: index.php');
	die();
}

include(SERVER_ROOT.'/classes/class_validate.php');
$Val=NEW VALIDATE;

if(!empty($_REQUEST['confirm'])) {
	// Confirm registration
	$DB->query("SELECT ID FROM users_main WHERE torrent_pass='".db_string($_REQUEST['confirm'])."' AND Enabled='0'");
	list($UserID)=$DB->next_record();
	
	if($UserID) {
		$DB->query("UPDATE users_main SET Enabled='1' WHERE ID='$UserID'");
		$Cache->increment('stats_user_count');
		include('step2.php');
	}
	
} elseif(OPEN_REGISTRATION || !empty($_REQUEST['invite'])) {
	$Val->SetFields('username',true,'regex', 'You did not enter a valid username.',array('regex'=>'/^[a-z0-9_?]{1,20}$/iD'));
	$Val->SetFields('email',true,'email', 'You did not enter a valid email address.');
	$Val->SetFields('password',true,'string', 'You did not enter a valid password (6 - 40 characters).',array('minlength'=>6,'maxlength'=>40));
	$Val->SetFields('confirm_password',true,'compare', 'Your passwords do not match.',array('comparefield'=>'password'));
	$Val->SetFields('readrules',true,'checkbox', 'You did not check the box that says you will read the rules.');
	$Val->SetFields('readwiki',true,'checkbox', 'You did not check the box that says you will read the wiki.');
	$Val->SetFields('agereq',true,'checkbox', 'You did not check the box that says you are 13 or older.');
	//$Val->SetFields('captcha',true,'string', 'You did not enter a captcha code.',array('minlength'=>6,'maxlength'=>6));

	if(!empty($_POST['submit'])) {
		// User has submitted registration form
		$Err=$Val->ValidateForm($_REQUEST);
		/*
		if(!$Err && strtolower($_SESSION['captcha'])!=strtolower($_REQUEST['captcha'])) {
			$Err="You did not enter the correct captcha code."; 
		}
		*/
		if(!$Err) {
			
			$DB->query("SELECT COUNT(ID) FROM users_main WHERE Username LIKE '".db_string(trim($_POST['username']))."'");
			list($UserCount)=$DB->next_record();
			
			if($UserCount) {
				$Err = "There is already someone registered with that username.";
				$_REQUEST['username']='';
			}
			
			if($_POST['invite']) {
				$DB->query("SELECT InviterID, Email FROM invites WHERE InviteKey='".db_string($_REQUEST['invite'])."'");
				if($DB->record_count() == 0) {
					$Err = 'Invite does not exist.';
					$InviterID=0;
				} else {
					list($InviterID, $InviteEmail) = $DB->next_record();
				}
			} else {
				$InviterID=0;
			}
		}
		
		if(!$Err) {
			$Secret=make_secret();
			$torrent_pass=make_secret();
			
			//Previously SELECT COUNT(ID) FROM users_main, which is a lot slower.
			$DB->query("SELECT ID FROM users_main LIMIT 1");
			$UserCount = $DB->record_count();
			if($UserCount == 0) {
				$NewInstall = true;
				$Class = SYSOP;
				$Enabled = '1';
			} else {
				$NewInstall = false;
				$Class = USER;
				$Enabled = '0';
			}

			
			
			$DB->query("INSERT INTO users_main 
				(Username,Email,PassHash,Secret,torrent_pass,IP,PermissionID,Enabled,Invites,Uploaded) VALUES
				('".db_string(trim($_POST['username']))."','".db_string($_POST['email'])."','".db_string(make_hash($_POST['password'],$Secret))."','".db_string($Secret)."','".db_string($torrent_pass)."','".db_string($_SERVER['REMOTE_ADDR'])."','".$Class."','".$Enabled."','".STARTING_INVITES."', '524288000')");
			
			
			$UserID = $DB->inserted_id();
			$DB->query("SELECT Code FROM geoip_country WHERE '".ip2long($_SERVER['REMOTE_ADDR'])."' BETWEEN StartIP AND EndIP");
			list($ipcc) = $DB->next_record();
			$DB->query("UPDATE users_main SET ipcc = '".$ipcc."' WHERE ID = '$UserID'");
			
			//User created, delete invite. If things break after this point then it's better to have a broken account to fix, or a 'free' invite floating around that can be reused
			$DB->query("DELETE FROM invites WHERE InviteKey='".db_string($_REQUEST['invite'])."'");

			$DB->query("SELECT ID FROM stylesheets WHERE `Default`='1'");
			list($StyleID) = $DB->next_record();
			update_tracker('add_user', array('id' => $UserID, 'passkey' => $torrent_pass));
			$AuthKey = make_secret();
			
			$DB->query("INSERT INTO users_info (UserID,StyleID,AuthKey, Inviter, JoinDate) VALUES ('$UserID','$StyleID','".db_string($AuthKey)."', '$InviterID', '".sqltime()."')");
			
			$DB->query("INSERT INTO users_history_ips
					(UserID, IP, StartTime) VALUES
					('$UserID', '".db_string($_SERVER['REMOTE_ADDR'])."', '".sqltime()."')");
			
			
			
			
			$DB->query("INSERT INTO users_history_emails
				(UserID, Email, Time, IP) VALUES 
				('$UserID', '".db_string($_REQUEST['email'])."', '0000-00-00 00:00:00', '".db_string($_SERVER['REMOTE_ADDR'])."')");
				
			if ($_REQUEST['email'] != $InviteEmail) {
				$DB->query("INSERT INTO users_history_emails
					(UserID, Email, Time, IP) VALUES 
					('$UserID', '$InviteEmail', '".sqltime()."', '".db_string($_SERVER['REMOTE_ADDR'])."')");
			}
			
			
			
			// Manage invite trees, delete invite
			
			if($InviterID !== NULL) {
				$DB->query("SELECT 
					TreePosition, TreeID, TreeLevel 
					FROM invite_tree WHERE UserID='$InviterID'");
				list($InviterTreePosition, $TreeID, $TreeLevel) = $DB->next_record();
				
				// If the inviter doesn't have an invite tree
				// Note - this should never happen unless you've transfered from another db, like we have
				if($DB->record_count() == 0) {
					$DB->query("SELECT MAX(TreeID)+1 FROM invite_tree");
					list($TreeID) = $DB->next_record();
					
					$DB->query("INSERT INTO invite_tree
						(UserID, InviterID, TreePosition, TreeID, TreeLevel)
						VALUES ('$InviterID', '0', '1', '$TreeID', '1')");
					
					$TreePosition = 2;
					$TreeLevel = 2;
				} else {
					$DB->query("SELECT 
						TreePosition 
						FROM invite_tree 
						WHERE TreePosition>'$InviterTreePosition'
						AND TreeLevel<='$TreeLevel' 
						AND TreeID='$TreeID'
						ORDER BY TreePosition 
						LIMIT 1");
					list($TreePosition) = $DB->next_record();
					
					if($TreePosition) {
						$DB->query("UPDATE invite_tree SET TreePosition=TreePosition+1 WHERE TreeID='$TreeID' AND TreePosition>='$TreePosition'");
					} else {
						$DB->query("SELECT 
							TreePosition+1 
							FROM invite_tree 
							WHERE TreeID='$TreeID'
							ORDER BY TreePosition DESC 
							LIMIT 1");
						list($TreePosition) = $DB->next_record();
					}
					$TreeLevel++;
					
					// Create invite tree record
					$DB->query("INSERT INTO invite_tree 
						(UserID, InviterID, TreePosition, TreeID, TreeLevel) VALUES
						('$UserID', '$InviterID', '$TreePosition', '$TreeID', '$TreeLevel')");
				}
			} else { // No inviter (open registration)
				$DB->query("SELECT MAX(TreeID) FROM invite_tree");
				list($TreeID) = $DB->next_record();
				$TreeID++;
				$InviterID = 0;
				$TreePosition=1;
				$TreeLevel=1;
			}
	
			
			include(SERVER_ROOT.'/classes/class_templates.php');
			$TPL=NEW TEMPLATE;
			$TPL->open(SERVER_ROOT.'/templates/new_registration.tpl');
			
			$TPL->set('Username',$_REQUEST['username']);
			$TPL->set('TorrentKey',$torrent_pass);
			$TPL->set('SITE_NAME',SITE_NAME);
			$TPL->set('SITE_URL',SITE_URL);

			send_email($_REQUEST['email'],'New account confirmation at '.SITE_NAME,$TPL->get(),'noreply');
			$Sent=1;
		}
		
	} elseif($_GET['invite']) {
		// If they haven't submitted the form, check to see if their invite is good
		$DB->query("SELECT InviteKey FROM invites WHERE InviteKey='".db_string($_GET['invite'])."'");
		if($DB->record_count() == 0){
			error('Invite not found!');
		}
	}
	
	include('step1.php');
	
} elseif(!OPEN_REGISTRATION) {
	if (isset($_GET['welcome'])) {
		include('code.php');
	} else {
		include('closed.php');
	}
}
?>
