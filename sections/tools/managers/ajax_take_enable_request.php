<?
if (!check_perms('users_mod')) {
    json_error(403);
}

if (!FEATURE_EMAIL_REENABLE) {
    json_error("This feature is currently disabled.");
}

$Type = $_GET['type'];

if ($Type == "resolve") {
    $IDs = $_GET['ids'];
    $Comment = db_string($_GET['comment']);
    $Status = db_string($_GET['status']);

    // Error check and set things up
    if ($Status == "Approve" || $Status == "Approve Selected") {
        $Status = AutoEnable::APPROVED;
    } else if ($Status == "Reject" || $Status == "Reject Selected") {
        $Status = AutoEnable::DENIED;
    } else if ($Status == "Discard" || $Status == "Discard Selected") {
        $Status = AutoEnable::DISCARDED;
    } else {
        json_error("Invalid resolution option");
    }

    if (is_array($IDs) && count($IDs) == 0) {
        json_error("You must select at least one reuqest to use this option");
    } else if (!is_array($IDs) && !is_number($IDs)) {
        json_error("You must select at least 1 request");
    }

    // Handle request
    AutoEnable::handle_requests($IDs, $Status, $Comment);
} else if ($Type == "unresolve") {
    $ID = (int) $_GET['id'];
    AutoEnable::unresolve_request($ID);
} else {
    json_error("Invalid type");
}

echo json_encode(array("status" => "success"));

function json_error($Message) {
    echo json_encode(array("status" => $Message));
    die();
}
