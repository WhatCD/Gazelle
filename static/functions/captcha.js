function refreshCaptcha() {
	var time = new Date();
	$('#captcha_img').src='captcha.php?t='+time.getTime();
}
