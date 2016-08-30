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
		if ($_POST['ajax'] == 'exportstatement') {
			//validation
			// p($_POST);
			
			foreach (array('start', 'end') as $date) {
				if (isset($_POST[$date])) {
					if (strtotime($_POST[$date]) != 0) {
						$$date = date('Y-m-d', strtotime($_POST[$date]));
					}
				}
				if (!isset($$date)) {
					$$date = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));	//default to yesterday for both
				}
			}
			
			if (isset($_POST['accounts'])) {
				if (!empty($_POST['accounts'])) {
					//bed3 - tenant fees & tenant names (doesn't need to be in loop)
					$q = $db->prepare("
						SELECT
							tenants.id AS `tenantid`,
							tenancies.id AS `tenancyid`,
							tenantfees.id AS `tenantfeeid`,
							tenants.name AS `name`
						FROM `tenantfees` RIGHT JOIN (
							`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
						) ON tenantfees.tenancyid = tenancies.id
						WHERE tenants.clientid = $clientid
					");
					$q->execute();
					$r = $q->fetchAll(PDO::FETCH_ASSOC);
					// p($r);
					$bed3 = array();
					foreach ($r as $row) {
						if (!array_key_exists($row['tenantid'], $bed3)) {
							$bed3[$row['tenantid']] = array();
						}
						$bed3[$row['tenantid']]['name'] = $row['name'];
						if (!empty($row['tenancyid'])) {
							if (!array_key_exists($row['tenancyid'], $bed3[$row['tenantid']])) {
								$bed3[$row['tenantid']][$row['tenancyid']] = array();
							}
							if (!empty($row['tenantfeeid'])) {
								$bed3[$row['tenantid']][$row['tenancyid']][$row['tenantfeeid']] = $row['tenantfeeid'];
							}
						}
					}
					// p($bed3);
					
					//bed4 - aagaghhh
					$q = $db->prepare("
						SELECT
							tenantfees.id AS `tenantfeeid`,
							tenantfees.type AS `tenantfeetype`,
							tenantfees.desc AS `tenantfeedesc`,
							tenancies.id AS `tenancyid`,
							rooms.id AS `roomid`,
							rooms.no AS `roomno`,
							mp.mcid AS `mcid`,
							mp.mcstart AS `mcstart`,
							mp.mcend AS `mcend`,
							mp.mgmt AS `mgmt`,
							mp.lease AS `lease`,
							mp.propertyid AS `propertyid`,
							mp.property AS `property`,
							mp.xero_tracking1 AS `xero_tracking1`
						FROM `tenantfees` RIGHT JOIN (
							`tenancies` LEFT JOIN (
								`rooms` RIGHT JOIN (
									SELECT
										mgmtcontracts.id AS `mcid`,
										mgmtcontracts.startdate AS `mcstart`,
										mgmtcontracts.enddate AS `mcend`,
										mgmtcontracts.mgmt AS `mgmt`,
										mgmtcontracts.lease AS `lease`,
										properties.id AS `propertyid`,
										CONCAT(properties.no, ' ', properties.street) AS `property`,
										properties.xero_tracking1 AS `xero_tracking1`,
										properties.clientid AS `clientid`
									FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id
								) mp ON rooms.propertyid = mp.propertyid
							) on tenancies.roomid = rooms.id
						) on tenantfees.tenancyid = tenancies.id
						WHERE mp.clientid = $clientid
						
					");
					$q->execute();
					$r = $q->fetchAll(PDO::FETCH_ASSOC);
					// p($r);
					$bed4 = array();
					$rowcount = 0;
					foreach ($r as $row) {
						if (!array_key_exists($row['mcid'], $bed4)) {
							$bed4[$row['mcid']] = array(); // lists rooms
							$bed4[$row['mcid']]['propertyid'] = $row['propertyid'];
							$bed4[$row['mcid']]['property'] = $row['property'];
							$bed4[$row['mcid']]['mcstart'] = $row['mcstart'];
							$bed4[$row['mcid']]['mcend'] = $row['mcend'];
							$bed4[$row['mcid']]['mgmt'] = $row['mgmt'];
							$bed4[$row['mcid']]['lease'] = $row['lease'];
							$bed4[$row['mcid']]['xero_tracking1'] = $row['xero_tracking1'];
						}
						if (!array_key_exists($row['roomno'], $bed4[$row['mcid']])) {
							$bed4[$row['mcid']][$row['roomno']] = array(); // lists tenancies
						}
						if (!array_key_exists($row['tenancyid'], $bed4[$row['mcid']][$row['roomno']])) {
							$bed4[$row['mcid']][$row['roomno']][$row['tenancyid']] = array(); //lists fees
						}
						$bed4[$row['mcid']][$row['roomno']][$row['tenancyid']][$row['tenantfeeid']] = array(
							'type' => $row['tenantfeetype'],
							'desc' => $row['tenantfeedesc']
						);
						// $bed4[$row['mcid']][$row['roomno']][$row['tenancyid']][$row['tenantfeeid']] = $rowcount;
						$rowcount++;
					}
					// p($bed4);
					
					//bed6 (last one!) - xero accounts
					$q = $db->query("SELECT * FROM `clients` WHERE `id` = $clientid");
					$r = $q->fetch(PDO::FETCH_ASSOC);
					$bed6 = array(
						'xero_acc_rentm' => $r['xero_acc_rentm'],
						'xero_acc_rentr' => $r['xero_acc_rentr'],
						'xero_acc_appm' => $r['xero_acc_appm'],
						'xero_acc_appr' => $r['xero_acc_appr'],
						'xero_acc_letfee' => $r['xero_acc_letfee'],
						'xero_acc_mgmtfee' => $r['xero_acc_mgmtfee'],
						'xero_acc_main' => $r['xero_acc_main'],
						'xero_acc_other' => $r['xero_acc_other'],
						'xero_tracking1' => $r['xero_tracking1']
					);
					
					function cmp(array $a, array $b) {
						if ($a[0] < $b[0]) {
							return -1;
						} else if ($a[0] > $b[0]) {
							return 1;
						} else {
							return 0;
						}
					}					
					
					foreach($_POST['accounts'] as $account) {
						//account check
						$q = $db->prepare("SELECT * FROM `bankaccounts` WHERE `id` = ? AND `clientid` = $clientid");
						$q->execute(array($account));
						$rc = $q->rowCount();
						if ($rc == 1) {
							$r = $q->fetch();
							$filename = "[".$r['id']."] ".$r['name'];
							//bed1 - reconciled statement lines
							$q = $db->prepare("
								SELECT
									statementlines.id AS `id`,
									statementlines.desc AS `desc`
								FROM `statementlines` LEFT JOIN `statements` ON statementlines.statementid = statements.id
								WHERE statements.accountid = ?
								AND statementlines.status = 'r'
								AND statementlines.generation != 'p'
							");
							$q->execute(array($account));
							$r = $q->fetchAll(PDO::FETCH_ASSOC);
							$bed1 = array();
							foreach ($r as $row) {
								$bed1[$row['id']] = $row['desc']; //only one value so not bothering with subarray
							}
							// p($bed1);
							
							//bed2 - payments (landlord payments currently not supported)
							$q = $db->prepare("
								SELECT
									payments.id AS `id`,
									payments.contacttype AS `ctype`,
									payments.contactid AS `cid`,
									payments.amount AS `amount`,
									statementlines.date AS `date`,
									payments.statementlineid AS `statementlineid`
								FROM `payments` LEFT JOIN (
									`statementlines` LEFT JOIN `statements` ON statementlines.statementid = statements.id
								) ON payments.statementlineid = statementlines.id
								WHERE statements.accountid = ?
								AND (statementlines.date BETWEEN '$start' AND '$end')
								AND payments.contacttype != 'l'
							");
							$q->execute(array($account));
							$r = $q->fetchAll(PDO::FETCH_ASSOC);
							$bed2 = array();
							foreach ($r as $row) {
								$bed2[$row['id']] = array(
									'ctype' => $row['ctype'],
									'cid' => $row['cid'],
									'amount' => $row['amount'],
									'date' => $row['date'],
									'statementlineid' => $row['statementlineid']
								);
							}
							// p($bed2);
							
							//bed2b - LANDLORD PAYMENTS
							$q = $db->prepare("
								SELECT
									payments.id AS `id`,
									payments.amount AS `amount`,
									statementlines.date AS `date`,
									statementlines.desc AS `desc`,
									payments.statementlineid AS `statementlineid`
								FROM `payments` LEFT JOIN (
									`statementlines` LEFT JOIN `statements` ON statementlines.statementid = statements.id
								) ON payments.statementlineid = statementlines.id
								WHERE statements.accountid = ?
								AND (statementlines.date BETWEEN '$start' AND '$end')
								AND payments.contacttype = 'l'
							");
							$q->execute(array($account));
							$r = $q->fetchAll(PDO::FETCH_ASSOC);
							$bed2b = array();
							foreach ($r as $row) {
								$bed2b[$row['id']] = array(
									'amount' => $row['amount'],
									'date' => $row['date'],
									'desc' => $row['desc']
								);
							}
							// p($bed2b);
							
							//bed5 - unreconciled & ignored statementlines
							$q = $db->prepare("
								SELECT
									statementlines.id AS `id`,
									statementlines.date AS `date`,
									statementlines.desc AS `desc`,
									statementlines.amount AS `amount`
								FROM `statementlines` LEFT JOIN `statements` ON statementlines.statementid = statements.id
								WHERE statements.accountid = ?
								AND (statementlines.status = 'u' OR statementlines.status = 'i')
								AND statementlines.generation != 'p'
								AND (statementlines.date BETWEEN '$start' AND '$end')
							");
							$q->execute(array($account));
							$r = $q->fetchAll(PDO::FETCH_ASSOC);
							$bed5 = array();
							foreach ($r as $row) {
								$bed5[$row['id']] = array(
									'date' => $row['date'],
									'desc' => $row['desc'],
									'amount' => $row['amount']
								);
							}
							// p($bed5);
							
							// and begin...
							$export = array();
							
							foreach ($bed5 as $uisl) {	//unreconciled/ignored statement line
								$export[] = array(
									$uisl['date'],
									null,
									null,
									$uisl['amount']/100,
									null,
									null,
									null,
									htmlspecialchars_decode($uisl['desc'])
								);
							}
							
							foreach ($bed2b as $lp) {	//landlord payments
								$export[] = array(
									$lp['date'],
									null,
									null,
									$lp['amount']/100,
									null,
									null,
									null,
									htmlspecialchars_decode($lp['desc'])
								);								
							}
							
							// p($export);
							$pcount = 0;
							foreach ($bed2 as $payment) {
								// if ($pcount == 0) {	// *************DEBUG TEMP**************
								unset($payee);
								unset($currenttenancy);
								unset($propertyid);
								unset($xerotrack1);
								unset($currenttenantfee);
								unset($currentroom);
								unset($acc);
								unset($ref);
								
								//payee
								if ($payment['ctype'] == 't') {
									foreach ($bed3 as $tenant) {
										if (array_key_exists($payment['cid'], $tenant)) {
											$payee = $tenant['name'];
											$currenttenancy = $payment['cid'];
										}
									}
								} else if ($payment['ctype'] == 'f') {
									foreach ($bed3 as $tenant) {
										foreach ($tenant as $tenancyid => $tenancy) {
											if (is_array($tenancy)) {
												if (array_key_exists($payment['cid'], $tenancy)) {
													$payee = $tenant['name'];
													$currenttenancy = $tenancyid;
												}
											}
										}
									}
								}
								
								//desc
								if ($payment['ctype'] == 't') {
									foreach ($bed4 as $mc) {
										foreach ($mc as $roomno => $room) {
											if (is_array($room)) {
												if (array_key_exists($currenttenancy, $room)) {
													$desc = "Rent payment for room ".$roomno.", ".$mc['property'];
													$propertyid = $mc['propertyid'];
													$xerotrack1 = $mc['xero_tracking1'];
												}
											}
										}
									}
								} else if ($payment['ctype'] == 'f') {
									foreach ($bed4 as $mc) {
										foreach ($mc as $roomno => $room) {
											if (is_array($room)) {
												if (array_key_exists($currenttenancy, $room)) {
													foreach ($room[$currenttenancy] as $tenantfeeid => $tenantfee) {
														if ($tenantfeeid == $payment['cid']) {
															$desc = $tenantfee['desc'];
															$propertyid = $mc['propertyid'];
															$xerotrack1 = $mc['xero_tracking1'];
															$currenttenantfee = $tenantfeeid;
															$currentroom = $roomno;
														}
													}
												}
											}
										}
									}									
								}
								
								//acc
								if ($payment['ctype'] == 't') {
									foreach ($bed4 as $mc) {
										if ($mc['propertyid'] == $propertyid) {
											if (/* $mc['mgmt'] > 0 && */ $mc['mgmt'] < 100) { 		//0% management fee now counts as managed, used for tenant find only
												$acc = $bed6['xero_acc_rentm'];
											} else {
												$acc = $bed6['xero_acc_rentr'];
											}
										}
									}
								} else if ($payment['ctype'] == 'f') {
									foreach ($bed4 as $mc) {
										if ($mc['propertyid'] == $propertyid) {
											if (/* $mc['mgmt'] > 0 && */ $mc['mgmt'] < 100) {
												if ($mc[$currentroom][$currenttenancy][$currenttenantfee]['type'] == 'a') {
													$acc = $bed6['xero_acc_appm'];
												} else {
													$acc = null;		//not including these as they will need receipts
												}
											} else {
												if ($mc[$currentroom][$currenttenancy][$currenttenantfee]['type'] == 'a') {
													$acc = $bed6['xero_acc_appr'];
												} else {
													$acc = null;		//not including these as they will need receipts
												}												
											}											
										}
									}
								}

								//ref
								$ref = $bed1[$payment['statementlineid']];
								
								$export[] = array(
									$payment['date'],
									html_entity_decode($payee),
									html_entity_decode($desc),
									$payment['amount']/100,
									$acc,
									'No VAT',
									$xerotrack1,
									'[PPMA] '.htmlspecialchars_decode($ref)
								);										

								// } $pcount++;	// *************DEBUG TEMP**************
							}
							// p($export);
							usort($export, 'cmp');
							array_unshift($export, array(
								'Date',
								'Payee',
								'Description',
								'Amount',
								'Account',
								'Tax Type',
								$bed6['xero_tracking1'],
								'Reference'
							));
							
							$fp = fopen('db/statementexport/'.$filename.'.csv', 'w');
							
							foreach ($export as $fields) {
								fputcsv($fp, $fields);
							}
							
							fclose($fp);
							
							echo "<p><a href=\"db/statementexport/".$filename.".csv\">Click to download ".$filename.".csv</a></p>\n";
							
						} 	//else give error
					}
				} 	// else give error
			}	// else give error
			
			
		}
	} else {
		header('Location: index.php');
	}
} else {
	header('Location: index.php');
}
	