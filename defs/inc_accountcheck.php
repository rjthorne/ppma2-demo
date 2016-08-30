<?php

sec_session_start();
if (login_check($db) == true) {
	$id = $_SESSION['user_id'];
	if ($q = $db->prepare("SELECT `approved`, `clientid`, `usertype` FROM `users` WHERE `id` = ?")) {
		$q->bindValue(1, $id, PDO::PARAM_INT);
		$q->execute();
		$r = $q->fetch(PDO::FETCH_ASSOC);
		if (($r['approved'] == 'y' && $r['clientid'] != 0) || $r['usertype'] == 'a' ) {
			$id = $_SESSION['user_id'];
			$clientid = $r['clientid'];
			$usertype = $r['usertype'] == 'a';

			if ($clientid == 0) {
				$q2 = $db->query("SELECT `id` FROM `docs` WHERE `clientid` = '0'");
				$r2 = $q2->fetchAll(PDO::FETCH_ASSOC);
				$nr = $q2->rowCount();
				if ($nr > 0) 
					header('Location: adminupload.php');
				$clientname = '(no company specified)';
			} else {
				if ($q = $db->prepare("SELECT clients.name FROM `clients` LEFT JOIN `users` ON clients.id = users.clientid WHERE users.clientid =?")) {
					$q->bindValue(1, $clientid, PDO::PARAM_INT);
					$q->execute();
					$r = $q->fetch(PDO::FETCH_ASSOC);
					$clientname = $r['name'];
				}
			}
		$displaycontent = true;
		} else {
			header('Location: home.php');
		}
	} else {
		echo "An unknown database error occurred. Please contact <a href=\"mailto:accounts@ppn-uk.co.uk\">accounts@ppn-uk.co.uk</a> if the problem persists.";
	}
} else {
	header('Location: index.php'); //TODO: GET function for attempted page visit
}
?>