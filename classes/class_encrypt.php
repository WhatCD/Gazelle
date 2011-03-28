<?
/*************************************************************************|
|--------------- Encryption class ----------------------------------------|
|*************************************************************************|

This class handles encryption and decryption, that's all folks.

|*************************************************************************/

if (!extension_loaded('mcrypt')) {
	error('Mcrypt Extension not loaded.');
}

class CRYPT {
	public function encrypt($Str,$Key=ENCKEY) {
		srand();
		$Str=str_pad($Str, 32-strlen($Str));
		$IVSize=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$IV=mcrypt_create_iv($IVSize, MCRYPT_RAND);
		$CryptStr=mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $Key, $Str, MCRYPT_MODE_CBC, $IV);
		return base64_encode($IV.$CryptStr);
	}

	public function decrypt($CryptStr,$Key=ENCKEY) {
		if ($CryptStr!='') {
			$IV=substr(base64_decode($CryptStr),0,16);
			$CryptStr=substr(base64_decode($CryptStr),16);
			return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $Key, $CryptStr, MCRYPT_MODE_CBC,$IV));
		} else {
			return '';
		}
	}
} // class ENCRYPT()
?>
