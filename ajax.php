<?php // include 'defs/defs.php'; 

include_once 'defs/db_connect.php';
include_once 'defs/functions.php';

sec_session_start();
if (login_check($db) == true) {

	$id = $_SESSION['user_id'];
	$q = $db->prepare("SELECT `clientid` FROM `users` WHERE `id` = ?");
	$q->bindValue(1, $id, PDO::PARAM_INT);
	$q->execute();
	$r = $q->fetch(PDO::FETCH_ASSOC);
	$clientid = $r['clientid'];

	if (isset($_POST['ajax'])){

//================================== DB UPDATE (depreciated) ========================================

		if ($_POST['ajax'] == 'update') {
			foreach ($_POST as $k => $v) {
				$$k = $_POST[$k];
			}
			if ($prefix == 'doc') {
				$table = 'docs';
			} else if ($prefix == 'prop') {
				$table = 'properties';
			} else if ($prefix == 'util') {
				$table = 'utilities';
			} 
			$textfields = array(
				'notes','company','doctype','amount',		//docs
				'address','status','rooms',					//properties
				'property', 'type', 'ref', 'term'			//utils
			);
			$datefields = array(
				'pdate','idate',							//docs
				'sdate','edate'								//props/utils
			);
			if (in_array($field, $textfields)) {
				if ($field == 'amount') {
					$val = round(str_replace(',', '', $_POST['value'])*100);
				} else {
					$val = urldecode(htmlspecialchars($_POST['value']));
				}
			} else if (in_array($field, $datefields)) {
				$val = date('Y-m-d', strtotime($_POST['value']));
			}
			if (isset($val) && isset($table)) {
				if ($field == 'property') {
					$q = $db->prepare("UPDATE `utilities` SET `propid` =? WHERE `id` =? ");
				} else {
					$q = $db->prepare("UPDATE `".$table."` SET `".$field."` =? WHERE `id` =? ");
				}
				$q->execute(array($val, $id));
				if (in_array($field, $datefields)) {
					echo $_POST['value'];
				} else if ($field == 'amount') {
					echo number_format($val/100, 2, '.', ',');
				} else if ($field == 'property') {
					$q2= $db->prepare("SELECT `address` FROM `properties` WHERE `id` =?");
					$q2->bindValue(1, $val);
					$q2->execute();
					$r = $q2->fetch(PDO::FETCH_ASSOC);
					echo $r['address'];
				} else if ($field == 'term') {
					$term = array(
						'w' => 'Weekly',
						'f' => 'Fortnightly',
						'm' => 'Monthly',
						'q' => 'Quarterly',
						'a' => 'Annually'
					);
					echo $term[$val];
				} else {
					echo $val;
				}
			}

//================================== DB IMPORT ========================================

		} else if ($_POST['ajax'] == 'import') {

			$cid = $clientid;

			if ($_POST['importtype'] != 'tenants') {
				if ($_POST['importtype'] == 'properties') {
					$importarr = array('clientid', 'no', 'street', 'town', 'postcode', 'rooms', 'sname');
					$q2 = $db->prepare("
						INSERT INTO `properties` (
							`clientid`,
							`no`,
							`street`,
							`town`,
							`postcode`,
							`rooms`,
							`sname`
						) VALUES (
							:clientid,
							:no,
							:street,
							:town,
							:postcode,
							:rooms,
							:sname
						)
					");
					$returl = 'properties.php';

				} else if ($_POST['importtype'] == 'landlords') {
					$importarr = array('clientid', 'name', 'address1', 'address2', 'town', 'postcode', 'phone1', 'phone2', 'email1', 'email2');
					$q2 = $db->prepare("
						INSERT INTO `landlords` (
							`clientid`,
							`name`,
							`address1`,
							`address2`,
							`town`,
							`postcode`,
							`phone1`,
							`phone2`,
							`email1`,
							`email2`
						) VALUES (
							:clientid,
							:name,
							:address1,
							:address2,
							:town,
							:postcode,
							:phone1,
							:phone2,
							:email1,
							:email2
						)
					");
					$returl = 'landlords.php';

				} 

				$csv = file('db/upload/upload'.$_POST['upload']);
				$rows = array_map("str_getcsv", $csv);

				foreach ($rows as $arr) {	// cycling through each line in csv
					$uarr = array();
					for ($x=0; $x<sizeof($arr); $x++) {
						$uarr[] = trim(htmlentities(utf8_encode($arr[$x])));
					}

					array_unshift($uarr, $cid);
					for ($x = 0; $x < count($uarr); $x++) {
						if (strlen($uarr[$x]) > 0 ) {
							$q2->bindValue(':'.$importarr[$x], $uarr[$x]);
						} else {
							$q2->bindValue(':'.$importarr[$x], null, PDO::PARAM_INT);
						}
					}
					$q2->execute();

					if ($_POST['importtype'] == 'properties') {	// rooms
						foreach ($db->query("SELECT `id`, `rooms` FROM `properties` ORDER BY `id` DESC LIMIT 1") as $row) { // most recent property
							$pid = $row['id'];
							$rooms = $row['rooms'];
						}
						for ($r = 1; $r <= $rooms; $r++) {
							$q = $db->prepare("INSERT INTO `rooms` (`propertyid`, `no`) VALUES ('$pid', '$r')");
							$q->execute();
						}
					}
				}
			} else {
				$importarr = array('name', 'phone1', 'phone2', 'email1', 'email2', 'dob');
				$q2 = $db->prepare("
					INSERT INTO `tenants` (
						`clientid`,
						`name`,
						`phone1`,
						`phone2`,
						`email1`,
						`email2`,
						`dob`
					) VALUES (
						:clientid,
						:name,
						:phone1,
						:phone2,
						:email1,
						:email2,
						:dob
					)
				");

				$q3 = $db->prepare("
					INSERT INTO `tenancies` (
						`tenantid`,
						`roomid`,
						`startdate`,
						`minenddate`,
						`enddate`,
						`period`,
						`rent`,
						`obal`,
						`obaldate`
					) VALUES (
						:tenantid,
						:roomid,
						:startdate,
						:minenddate,
						:enddate,
						:period,
						:rent,
						:obal,
						:obaldate
					)
				");

				$returl = 'tenants.php';

				$csv = file('db/upload/upload'.$_POST['upload']);
				$rows = array_map("str_getcsv", $csv);

				foreach ($rows as $arr) {
					$uarr = array();
					for ($x=0; $x<sizeof($arr); $x++) {
						$uarr[] = $string = preg_replace("/&nbsp;/",'',htmlentities(utf8_encode(trim($arr[$x]))));
					}

					$q2->bindValue(':clientid', $cid);
					for ($x=0; $x<6; $x++) {
						if (strlen($uarr[$x]) > 0 ) {
							if ($x == 5) {
								$q2->bindValue(':'.$importarr[$x], date('Y-m-d', strtotime(str_replace('/', '-', $uarr[$x]))));
							} else {
								$q2->bindValue(':'.$importarr[$x], $uarr[$x]);
							}
						} else {
							$q2->bindValue(':'.$importarr[$x], null, PDO::PARAM_INT);
						}
					}
					$q2->execute();

					// begin tenancy import
					foreach ($db->query("SELECT `id` FROM `tenants` ORDER BY `id` DESC LIMIT 1") as $row) {
						$tid = $row['id'];
					}
					$q3->bindValue(':tenantid', $tid);

					$roomquery = $db->prepare("
						SELECT rooms.id as `rid`
						FROM `rooms` LEFT JOIN `properties`
						ON rooms.propertyid = properties.id
						WHERE properties.sname =?
						AND rooms.no =?
						AND properties.clientid = $clientid
					");
					$roomquery->execute(array($uarr[6], $uarr[7]));
					$roomr = $roomquery->fetch(PDO::FETCH_ASSOC);
					$q3->bindValue(':roomid', $roomr['rid']);

					if (strlen($uarr[8]) > 0 ) {
						$ukify = str_replace('/', '-', $uarr[8]);
						if (strtotime($ukify) != 0) {
							$q3->bindValue(':startdate', date("Y-m-d", strtotime($ukify)));
						} else {
							$q3->bindValue(':startdate', null, PDO::PARAM_INT);
						}
					} else {
						$q3->bindValue(':startdate', null, PDO::PARAM_INT);
					}

					$minenddate = (strtotime("+".$uarr[9], strtotime($uarr[8])) != 0 ? date("Y-m-d", strtotime ("-1 day", strtotime("+".$uarr[9], strtotime($uarr[8])))) : "-");
					if ($minenddate != "-") {
						$q3->bindValue(':minenddate', $minenddate);
					} else {
						$q3->bindValue(':minenddate', null, PDO::PARAM_INT);
					}

					if (strlen($uarr[10]) > 0 ) {
						$ukify = str_replace('/', '-', $uarr[10]);
						if (strtotime($ukify) != 0) {
							$q3->bindValue(':enddate', date("Y-m-d", strtotime($ukify)));
						} else {
							$q3->bindValue(':enddate', null, PDO::PARAM_INT);
						}
					} else {
						$q3->bindValue(':enddate', null, PDO::PARAM_INT);
					}

					$q3->bindValue(':period', strtoupper($uarr[11]));

					$q3->bindValue(':rent', preg_replace("/([^0-9\\.])/i", "", $uarr[12]) * 100);

					if (strlen(preg_replace("/([^0-9\\.])/i", "", $uarr[13])) >= 1) {
						$q3->bindValue(':obal', preg_replace("/([^0-9\\.-])/i", "", $uarr[13]) * 100);
					} else {
						$q3->bindValue(':obal', 0);
					}

					if (isset($_POST['importopeningdate'])) {
						if (strtotime($_POST['importopeningdate']) != 0) {
							$q3->bindValue(':obaldate', date('Y-m-d', strtotime($_POST['importopeningdate'])));
						} else {
							$q3->bindValue(':obaldate', date('Y-m-d'));
						}
					} else {
						$q3->bindValue(':obaldate', date('Y-m-d'));
					}

					$q3->execute();
				}

			}

			echo "<script> document.location = '".$returl."' </script>";
			// echo "mehmehmehmeh";

//================================== TENANTS ========================================

		} else if ($_POST['ajax'] == 'updaterooms') {
			// echo "DOUCHINGTON";
			// print_r ($_POST);
			if (is_numeric($_POST['pid'])) {
				foreach($db->query("SELECT `id`, `no` FROM `rooms` WHERE `del` = 'n' AND `propertyid` = '".$_POST['pid']."'") as $room) {
					echo "<option value=\"".$room['id']."\">".$room['no']."</option>\n";
					// echo "whut<br>";
				}
			}

		} else if ($_POST['ajax'] == 'newstartdate') {
			$ukify = str_replace('/', '-', $_POST['enddate']);
			if (strtotime($ukify) != 0) {
				echo date('j M Y', strtotime('+1 day', strtotime($ukify)));
			}

		} else if ($_POST['ajax'] == 'addtenancy') {
			//oh boy..
			// print "<pre>";
			// print_r($_POST);
			// print "</pre>";

			$q = $db->prepare("
				INSERT INTO `tenancies` (
					`tenantid`,
					`roomid`,
					`startdate`,
					`minenddate`,
					`enddate`,
					`period`,
					`rent`,
					`obal`,
					`obaldate`
				) VALUES (
					:tenantid,
					:roomid,
					:startdate,
					:minenddate,
					:enddate,
					:period,
					:rent,
					:obal,
					:obaldate
				)
			");

			foreach ($_POST as $k => $v) {
				$$k = htmlentities($v);
			}

			// begin validation
			$error = 0;

			// tenantid with client?			
			$q2 = $db->prepare("SELECT `clientid` FROM `tenants` WHERE `id` =?");
			$q2->bindValue (1, $tenantid);
			$q2->execute();
			$r2 = $q2->fetch(PDO::FETCH_ASSOC);
			$q3 = $db->prepare("SELECT `clientid` FROM `users` WHERE `id` = ?");
			$q3->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
			$q3->execute();
			$r3 = $q3->fetch(PDO::FETCH_ASSOC);						
			if ($r2['clientid'] == $r3['clientid']) {
				$q->bindValue(':tenantid', $tenantid);
			} else {
				$error & 64;
			}

			// roomid (no validation needed)
			$q->bindValue(':roomid', $roomid);

			// startdate  
			$ukify = str_replace('/', '-', $startdate);
			if (strtotime($ukify) != 0) {
				$q->bindValue(':startdate', date("Y-m-d", strtotime($ukify)));
			} else {
				$error |= 8;
			}

			// minenddate - changed to min period
			if (in_array($minenddate, array('2', '3', '6', '12'))) {
				if ($error & 8) {
					$q->bindValue(':minenddate', null, PDO::PARAM_INT);
				} else {
					$minenddate = date ("Y-m-d", strtotime ("-1 day", strtotime("+".$minenddate." months", strtotime($startdate))));
					$q->bindValue(':minenddate', $minenddate);
				}
			} else {
				$q->bindValue(':minenddate', null, PDO::PARAM_INT);
			}

			// enddate
			$ukify = str_replace('/', '-', $enddate);
			if (strtotime($ukify) != 0) {
				$q->bindValue(':enddate', date("Y-m-d", strtotime($ukify)));
			} else {
				$q->bindValue(':enddate', null, PDO::PARAM_INT);
			}

			// period
			if (in_array($period, array('W','F','4','M','Q'))) {
				$q->bindValue(':period', $period);
			} else {
				$error |= 16;
			}

			// rent
			if (strlen(preg_replace("/([^0-9\\.])/i", "", $rent)) >= 1) {
				$q->bindValue(':rent', preg_replace("/([^0-9\\.])/i", "", $rent) * 100);
			} else {
				$error |= 32;
			}

			// obal
			if (strlen(preg_replace("/([^0-9\\.])/i", "", $obal)) >= 1) {
				$q->bindValue(':obal', preg_replace("/([^0-9\\.-])/i", "", $obal) * 100);
			} else {
				$q->bindValue(':obal', 0);
			}

			// obaldate
			$ukify = str_replace('/', '-', $obaldate);
			if (strtotime($ukify) != 0) {
				$q->bindValue(':obaldate', date("Y-m-d", strtotime($ukify)));
			} else {
				$q->bindValue(':obaldate', null, PDO::PARAM_INT);
			}

			if ($error != 0) {
				echo "<p class=\"error\">ERROR</p>";
				if ($error & 64) {
					echo "<p>Invalid tenant ID.</p>\n";
				}
				if ($error & 8) {
					echo "<p>Invalid start date.</p>\n";
				}
				if ($error & 16) {
					echo "<p>'Payment period' is invalid.</p>\n";
				}
				if ($error & 32) {
					echo "<p>'Rental amount' is either empty or invalid.</p>\n";
				}				
			} else {
				$q->execute();

				foreach ($db->query("SELECT `id` FROM `tenancies` ORDER BY `id` DESC LIMIT 1") as $trow) {
					echo "<input type=\"hidden\" name=\"addtenancy\" value=\"".$trow['id']."\" /> ";
				}
				
				//fee description info
				// $q4 = $db->prepare("
					// SELECT
						// tt.tenantname AS `name`,
						// rooms.no AS `room`,
						// CONCAT (properties.no,' ',properties.street) AS `property`,
						// properties.id AS `propertyid`
					// FROM (
						// SELECT
							// tenancies.id AS `tenancyid`,
							// tenancies.roomid AS `roomid`,
							// tenants.name AS `tenantname`
						// FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
					// LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
					// WHERE tt.tenancyid = ?	
				// ");
				$q4 = $db->prepare("
					SELECT
						tt.tenantname AS `name`,
						rooms.no AS `room`,
						mp.address AS `property`,
						mp.pid AS `propertyid`,
						mp.mcid AS `mcid`
					FROM (
						SELECT
							tenancies.id AS `tenancyid`,
							tenancies.roomid AS `roomid`,
							tenants.name AS `tenantname`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
					LEFT JOIN (
						`rooms` LEFT JOIN (
							SELECT
								properties.id AS `pid`,
								CONCAT (properties.no,' ',properties.street) AS `address`,
								mgmtcontracts.id AS `mcid`
							FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id
							ORDER BY (mgmtcontracts.enddate IS NOT NULL), mgmtcontracts.enddate DESC
						) mp ON rooms.propertyid = mp.pid
					) ON tt.roomid = rooms.id
					WHERE tt.tenancyid = ?	
				");
				
				// app fee check
				if (strlen(preg_replace("/([^0-9\\.])/i","", $appfee)) != 0 && number_format($appfee) != 0) {
					$q4->execute(array($trow['id']));
					$r4 = $q4->fetch();
					$q5 = $db->prepare("
						INSERT INTO `tenantfees` (
							`tenancyid`,
							`date`,
							`amount`,
							`payableto`,
							`desc`
						) VALUES (
							?,
							?,
							?,
							?,
							?				
						)
					");
					$q5->execute(array(
						$trow['id'],
						date("Y-m-d", strtotime(str_replace('/', '-', $startdate))),
						preg_replace("/([^0-9\\.-])/i", "", $appfee) * 100,
						'c',
						"Application fee for ".$r4['name'].", Room ".$r4['room'].", ".$r4['property']
					));
					$feeid = $db->lastInsertId();
					$q6 = $db->prepare("UPDATE `tenancies` SET `appfeeid` = ? WHERE `id` = ?");
					$q6->execute(array($feeid, $trow['id']));
				}
				

				// let fee check
				if (strlen(preg_replace("/([^0-9\\.])/i","", $letfee)) != 0 && number_format($letfee) != 0) {
					$q4->execute(array($trow['id']));
					$rc4 = $q4->rowCount();
					if ($rc4 >= 1) { 
						// will fail if no mgmtcontract set. if multiple:-
						// 		- if only one MC without an enddate, it will pick that
						//		- if all MCs have enddates, it will pick the most recent one
						//		- if multiple MCs have no enddates, a random one will be picked
						if ($rc4 > 1) {
							//display notice explaining the above, advise to check landlord report
							//probably worth only doing this when multiple MCs have no enddates or latest enddate is duplicated. good luck with that one...
						}
						$r4 = $q4->fetch();
						$q5 = $db->prepare("
							INSERT INTO `propertyfees` (
								`mcid`, 
								`date`,
								`amount`,
								`desc`,
								`type`
							) VALUES (
								?,
								?,
								?,
								?,
								?
							)
						");
						$q5->execute(array(
							$r4['mcid'],
							date("Y-m-d", strtotime(str_replace('/', '-', $startdate))),
							preg_replace("/([^0-9\\.-])/i", "", $letfee) * 100,
							"Letting fee for ".$r4['name'].", room ".$r4['room'].", ".$r4['property'],
							"l"
						));
						$feeid = $db->lastInsertId();
						$q6 = $db->prepare("UPDATE `tenancies` SET `letfeeid` = ? WHERE `id` = ?");
						$q6->execute(array($feeid, $trow['id']));
					} else {
						//fail
					}
				}
				

				echo "<script> $('#submit').click(); </script>";
				// print "<pre>";
				// print_r($_POST);
				// print "</pre>";
				// echo "<p>click submit</p>";
			}
			
		} else if ($_POST['ajax'] == 'addtenantfee') {

			$q = $db->prepare("SELECT tenancies.id FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id WHERE tenancies.id = ? AND tenants.clientid = $clientid");
			if (!isset($_POST['tenancy'])) {
				$_POST['tenancy'] = 0;
			}
			$q->execute(array($_POST['tenancy']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q = $db->prepare("
					INSERT INTO `tenantfees` (
						`tenancyid`,
						`date`,
						`amount`,
						`payableto`,
						`desc`
					) VALUES (
						:tenancyid,
						:date,
						:amount,
						:payableto,
						:desc					
					)
				");
				$q->bindValue(':tenancyid', $_POST['tenancy']);
				$error = 0;
				
				if (strtotime($_POST['date']) != 0) {
					$q->bindValue(':date', date('Y-m-d', strtotime($_POST['date'])));
				} else {
					$error |= 1;
				}
				
				if (is_numeric(round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)))) {
					$q->bindValue(':amount', round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)));;
				} else {
					$error |= 2;
				}
				
				if ($_POST['ptl'] == 'l') {
					$q->bindValue(':payableto', 'l');
				} else {
					$q->bindValue(':payableto', 'c');
				}
				
				if (strlen(trim(htmlentities(utf8_encode($_POST['desc'])))) != 0) {
					$q->bindValue(':desc', htmlentities(utf8_encode($_POST['desc'])));
				} else {
					$error |= 4;
				}
				
				if ($error == 0) {
					$q->execute();
					echo '<script> document.location.reload(true); </script>';
					// print "<pre>";
					// print_r($_POST);
					// print "</pre>";
				} else {
					echo "<p class=\"error\">ERROR</p>";
					if ($error & 1) {
						echo "<p class=\"error\">Invalid date.</p>";
					}
					if ($error & 2) {
						echo "<p class=\"error\">Invalid amount.</p>";
					}
					if ($error & 4) {
						echo "<p class=\"error\">Description is empty.</p>";
					}
				}				
			} else {
				echo "<p class=\"error\">ERROR: Invalid tenancy ID</p>";
			}
			
		} else if ($_POST['ajax'] == 'addadjustment') {

			// p($_POST);
			$q = $db->prepare("SELECT tenancies.id FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id WHERE tenancies.id = ? AND tenants.clientid = $clientid");
			if (!isset($_POST['tenancy'])) {
				$_POST['tenancy'] = 0;
			}
			$q->execute(array($_POST['tenancy']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q = $db->prepare("
					INSERT INTO `adjustments` (
						`tenancyid`,
						`date`,
						`amount`,
						`applymgmt`,
						`desc`
					) VALUES (
						:tenancyid,
						:date,
						:amount,
						:applymgmt,
						:desc					
					)
				");
				$q->bindValue(':tenancyid', $_POST['tenancy']);
				$error = 0;
				
				if (strtotime($_POST['date']) != 0) {
					$q->bindValue(':date', date('Y-m-d', strtotime($_POST['date'])));
				} else {
					$error |= 1;
				}
				
				if (is_numeric(round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)))) {
					$q->bindValue(':amount', round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)));;
				} else {
					$error |= 2;
				}
				
				if ($_POST['amf'] == 'y') {
					$q->bindValue(':applymgmt', 'y');
				} else {
					$q->bindValue(':applymgmt', 'n');
				}
				
				if (strlen(trim(htmlentities(utf8_encode($_POST['desc'])))) != 0) {
					$q->bindValue(':desc', htmlentities(utf8_encode($_POST['desc'])));
				} else {
					$error |= 4;
				}
				
				if ($error == 0) {
					$q->execute();
					echo '<script> document.location.reload(true); </script>';
					// print "<pre>";
					// print_r($_POST);
					// print "</pre>";
				} else {
					echo "<p class=\"error\">ERROR</p>";
					if ($error & 1) {
						echo "<p class=\"error\">Invalid date.</p>";
					}
					if ($error & 2) {
						echo "<p class=\"error\">Invalid amount.</p>";
					}
					if ($error & 4) {
						echo "<p class=\"error\">Description is empty.</p>";
					}
				}				
			} else {
				echo "<p class=\"error\">ERROR: Invalid tenancy ID</p>";
			}

// ================================== LANDLORDS ========================================

		} else if ($_POST['ajax'] == 'addmc') {
			//oh boy..
			// print "<pre>";
			// print_r($_POST);
			// print "</pre>";

			$q = $db->prepare("
				INSERT INTO `mgmtcontracts` (
					`landlordid`,
					`propertyid`,
					`startdate`,
					`enddate`,
					`mgmt`,
					`lease`,
					`obal`,
					`obaldate`
				) VALUES (
					:landlordid,
					:propertyid,
					:startdate,
					:enddate,
					:mgmt,
					:lease,
					:obal,
					:obaldate
				)
			");

			foreach ($_POST as $k => $v) {
				$$k = htmlentities($v);
			}

			// begin validation
			$error = 0;
		
			$q2 = $db->prepare("SELECT `clientid` FROM `landlords` WHERE `id` =?");
			$q2->bindValue (1, $landlordid);
			$q2->execute();
			$r2 = $q2->fetch(PDO::FETCH_ASSOC);
			$q3 = $db->prepare("SELECT `clientid` FROM `users` WHERE `id` = ?"); //wtf
			$q3->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
			$q3->execute();
			$r3 = $q3->fetch(PDO::FETCH_ASSOC);						
			if ($r2['clientid'] == $r3['clientid']) {
				$q->bindValue(':landlordid', $landlordid);
			} else {
				$error & 64;
			}

			// propertyid (no validation needed.. i don't think?)
			$q->bindValue(':propertyid', $propertyid);

			// startdate  
			$ukify = str_replace('/', '-', $startdate);
			if (strtotime($ukify) != 0) {
				$q->bindValue(':startdate', date("Y-m-d", strtotime($ukify)));
			} else {
				$error |= 8;
			}

			// enddate
			$ukify = str_replace('/', '-', $enddate);
			if (strtotime($ukify) != 0) {
				$q->bindValue(':enddate', date("Y-m-d", strtotime($ukify)));
			} else {
				$q->bindValue(':enddate', null, PDO::PARAM_INT);
			}

			// mgmt
			$mgmt = preg_replace("/([^0-9\\.])/i", "", $mgmt);
			if (strlen($mgmt == 0)) {
				$mgmt = 100;
			}
			if ($mgmt >= 0 && $mgmt <= 100) {
				$q->bindValue(':mgmt', $mgmt);
			} else {
				$error |= 16;
			}

			// rent
			if (strlen($lease == 0)) {
				$lease = 0;	//default if none entered
			}
			if (strlen(preg_replace("/([^0-9\\.])/i", "", $lease)) >= 1) {
				$q->bindValue(':lease', preg_replace("/([^0-9\\.])/i", "", $lease) * 100);
			} else {
				$error |= 32;
			}

			// obal
			if (strlen(preg_replace("/([^0-9\\.])/i", "", $obal)) >= 1) {
				$q->bindValue(':obal', preg_replace("/([^0-9\\.-])/i", "", $obal) * 100);
			} else {
				$q->bindValue(':obal', 0);
			}

			// obaldate
			$ukify = str_replace('/', '-', $obaldate);
			if (strtotime($ukify) != 0) {
				$q->bindValue(':obaldate', date("Y-m-d", strtotime($ukify)));
			} else {
				$q->bindValue(':obaldate', null, PDO::PARAM_INT);
			}

			if ($error != 0) {
				echo "<p class=\"error\">ERROR</p>";
				if ($error & 64) {
					echo "<p>Invalid landlord ID.</p>\n";
				}
				if ($error & 8) {
					echo "<p>Invalid start date.</p>\n";
				}
				if ($error & 16) {
					echo "<p>Management % must be between 0 and 100.</p>\n";
				}
				if ($error & 32) {
					echo "<p>Invalid lease amount.</p>\n";
				}				
			} else {
				$q->execute();

				foreach ($db->query("SELECT `id` FROM `mgmtcontracts` ORDER BY `id` DESC LIMIT 1") as $mcrow) {
					echo "<input type=\"hidden\" name=\"addmc\" value=\"".$mcrow['id']."\" /> ";
				}				

				echo "<script> $('#submit').click(); </script>";
				// print "<pre>";
				// print_r($_POST);
				// print "</pre>";
				// echo "<p>click submit</p>";
			}		
			
// ================================== PROPERTIES  ========================================

		} else if ($_POST['ajax'] == 'addpropertyfee') {
			$q = $db->prepare("SELECT * FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id WHERE mgmtcontracts.id = ? AND properties.clientid = $clientid ");
			if (!isset($_POST['mc'])) {
				$_POST['mc'] = 0;
			}			
			$q->execute(array($_POST['mc']));	
			$rc = $q->rowCount();
			if ($rc == 1) {
				$error = 0;
				$r = $q->fetch();
				$q = $db->prepare("
					INSERT INTO `propertyfees` (
						`mcid`,
						`date`,
						`amount`,
						`desc`,
						`type`
					) VALUES (
						:mcid,
						:date,
						:amount,
						:desc,
						:type
					)
				");
				$q->bindValue(':mcid', $_POST['mc']);
				
				if (strtotime($_POST['date']) != 0) {
					$q->bindValue(':date', date('Y-m-d', strtotime($_POST['date'])));
				} else {
					$error |= 2;
				}
				
				if (is_numeric(round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)))) {
					$q->bindValue(':amount', round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)));;
				} else {
					$error |= 4;
				}
				
				if (strlen(trim(htmlentities(utf8_encode($_POST['desc'])))) != 0) {
					$q->bindValue(':desc', htmlentities(utf8_encode($_POST['desc'])));
				} else {
					$error |= 8;
				}
				
				if ($_POST['type'] == 'm') {
					$q->bindValue(':type', 'm');
				} else {
					$q->bindValue(':type', 'o');
				}
				
				if ($error == 0) {
					$q->execute();
					echo '<script> document.location.reload(true); </script>';
					// echo 'bound 4 da reload';
				} else {
					echo "<p class=\"error\">ERROR</p>";
					if ($error & 2) {
						echo "<p class=\"error\">Invalid date.</p>";
					}
					if ($error & 4) {
						echo "<p class=\"error\">Invalid amount.</p>";
					}
					if ($error & 8) {
						echo "<p class=\"error\">Description is empty.</p>";
					}
				}				
			} else {
				echo "<p class=\"error\">ERROR: Invalid management contract ID.</p>";
			}
			
		} else if ($_POST['ajax'] == 'editpropertyfee') {		
			$q = $db->prepare("SELECT * FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id WHERE propertyfees.id = ? AND properties.clientid = $clientid");
			$id = (isset($_POST['id']) ? $_POST['id'] : 0);
			$q->execute(array($id));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$error = 0;
				// i've written this whilst tired and a little tipsy. it will look at each field for errors and submit the ones without errors. this probably isn't the best way to do it (should only submit everything when no errors) but it will do as i can't think of a neat way to do it properly
				if (strtotime($_POST['date']) != 0) {
					$q1 = $db->prepare("UPDATE `propertyfees` SET `date` = ? WHERE `id` = $id");
					$q1->execute(array(date('Y-m-d', strtotime($_POST['date']))));
				} else {
					$error |= 2;
				}
				if (is_numeric(round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100)))) {
					$q2 = $db->prepare("UPDATE `propertyfees` SET `amount` = ? WHERE `id` = $id");
					$q2->execute(array(round(preg_replace("/([^0-9\\.-])/i", "", $_POST['amount'] * 100))));;
				} else {
					$error |= 4;
				}
				if (strlen(trim(htmlentities(utf8_encode($_POST['desc'])))) != 0) {
					$q3 = $db->prepare("UPDATE `propertyfees` SET `desc` = ? WHERE `id` = $id");
					$q3->execute(array(htmlentities(utf8_encode($_POST['desc']))));
				} else {
					$error |= 8;
				}
				
				if ($_POST['type'] == 'm') {
					$q4 = $db->prepare("UPDATE `propertyfees` SET `type` = 'm' WHERE `id` = $id");
					$q4->execute();
				} else if ($_POST['type'] == 'l') {	
					//do nothing
				} else {
					$q4 = $db->prepare("UPDATE `propertyfees` SET `type` = 'o' WHERE `id` = $id");
					$q4->execute();
				}
				
				if ($error == 0) {
					echo '<script> document.location.reload(true); </script>';
				} else {
					echo "<p class=\"error\">ERROR</p>";
					if ($error & 2) {
						echo "<p class=\"error\">Invalid date.</p>";
					}
					if ($error & 4) {
						echo "<p class=\"error\">Invalid amount.</p>";
					}
					if ($error & 8) {
						echo "<p class=\"error\">Description is empty.</p>";
					}
				}									
				
			} else {
				echo "<p class=\"error\">ERROR: Invalid item ID.</p>";
			}

		} else if ($_POST['ajax'] == 'deletepropertyfee') {
			$q = $db->prepare("SELECT propertyfees.type AS `type` FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id WHERE propertyfees.id = ? AND properties.clientid = $clientid");
			$id = (isset($_POST['id']) ? $_POST['id'] : 0);
			$q->execute(array($id));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				if ($r['type'] != 'l') {
					$q = $db->prepare("DELETE FROM `propertyfees` WHERE `id` = ?");
					$q->execute(array($id));
					echo '<script> document.location.reload(true); </script>';
				} else {
					echo "<p class=\"error\">ERROR: Letting fees can only be removed by unticking the box in the tenancy details.</p>";
				}
			} else {
				echo "<p class=\"error\">ERROR: Invalid item ID.</p>";
			}			
			

//==================================== BANK ======================================

		} else if ($_POST['ajax'] == 'updatebankaccountorder') {
			$q = $db->prepare("UPDATE `bankaccounts` SET `order` =? WHERE `id` =?");
			$q->execute(array($_POST['order'], $_POST['account']));
			// echo "Bank account id ".$_POST['account']." order changed to ".$_POST['order']."<br>";
			
		} else if ($_POST['ajax'] == 'addnewbankaccount') {
			if (strlen(trim($_POST['name'])) > 0) {
				$_POST['clientid'] = $clientid;
				$q = $db->prepare("
					INSERT INTO `bankaccounts` (
						`name`,
						`bank_s`,
						`bank_l`,
						`notes`,
						`order`,
						`clientid`
					) VALUES (
						:name,
						:bank_s,
						:bank_l,
						:notes,
						:order,
						:clientid
					)
				");
				foreach ($_POST as $k => $v) {
					if ($k != 'ajax') {
						$$k = preg_replace("/&nbsp;/",'',htmlentities(utf8_encode(trim($v))));
						$q->bindValue(':'.$k, $$k);
					}
				}
				$q->execute();
				echo '<script> document.location.reload(true); </script>';
			} else {
				//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ name blank, no action, display error
			}
		
		} else if ($_POST['ajax'] == 'amendbankaccount') {		
			$colsarr = array('name', 'bank_s', 'bank_l', 'notes');
			$id = $_POST['id'];
			if (strlen(trim($_POST['name'])) > 0 ) {
				foreach ($_POST as $k => $v) {
					if ($k != 'ajax' && $k != 'id' && in_array($k, $colsarr)){
						$q = $db->prepare("UPDATE `bankaccounts` SET `$k` = ? WHERE `id` = ? ");
						$q->execute(array(preg_replace("/&nbsp;/",'',htmlentities(utf8_encode(trim($v)))), $id));
					}
				}
				echo '<script> document.location.reload(true); </script>';
			} else {
				//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ name blank, no action, display error
				//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ name blank, no action, display error
			}
		
		} else if ($_POST['ajax'] == 'deletenewstatement') {
			unlink('db/statements/'.$clientid.'/'.$_POST['filename']);
			echo '<script> document.location.reload(true); </script>';
			// echo 'db/statements/'.$clientid.'/'.$_POST['filename'];
			
		} else if ($_POST['ajax'] == 'loadstatement') {
			$q2 = $db->prepare("
				SELECT
					statements.importdate AS `importdate`,
					bankaccounts.name as `name`,
					statementlines.id AS `id`,
					statementlines.date AS `date`,
					statementlines.desc AS `desc`,
					statementlines.amount AS `amount`,
					statementlines.status AS `status`,
					statementlines.generation AS `gen`
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statements.id = ?
				AND statementlines.generation != 'c'
				AND bankaccounts.clientid = $clientid
			");			
			$q2->execute(array($_POST['id']));
			$r2 = $q2->fetchAll(PDO::FETCH_ASSOC);
			// p($r2);
			echo "<h2>Statement for account ".$r2[0]['name']." imported on ".date('j M Y', strtotime($r2[0]['importdate']))."</h3>";
			echo "<table class=\"statementpreview\">";
			echo "	<thead>";
			echo "		<tr>";
			echo "			<th>Date</th>";
			echo "			<th>Description</th>";
			echo "			<th>Amount</th>";
			echo "		</tr>";
			echo "	</thead>";
			echo "	<tbody>";
			$r2count = 0;
			foreach ($r2 as $row) {
				$r2count++;
				if ($row['status'] == 'd') {
					echo "		<tr class=\"ignoreline dupeline previewline\">";
				} else if ($row['status'] == 'r') {
					echo "		<tr class=\"reconciledline previewline\">";
				} else if ($row['status'] == 'i') {
					echo "		<tr class=\"ignoreline previewline\">";
				} else if ($row['gen'] == 'n') {		//assumed status is 'u' for this and next
					echo "		<tr class=\"previewline\">";
				} else if ($row['gen'] == 'p') {
					//search for reconciled children. if found, line will be orange, if not, will be normal
					$q3 = $db->prepare("SELECT payments.id AS `id` FROM `payments` LEFT JOIN `statementlines` ON payments.statementlineid = statementlines.id WHERE statementlines.parentid = ?");
					$q3->execute(array($row['id']));
					$rc3 = $q3->rowCount();
					if ($rc3 == 0) {
						echo "		<tr class=\"previewline\">";
					} else {
						$r3 = $q3->fetchAll(PDO::FETCH_ASSOC);
						echo "		<tr class=\"partreconciledline previewline\">";
					}
				} 
				echo "			<input type=\"hidden\" class=\"previewlineid\" value=\"".$row['id']."\" />";
				echo "			<td>".date ('j M Y', strtotime($row['date']))."</td>";
				echo "			<td>";
				echo "				".$row['desc'];
				echo "				<div class=\"dropdown\">";
				echo "					<div class=\"submenu\" id=\"submenu".$r2count."\">";
				echo "						<ul class=\"root\">";
				if ($row['status'] == 'd') {
					echo "							<li class=\"markdupe\">Click to unmark this line as a duplicate</li>";
				} else if ($row['status'] == 'r') {
					if ($row['gen'] == 'n') {
						// query payment 
						$q4 = $db->prepare("SELECT payments.id AS `id` FROM `payments` LEFT JOIN `statementlines` ON payments.statementlineid = statementlines.id WHERE statementlines.id = ?");
						$q4->execute(array($row['id']));
						$r4 = $q4->fetch();
						echo "							<li class=\"loadpayment\"><a href=\"bank.php?s=detail&d=".$r4['id']."\">Click to load payment #".$r4['id']."</a></li>";
					} else {
						//query ALL THE PAYMENTS
						$q4 = $db->prepare("SELECT payments.id AS `id` FROM `payments` LEFT JOIN `statementlines` ON payments.statementlineid = statementlines.id WHERE statementlines.parentid = ?");
						$q4->execute(array($row['id']));
						$r4 = $q4->fetchAll(PDO::FETCH_ASSOC);
						foreach ($r4 as $row4) {
							echo "							<li class=\"loadpayment\"><a href=\"bank.php?s=detail&d=".$r4['id']."\">Click to load payment #".$r4['id']."</a></li>";
						}
					}
				} else if ($row['status'] == 'i') { 
					echo "							<li class=\"ignore\">Click to unignore this line</li>";
				} else if ($row['gen'] == 'n') {		//status is 'u'
					echo "							<li class=\"ignore\">Click to ignore this line</li>";
					echo "							<li class=\"markdupe\">Click to mark this line as a duplicate</li>";
				} else if ($row['gen'] == 'p') {
					if ($rc3 == 0) {
						echo "							<li class=\"ignore\">Click to ignore this line</li>";
						echo "							<li class=\"markdupe\">Click to mark this line as a duplicate</li>";
					} else {
						foreach ($r3 as $row3) {
							echo "							<li class=\"loadpayment\"><a href=\"bank.php?s=detail&d=".$row3['id']."\">Click to load payment #".$row3['id']."</a></li>";
						}
					}
				} 
					
				echo "						</ul>";
				echo "					</div>";
				echo "				</div>";			
				echo "			</td>";
				echo "			<td>".number_format(preg_replace("/([^0-9\\.-])/i", "", $row['amount'] / 100), 2, '.', ',')."</td>";
				echo "		</tr>";
			}
			echo "	</tbody>";
			echo "</table>";
			echo "<p>";
			echo "	<input type=\"hidden\" id=\"delstatementid\" value=\"".$_POST['id']."\" /> ";
			echo "	<input type=\"button\" class=\"button\" id=\"deletestatement\" value=\"Delete statement\" />";
			echo "</p>";
			
		} else if ($_POST['ajax'] == 'markdupe') {
			$q = $db->prepare("
				SELECT
					statementlines.id AS `id`,
					statementlines.status AS `status`
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statementlines.id = ?
				AND bankaccounts.clientid = $clientid
			");			
			$q->execute(array($_POST['id']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q2 = $db->prepare("UPDATE `statementlines` SET `status` = ? WHERE `id` = ?");
				if ($r['status'] == 'u') {
					$q2->execute(array('d', $r['id']));
					echo "d";
				} else if ($r['status'] == 'd') {
					$q2->execute(array('u', $r['id']));
					echo "u";
				}
			} else {
				echo "<span class=\"error\">ERROR: Invalid statement line id </span>";
			}
			
		} else if ($_POST['ajax'] == 'ignore') {
			$q = $db->prepare("
				SELECT
					statementlines.id AS `id`,
					statementlines.status AS `status`
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statementlines.id = ?
				AND bankaccounts.clientid = $clientid
			");	
			$q->execute(array($_POST['id']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q2 = $db->prepare("UPDATE `statementlines` SET `status` = ? WHERE `id` = ?");
				if ($r['status'] == 'u') {
					$q2->execute(array('i', $r['id']));
					echo "i";
				} else if ($r['status'] == 'i') {
					$q2->execute(array('u', $r['id']));
					echo "u";
				}
			} else {
				echo "<span class=\"error\">ERROR: Invalid statement line id </span>";
			}
			
		} else if ($_POST['ajax'] == 'reconignore') {
			$gen = ($_POST['gen'] == 'c' ? 'c' : 'n');
			$q = $db->prepare("
				SELECT
					statementlines.id AS `id`
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statementlines.id = ?
				AND bankaccounts.clientid = $clientid
			");	
			$q->execute(array($_POST['id']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q2 = $db->prepare("UPDATE `statementlines` SET `status` = 'i' WHERE `id` = ?");
				$q2->execute(array($r['id']));			
				if ($gen == 'c') {
					$child = $_POST['id'];
					$q = $db->prepare("SELECT `parentid` FROM `statementlines` WHERE `id` = ?");
					$q->execute(array($child));
					$r = $q->fetch();
					$parent = $r['parentid'];
					$q = $db->prepare("SELECT * FROM `statementlines` WHERE `parentid` = ? AND `status` = 'u'");
					$q->execute(array($parent));
					$rc = $q->rowCount();
					if ($rc == 0) { // if c we CAN reconcile p!
						$q = $db->prepare("UPDATE `statementlines` SET `status` = 'r' WHERE `id` = ?");
						$q->execute(array($parent));
					}
				}
				// echo '<script> document.location.reload(true); </script>';
	
				if ($gen == 'c') {				
					echo "<input type=\"hidden\" class=\"recongen\" value=\"c\" />\n";
				} else {
					echo "<input type=\"hidden\" class=\"recongen\" value=\"n\" />\n";
				}
				echo "<input type=\"hidden\" class=\"reconlineid\" value=\"".$_POST['id']."\" />\n";
				if ($gen == 'c') {	
					echo "<span class=\"ignoredchild\" >Split line ignored. <a class=\"reconundo\" href=\"javascript:void(0)\">Click here to unignore</a>.</span>\n";
					echo "<input class=\"reconsplitamount\" value=\"".number_format(preg_replace("/([^0-9\\.-])/i", "", $_POST['samount'] ), 2, '.', ',')."\" disabled=\"disabled\" /> <input type=\"button\" class=\"button rsdisabled dischmabled\" value=\"Split\" />";
				} else {
					echo "<span class=\"ignoredchild\" >Line ignored. <a class=\"reconundo\" href=\"javascript:void(0)\">Click here to unignore</a>.</span>\n";
				}
			} else {
				echo "<span class=\"error\">ERROR: Invalid statement line id </span>";
			}
			
		} else if ($_POST['ajax'] == 'reconundo') {
			// p($_POST);
			$q = $db->prepare("
				SELECT
					statementlines.id AS `id`,
					statementlines.generation AS `generation`,
					statementlines.parentid AS `parentid`
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statementlines.id = ?
				AND bankaccounts.clientid = $clientid
			");	
			$q->execute(array($_POST['id']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q2 = $db->prepare("UPDATE `statementlines` SET `status` = 'u' WHERE `id` = ?");
				$q2->execute(array($r['id']));
				if ($r['generation'] == 'c') {
					$q2->execute(array($r['parentid']));
				}
				
				$q3 = $db->prepare("DELETE FROM `payments` WHERE `statementlineid` = ? ");
				$q3->execute(array($r['id']));
				
				echo '<script> document.location.reload(true); </script>';
			} else {
				echo "<span class=\"error\">ERROR: Invalid statement line id </span>";
			}
			

		
		} else if ($_POST['ajax'] == 'split') {
			// echo "received ajax post <br>";
			// check we own the bank account
			$q = $db->prepare("
				SELECT
					statementlines.statementid AS `statementid`,
					statementlines.date AS `date`,
					statementlines.desc AS `desc`,
					statementlines.hash AS `hash`
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statementlines.id = ?
				AND bankaccounts.clientid = $clientid
			");
			$q->execute(array($_POST['id']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				if ($_POST['gen'] == 'n') {
					// change to parent
					$q = $db->prepare("UPDATE `statementlines` SET `generation` = 'p' WHERE `id` = ?");
					$q->execute(array($_POST['id']));
					// create 2 children
					// child 1...
					$amount = $_POST['splitamount'] * 100;
					$q = $db->prepare("
						INSERT INTO `statementlines` (
							`statementid`,
							`date`,
							`desc`,
							`hash`,
							`amount`,
							`generation`,
							`parentid`
						) VALUES (
							:statementid,
							:date,
							:desc,
							:hash,
							:amount,
							:generation,
							:parentid							
						)
					");
					$q->bindValue(':statementid', $r['statementid']);
					$q->bindValue(':date', $r['date']);
					$q->bindValue(':desc', $r['desc']);
					$q->bindValue(':hash', $r['hash']);
					$q->bindValue(':amount', $amount);
					$q->bindValue(':generation', 'c');
					$q->bindValue(':parentid', $_POST['id']);
					$q->execute();
					// child 2... (this is massively inefficient code! to come back to)
					$amount = ($_POST['ltotal'] - $_POST['splitamount']) * 100;
					$q = $db->prepare("
						INSERT INTO `statementlines` (
							`statementid`,
							`date`,
							`desc`,
							`hash`,
							`amount`,
							`generation`,
							`parentid`
						) VALUES (
							:statementid,
							:date,
							:desc,
							:hash,
							:amount,
							:generation,
							:parentid							
						)
					");
					$q->bindValue(':statementid', $r['statementid']);
					$q->bindValue(':date', $r['date']);
					$q->bindValue(':desc', $r['desc']);
					$q->bindValue(':hash', $r['hash']);
					$q->bindValue(':amount', $amount);
					$q->bindValue(':generation', 'c');
					$q->bindValue(':parentid', $_POST['id']);
					$q->execute();					
				} else { // child
					// change total
					$amount = $_POST['splitamount'] * 100;
					$q = $db->prepare("UPDATE `statementlines` SET `amount` = ? WHERE `id` = ?");
					$q->execute(array($amount, $_POST['id']));
					// create new child, first finding parentid
					$q2 = $db->prepare("SELECT `parentid` FROM `statementlines` WHERE `id` = ?");
					$q2->execute(array($_POST['id']));
					$r2 = $q2->fetch();
					$amount = ($_POST['ltotal'] - $_POST['stotal']) * 100;
					$q = $db->prepare("
						INSERT INTO `statementlines` (
							`statementid`,
							`date`,
							`desc`,
							`hash`,
							`amount`,
							`generation`,
							`parentid`
						) VALUES (
							:statementid,
							:date,
							:desc,
							:hash,
							:amount,
							:generation,
							:parentid							
						)
					");
					$q->bindValue(':statementid', $r['statementid']);
					$q->bindValue(':date', $r['date']);
					$q->bindValue(':desc', $r['desc']);
					$q->bindValue(':hash', $r['hash']);
					$q->bindValue(':amount', $amount);
					$q->bindValue(':generation', 'c');
					$q->bindValue(':parentid', $r2['parentid']);
					$q->execute();							
				}
				echo '<script> document.location.reload(true); </script>'; //not the nicest way but it'll do
			} else {
				echo "<span class=\"error\">ERROR: Invalid statement line id </span>";
			}
			
		} else if ($_POST['ajax'] == 'unsplit') {
			$q = $db->prepare("
				SELECT
					statementlines.parentid
				FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
				WHERE statementlines.id = ?
				AND bankaccounts.clientid = $clientid				
			");
			$q->execute(array($_POST['child']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$children = array();
				foreach ($db->query("SELECT `id` FROM `statementlines` WHERE `parentid` = ".$r['parentid']) as $child) {
					$children[] = $child['id'];
				}
				$q = $db->prepare("DELETE FROM `payments` WHERE `statementlineid` = ?");
				foreach ($children as $childid) {
					$q->execute(array($childid));
				}
				$q = $db->prepare("DELETE FROM `statementlines` WHERE `parentid` = ?");
				$q->execute(array($r['parentid']));
				$q = $db->prepare("UPDATE `statementlines` SET `generation` = 'n' WHERE `id` = ?");
				$q->execute(array($r['parentid']));
				echo '<script> document.location.reload(true); </script>';
			} else {
				echo "<span class=\"error\">ERROR: Invalid statement line id </span>";
			}
		
		} else if ($_POST['ajax'] == 'reconcile') {
			$gen = ($_POST['gen'] == 'c' ? 'c' : 'n');
			if ($gen == 'n') {
				// $amount = round($_POST['lamount'] * 100);
				$amount = round(preg_replace("/([^0-9\\.-])/i", "", $_POST['lamount']) * 100);
			} else { // check split total
				$child = $_POST['sline'];
				$q = $db->prepare("SELECT `parentid` FROM `statementlines` WHERE `id` = ?");
				$q->execute(array($child));
				$r = $q->fetch();
				$parent = $r['parentid'];
				$q = $db->prepare("SELECT `amount` FROM `statementlines` WHERE `parentid` = ?");
				$q->execute(array($parent));
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
				$splittotal = 0;
				foreach ($r as $row) {
					$splittotal += $row['amount'];
				}
				if ($splittotal == round($_POST['lamount'] * 100)) {
					$amount = round($_POST['samount'] * 100);
				} else {
					$error = 1;
				}
			}			
			foreach (array($_POST['sline'], $_POST['ctype'], $_POST['cid'], $_POST['ldate']) as $field) {
				if (strlen($field) == 0) {
					$error = 1;
				}
			}			
			if (!isset($error)) {
				$q = $db->prepare("
					INSERT INTO `payments` (
						`statementlineid`,
						`contacttype`,
						`contactid`,
						`amount`,
						`date`
					) VALUES (
						:statementlineid,
						:contacttype,
						:contactid,
						:amount,
						:date						
					)
				");				
				$q->bindValue(':statementlineid', $_POST['sline']);				
				if ($_POST['ctype'] != 'l' && $_POST['ctype'] != 'f') {
					$_POST['ctype'] = 't';
				}
				$q->bindValue(':contacttype', $_POST['ctype']);								
				$q->bindValue(':contactid', $_POST['cid']);
				$q->bindValue(':amount', $amount);				
				// $q->bindValue(':amount', preg_replace("/([^0-9\\.-])/i", "", $amount));				
				if (strtotime($_POST['pdate']) != 0) {
					$date = date('Y-m-d', strtotime($_POST['pdate']));
				} else {
					$date = date('Y-m-d', strtotime($_POST['ldate']));
				}
				$q->bindValue(':date', $date);		
				$q->execute();
				$q = $db->prepare("UPDATE `statementlines` SET `status` = 'r' WHERE `id` = ?");
				$q->execute(array($_POST['sline']));
				if ($gen == 'c') {
					$q = $db->prepare("SELECT * FROM `statementlines` WHERE `parentid` = ? AND `status` = 'u'");
					$q->execute(array($parent));
					$rc = $q->rowCount();
					if ($rc == 0) { // if c we CAN reconcile p!
						$q = $db->prepare("UPDATE `statementlines` SET `status` = 'r' WHERE `id` = ?");
						$q->execute(array($parent));
					}
				}
				// echo '<script> document.location.reload(true); </script>';	// TEMP
				// print "<pre>";
				// print_r($_POST);
				// print "</pre>";
				// echo "Success!";
				$q = $db->prepare("SELECT payments.id AS `id` FROM `payments` LEFT JOIN `statementlines` ON payments.statementlineid = statementlines.id WHERE statementlines.id = ?");
				$q->execute(array($_POST['sline']));
				$r = $q->fetch();
				if ($gen == 'c') {				
					echo "<input type=\"hidden\" class=\"recongen\" value=\"c\" />\n";
				} else {
					echo "<input type=\"hidden\" class=\"recongen\" value=\"n\" />\n";
				}
				echo "<input type=\"hidden\" class=\"reconlineid\" value=\"".$_POST['sline']."\" />\n";
				echo "<span class=\"reconciledchild\" ><a href=\"bank.php?s=detail&d=".$r['id']."\">Reconciled to payment #".$r['id']."</a></span>\n";
				if ($gen == 'c') {
					echo "<input class=\"reconsplitamount\" value=\"".number_format(preg_replace("/([^0-9\\.-])/i", "", $amount / 100), 2, '.', ',')."\" disabled=\"disabled\" /> <input type=\"button\" class=\"button rsdisabled dischmabled\" value=\"Split\" />";
				}
				echo "<input type=\"button\" class=\"button reconundo\" value=\"Undo\" />\n";
			} else {
				echo "<span class=\"error\">ERROR: Could not reconcile line</span>";
				p($_POST);
			}

		} else if ($_POST['ajax'] == 'undopayment') {
			$q = $db->prepare("
				SELECT
					payments.id AS `paymentid`,
					statementlines.id AS `lineid`,
					statementlines.generation AS `linegen`,
					statementlines.parentid AS `lineparent`
				FROM `payments` LEFT JOIN (`statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id) ON payments.statementlineid = statementlines.id
				WHERE payments.id = ?
				AND bankaccounts.clientid = $clientid
			");
			$q->execute(array($_POST['id']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$r = $q->fetch();
				$q2 = $db->prepare("DELETE FROM `payments` WHERE `id` = ?");
				$q2->execute(array($r['paymentid']));
				$q2 = $db->prepare("UPDATE `statementlines` SET `status` = 'u' WHERE `id` = ?");
				$q2->execute(array($r['lineid']));
				if ($r['linegen'] == 'c') {
					$q3 = $db->prepare("UPDATE `statementlines` SET `status` = 'u' WHERE `id` = ?");
					$q3->execute(array($r['lineparent']));
				}
			} // else no
			echo '<script> document.location = "bank.php?s=payments" </script>';

//============================== REPORTS ================================

		} else if ($_POST['ajax'] == 'landlordreportupdate') {
			if (strtotime($_POST['start']) != 0) {
				$start = date('Y-m-d', strtotime($_POST['start']));
			} else {
				$start = date('Y-m-d', strtotime("first day of previous month"));
			}
			if (strtotime($_POST['end']) != 0) {
				$end = date('Y-m-d', strtotime($_POST['end']));
			} else {
				$end = date('Y-m-d', strtotime("last day of previous month"));
			}
			echo '<script> document.location = "reports.php?s=landlord&d='.$_POST['d'].'&start='.$start.'&end='.$end.'" </script>';

		} else if ($_POST['ajax'] == 'landlordreportxero') {
			// p($_POST);
			
			$xero_contact_0 = $_POST['contact'];
			$xero_invref_10 = $_POST['invref'];
			$xero_invdate_11 = $_POST['invdate'];
			$xero_duedate_12 = date('j M Y', strtotime('+3 days', strtotime($_POST['invdate'])));
			
			$q = $db->query("SELECT * FROM `clients` WHERE `id` = $clientid");
			$r = $q->fetch();
			$xero_tracking_c1_21 = $r['xero_tracking1'];
			$rent = $r['xero_acc_rentm'];
			$let = $r['xero_acc_letfee'];
			$man = $r['xero_acc_mgmtfee'];
			$main = $r['xero_acc_main'];
			$other = $r['xero_acc_other'];
			
			file_put_contents("db/reports/".$xero_invref_10.".csv", "*ContactName,EmailAddress,POAddressLine1,POAddressLine2,POAddressLine3,POAddressLine4,POCity,PORegion,POPostalCode,POCountry,*InvoiceNumber,*InvoiceDate,*DueDate,Total,InventoryItemCode,*Description,*Quantity,*UnitAmount,*AccountCode,*TaxType,TaxAmount,TrackingName1,TrackingOption1,TrackingName2,TrackingOption2,Currency\n", LOCK_EX);
			
			foreach ($_POST['data'] as $line) {
				$q = $db->prepare("SELECT * FROM `properties` WHERE `id` = ? AND `clientid` = $clientid");
				$q->execute(array($line[4]));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					$sname = $r['sname'];
					$xero_tracking_o1_22 = $r['xero_tracking1'];
				}	
					file_put_contents("db/reports/".$xero_invref_10.".csv", "\"$xero_contact_0\",,,,,,,,,,\"$xero_invref_10\",\"$xero_invdate_11\",\"$xero_duedate_12\",,,\"[$sname] ".$line[0].": ".$line[1]."\",1,".$line[2].",\"".$$line[3]."\",No VAT,,\"$xero_tracking_c1_21\",\"$xero_tracking_o1_22\",,,,\n", FILE_APPEND);
				
			}
			
			
		} else if ($_POST['ajax'] == 'tenantreportupdate') {
			$returl = 'reports.php?s=tenant&d='.$_POST['d'];
			if ($_POST['t'] != 0) {
				$returl .= '&t='.$_POST['t'];
			}
			if (strtotime($_POST['start']) != 0) {
				$returl .= '&start='.date('Y-m-d', strtotime($_POST['start']));
			} 
			if (strtotime($_POST['end']) != 0) {
				$returl .= '&end='.date('Y-m-d', strtotime($_POST['end']));
			}
				
			echo '<script> document.location = "'.$returl.'" </script>';

			
		} else if ($_POST['ajax'] == 'roomtableupdate') {
			$returl = 'reports.php?s=roomtable';
			if (in_array($_POST['type'], array('a', 'l', 'm'))) {
				$returl .= '&type='.$_POST['type'];
			}
			if (in_array($_POST['mode'], array('d', 's', 'o'))) {
				$returl .= '&mode='.$_POST['mode'];
			}
			if (strtotime($_POST['date']) != 0) {
				$returl .= '&date='.date('Y-m-d', strtotime($_POST['date']));
			}			
			echo '<script> document.location = "'.$returl.'" </script>';
			
			
		} else if ($_POST['ajax'] == 'updatearrearsstatus') {
			$q = $db->prepare("SELECT * FROM `tenants` WHERE `id` = ? AND `clientid` = $clientid");
			$q->execute(array($_POST['tenantid']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$q = $db->prepare("SELECT * FROM `arrearsstatuses` WHERE `id` = ?");
				$q->execute(array($_POST['statusid']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$q = $db->prepare("UPDATE `tenants` SET `status` = ? WHERE `id` = ?");
					$q->execute(array($_POST['statusid'], $_POST['tenantid']));
				}
			}
		
		} else if ($_POST['ajax'] == 'submitarrearsnote') {
			$q = $db->prepare("SELECT * FROM `tenants` WHERE `id` = ? AND `clientid` = $clientid");
			$q->execute(array($_POST['tenantid']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$q = $db->prepare("INSERT INTO `arrearsnotes` (
					`timestamp`,
					`userid`,
					`tenantid`,
					`text`
				) VALUES (
					:timestamp,
					:userid,
					:tenantid,
					:text
				)");
				$q->bindValue(':timestamp', date('Y-m-d H:i:s'));
				$q->bindValue(':userid', $_SESSION['user_id'], PDO::PARAM_INT);
				$q->bindValue(':tenantid', $_POST['tenantid'], PDO::PARAM_INT);
				$q->bindValue(':text', preg_replace("/&nbsp;/",'',htmlentities(trim($_POST['notecontent']))));
				$q->execute();
				
				$lastid = $db->lastInsertId();
				$q = $db->prepare("SELECT * FROM `arrearsnotes` WHERE `id` = ?");
				$q->execute(array($lastid));
				$row = $q->fetch();
				
				$datetime = date('j M Y, g:ia', strtotime($row['timestamp']));
				$notecontent = $row['text'];
				$q2 = $db->prepare("SELECT `username` FROM `users` WHERE `id` = ?");
				$q2->execute(array($row['userid']));
				$r2 = $q2->fetch();
				$user = $r2['username'];
				echo "							<li data-id=\"".$lastid."\"><strong>".$datetime."</strong>: <span class=\"notecontent\">".stripslashes($notecontent)."</span> -<strong>".$user."</strong> | <span class=\"editnote\">Edit</span> | <span class=\"resolvenote\">Resolve</span></li>";
			}			

		} else if ($_POST['ajax'] == 'resolvenote') {
			$q = $db->prepare("SELECT * FROM `arrearsnotes` LEFT JOIN `tenants` ON arrearsnotes.tenantid = tenants.id WHERE tenants.clientid = $clientid AND arrearsnotes.id = ?");
			$q->execute(array($_POST['noteid']));
			$rc = $q->rowCount();
			if ($rc == 1) {
				$q = $db->prepare("UPDATE `arrearsnotes` SET `status` = 'r' WHERE `id` = ?");
				$q->execute(array($_POST['noteid']));
				
				// echo $_POST['noteid'];
			}
			
		} else if ($_POST['ajax'] == 'submiteditednote') {
			$q = $db->prepare("SELECT * FROM `arrearsnotes` LEFT JOIN `tenants` ON arrearsnotes.tenantid = tenants.id WHERE tenants.clientid = $clientid AND arrearsnotes.id = ?");
			$q->execute(array($_POST['noteid']));
			$rc = $q->rowCount();
			if ($rc == 1) {			
				$q = $db->prepare("UPDATE `arrearsnotes` SET `text` = :text WHERE `id` = :id");
				$q->bindValue(':text', preg_replace("/&nbsp;/",'',htmlentities(trim($_POST['updatedcontent']))));
				$q->bindValue(':id', $_POST['noteid'], PDO::PARAM_INT);
				$q->execute();		

				echo preg_replace("/&nbsp;/",'',htmlentities(trim($_POST['updatedcontent'])));
			}
			
			
//============================== TABLE SORTING (depreciated.. for now) ================================

		} else if ($_POST['ajax'] == 'sortcols') {
			$id = $_SESSION['user_id'];
			if ($_POST['page'] == 'docbrowser') {
				$q = $db->prepare("UPDATE `users` SET `docsort` =? WHERE id =?");
			} else if ($_POST['page'] == 'properties') {
				$q = $db->prepare("UPDATE `users` SET `propsort` =? WHERE id =?");
			} else if ($_POST['page'] == 'utilities') {
				$q = $db->prepare("UPDATE `users` SET `utilsort` =? WHERE id =?");
				if ($_POST['col'] == 'property') {
					$_POST['col'] = 'properties.address';
				} else {
					$_POST['col'] = 'utilities.'.$_POST['col'];
				}
			}
			$q->execute(array($_POST['col'], $id));
			echo '<script> document.location.reload(true); </script>';


//======================================= ADMIN ===================================


		} else if ($_POST['ajax'] == 'changeclientID') {
			$id = $_SESSION['user_id'];
			$q = $db->prepare("UPDATE `users` SET `clientid` =? WHERE id =?");
			$q->execute(array($_POST['cid'], $id));


//====================================== STATEMENT UPLOADS ====================================
		}
	} else if (isset($_FILES['statement'])) {
		$id = $_SESSION['user_id'];
		if ($q = $db->prepare("SELECT `clientid` FROM `users` WHERE `id` = ?")) {
			$q->bindValue(1, $id, PDO::PARAM_INT);
			$q->execute();
			$r = $q->fetch(PDO::FETCH_ASSOC);
			$clientid = $r['clientid'];
			if (!is_dir('db/statements/'.$clientid))
				mkdir('db/statements/'.$clientid);
			$output_dir = 'db/statements/'.$clientid.'/';
			$ret = array();
			$error =$_FILES['statement']['error'];
			if(!is_array($_FILES['statement']['name'])) {
				$fileName = $_FILES['statement']['name'];
				move_uploaded_file($_FILES['statement']['tmp_name'],$output_dir.$fileName);
				$ret[]= $fileName;
			} else {
				$fileCount = count($_FILES['statement']['name']);
				for($i=0; $i < $fileCount; $i++) {
					$fileName = $_FILES['statement']['name'][$i];
					move_uploaded_file($_FILES['statement']['tmp_name'][$i],$output_dir.$fileName);
					$ret[]= $fileName;
				}
			}
			echo json_encode($ret);
		}
	}
} else {
	header('Location: index.php');
}

?>