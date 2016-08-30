<?php // include 'defs/defs.php'; 

include_once 'defs/db_connect.php';
include_once 'defs/functions.php';

sec_session_start();
if (login_check($db) == true) {

	if (isset($_POST['ajax'])){
		$id = $_SESSION['user_id'];
		$q = $db->prepare("SELECT `clientid` FROM `users` WHERE `id` = ?");
		$q->bindValue(1, $id, PDO::PARAM_INT);
		$q->execute();
		$r = $q->fetch(PDO::FETCH_ASSOC);
		$clientid = $r['clientid'];
		if ($_POST['ajax'] == 'importstatement') {
			//where the magic happens
			// p($_POST);
			if (strlen($_POST['account']) < 1) {
				echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e1\" /> ";
				$error = 1;
			} else {
				$rows = array_map("str_getcsv", file('db/statements/'.$clientid.'/'.$_POST['filename']));
				if ($_POST['ignorefirst'] == 'on') {
					array_shift($rows);
				}
				$colcount = sizeof($rows[0]);
				$q = $db->prepare("SELECT * FROM `bankaccounts` WHERE `id` = ?");
				$q->bindValue(1, $_POST['account'], PDO::PARAM_INT);
				$q->execute();
				$c = $q->rowCount();
				if ($c == 0) {
					echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e2\" /> ";
					$error = 1;
				} else {
					$r = $q->fetch();
					$bank = $r['bank_s'];
					$accountid = $r['id'];	//used later
					$import = array();
					// ==== bank conversion ====
					if ($bank == 'lloyds') {
						if ($colcount == 8) {
							$rowsr = array_reverse($rows);
							foreach ($rowsr as $row) {
								$import[] = array(str_replace('/', '-', $row[0]), $row[4], $row[6] - $row[5]);
							}
							// p($import);
						} else {
							echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e3\" /> ";
							$error = 1;
						}
						
					// } else if ($bank == 'eway') {		
						// if ($colcount == 13) {
							// $rowsr = array_reverse($rows);
							// foreach ($rowsr as $row) {
								// if ($row[6] == "Successful") {
									// $datetime = explode (' ', $row[0]);
									// $date = str_replace('/', '-', $datetime[0]);
									// $ukdate = substr($date, 0, 6).'20'.substr($date, -2);	//converts to 4 digit year
									// $import[] = array($ukdate, $row[3]." - ".$row[11], $row[9]);
								// }
							// }
						// } else {
							// echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e3\" /> ";
							// $error = 1;
						// }
					} else if ($bank == 'eway') {
						if ($colcount == 7) {
							$rowsr = array_reverse($rows);
							foreach ($rowsr as $row) {
								if ($row[3] == "Successful") {
									$datetime = explode (' ', $row[0]);
									$date = str_replace('/', '-', $datetime[0]);
									$ukdate = substr($date, 0, 6).'20'.substr($date, -2);	//converts to 4 digit year
									$import[] = array($ukdate, $row[4]." | ".$row[5]." | ".$row[6], $row[1]);
								}
							}
						} else {
							echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e3\" /> ";
							$error = 1;
						}
						
					} else if ($bank == 'erms') {
						if ($colcount == 13) {
							$rowsr = array_reverse($rows);
							foreach ($rowsr as $row) {
								$datestr = explode(" ",$row[3]);
								if ($row[7] == "REFUND_SALE") {
									$amount = 0 - $row[4];
									$findme = 'AUTH';
								} else {
									$amount = $row[4];
									$findme = 'APPROVED';
								}
								$pos = strpos($row[12], $findme);
								if ($pos === 0) {
									$date = date("d-M-Y", strtotime(str_replace("/", "-", $datestr[0])));
									$desc = $row[1]." | ".$row[8]." | ".$row[2];
									$import[] = array($date, $desc, $amount);
								}
							
							}
							// p($import);
						} else {
							echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e3\" /> ";
							$error = 1;
						}
					} else if ($bank == 'securetrading') {
						if ($colcount == 13) {
							foreach ($rows as $row) {
								if ($row[7] == '100' || $row[7] == '0') {	//includes pending (from same day)
									$datetime = explode (' ', $row[3]);
									$date = str_replace('/', '-', $datetime[0]);
									$amount = ($row[12] == "REFUND" ? 0 - $row[6] : $row[6]);
									$import[] = array($date, $row[1]." - ".$row[10], $amount);
								}
							}
							// p($import);
						} else {
							echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e3\" /> ";
							$error = 1;
						}
					} else if ($bank == 'landz') {
						if ($colcount == 6) {
							// -- custom sort by date -- 
							function date_compare($a, $b)
							{
								$t1 = strtotime($a[3]);
								$t2 = strtotime($b[3]);
								return $t1 - $t2;
							}    
							usort($rows, 'date_compare');		
							// -- end date sort -- 
							foreach ($rows as $row) {
								if ($row[5] == "Success" || $row[5] == "Due") {
									$import[] = array($row[3], $row[1]." - ".$row[0], $row[4]);
								}
							}
							// p($import);
						} else {
							echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e3\" /> ";
							$error = 1;
						}
					} else {
						echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"e4\" /> ";
						$error = 1;
					}
				}
			}
			if (!isset($error)) {
				// update statements table
				$q = $db->prepare("
					INSERT INTO `statements` (
						`accountid`,
						`importdate`,
						`startdate`,
						`lines`,
						`enddate`
					) VALUES (
						:accountid,
						:importdate,
						:startdate,
						:lines,
						:enddate
					)
				");
				$q->bindValue(':accountid', $accountid);
				$q->bindValue(':importdate', date('Y-m-d'));
				$q->bindValue(':startdate', date('Y-m-d', strtotime($import[0][0])));
				$q->bindValue(':lines', sizeof($import));
				$q->bindValue(':enddate', date('Y-m-d', strtotime($import[sizeof($import) - 1][0])));
				$q->execute();
				$statementid = $db->lastInsertID();
				
				foreach ($import as $row) {	// update lines table
					unset($dbrow);
					$dbrow = array();
					foreach ($row as $column) {
						$dbrow[] = preg_replace("/&nbsp;/",'',htmlentities(trim($column)));
					}
					// DUPE CHECKING! $dbrow
					unset($status);
					$pence = preg_replace("/([^0-9\\.-])/i", "", $dbrow[2]) * 100;
					// $dc1 = $db->prepare("
						// SELECT
							// *
						// FROM `statementlines`
						// WHERE `date` = ?
					// ");
					$dc1 = $db->prepare("
						SELECT
							*
						FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
						WHERE statementlines.date = ?
						AND bankaccounts.id = ?
						AND bankaccounts.clientid = ?
					");
					$dc1->execute(array(date('Y-m-d', strtotime($dbrow[0])), $_POST['account'], $clientid));
					$rc1 = $dc1->rowCount();
					if ($rc1 > 0) {		//strike 1
						// $dc2 = $db->prepare("
							// SELECT
								// *
							// FROM `statementlines`
							// WHERE `amount` = ?
							// AND `date` = ?"
						// );
						$dc2 = $db->prepare("
							SELECT
								*
							FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
							WHERE statementlines.amount = ?
							AND statementlines.date = ?
							AND bankaccounts.id = ?
							AND bankaccounts.clientid = ?
						");
						$dc2->execute(array($pence, date('Y-m-d', strtotime($dbrow[0])), $_POST['account'], $clientid));
						$rc2 = $dc2->rowCount();
						if ($rc2 > 0) {		//strike 2
							// $dc3 = $db->prepare("
							// SELECT
								// *
							// FROM `statementlines`
							// WHERE `hash` = ?
							// AND `amount` = ?
							// AND `date` = ?
							// ");
							$dc3 = $db->prepare("
								SELECT
									statementlines.statementid AS `statementid`
								FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
								WHERE statementlines.desc = ?
								AND statementlines.amount = ?
								AND statementlines.date = ?
								AND bankaccounts.id = ?
								AND bankaccounts.clientid = ?
							");
							$dc3->execute(array($dbrow[1], $pence, date('Y-m-d', strtotime($dbrow[0])), $_POST['account'], $clientid));
							$rc3 = $dc3->rowCount();
							if ($rc3 > 0) {		//strike 3
								$r3 = $dc3->fetchAll();
								foreach ($r3 as $row3) {
									if ($row3['statementid'] != $statementid) {		// this will only mark as dupe if the line is from a different statement, helps prevent two identical lines on the same import being marked as duplicate (generally won't be the case)
										$status = 'd';																	
									}	

								}
								
									
								
							// } else {
								// d('passed rc3 - hash is '.murmurhash($dbrow[1]));
							}
						// } else {
							// d('passed rc2');
						}
					// } else {
						// d('passed rc1');
					}
					if (!isset($status)) {
						$status = 'u';
					}
					$q = $db->prepare("
						INSERT INTO `statementlines` (
							`statementid`,
							`date`,
							`desc`,
							`amount`,
							`hash`,
							`status`
						) VALUES (
							:statementid,
							:date,
							:desc,
							:amount,
							:hash,
							:status
						)
					");
					
					$q->bindValue(':statementid', $statementid);
					$q->bindValue(':date', date('Y-m-d', strtotime($dbrow[0])));
					$q->bindValue(':desc', $dbrow[1]);
					$q->bindValue(':amount', $pence);
					$q->bindValue(':hash', murmurhash($dbrow[1]));
					$q->bindValue(':status', $status);
					$q->execute();		// finished line
				}
			// search for earliest non-dupe statementline and update statement start date accordingly	
			$q2 = $db->prepare("SELECT `date` FROM `statementlines` WHERE `statementid` = ? AND `status` = 'u' ORDER BY `date` LIMIT 1");	
			$q2->execute(array($statementid));
			$r2 = $q2->fetch();
			$q2 = $db->prepare("UPDATE `statements` SET `startdate` = ? WHERE `id` = ?");
			$q2->execute(array($r2['date'], $statementid));
			
			unlink('db/statements/'.$clientid.'/'.$_POST['filename']);
			echo "<input type=\"hidden\" id=\"status".$_POST['statementno']."\" value=\"s\" /> ";
			

			}
		}
	} else {
		header('Location: index.php');
	}
} else {
	header('Location: index.php');
}
	