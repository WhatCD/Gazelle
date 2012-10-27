(function ($) {
 $(document).ready(function() {
	 if($("#pushservice").val() > 0) {
		 $('#pushsettings').show();
		 if($("#pushservice").val() == 3) {
			 $('#pushsettings_username').show();
		 }
	 }
	 $("#pushservice").change(function() {
	     if($(this).val() > 0) {
	    	 $('#pushsettings').show(500);
	    	 if($(this).val() == 3) {
				 $('#pushsettings_username').show();
	    	 }
	    	 else {
	    		 $('#pushsettings_username').hide();
	    	 }
	     }
	     else {
	    	 $('#pushsettings').hide(500);
			 $('#pushsettings_username').hide();
	     }
	    });
	 });
 }(jQuery));

