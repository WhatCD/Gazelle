<?php

$DB->query("
	SELECT
		f.FriendID,
		u.Username
	FROM friends AS f
		RIGHT JOIN users_enable_recommendations AS r
			ON r.ID = f.FriendID
				AND r.Enable = 1
		RIGHT JOIN users_main AS u
			ON u.ID = f.FriendID
	WHERE f.UserID = '$LoggedUser[ID]'
	ORDER BY u.Username ASC");
echo json_encode($DB->to_array(false, MYSQLI_ASSOC));
die();
