(function() {
    var ids = Array();
    $(document).ready(function() {
        $("input[id^=check_all]").click(function() {
            // Check or uncheck all requests
            var checked = ($(this).attr('checked') == 'checked') ? true : false;
            $("input[id^=multi]").each(function() {
                $(this).attr('checked', checked);
                var id = $(this).data('id');
                if (checked && $.inArray(id, ids) == -1) {
                    ids.push(id);
                } else if (!checked && $.inArray(id, ids) != -1) {
                    ids = $.grep(ids, function(value) {
                        return value != id;
                    });
                }
            });
        });
        $("input[id^=multi]").click(function() {
            // Put the ID in the array if checked, or removed if unchecked
            var checked = ($(this).attr('checked') == 'checked') ? true : false;
            var id = $(this).data('id');
            if (checked && $.inArray(id, ids) == -1) {
                ids.push(id);
            } else if (!checked && $.inArray(id, ids) != -1) {
                ids = $.grep(ids, function(value) {
                    return value != id;
                });
            }
        });
        $("input[id^=outcome]").click(function() {
            if ($(this).val() != 'Discard' && !confirm('Are you sure you wish to do this? This cannot be undone!')) {
                return false;
            }
            var id = $(this).data('id');
            if (id !== undefined) {
                // Only resolving one row
                resolveIDs = [id];
                var comment = $("input[id^=comment" + id + "]").val();
            } else {
                resolveIDs = ids;
                comment = '';
            }

            $.ajax({
                type : "GET",
                dataType : "json",
                url : "tools.php?action=ajax_take_enable_request",
                data : {
                    "ids" : resolveIDs,
                    "comment" : comment,
                    "status" : $(this).val(),
                    "type" : "resolve"
                }
            }).done(function(response) {
                if (response['status'] == 'success') {
                    for (var i = 0; i < resolveIDs.length; i++) {
                        $("#row_" + resolveIDs[i]).remove();
                    }
                } else {
                    alert(response['status']);
                }
            });
        });
        $("a[id^=unresolve]").click(function() {
            var id = $(this).data('id');
            if (id !== undefined) {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "tools.php?action=ajax_take_enable_request",
                    data : {
                        "id" : id,
                        "type" : "unresolve"
                    }
                }).done(function(response) {
                    if (response['status'] == 'success') {
                        $("#row_" + id).remove();
                        alert("The request has been un-resolved. Please refresh your browser to see it.");
                    } else {
                        alert(response['status']);
                    }
                });
            }
        });
    });
})();

function ChangeDateSearch(rangeVariable, dateTwoID) {
    var fullID = "#" + dateTwoID;
    if (rangeVariable === 'between') {
        $(fullID).show();
    } else {
        $(fullID).hide();
    }
}
