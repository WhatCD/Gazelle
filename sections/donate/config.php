<?

define('PAYPAL_ADDRESS','');

define('PAYPAL_CURRENCY','EUR');
define('PAYPAL_SYMBOL','&#8364;');
define('PAYPAL_MINIMUM',5);

function btc_received() {

}

function btc_balance() {

}

// This will be rarely called, so let's go directly to the database
function btc_address($UserID, $GenAddress = false) {
	global $DB;
	$UserID = (int)$UserID;
	$DB->query("
		SELECT BitcoinAddress
		FROM users_info
		WHERE UserID = '$UserID'");
	list($Addr) = $DB->next_record();

	if (!empty($Addr)) {
		return $Addr;
	} elseif ($GenAddress) {
		if (empty($NewAddr)) {
			error(0);
		}
		$DB->query("
			UPDATE users_info
			SET BitcoinAddress = '".db_string($NewAddr)."'
			WHERE UserID = '$UserID'
				AND BitcoinAddress IS NULL");
		return $NewAddr;
	} else {
		return false;
	}
}
?>
