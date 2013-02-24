(function ($) {
	var PUSHOVER = 5;
	var TOASTY = 4;
 $(document).ready(function() {
	 if($("#pushservice").val() > 0) {
		 $('#pushsettings').show();
		 if($("#pushservice").val() == PUSHOVER) {
			 $('#pushsettings_username').show();
		 }
	 }
	 $("#pushservice").change(function() {
	     if($(this).val() > 0) {
	    	 $('#pushsettings').show(500);
	    	 if($(this).val() == PUSHOVER) {
				 $('#pushsettings_username').show();
	    	 }
	    	 else {
	    		 $('#pushsettings_username').hide();
	    	 }

			 if($(this).val() == TOASTY) {
				 $('#pushservice_title').text("Device ID");
				 $('#pushusername_title').text("Username");
			 }
			 else if($(this).val() == PUSHOVER) {
				 $('#pushservice_title').text("Token");
				 $('#pushusername_title').text("User Key");
			 }
			 else {
				 $('#pushservice_title').text("API Key");
				 $('#pushusername_title').text("Username");
			 }
	     }
	     else {
	    	 $('#pushsettings').hide(500);
			 $('#pushsettings_username').hide();
	     }
	    });
	 });
 }(jQuery));

