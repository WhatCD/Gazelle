//Couldn't use an associative array because javascript sorting is stupid http://dev-answers.blogspot.com/2012/03/javascript-object-keys-being-sorted-in.html

(function($) {
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

