/**
*
* Validates passwords to make sure they are powerful
**/

(function() {
var CLEAR = 0;
var WEAK = 1;
var STRONG = 3;
var SHORT = 4;
var MATCH_IRCKEY = 5;
var MATCH_USERNAME = 6;
var COMMON = 7;

var USER_PATH = "/user.php";

$(document).ready(function() {

var old = $("#new_pass_1").val().length;
var password1;
var password2;

$("#new_pass_1").keyup(function() {
	password1 = $("#new_pass_1").val();
	if (password1.length != old) {
		disableSubmit();
		calculateComplexity(password1);
		old = password1.length;
	}

});

$("#new_pass_1").change(function() {
	password1 = $("#new_pass_1").val();
	password2 = $("#new_pass_2").val();

	if (password1.length == 0 && password2.length == 0) {
		enableSubmit();
	} else if (getStrong() == true) {
		validatePassword(password1);
	}

});

$("#new_pass_1").focus(function() {
	password1 = $("#new_pass_1").val();
	password2 = $("#new_pass_2").val();
	if (password1.length > 0) {
		checkMatching(password1, password2);
	}
});

$("#new_pass_2").keyup(function() {
	password2 = $("#new_pass_2").val();
	checkMatching(password1, password2);
});

$("#new_pass_1").blur(function() {
	password1 = $("#new_pass_1").val();
	password2 = $("#new_pass_2").val();
	if (password1.length == 0 && password2.length == 0) {
		enableSubmit();
	}
});

});

function validatePassword(password) {
	if (isUserPage()) {
	 $.ajax({
		type: 'POST',
		dataType: 'text',
		url : 'ajax.php?action=password_validate',
		data: 'password=' + password,
		async: false,
		success: function(value) {
			if (value == 'false') {
				setStatus(COMMON);
			}
		}
		});
	}
}

function calculateComplexity(password) {
	var length = password.length;
	var username;

	if (isUserPage()) {
		username = $(".username").text();
	}
	else {
		username = $("#username").val() || '';
	}

	var irckey;

	if (isUserPage()) {
		irckey = $("#irckey").val();
	}

	if (length >= 8) {
		setStatus(WEAK);
	}
	if (length >= 8 && isStrongPassword(password)) {
		setStatus(STRONG);
	}
	if (length > 0 && length < 8) {
		setStatus(SHORT);
	}
	if (length == 0) {
		setStatus(CLEAR);
	}
	if (isUserPage()) {
		if (irckey.length > 0) {
			if (password.toLowerCase() == irckey.toLowerCase()) {
				setStatus(MATCH_IRCKEY);
			}
		}
	}
	if (username.length > 0) {
 		if (password.toLowerCase() == username.toLowerCase()) {
			setStatus(MATCH_USERNAME);
		}
	}
}

function isStrongPassword(password) {
	return /(?=^.{8,}$)(?=.*[^a-zA-Z])(?=.*[A-Z])(?=.*[a-z]).*$/.test(password);
}

function checkMatching(password1, password2) {
	if (password2.length > 0) {
		if (password1 == password2 && getStrong() == true) {
			 $("#pass_match").text("Passwords match").css("color", "green");
			 enableSubmit();
		} else if (getStrong() == true) {
			$("#pass_match").text("Passwords do not match").css("color", "red");
			disableSubmit();
		} else {
			$("#pass_match").text("Password isn't strong").css("color", "red");
			disableSubmit();
		}
	} else {
		$("#pass_match").text("");
	}
}

function getStrong() {
	return $("#pass_strength").text() == "Strong";
}

function setStatus(strength) {
	if (strength == WEAK) {
		disableSubmit();
		$("#pass_strength").text("Weak").css("color", "red");
	}
	if (strength == STRONG) {
		disableSubmit();
		$("#pass_strength").text("Strong").css("color", "green");
	}
	if (strength == SHORT) {
		disableSubmit();
		$("#pass_strength").text("Too Short").css("color", "red");
	}
	if (strength == MATCH_IRCKEY) {
		disableSubmit();
		$("#pass_strength").text("Password cannot match IRC Key").css("color", "red");
	}
	if (strength == MATCH_USERNAME) {
		disableSubmit();
		$("#pass_strength").text("Password cannot match Username").css("color", "red");
	}
	if (strength == COMMON) {
		 disableSubmit();
		 $("#pass_strength").text("Password is too common").css("color", "red");
	}
	if (strength == CLEAR) {
		$("#pass_strength").text("");
	}
}

function disableSubmit() {
	$('input[type="submit"]').attr('disabled','disabled');
}

function enableSubmit() {
	$('input[type="submit"]').removeAttr('disabled');
}

function isUserPage() {
	return window.location.pathname.indexOf(USER_PATH) != -1;
}

})();

