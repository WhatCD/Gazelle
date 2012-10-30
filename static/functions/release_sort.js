//Couldn't use an associative array because javascript sorting is stupid http://dev-answers.blogspot.com/2012/03/javascript-object-keys-being-sorted-in.html

(function($) {
    var DEFAULT = '\x3Cul class=\"sortable_list ui-sortable\" id=\"sortable\" style=\"\"\x3E\n\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"1_0\"\x3EAlbum\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"3_0\"\x3ESoundtrack\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"5_0\"\x3EEP\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"6_0\"\x3EAnthology\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"7_0\"\x3ECompilation\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"9_0\"\x3ESingle\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"11_0\"\x3ELive album\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"13_0\"\x3ERemix\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"14_0\"\x3EBootleg\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"15_0\"\x3EInterview\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"16_0\"\x3EMixtape\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"21_0\"\x3EUnknown\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"1024_0\"\x3EGuest Appearance\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"1023_0\"\x3ERemixed By\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"1022_0\"\x3EComposition\x3C\x2Fli\x3E\n\t\t\x3Cli class=\"sortable_item\" style=\"\"\x3E\x3Cinput type=\"checkbox\" id=\"1021_0\"\x3EProduced By\x3C\x2Fli\x3E\n\t\t\x3C\x2Ful\x3E';
    $(document).ready(function() {
	serialize();
	$("#sortable").sortable({
	    placeholder: "ui-state-highlight",
	    update: function() {
		serialize();
	    }
	});
	$("#toggle_sortable").click(function () {
	    $('#sortable_container').slideToggle(function() {
		$("#toggle_sortable").text($(this).is(":visible") ? "Collapse" : "Expand");
	    }); 
	});
	$("#reset_sortable").click(function () {
	    $('#sortable').html(DEFAULT);
	    serialize();
	});
    });
    function serialize() {
	var a = new Array();
	$("#sortable").find("input").each(function (i) {
	    $(this).unbind("click");
	    $(this).click(function() {
		var c = $(this).attr("checked") == "checked" ? 1 : 0;
		var old_id = $(this).attr("id");
		var new_id = old_id.slice(0, - 1) + c;
		$(this).attr("id", new_id);
		serialize();
	    });
	    a.push($(this).attr("id"));
	});
	$("#sorthide").val(JSON.stringify(a));
    }
}
(jQuery));

