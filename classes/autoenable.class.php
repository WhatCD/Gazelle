<?

class AutoEnable {

    // Constants for database values
    const APPROVED = 1;
    const DENIED = 2;
    const DISCARDED = 3;

    // Cache key to store the number of enable requests
    const CACHE_KEY_NAME = 'num_enable_requests';

    // The default request rejected message
    const REJECTED_MESSAGE = "Your request to re-enable your account has been rejected.<br />This may be because a request is already pending for your username, or because a recent request was denied.<br /><br />You are encouraged to discuss this with staff by visiting %s on %s";

    // The default request received message
    const RECEIVED_MESSAGE = "Your request to re-enable your account has been received. You can expect a reply message in your email within 48 hours.<br />If you do not receive an email after 48 hours have passed, please visit us on IRC for assistance.";

    /**
     * Handle a new enable request
     *
     * @param string $Username The user's username
     * @param string $Email The user's email address
     * @return string The output
     */
    public static function new_request($Username, $Email) {
        if (empty($Username)) {
            header("Location: login.php");
            die();
        }

        // Get the user's ID
        G::$DB->query("
                SELECT um.ID
                FROM users_main AS um
                JOIN users_info ui ON ui.UserID = um.ID
                WHERE um.Username = '$Username'
                  AND um.Enabled = '2'");

        if (G::$DB->has_results()) {
            // Make sure the user can make another request
            list($UserID) = G::$DB->next_record();
            G::$DB->query("
            SELECT 1 FROM users_enable_requests
            WHERE UserID = '$UserID'
              AND (
                    (
                      Timestamp > NOW() - INTERVAL 1 WEEK
                        AND HandledTimestamp IS NULL
                    )
                    OR
                    (
                      Timestamp > NOW() - INTERVAL 2 MONTH
                        AND
                          (Outcome = '".self::DENIED."'
                             OR Outcome = '".self::DISCARDED."')
                    )
                  )");
        }

        $IP = $_SERVER['REMOTE_ADDR'];

        if (G::$DB->has_results() || !isset($UserID)) {
            // User already has/had a pending activation request or username is invalid
            $Output = sprintf(self::REJECTED_MESSAGE, BOT_DISABLED_CHAN, BOT_SERVER);
            if (isset($UserID)) {
                Tools::update_user_notes($UserID, sqltime() . " - Enable request rejected from $IP\n\n");
            }
        } else {
            // New disable activation request
            $UserAgent = db_string($_SERVER['HTTP_USER_AGENT']);

            G::$DB->query("
                INSERT INTO users_enable_requests
                (UserID, Email, IP, UserAgent, Timestamp)
                VALUES ('$UserID', '$Email', '$IP', '$UserAgent', '".sqltime()."')");

            // Cache the number of requests for the modbar
            G::$Cache->increment_value(self::CACHE_KEY_NAME);
            setcookie('username', '', time() - 60 * 60, '/', '', false);
            $Output = self::RECEIVED_MESSAGE;
            Tools::update_user_notes($UserID, sqltime() . " - Enable request " . G::$DB->inserted_id() . " received from $IP\n\n");
        }

        return $Output;
    }

    /*
     * Handle requests
     *
     * @param int|int[] $IDs An array of IDs, or a single ID
     * @param int $Status The status to mark the requests as
     * @param string $Comment The staff member comment
     */
    public static function handle_requests($IDs, $Status, $Comment) {
        if ($Status != self::APPROVED && $Status != self::DENIED && $Status != self::DISCARDED) {
            error(404);
        }

        $UserInfo = array();
        $IDs = (!is_array($IDs)) ? [$IDs] : $IDs;

        if (count($IDs) == 0) {
            error(404);
        }

        foreach ($IDs as $ID) {
            if (!is_number($ID)) {
                error(404);
            }
        }

        G::$DB->query("SELECT Email, ID, UserID
                FROM users_enable_requests
                WHERE ID IN (".implode(',', $IDs).")
                    AND Outcome IS NULL");
        $Results = G::$DB->to_array(false, MYSQLI_NUM);

        if ($Status != self::DISCARDED) {
            // Prepare email
            require(SERVER_ROOT . '/classes/templates.class.php');
            $TPL = NEW TEMPLATE;
            if ($Status == self::APPROVED) {
                $TPL->open(SERVER_ROOT . '/templates/enable_request_accepted.tpl');
                $TPL->set('SITE_URL', NONSSL_SITE_URL);
            } else {
                $TPL->open(SERVER_ROOT . '/templates/enable_request_denied.tpl');
            }

            $TPL->set('SITE_NAME', SITE_NAME);

            foreach ($Results as $Result) {
                list($Email, $ID, $UserID) = $Result;
                $UserInfo[] = array($ID, $UserID);

                if ($Status == self::APPROVED) {
                    // Generate token
                    $Token = db_string(Users::make_secret());
                    G::$DB->query("
                        UPDATE users_enable_requests
                        SET Token = '$Token'
                        WHERE ID = '$ID'");
                    $TPL->set('TOKEN', $Token);
                }

                // Send email
                $Subject = "Your enable request for " . SITE_NAME . " has been ";
                $Subject .= ($Status == self::APPROVED) ? 'approved' : 'denied';

                Misc::send_email($Email, $Subject, $TPL->get(), 'noreply');
            }
        } else {
            foreach ($Results as $Result) {
                list(, $ID, $UserID) = $Result;
                $UserInfo[] = array($ID, $UserID);
            }
        }

        // User notes stuff
        G::$DB->query("
            SELECT Username
            FROM users_main
            WHERE ID = '" . G::$LoggedUser['ID'] . "'");
        list($StaffUser) = G::$DB->next_record();

        foreach ($UserInfo as $User) {
            list($ID, $UserID) = $User;
            $BaseComment = sqltime() . " - Enable request $ID " . strtolower(self::get_outcome_string($Status)) . ' by [user]'.$StaffUser.'[/user]';
            $BaseComment .= (!empty($Comment)) ? "\nReason: $Comment\n\n" : "\n\n";
            Tools::update_user_notes($UserID, $BaseComment);
        }

        // Update database values and decrement cache
        G::$DB->query("
                UPDATE users_enable_requests
                SET HandledTimestamp = '".sqltime()."',
                    CheckedBy = '".G::$LoggedUser['ID']."',
                    Outcome = '$Status'
                WHERE ID IN (".implode(',', $IDs).")");
        G::$Cache->decrement_value(self::CACHE_KEY_NAME, count($IDs));
    }

    /**
     * Unresolve a discarded request
     *
     * @param int $ID The request ID
     */
    public static function unresolve_request($ID) {
        $ID = (int) $ID;

        if (empty($ID)) {
            error(404);
        }

        G::$DB->query("
            SELECT UserID
            FROM users_enable_requests
            WHERE Outcome = '" . self::DISCARDED . "'
              AND ID = '$ID'");

        if (!G::$DB->has_results()) {
            error(404);
        } else {
            list($UserID) = G::$DB->next_record();
        }

        G::$DB->query("
            SELECT Username
            FROM users_main
            WHERE ID = '" . G::$LoggedUser['ID'] . "'");
        list($StaffUser) = G::$DB->next_record();

        Tools::update_user_notes($UserID, sqltime() . " - Enable request $ID unresolved by [user]" . $StaffUser . '[/user]' . "\n\n");
        G::$DB->query("
            UPDATE users_enable_requests
            SET Outcome = NULL, HandledTimestamp = NULL, CheckedBy = NULL
            WHERE ID = '$ID'");
        G::$Cache->increment_value(self::CACHE_KEY_NAME);
    }

    /**
     * Get the corresponding outcome string for a numerical value
     *
     * @param int $Outcome The outcome integer
     * @return string The formatted output string
     */
    public static function get_outcome_string($Outcome) {
        if ($Outcome == self::APPROVED) {
            $String = "Approved";
        } else if ($Outcome == self::DENIED) {
            $String = "Rejected";
        } else if ($Outcome == self::DISCARDED) {
            $String = "Discarded";
        } else {
            $String = "---";
        }

        return $String;
    }

    /**
     * Handle a user's request to enable an account
     *
     * @param string $Token The token
     * @return string The error output, or an empty string
     */
    public static function handle_token($Token) {
        $Token = db_string($Token);
        G::$DB->query("
            SELECT UserID, HandledTimestamp
            FROM users_enable_requests
            WHERE Token = '$Token'");

        if (G::$DB->has_results()) {
            list($UserID, $Timestamp) = G::$DB->next_record();
            G::$DB->query("UPDATE users_enable_requests SET Token = NULL WHERE Token = '$Token'");
            if ($Timestamp < time_minus(3600 * 48)) {
                // Old request
                Tools::update_user_notes($UserID, sqltime() . " - Tried to use an expired enable token from ".$_SERVER['REMOTE_ADDR']."\n\n");
                $Err = "Token has expired. Please visit ".BOT_DISABLED_CHAN." on ".BOT_SERVER." to discuss this with staff.";
            } else {
                // Good request, decrement cache value and enable account
                G::$Cache->decrement_value(AutoEnable::CACHE_KEY_NAME);
                G::$DB->query("UPDATE users_main SET Enabled = '1' WHERE ID = '$UserID'");
		G::$DB->query("UPDATE users_info SET BanReason = '0' WHERE UserID = '$UserID'");
                $Err = "Your account has been enabled. You may now log in.";
            }
        } else {
            $Err = "Invalid token.";
        }

        return $Err;
    }

    /**
     * Build the search query, from the searchbox inputs
     *
     * @param int $UserID The user ID
     * @param string $IP The IP
     * @param string $SubmittedTimestamp The timestamp representing when the request was submitted
     * @param int $HandledUserID The ID of the user that handled the request
     * @param string $HandledTimestamp The timestamp representing when the request was handled
     * @param int $OutcomeSearch The outcome of the request
     * @param boolean $Checked Should checked requests be included?
     * @return array The WHERE conditions for the query
     */
    public static function build_search_query($Username, $IP, $SubmittedBetween, $SubmittedTimestamp1, $SubmittedTimestamp2, $HandledUsername, $HandledBetween, $HandledTimestamp1, $HandledTimestamp2, $OutcomeSearch, $Checked) {
        $Where = array();

        if (!empty($Username)) {
            $Where[] = "um1.Username = '$Username'";
        }

        if (!empty($IP)) {
            $Where[] = "uer.IP = '$IP'";
        }

        if (!empty($SubmittedTimestamp1)) {
            switch($SubmittedBetween) {
                case 'on':
                    $Where[] = "DATE(uer.Timestamp) = DATE('$SubmittedTimestamp1')";
                    break;
                case 'before':
                    $Where[] = "DATE(uer.Timestamp) < DATE('$SubmittedTimestamp1')";
                    break;
                case 'after':
                    $Where[] = "DATE(uer.Timestamp) > DATE('$SubmittedTimestamp1')";
                    break;
                case 'between':
                    if (!empty($SubmittedTimestamp2)) {
                        $Where[] = "DATE(uer.Timestamp) BETWEEN DATE('$SubmittedTimestamp1') AND DATE('$SubmittedTimestamp2')";
                    }
                    break;
                default:
                    break;
            }
        }

        if (!empty($HandledTimestamp1)) {
            switch($HandledBetween) {
                case 'on':
                    $Where[] = "DATE(uer.HandledTimestamp) = DATE('$HandledTimestamp1')";
                    break;
                case 'before':
                    $Where[] = "DATE(uer.HandledTimestamp) < DATE('$HandledTimestamp1')";
                    break;
                case 'after':
                    $Where[] = "DATE(uer.HandledTimestamp) > DATE('$HandledTimestamp1')";
                    break;
                case 'between':
                    if (!empty($HandledTimestamp2)) {
                        $Where[] = "DATE(uer.HandledTimestamp) BETWEEN DATE('$HandledTimestamp1') AND DATE('$HandledTimestamp2')";
                    }
                    break;
                default:
                    break;
            }
        }

        if (!empty($HandledUsername)) {
            $Where[] = "um2.Username = '$HandledUsername'";
        }

        if (!empty($OutcomeSearch)) {
            $Where[] = "uer.Outcome = '$OutcomeSearch'";
        }

        if ($Checked) {
            // This is to skip the if statement in enable_requests.php
            $Where[] = "(uer.Outcome IS NULL OR uer.Outcome IS NOT NULL)";
        }

        return $Where;
    }
}
