<state><?
switch ($User['State']) {
	case 0:
		echo 'pending';
		break;
	case 1:
		echo 'accepted';
		break;
	case 2:
		echo 'rejected';
}
?></state>
