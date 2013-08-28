<?

$Skip = array();
$Skip[] = db_string($_GET['skip']);
$NotificationsManager = new NotificationsManager($LoggedUser['ID'], $Skip);

json_die("success", $NotificationsManager->get_notifications());

//echo '{"status":"success","response":[[{"message":"1st notification","url":"https:\/\/www.google.com\/","importance":"alert","AutoExpire":false},{"message":"2nd notification","url":"","importance":"alert","AutoExpire":true}]]}';