<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {
	$pagetitle = 'Tenants';

	// $headers = array('ID', 'Name', 'Phone', 'Current Property', 'Rent balance', 'Days in arrears'); - to include these last three once tenant object is implemented, to save copying & pasting lengthy queries again
	$headers = array('ID', 'Name', 'Phone');
	$tenantdeets = array('Name', 'Phone #1', 'Phone #2', 'Email #1', 'Email #2', 'Date of birth');

	$dbheaders = array('name', 'phone1', 'phone2', 'email1', 'email2', 'dob');

?>
<!DOCTYPE html>
<html>
	<head>
		<?php require 'defs/inc_jscss.php' ?>
		<script type="text/javascript" src="js/tenants.js"></script>
	</head>
	<body>
		<?php require 'defs/inc_header.php' ?>
		<div id="subheader">
			<div id="innersubheader">
				<?php if (isset($_GET['s'])) {
					if ($_GET['s'] == 'detail') {
						$s = 'detail';
					} else if ($_GET['s'] == 'feesexp') {
						$s = 'feesexp';
					} else if ($_GET['s'] == 'adjustments') {
						$s = 'adjustments';
					} else if ($_GET['s'] == 'add') {
						$s = 'add';
					} else if ($_GET['s'] == 'import') {
						$s = 'import';
					} else {
						$s = 'list';
					}
				} else {
					$s = 'list';
				}?>
				<span class="<?php echo ($s == 'list' ? 'subtab_active' : 'subtab') ?>" id="list">
					List
				</span>
				<span class="<?php echo ($s == 'detail' ? 'subtab_active' : 'subtab') ?>" id="detail">
					Details
				</span>
				<span class="<?php echo ($s == 'feesexp' ? 'subtab_active' : 'subtab') ?>" id="feesexp">
					Fees & Expenses
				</span>
				<span class="<?php echo ($s == 'adjustments' ? 'subtab_active' : 'subtab') ?>" id="adjustments">
					Rent Adjustments
				</span>
				<span class="<?php echo ($s == 'add' ? 'subtab_active' : 'subtab') ?>" id="add">
					Add
				</span>
				<span class="<?php echo ($s == 'import' ? 'subtab_active' : 'subtab') ?>" id="import">
					Import
				</span>
			</div>
		</div>
		<div id="main">
			<?php if ($s == 'list') { ?>
			<p>Double-click a row to view detail.</p>
			<table id="datatable">
				<thead>
					<tr><?php

						echo "\n";

						foreach ($headers as $header) {
							echo "						<th>$header</th>\n";
						}

						?>
					</tr>
				</thead>
				<tbody>
					<?php 
					if ($q = $db->prepare("
						SELECT 
							`id` as `tbl_id`,
							`name` as `tbl_name`,
							`phone1` as `tbl_phone1`
						FROM `tenants` 
						WHERE `clientid` = '$clientid'
						ORDER BY `name`
					")) {
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);
						foreach ($r as $row) {
							echo "					<tr id=\"".$row['tbl_id']."\">\n";
							foreach ($row as $k => $v) {
								echo "					<td>".$v."</td>\n";
							}
							// echo "					<td>-</td>\n";
							// echo "					<td>-</td>\n";
							// echo "					<td>-</td>\n";
							echo "					</tr>\n";
						}
					}

					?>
				</tbody>
				<input type="hidden" id="selectedid" />
			</table>

			<?php } else if ($s == 'detail') { ?>

			<?php

			if (!isset($_GET['d'])) {
				foreach ($db->query("SELECT `id` FROM `tenants` WHERE `clientid` = '$clientid' ORDER BY `id` DESC LIMIT 1") as $row) {
					echo "<script> document.location = '".$_SERVER['REQUEST_URI']."&d=".$row['id']."' </script>";
				}
			}

			if (isset($_POST['submit'])) {
				// print "<pre>";
				// print_r($_POST);
				// print "</pre>";
				$id = $_POST['input_id'];
				$tenantfields = array('input_name','input_phone1', 'input_phone2', 'input_email1', 'input_email2', 'input_dob');
				$tenancyfields = array('roomid', 'startdate', 'minenddate', 'enddate', 'rent', 'period', 'obal', 'obaldate');
				$error = 0;
				foreach ($_POST as $k => $v) {
					if (in_array($k, $tenantfields)) {
						$f = str_replace('input_', '', $k);
						$q = $db->prepare("UPDATE `tenants` SET `".$f."` =? WHERE `id` =?");
						if ($f == 'name') {
							if (strlen(trim($v)) >= 1) {
								$q->execute(array(htmlentities(utf8_encode($v)), $id));
							} else {
								$error |= 1;
							}
						} else if ($f == 'dob') {
							if (strlen(trim($v)) >= 1) {
								$ukify = str_replace('/', '-', $v);
								if (strtotime($ukify) != 0) {
									$q->execute(array(date('Y-m-d', strtotime($ukify)), $id));
								} else {
									$error |= 2;
								}
							} else {	// allows a blank (null) entry
								$q->bindValue(1, null, PDO::PARAM_INT);
								$q->bindValue(2, $id);
								$q->execute();
							}
						} else {
							$q->execute(array(htmlentities(utf8_encode($v)), $id));
						}
					}
				}

				if (isset($_POST['addtenancy'])) {
					$addtenancy = $_POST['addtenancy'];
				} else {
					$addtenancy = 0;
				}

				if ($q = $db->prepare("SELECT id FROM tenancies WHERE tenantid =?")) {
					$q->bindValue(1, $id, PDO::PARAM_INT);
					$q->execute();
					$r = $q->fetchAll(PDO::FETCH_ASSOC);
					foreach ($r as $row) { // each tenancy belonging to tenant
						if ($row['id'] != $addtenancy) {
							$tenancyerror = 0;
							$tid = $row['id'];
							// then loop through tenancyfields array, assigning each entry to a column name in a prepared query. perform validation on each field (as in imports) before executing.
							foreach ($tenancyfields as $tf) {

								$$tf = $_POST['t'.$tid.'_'.$tf];
								$q2 = $db->prepare("UPDATE `tenancies` SET $tf =? WHERE `id` = $tid");

								if ($tf == 'roomid') { // begin validation!
									$q2->bindValue(1, $roomid);
									$q2->execute();
								} else if ($tf == 'startdate') {
									$ukify = str_replace('/', '-', $startdate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$tenancyerror |= 8;
									}
								} else if ($tf == 'minenddate') {
									$ukify = str_replace('/', '-', $minenddate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$q2->bindValue(1, null, PDO::PARAM_INT);
										$q2->execute();
									}
								} else if ($tf == 'enddate') {
									$ukify = str_replace('/', '-', $enddate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$q2->bindValue(1, null, PDO::PARAM_INT);
										$q2->execute();
									}
								} else if ($tf == 'rent') {
									if (strlen(preg_replace("/([^0-9\\.])/i", "", $rent)) >= 1) {
										$rent = str_replace(',','',$rent);
										$q2->bindValue(1, preg_replace("/([^0-9\\.])/i", "", $rent * 100));
										$q2->execute();
									} else {
										$tenancyerror |= 32;
									}
								} else if ($tf == 'period') {
									if (in_array($period, array('W','F','4','M','Q'))) {
										$q2->bindValue(1, $period);
										$q2->execute();
									} else {
										$tenancyerror |= 16;
									}
								} else if ($tf == 'obal') {
									if (strlen(preg_replace("/([^0-9\\.])/i", "", $obal)) >= 1) {
										$obal = str_replace(',','',$obal);
										$q2->bindValue(1, preg_replace("/([^0-9\\.-])/i", "", $obal * 100));
										$q2->execute();
									} else {
										$q2->bindValue(1, 0);
										$q2->execute();
									}
								} else if ($tf == 'obaldate') {
									$ukify = str_replace('/', '-', $obaldate);
									if (strtotime($ukify) != 0) {
										$q2->bindValue(1, date("Y-m-d", strtotime($ukify)));
										$q2->execute();
									} else {
										$q2->bindValue(1, null, PDO::PARAM_INT);
										$q2->execute();
									}
								}
							}

							// app fee
							if (!isset($_POST['t'.$tid.'_appfee'])) {
								$_POST['t'.$tid.'_appfee'] = 0;
							}
							$appfee = $_POST['t'.$tid.'_appfee'];
							// d('appfee is '.$appfee);
							if (strlen(preg_replace("/([^0-9\\.])/i","", $appfee)) != 0 && number_format($appfee) != 0) {
								$appfeeentered = true;
								// d('test 1 - true');
							} else {
								$appfeeentered = false;
								// d('test 1 - false');
							}

							$feecheck = $db->prepare("SELECT `appfeeid` FROM `tenancies` WHERE `id` = ?");
							$feecheck->execute(array($tid));
							$feeresult = $feecheck->fetch();
							// d('appfeeid is '.$feeresult['appfeeid']);
							if ($feeresult['appfeeid'] != 0) {
								$hasappfee = true;
								// d('test 2 - true');
							} else {
								$hasappfee = false;
								// d('test 2 - false');
							}

							if ($appfeeentered == true && $hasappfee == true) {
								//update
								// d('update');
								$q3 = $db->prepare("UPDATE `tenantfees` SET `amount` = ? WHERE `id` = ?");
								$q3->execute(array(preg_replace("/([^0-9\\.])/i","", $appfee) * 100, $feeresult['appfeeid']));
							} else if ($appfeeentered == true && $hasappfee == false) {
								//insert
								// d('insert');
								$q4 = $db->prepare("
									SELECT
										tt.tenantname AS `name`,
										rooms.no AS `room`,
										CONCAT (properties.no,' ',properties.street) AS `property`,
										properties.id AS `propertyid`,
										tt.startdate AS `startdate`
									FROM (
										SELECT
											tenancies.id AS `tenancyid`,
											tenancies.roomid AS `roomid`,
											tenants.name AS `tenantname`,
											tenancies.startdate AS `startdate`
										FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
									LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
									WHERE tt.tenancyid = ?
								");
								$q4->execute(array($tid));
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
									$tid,
									$r4['startdate'],
									preg_replace("/([^0-9\\.-])/i", "", $appfee) * 100,
									'c',
									"Application fee for ".$r4['name'].", Room ".$r4['room'].", ".$r4['property']
								));
								$feeid = $db->lastInsertId();
								$q6 = $db->prepare("UPDATE `tenancies` SET `appfeeid` = ? WHERE `id` = ?");
								$q6->execute(array($feeid, $tid));
							} else if ($appfeeentered == false && $hasappfee == true) {
								//delete
								// d('delete');
								$q3 = $db->prepare("DELETE FROM `tenantfees` WHERE `id` = ?");
								$q3->execute(array($feeresult['appfeeid']));
								$q3 = $db->prepare("UPDATE `tenancies` SET `appfeeid` = 0 WHERE `id` = ?");
								$q3->execute(array($tid));
								//now delete payment if applied
								$q4 = $db->prepare("SELECT * FROM `payments` WHERE `contacttype` = 'f' AND `contactid` = ?");
								$q4->execute(array($feeresult['appfeeid']));
								$rc4 = $q4->rowCount();
								if ($rc4 == 1) {
									$r4 = $q4->fetch();
									
									$q5 = $db->prepare("DELETE FROM `payments` WHERE `id` = ?");
									$q5->execute(array($r4['id']));
									
									$q6 = $db->prepare("SELECT * FROM `statementlines` WHERE `id` = ?");
									$q6->execute(array($r4['statementlineid']));
									$r6 = $q6->fetch();
									
									$q7 = $db->prepare("UPDATE `statementlines` SET `status` = 'u' WHERE `id` = ?");
									$q7->execute(array($r6['id']));
									
									if ($r6['generation'] == 'c') {
										$q8 = $db->prepare("UPDATE `statementlines` SET `status` = 'u' WHERE `id` = ?");
										$q8->execute(array($r6['parentid']));
									}
								}
							} else { 
								//do nothing
							}

							// LET fee
							if (!isset($_POST['t'.$tid.'_letfee'])) {
								$_POST['t'.$tid.'_letfee'] = 0;
							}
							$letfee = $_POST['t'.$tid.'_letfee'];
							if (strlen(preg_replace("/([^0-9\\.])/i","", $letfee)) != 0 && number_format($letfee) != 0) {
								$letfeeentered = true;
							} else {
								$letfeeentered = false;
							}

							$feecheck = $db->prepare("SELECT `letfeeid` FROM `tenancies` WHERE `id` = ?");
							$feecheck->execute(array($tid));
							$feeresult = $feecheck->fetch();

							if ($feeresult['letfeeid'] != 0) {
								$hasletfee = true;
							} else {
								$hasletfee = false;
							}

							if ($letfeeentered == true && $hasletfee == true) {
								//update
								$q3 = $db->prepare("UPDATE `propertyfees` SET `amount` = ? WHERE `id` = ?");
								$q3->execute(array(preg_replace("/([^0-9\\.])/i","", $letfee) * 100, $feeresult['letfeeid']));
							} else if ($letfeeentered == true && $hasletfee == false) {
								//insert
								$q4 = $db->prepare("
									SELECT
										tt.tenantname AS `name`,
										rooms.no AS `room`,
										mp.address AS `property`,
										mp.pid AS `propertyid`,
										mp.mcid AS `mcid`,
										tt.startdate AS `startdate`
									FROM (
										SELECT
											tenancies.id AS `tenancyid`,
											tenancies.roomid AS `roomid`,
											tenants.name AS `tenantname`,
											tenancies.startdate AS `startdate`
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
								// $q4 = $db->prepare("
									// SELECT
										// tt.tenantname AS `name`,
										// rooms.no AS `room`,
										// CONCAT (properties.no,' ',properties.street) AS `property`,
										// properties.id AS `propertyid`,
										// tt.startdate AS `startdate`
									// FROM (
										// SELECT
											// tenancies.id AS `tenancyid`,
											// tenancies.roomid AS `roomid`,
											// tenants.name AS `tenantname`,
											// tenancies.startdate AS `startdate`
										// FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
									// LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
									// WHERE tt.tenancyid = ?
								// ");
								$q4->execute(array($tid));
								$rc4 = $q4->rowCount();
								if ($rc4 >= 1) { //see ajax.php 'let fee check'
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
										$r4['startdate'],
										preg_replace("/([^0-9\\.-])/i", "", $letfee) * 100,
										"Letting fee for ".$r4['name'].", Room ".$r4['room'].", ".$r4['property'],
										'l'
									));
									$feeid = $db->lastInsertId();
									$q6 = $db->prepare("UPDATE `tenancies` SET `letfeeid` = ? WHERE `id` = ?");
									$q6->execute(array($feeid, $tid));
								}
							} else if ($letfeeentered == false && $hasletfee == true) {
								//delete
								$q3 = $db->prepare("DELETE FROM `propertyfees` WHERE `id` = ?");
								$q3->execute(array($feeresult['letfeeid']));
								$q3 = $db->prepare("UPDATE `tenancies` SET `letfeeid` = 0 WHERE `id` = ?");
								$q3->execute(array($tid));
							} else { 
								//do nothing
							}



							// how do we report errors for each tenancy?
							// like this...

							if ($tenancyerror & 8) {
								$tenancyerrors8 = array();
								$tenancyerrors8[] = $tid;
							} else if ($tenancyerror & 16) {
								$tenancyerrors16 = array();
								$tenancyerrors16[] = $tid;
							} else if ($tenancyerror & 32) {
								$tenancyerrors32 = array();
								$tenancyerrors32[] = $tid;
							}
						}
					}
				} else {
					//there was an error with the database
				}

				if ($error == 0 && !isset($tenancyerrors8) && !isset($tenancyerrors16) && !isset($tenancyerrors32) ) {
					echo "<p>All changes submitted successfully!</p>\n";
				} else {
					echo "		<p class=\"error\">ERROR</p>";
					if ($error & 1) {
						echo "		<p>'Name' must not be empty.</p>";
					}
					if ($error & 2) {
						echo "		<p>'Date of birth' is invalid.</p>";
					}
					if (isset($tenancyerrors8)) {
						foreach ($tenancyerrors8 as $erroneoustenancy) {
							echo "		<p>'Start date' was missing or invalid on tenancy #$erroneoustenancy.</p>";
						}
					}
					if (isset($tenancyerrors16)) {
						foreach ($tenancyerrors16 as $erroneoustenancy) {
							echo "		<p>'Period' was missing or invalid on tenancy #$erroneoustenancy.</p>";
						}
					}
					if (isset($tenancyerrors32)) {
						foreach ($tenancyerrors32 as $erroneoustenancy) {
							echo "		<p>'Rent' was missing or invalid on tenancy #$erroneoustenancy.</p>";
						}
					}

					echo "		<p>Please amend and re-submit changes below.</p>";
				}
			}

			?>

			<form id="tenantedit" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST">
				<fieldset class="fieldset">
					<legend>Edit tenant details</legend>
					<input type="hidden" id="input_id" name="input_id" value="<?php echo $_GET['d'] ?>" />
					<?php

					if ($q = $db->prepare("
						SELECT
							`name` as `input_name`,
							`phone1` as `input_phone1`,
							`phone2` as `input_phone2`,
							`email1` as `input_email1`,
							`email2` as `input_email2`,
							`dob` as `input_dob`
						FROM `tenants`
						WHERE `id` =?
						AND clientid = '$clientid'
					")) {
						$q->bindValue(1, $_GET['d']);
						$q->execute();
						if ($q->rowCount() > 0) {
							$r = $q->fetch(PDO::FETCH_ASSOC);
							$fieldcount = -1;
							foreach ($r as $k => $v) {
								$fieldcount++;
								echo "					<p>\n";
								echo "						<label for=\"".$k."\" class=\"zoomlabel-tenant\">".$tenantdeets[$fieldcount].":</label>\n";
								if ($k == 'input_dob') {
									echo "						<input id=\"".$k."\" name=\"".$k."\" class=\"detailinput\" value=\"".(strtotime($v) != 0 ? date("j M Y", strtotime($v)) : "")."\" />  \n";
								} else {
									echo "						<input id=\"".$k."\" name=\"".$k."\" class=\"detailinput\" value=\"".$v."\" />  \n";
								}
								echo "					</p>\n";
							}
						} else {
							echo "<p class=\"error\">ERROR</p>\n";
							echo "<p>Invalid tenant ID</p>\n";
						}
					} else {
						echo "<p>There was an error with the database</p>";
					}

					?>
				</fieldset>

				<div class="rfieldset">
					<h2>Balances & Reports</h2>
					<a href="reports.php?s=tenant&d=<?php echo $_GET['d'] ?>">View tenant statement</a>
				</div>

				<div class="lfieldset">
					<h2>Tenancies</h2>
					<div class="tenancycont">
						<input type="hidden" id="id0" value="0" />
						<h3>[Add new tenancy]</h3>
						<div class="tenancyhide">
							<div class="tenancycol1 tenancyhide">
								<p>
									<label class="tenancylabel" for="t0_property">Property:</label>
									<select class="tenancyinput1" id="t0_property">
										<option value=""></option>
										<?php
										foreach ($db->query("SELECT	id, no,	street FROM `properties` WHERE clientid = $clientid ORDER BY `no`") as $p) {
											echo "									<option value=\"".$p['id']."\">".$p['no']." ".$p['street']."</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel" for="t0_startdate">Start date:</label>
										<input class="genericdate" id="t0_startdate" />
									</span>
								</p>
								<p>
									<label class="tenancylabel" for="t0_rent">Rent:</label>
									<input class="tenancyinput3a" id="t0_rent" />
								</p>
								<p>
									<label class="tenancylabel" for="t0_obal">Opening balance:</label>
									<input class="tenancyinput3a" id="t0_obal" />
								</p>
							</div>
							<div class="tenancycol2 tenancyhide">
								<p>
									<label class="tenancylabel2" for="t0_roomid">Room:</label>
									<select class="tenancyinput2" id="t0_roomid">
										<?php //options, don't think we need anything initially... ?>
									</select>
								</p>
								<p>
									<label class="tenancylabel2" for="t0_minenddate">Min. period:</label>
									<select class="tenancyinput4" id="t0_minenddate">
										<?php
										foreach (array(
											'n' => 'None',
											'2' => '2 Months',
											'3' => '3 Months',
											'6' => '6 Months',
											'12' => '12 Months'
										) as $k => $v) {
											echo "									<option value=\"$k\">$v</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<label class="tenancylabel2" for="t0_period">Period:</label>
									<select class="tenancyinput4" id="t0_period">
										<?php
										foreach (array('W' => 'Weekly', 'F' => 'Fortnightly', '4' => 'Four-weekly', 'M' => 'Monthly', 'Q' => 'Quarterly') as $k => $v) {
											echo "									<option value=\"$k\">$v</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t0_obaldate">Opening date:</label>
										<input class="genericdate" id="t0_obaldate" />
									</span>
								</p>
							</div>
							<div class="tenancycol3 tenancyhide">
								<p>
									&nbsp;
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t0_enddate">End date:</label>
										<input class="genericdate" id="t0_enddate"  />
									</span>
								</p>
								<p>
									<span class="checkboxcontl">
										<input type="checkbox" class="checkboxl" id="t0_appfeecheck" />
										<label class="tenancylabel2">App fee:</label>
										<input class="tenancyinput3a" id="t0_appfee" disabled="disabled" />
									</span>
								</p>
								<p>
									<span class="checkboxcontl">
										<input type="checkbox" class="checkboxl" id="t0_letfeecheck" />
										<label class="tenancylabel2">Letting fee:</label>
										<input class="tenancyinput3a" id="t0_letfee" disabled="disabled" />
									</span>
								</p>
							</div>

							<div class="tenancyfoot tenancyhide">
								<input type="button" class="button" id="addtenancy" value="Add tenancy" />
							</div>
						</div>
					</div>
				<?php

					if ($q = $db->prepare("
						SELECT 
							tenancies.id as `tenancyid`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
						WHERE tenants.id = ?
						AND clientid = '$clientid'
						ORDER BY tenancies.enddate IS NULL DESC, tenancies.enddate DESC
					")) {
						$q->bindValue(1, $_GET['d']);
						$q->execute();
						$r = $q->fetchAll(PDO::FETCH_ASSOC);
						$rownum = 0;
						foreach ($r as $row) {
							$rownum++;
							$tenancyid = $row['tenancyid'];
							$q2 = $db->query("
							SELECT 
								tenancies.id as `t_id`,
								properties.id as `p_id`,
								properties.no as `p_no`,
								properties.street as `p_street`,
								tenancies.roomid as `r_id`,
								rooms.no as `r_no`,
								tenancies.startdate as `t_startdate`,
								tenancies.minenddate as `t_minenddate`,
								tenancies.enddate as `t_enddate`,
								tenancies.rent as `t_rent`,
								tenancies.period as `t_period`,
								tenancies.obal as `t_obal`,
								tenancies.obaldate as `t_obaldate`,
								tenancies.appfeeid as `t_appfeeid`,
								tenancies.letfeeid as `t_letfeeid`
							FROM `tenancies` LEFT JOIN ( `rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id ) ON tenancies.roomid = rooms.id
							WHERE tenancies.id = ".$row['tenancyid']."
							");
							$r2 = $q2->fetch(PDO::FETCH_ASSOC);
							$t = $r2['t_id'];
				?>

					<div class="tenancycont">
						<input type="hidden" class="tenancyid" id="id<?php echo $rownum ?>" value="<?php echo $t ?>" />
						<h3>[#<?php echo $t ?>] <?php echo date('j M Y', strtotime ($r2['t_startdate'])) ?> - <?php echo (strtotime($r2['t_enddate']) != 0 ? date("j M Y", strtotime($r2['t_enddate'])) : "present") ?> :: <?php echo $r2['p_no']." ".$r2['p_street'] ?></h3>
						<div class="<?php echo (strtotime($r2['t_enddate']) != 0 && strtotime($r2['t_enddate']) < strtotime('today') ? "tenancyhide" : "tenancyshow") ?>">
							<div class="tenancycol1 <?php echo (strtotime($r2['t_enddate']) != 0 && strtotime($r2['t_enddate']) < strtotime('today') ? "tenancyhide" : "tenancyshow") ?>">
								<p>
									<label class="tenancylabel" for="t<?php echo $t ?>_property">Property:</label>
									<select class="tenancyinput1" name="t<?php echo $t ?>_property" id="t<?php echo $t ?>_property">
										<?php
										foreach ($db->query("SELECT	id, no,	street FROM `properties` WHERE clientid = $clientid") as $p) {
											echo "									<option value=\"".$p['id']."\"";
											if ($p['id'] == $r2['p_id']) {
												echo "selected=\"selected\"";
											}
											echo ">".$p['no']." ".$p['street']."</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel" for="t<?php echo $t ?>_startdate">Start date:</label>
										<input class="genericdate" name="t<?php echo $t ?>_startdate" id="t<?php echo $t ?>_startdate" value="<?php echo date('j M Y', strtotime ($r2['t_startdate'])) ?>" />
									</span>
								</p>
								<p>
									<label class="tenancylabel" for="t<?php echo $t ?>_rent">Rent:</label>
									<input class="tenancyinput3a" name="t<?php echo $t ?>_rent" id="t<?php echo $t ?>_rent" value="<?php echo number_format($r2['t_rent'] / 100, 2, '.', ',')?>" />
								</p>
								<p>
									<label class="tenancylabel" for="t<?php echo $t ?>_obal">Opening balance:</label>
									<input class="tenancyinput3a" name="t<?php echo $t ?>_obal" id="t<?php echo $t ?>_obal" value="<?php echo number_format($r2['t_obal'] / 100, 2, '.', ',') ?>" />
								</p>
							</div>
							<div class="tenancycol2 <?php echo (strtotime($r2['t_enddate']) != 0 && strtotime($r2['t_enddate']) < strtotime('today') ? "tenancyhide" : "tenancyshow") ?>">
								<p>
									<label class="tenancylabel2" for="t<?php echo $t ?>_roomid">Room:</label>
									<select class="tenancyinput2" name="t<?php echo $t ?>_roomid" id="t<?php echo $t ?>_roomid">
										<?php
										foreach ($db->query("SELECT	id, no FROM `rooms` WHERE propertyid = ".$r2['p_id']) as $room) {
											echo "									<option value=\"".$room['id']."\"";
											if ($room['id'] == $r2['r_id']) {
												echo "selected=\"selected\"";
											}
											echo ">".$room['no']."</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t<?php echo $t ?>_minenddate">Min. end date:</label>
										<input class="genericdate" name="t<?php echo $t ?>_minenddate" id="t<?php echo $t ?>_minenddate" value="<?php echo (strtotime($r2['t_minenddate']) != 0 ? date("j M Y", strtotime($r2['t_minenddate'])) : "") ?>" />
									</span>
								</p>
								<p>
									<label class="tenancylabel2" for="t<?php echo $t ?>_period">Period:</label>
									<select class="tenancyinput4" name="t<?php echo $t ?>_period" id="t<?php echo $t ?>_period">
										<?php
										foreach (array('W' => 'Weekly', 'F' => 'Fortnightly', '4' => 'Four-weekly', 'M' => 'Monthly', 'Q' => 'Quarterly') as $k => $v) {
											echo "									<option value=\"$k\"";
											if ($k == $r2['t_period']) {
												echo "selected=\"selected\"";
											}
											echo ">$v</option>\n";
										}
										?>
									</select>
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t<?php echo $t ?>_obaldate">Opening date:</label>
										<input class="genericdate" name="t<?php echo $t ?>_obaldate" id="t<?php echo $t ?>_obaldate" value="<?php echo (strtotime($r2['t_obaldate']) != 0 ? date("j M Y", strtotime($r2['t_obaldate'])) : "") ?>" />
									</span>
								</p>
							</div>
							<div class="tenancycol3 <?php echo (strtotime($r2['t_enddate']) != 0 && strtotime($r2['t_enddate']) < strtotime('today') ? "tenancyhide" : "tenancyshow") ?>">
								<p>
									&nbsp;
								</p>
								<p>
									<span class="datecont">
										<label class="tenancylabel2" for="t<?php echo $t ?>_enddate">End date:</label>
										<input class="genericdate enddate" name="t<?php echo $t ?>_enddate" id="t<?php echo $t ?>_enddate" value="<?php echo (strtotime($r2['t_enddate']) != 0 ? date("j M Y", strtotime($r2['t_enddate'])) : "") ?>" />
									</span>
								</p>
								<p>
									<span class="checkboxcontl"> 
										<?php
										if ($r2['t_appfeeid'] != 0) {
											$appchecked = "checked=\"checked\" ";
											$appq = $db->prepare("SELECT `amount` from `tenantfees` WHERE `id` = ?");
											$appq->execute(array($r2['t_appfeeid']));
											$appr = $appq->fetch();
											$appval = "value=\"".number_format($appr['amount'] / 100, 2, '.', ',')."\" ";
										} else {
											$appchecked = "";
											$appval = "";
										}
										?>
										<input type="checkbox" class="checkboxl feecheck" <?php echo $appchecked ?>/>
										<label class="tenancylabel2" for="t<?php echo $t ?>_appfee">App fee:</label>
										<input class="tenancyinput3a fee" id="t<?php echo $t ?>_appfee" name="t<?php echo $t ?>_appfee" <?php echo $appval ?>/>
									</span>
								</p>
								<p>
									<span class="checkboxcontl">
										<?php
										if ($r2['t_letfeeid'] != 0) {
											$letchecked = "checked=\"checked\" ";
											$letq = $db->prepare("SELECT `amount` from `propertyfees` WHERE `id` = ?");
											$letq->execute(array($r2['t_letfeeid']));
											$letr = $letq->fetch();
											$letval = "value=\"".number_format($letr['amount'] / 100, 2, '.', ',')."\" ";
										} else {
											$letchecked = "";
											$letval = "";
										}
										?>
										<input type="checkbox" class="checkboxl feecheck" <?php echo $letchecked ?>/>
										<label class="tenancylabel2" for="t<?php echo $t ?>_letfee">Letting fee:</label>
										<input class="tenancyinput3a fee" id="t<?php echo $t ?>_letfee" name="t<?php echo $t ?>_letfee" <?php echo $letval ?>/>
									</span>
								</p>
							</div>
						</div>
					</div>
					<?php
						}
					}
					?>

				</div>

				<p class="detailsubmit"><input type="submit" id="submit" name="submit" value="Submit changes" class="button"/></p>
			</form>



			<?php } else if ($s == 'add') { ?>

			<?php


			if (isset($_SESSION['error'])) {
				$error = $_SESSION['error'];
				echo "		<p class=\"error\">ERROR</p>";
				if ($error & 1) {
					echo "		<p>'Name' must not be empty.</p>";
				}
				echo "		<p>Please amend and re-submit changes below.</p>";

				foreach ($_SESSION['newten'] as $k => $v) {
					$_POST[$k] = $v;
				}
				unset($_SESSION['error']);
				unset($_SESSION['newten']);
			}

			?>


			<form action="tenant_add.php" method="POST">
				<fieldset class="fieldset">
					<legend>Add a new tenant</legend>
					<?php

					for ($x = 0; $x < sizeof($tenantdeets); $x++) {
						if (isset($_POST[$dbheaders[$x]])) {
							$v = $_POST[$dbheaders[$x]];
						} else {
							$v = '';
						}
						echo "					<p>\n";
						echo "						<label for\"".$dbheaders[$x]."\" class=\"zoomlabel\">".$tenantdeets[$x].":</label>\n";
						echo "						<input id=\"".$dbheaders[$x]."\" name=\"".$dbheaders[$x]."\" value=\"".$v."\" />  \n";
						echo "					</p>\n";
					}

					?>
				</fieldset>
				<p class="detailsubmit"><input type="submit" name="submit" value="Add tenant" class="button"/></p>
			</form>

			<?php } else if ($s == 'import') { ?>

			<?php 
			if (isset($_FILES["file"]))	{
				if ($_FILES["file"]["error"] > 0) {
					echo "<p>Error: " . $_FILES["file"]["error"] . "</p>\n";
				} else {
					$csv_mimetypes = array(
						'text/csv',
						'text/plain',
						'application/csv',
						'text/comma-separated-values',
						'application/excel',
						'application/vnd.ms-excel',
						'application/vnd.msexcel',
						'text/anytext',
						'application/octet-stream',
						'application/txt',
					);
					if (in_array($_FILES['file']['type'], $csv_mimetypes)) {	// possible CSV file
						echo "		<p class=\"italic\">[Upload: " . $_FILES["file"]["name"] . "]</p>\n";
						echo "		<p class=\"italic\">[Type: " . $_FILES["file"]["type"] . "]</p>\n";
						echo "		<p class=\"italic\">[Size: " . ($_FILES["file"]["size"]) . " B]</p>\n";
						echo "		<p class=\"italic\">[Upload successful. Checking file contents...]</p>\n";

						$csv = file($_FILES['file']['tmp_name']);
						array_shift($csv);

						$testrows = array_map("str_getcsv", $csv);
						$testarr = $testrows[0];
					} else {
						echo "		<p>Invalid file format - check file is saved as a CSV.</p>\n";
					}
					if (count($testarr) == 14) {	?>
						<p>
							CSV contains correct number of columns! Please check data below before continuing. If incorrect, amend and <a href="<?php echo $_SERVER['REQUEST_URI'] ?>">click here to re-upload</a>.
						</p>

						<?php $rows = array_map("str_getcsv", $csv);

						foreach ($rows as $arr) {
							$error = 0;
							$uarr = array();
							for ($x=0; $x<sizeof($arr); $x++) {
								$uarr[] = $string = preg_replace("/&nbsp;/",'',htmlentities(utf8_encode(trim($arr[$x]))));
							}

							// check name:
							if (strlen($uarr[0]) >= 1) {
								$name = $uarr[0];
							} else {
								$name = "<span class=\"error\">ERROR</span>\n";
								$error |= 1;
							}

							// check property:
							$q = $db->prepare("SELECT `id`, CONCAT(`no`, ' ', `street`) as `property` FROM `properties` WHERE `sname` =?");
							$q->bindValue(1, $uarr[6]);
							$q->execute();
							if ($q->rowCount() > 0) {
								$r = $q->fetch(PDO::FETCH_ASSOC);
								$property = $r['property'];
								$pid = $r['id'];
							} else {
								$property = "<span class=\"error\">ERROR ('$uarr[6]')</span>\n";
								$error |= 2;
							}

							// check room no:
							if (!isset($pid)) {
								$room = $uarr[7];		// invalid property so no use checking room
							} else {
								$q = $db->prepare("SELECT * FROM `rooms` WHERE `no` =? AND `propertyid` = '$pid' AND `del` = 'n' ");
								$q->bindValue(1, $uarr[7]);
								$q->execute();
								if ($q->rowCount() > 0) {
									$r = $q->fetch(PDO::FETCH_ASSOC);
									$room = $r['no'];
								} else {
									$room = "<span class=\"error\">ERROR ('$uarr[7]')</span>\n";
									$error |= 4;
								}
							}

							// check start date:
							$ukify = str_replace('/', '-', $uarr[8]);
							if (strtotime($ukify) != 0) {
								$sdate = date("j M Y", strtotime($ukify));
							} else {
								$sdate = "<span class=\"error\">ERROR ('$uarr[8]')</span>\n";
								$error |= 8;
							}

							// check payment period:
							unset($period);
							foreach (array('W' => 'Weekly', 'F' => 'Fortnightly', '4' => 'Four-weekly', 'M' => 'Monthly', 'Q' => 'Quarterly') as $k => $v) {
								$trimstrtoupperuarr11 = trim(strtoupper($uarr[11]));
								if ($trimstrtoupperuarr11[0] == $k) {
									$period = $v;
								}
							}
							if (!isset($period)) {
								$period = "<span class=\"error\">ERROR ('$uarr[11]')</span>\n";
								$error |= 16;
							}

							// check rental amount:
							if (strlen(preg_replace("/([^0-9\\.])/i", "", $uarr[12])) >= 1) { 	//someone might want to set £0 rent. check empty only
								$rent = number_format(preg_replace("/([^0-9\\.])/i", "", $uarr[12]), 2, '.', ',');
							} else {
								$rent = "<span class=\"error\">ERROR ('$uarr[12]')</span>\n";
								$error |= 32;
							}

							// check opening balance (not essential)
							if (strlen(preg_replace("/([^0-9\\.])/i", "", $uarr[13])) >= 1) {
								$obal = number_format(preg_replace("/([^0-9\\.-])/i", "", $uarr[13]), 2, '.', ',');
							} else {
								$obal = number_format(0, 2, '.', ',');
							}

						?>

						<div class="csvline-cont">
							<div class="csvline-row">
								<div class="csvline-subrow">
									<div class="csvline-label">Tenant name: </div>
									<div class="csvline-field"><?php echo $name; ?></div>
									<div class="csvline-label">Mobile: </div>
									<div class="csvline-field"><?php echo $uarr[1]; ?></div>
									<div class="csvline-label">Phone #2: </div>
									<div class="csvline-field"><?php echo $uarr[2]; ?></div>
								</div>
								<div class="csvline-subrow">
									<div class="csvline-label">Date of birth: </div>
									<div class="csvline-field"><?php echo (strtotime($uarr[5]) != 0 ? date("j M Y", strtotime($uarr[5])) : "-") ?></div> <?php //won't work if someone was born on 1st Jan 1970!?>
									<div class="csvline-label">Email: </div>
									<div class="csvline-field"><?php echo $uarr[3]; ?></div>
									<div class="csvline-label">Email #2: </div>
									<div class="csvline-field"><?php echo $uarr[4]; ?></div>
								</div>
							</div>
							<p></p>
							<div class="csvline-row">
								<div class="csvline-subrow">
									<div class="csvline-label">Property: </div>
									<div class="csvline-field"><?php echo $property; ?></div>
									<div class="csvline-label">Room: </div>
									<div class="csvline-field"><?php echo $room; ?></div>
								</div>
								<div class="csvline-subrow">
									<div class="csvline-label">Start date: </div>
									<div class="csvline-field"><?php echo $sdate; ?></div>
									<div class="csvline-label">Min. end date: </div>
									<div class="csvline-field"><?php echo (strtotime("+".$uarr[9], strtotime($uarr[8])) != 0 ? date("j M Y", strtotime ("-1 day", strtotime("+".$uarr[9], strtotime($uarr[8])))) : "-"); ?></div>
									<div class="csvline-label">End date: </div>
									<div class="csvline-field"><?php echo $uarr[10]; ?></div>
								</div>
								<div class="csvline-subrow">
									<div class="csvline-label">Payment period: </div>
									<div class="csvline-field"><?php echo $period; ?></div>
									<div class="csvline-label">Rental amount: </div>
									<div class="csvline-field"><?php echo $rent; //check format ?></div>
									<div class="csvline-label">Opening balance: </div>
									<div class="csvline-field"><?php echo $obal; //check format ?></div>
								</div>
							</div>
						</div>


						<?php

							if ($error > 0) {
								if ($error & 1) {
									echo "						<p>'Tenant name' must not be empty.</p>\n";
								}
								if ($error & 2) {
									echo "						<p>Couldn't find property '".$uarr[6]."' in database.</p>\n";
								}
								if ($error & 4) {
									echo "						<p>Could not find room number ".$uarr[7]." in property '".$uarr[6]."'.</p>\n";
								}
								if ($error & 8) {
									echo "						<p>Invalid start date.</p>\n";
								}
								if ($error & 16) {
									echo "						<p>'Payment period' is invalid (should be one of the following: W, F, 4, M, Q)</p>\n";
								}
								if ($error & 32) {
									echo "						<p>'Rental amount' is either empty or invalid.</p>\n";
								}
							}
						}	//move to next row

						$rand = rand();
						echo "		<input type=\"hidden\" id=\"uploadid\" value=\"".$rand."\" /> ";
						file_put_contents("db/upload/upload".$rand, $csv, LOCK_EX);

						if ($error == 0) {
							echo "		<p>\n";
							echo "			<span class=\"datecont\">\n";
							echo "				<label for=\"importopeningdate\">Select opening date for this import:</label>\n";
							echo "				<input class=\"genericdate\" id=\"importopeningdate\" value=\"".date('j M Y')."\" readonly=\"readonly\" />\n";
							echo "			</span>\n";
							echo "			<input type=\"button\" class=\"button\" id=\"importbtn\" value=\"Import Tenancies!\" />\n";
							echo "		</p>\n";
							echo "		<div id=\"output\"></div>\n";
						} else {
							echo "		<p>Please amend errors highlighted above and <a href=\"".$_SERVER['REQUEST_URI']."\">click here to re-upload</a>.</p>";

						}
					} else {
						echo "		<p>Incorrect number of columns - please check the syntax requirements and <a href=\"properties.php?s=import\">click here to try again</a>.</p>\n";
					}
				}
			} else {
			?>

			<p>To import a list of tenants and tenancy information, first create a CSV file with the following headings in row 1 (compulsory fields marked with a <span class="asterisk">*</span>):</p>
			<ul>
				<li>Name<span class="asterisk">*</span></li>
				<li>Mobile no.</li>
				<li>Secondary/ Landline no.</li>
				<li>Email address #1</li>
				<li>Email address #2</li>
				<li>Date of birth</li>
				<li>Property (short name)<span class="asterisk">*</span></li>
				<li>Room no.<span class="asterisk">*</span></li>
				<li>Tenancy start date<span class="asterisk">*</span></li>
				<li>Minimum period</li>
				<li>Tenancy end date</li>
				<li>Payment period<span class="asterisk">*</span></li>
				<li>Rental amount<span class="asterisk">*</span></li>
				<li>Opening balance</li>
			</ul>

			<h2>Notes</h2>

			<ol>
				<li>The columns need to be in the exact same order as the above list for the import to work.</li>
				<li>Properties need to be imported/entered into PPMA before tenancy information can be set.</li>
				<li>The 'Property (short name)' field in the tenancy import should match the 'short name' of the property as set in <a href="properties.php">Properties</a>.</li>
				<li>'Minimum period', if entered, should be written as 'X months' or 'X weeks', where 'X' is a numeric figure (e.g. '6 months', '8 weeks'). If left blank, no minimum period will be assumed.</li>
				<li>For a single-let property, please use '0' (zero) as the Room no.</li>
				<li>For 'Payment period', please use one of the following digits:
					<ol type="i">
						<li><i>W</i> (weekly)</li>
						<li><i>F</i> (fortnightly)</li>
						<li><i>4</i> (four-weekly)</li>
						<li><i>M</i> (monthly)</li>
						<li><i>Q</i> (quarterly)</li>
					</ol>
				</li>
				<li>'Opening balance' is the balance of the tenancy at the date of import. If not entered, it will default to zero. Both the opening balance and the opening balance date can be amended at any time.</li>
			</ol>
			<p><span id="yourBtn" class="button" onclick="getFile()">Click here to upload the CSV</span></p>
			<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST" enctype="multipart/form-data" name="myForm">
				<span class="hidden"><input id="upfile" name="file" type="file" value="upload" onchange="sub(this)"/></span>
			</form>
			<?php
			}
			?>

			<?php } else if ($s == 'feesexp') { ?>

			<?php 

			if (isset($_GET['d'])) {
				// $q = $db->prepare("
					// SELECT
						// tenancies.id AS `id`,
						// tenants.name AS `name`
					// FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
					// WHERE tenancies.id = ?
					// AND tenants.clientid = ?
				// ");
				$q = $db->prepare("
					SELECT
						tt.tenancyid AS `id`,
						tt.tenantname AS `name`,
						tt.tenantid AS `tid`,
						properties.sname AS `sname`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							tenancies.id AS `tenancyid`,
							tenancies.tenantid AS `tenantid`,
							tenancies.roomid AS `roomid`,
							tenants.name AS `tenantname`,
							tenancies.enddate AS `tenancyenddate`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
					LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
					WHERE properties.clientid = $clientid
					AND tt.tenancyid = ?
					ORDER BY tt.tenantname, (tt.tenancyenddate IS NOT NULL), tt.tenancyenddate DESC;
				");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					$tenant = $r['name']." (".$r['sname'].") [#".$r['id']."]";
					$tenancyid = $r['id'];
					$tenantid = $r['tid'];
				} else {
					$tenant = 'all tenants';
					$tenancyid = 0;
					$tenantid = 0;
				}
			} else {
				$tenant = 'all tenants';
				$tenancyid = 0;
				$tenantid = 0;
			}

			?>
			<input type="hidden" id="getd" value="<?php echo $tenancyid ?>" />
			<input type="hidden" id="gett" value="<?php echo $tenantid ?>" />


			<div class="lfieldset">
				<h2>Fees and Expenses for <?php echo $tenant ?></h2>
				<label for="tenantfeesxac">Select tenant:</label>
				<span class="xaccont">
					<input id="tenantfeesxac" />
					<div class="xacmenu">
						Javascript fail
					</div>
				</span>
				<span id="tenantfeesxacinfo"></span>
				<br /><br />
				<span>Fees are assigned to tenancies. Tenants with multiple tenancies will have multiple entries in the above list.</span>
			</div>
			<?php if ($tenancyid == 0) {
					echo "				<div id=\"pleaseselecttenant\">Please select a tenant from the above drop-down list to add a new item.</div>\n";
				} else { ?>
			<div class="lfieldset">
				<h2>Add a new item</h2>
				<p>
					<span class="datecont">
						<label for="addfeedate">Date:</label>
						<input class="genericdate" id="addfeedate" />
					</span>
					<label for="addfeeamount">Amount:</label>
					<input id="addfeeamount" />
					<label for="addfeedesctenant">Description:</label>
					<input id="addfeedesctenant" />
					<span class="checkboxcont">
						<label for="addfeepayabletolandlord">Payable to Landlord?</label>
						<input id="addfeepayabletolandlord" type="checkbox" class="checkbox" />
					</span>
					<input type="button" class="button" id="addfee" value="Add"/>
				</p>
			</div>

			<?php } ?>

			<table>
				<thead>
					<tr>
						<th>Date</th>
						<?php if ($tenancyid == 0) { ?> <th>Tenant (property)</th> <?php } ?>
						<th>Amount</th>
						<th>Payable to</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody><?php echo "\n";
				if ($tenancyid == 0) {
					$q = $db->prepare("
						SELECT
							tenantfees.id AS `id`,
							tenantfees.date AS `date`,
							CONCAT (tt.name,' (',properties.sname,')') AS `tenancy`,
							tenantfees.amount AS `amount`,
							tenantfees.payableto AS `payableto`,
							tenantfees.desc AS `desc`
						FROM `tenantfees`
							LEFT JOIN (
								(SELECT
									tenants.name AS `name`,
									tenancies.id AS `tenancyid`,
									tenancies.roomid AS `roomid`,
									tenants.clientid AS `clientid`
								FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
								LEFT JOIN (`rooms`
									LEFT JOIN `properties`
									ON rooms.propertyid = properties.id) 
								ON tt.roomid = rooms.id)
							ON tenantfees.tenancyid = tt.tenancyid
						WHERE tt.clientid = $clientid
					");
				} else { 
					$q = $db->prepare("
						SELECT
							tenantfees.id AS `id`,
							tenantfees.date AS `date`,
							CONCAT (tt.name,' (',properties.sname,')') AS `tenancy`,
							tenantfees.amount AS `amount`,
							tenantfees.payableto AS `payableto`,
							tenantfees.desc AS `desc`
						FROM `tenantfees`
							LEFT JOIN (
								(SELECT
									tenants.name AS `name`,
									tenancies.id AS `tenancyid`,
									tenancies.roomid AS `roomid`,
									tenants.clientid AS `clientid`
								FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
								LEFT JOIN (`rooms`
									LEFT JOIN `properties`
									ON rooms.propertyid = properties.id) 
								ON tt.roomid = rooms.id)
							ON tenantfees.tenancyid = tt.tenancyid
						WHERE tt.clientid = $clientid
						AND tt.tenancyid = ?
					");
					$q->bindValue(1, $tenancyid, PDO::PARAM_INT);
				}
				$q->execute();
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
				foreach ($r as $row) {
					echo "					<tr data-id=\"".$row['id']."\">\n";
					echo "						<td>".date('j M Y', strtotime($row['date']))."</td>\n";
					if ($tenancyid == 0) {
						echo "						<td>".$row['tenancy']."</td>\n";
					}
					echo "						<td>".number_format($row['amount'] / 100, 2, '.', ',')."</td>\n";
					echo "						<td>".($row['payableto'] == 'l' ? 'Landlord' : $clientname)."</td>\n";
					echo "						<td>".$row['desc']."</td>\n";
					echo "					</tr>\n";
				}

				?></tbody>
			</table>

			<div id="xaclookuplist">
				<ul><?php
				echo "\n";
				foreach ($db->query("
					SELECT
						tt.tenancyid AS `tid`,
						tt.tenantname AS `tname`,
						properties.sname AS `sname`,
						tt.tenantid AS `ttid`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							tenancies.id AS `tenancyid`,
							tenancies.tenantid AS `tenantid`,
							tenancies.roomid AS `roomid`,
							tenants.name AS `tenantname`,
							tenancies.enddate AS `tenancyenddate`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
					LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
					WHERE properties.clientid = $clientid
					ORDER BY tt.tenantname, (tt.tenancyenddate IS NOT NULL), tt.tenancyenddate DESC;
				") as $trow) {
					echo "				<li data-tenancyid=\"".$trow['tid']."\" data-tenantid=\"".$trow['ttid']."\" data-address=\"".$trow['lname']."\">".$trow['tname']." (".$trow['sname'].") [#".$trow['tid']."]</li>\n";
				};
				?>
				</ul>
			</div> 

			<?php } else if ($s == 'adjustments') { ?>

			<?php

			if (isset($_GET['d'])) {
				$q = $db->prepare("
					SELECT
						tt.tenancyid AS `id`,
						tt.tenantname AS `name`,
						tt.tenantid AS `tid`,
						properties.sname AS `sname`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							tenancies.id AS `tenancyid`,
							tenancies.tenantid AS `tenantid`,
							tenancies.roomid AS `roomid`,
							tenants.name AS `tenantname`,
							tenancies.enddate AS `tenancyenddate`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
					LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
					WHERE properties.clientid = $clientid
					AND tt.tenancyid = ?
					ORDER BY tt.tenantname, (tt.tenancyenddate IS NOT NULL), tt.tenancyenddate DESC;
				");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					$tenant = $r['name']." (".$r['sname'].") [#".$r['id']."]";
					$tenancyid = $r['id'];
					$tenantid = $r['tid'];
				} else {
					$tenant = 'all tenants';
					$tenancyid = 0;
					$tenantid = 0;
				}
			} else {
				$tenant = 'all tenants';
				$tenancyid = 0;
				$tenantid = 0;
			}

			?>
			<input type="hidden" id="getd" value="<?php echo $tenancyid ?>" />
			<input type="hidden" id="gett" value="<?php echo $tenantid ?>" />

			<div class="lfieldset">
				<h2>Rent adjustments for <?php echo $tenant ?></h2>
				<label for="adjustmentsxac">Select tenant:</label>
				<span class="xaccont">
					<input id="adjustmentsxac" />
					<div class="xacmenu">
						Javascript fail
					</div>
				</span>
				<span id="adjustmentsxacinfo"></span>
				<br /><br />
				<span>Adjustments are assigned to tenancies. Tenants with multiple tenancies will have multiple entries in the above list.</span>
			</div>

			<?php if ($tenancyid == 0) {
					echo "				<div id=\"pleaseselecttenant\">Please select a tenant from the above drop-down list to add a new adjustment.</div>\n";
				} else { ?>
			<div class="lfieldset">
				<h2>Add a new adjustment (positive for credit, negative for debit)</h2>
				<p>
					<span class="datecont">
						<label for="addadjustmentdate">Date:</label>
						<input id="addadjustmentdate" class="genericdate" />
					</span>
					<label for="addadjustmentamount">Amount:</label>
					<input id="addadjustmentamount" />
					<label for="addadjustmentdesc">Description:</label>
					<input id="addadjustmentdesc" />
					<span class="checkboxcont">
						<label for="addadjustmentmgmt">Apply mgmt. fee?</label>
						<input id="addadjustmentmgmt" type="checkbox" class="checkbox" />
					</span>
					<input type="button" class="button" id="addadjustment" value="Add"/>
				</p>
			</div>

			<?php } ?>

			<!-- table -->

			<table>
				<thead>
					<tr>
						<th>Date</th>
						<?php if ($tenancyid == 0) { ?> <th>Tenant (property)</th> <?php } ?>
						<th>Amount</th>
						<th>Management fee applied</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody><?php echo "\n";
				if ($tenancyid == 0) {
					$q = $db->prepare("
						SELECT
							adjustments.id AS `id`,
							adjustments.date AS `date`,
							CONCAT (tt.name,' (',properties.sname,')') AS `tenancy`,
							adjustments.amount AS `amount`,
							adjustments.applymgmt AS `applymgmt`,
							adjustments.desc AS `desc`
						FROM `adjustments`
							LEFT JOIN (
								(SELECT
									tenants.name AS `name`,
									tenancies.id AS `tenancyid`,
									tenancies.roomid AS `roomid`,
									tenants.clientid AS `clientid`
								FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
								LEFT JOIN (`rooms`
									LEFT JOIN `properties`
									ON rooms.propertyid = properties.id) 
								ON tt.roomid = rooms.id)
							ON adjustments.tenancyid = tt.tenancyid
						WHERE tt.clientid = $clientid
					");
				} else { 
					$q = $db->prepare("
						SELECT
							adjustments.id AS `id`,
							adjustments.date AS `date`,
							CONCAT (tt.name,' (',properties.sname,')') AS `tenancy`,
							adjustments.amount AS `amount`,
							adjustments.applymgmt AS `applymgmt`,
							adjustments.desc AS `desc`
						FROM `adjustments`
							LEFT JOIN (
								(SELECT
									tenants.name AS `name`,
									tenancies.id AS `tenancyid`,
									tenancies.roomid AS `roomid`,
									tenants.clientid AS `clientid`
								FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
								LEFT JOIN (`rooms`
									LEFT JOIN `properties`
									ON rooms.propertyid = properties.id) 
								ON tt.roomid = rooms.id)
							ON adjustments.tenancyid = tt.tenancyid
						WHERE tt.clientid = $clientid
						AND tt.tenancyid = ?
					");
					$q->bindValue(1, $tenancyid, PDO::PARAM_INT);
				}
				$q->execute();
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
				foreach ($r as $row) {
					echo "					<tr data-id=\"".$row['id']."\">\n";
					echo "						<td>".date('j M Y', strtotime($row['date']))."</td>\n";
					if ($tenancyid == 0) {
						echo "						<td>".$row['tenancy']."</td>\n";
					}
					echo "						<td>".number_format($row['amount'] / 100, 2, '.', ',')."</td>\n";
					echo "						<td>".($row['applymgmt'] == 'y' ? 'Yes' : 'No')."</td>\n";
					echo "						<td>".$row['desc']."</td>\n";
					echo "					</tr>\n";
				}

				?></tbody>
			</table>


			<div id="xaclookuplist">
				<ul><?php
				echo "\n";
				foreach ($db->query("
					SELECT
						tt.tenancyid AS `tid`,
						tt.tenantname AS `tname`,
						properties.sname AS `sname`,
						tt.tenantid AS `ttid`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							tenancies.id AS `tenancyid`,
							tenancies.tenantid AS `tenantid`,
							tenancies.roomid AS `roomid`,
							tenants.name AS `tenantname`,
							tenancies.enddate AS `tenancyenddate`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
					LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tt.roomid = rooms.id
					WHERE properties.clientid = $clientid
					ORDER BY tt.tenantname, (tt.tenancyenddate IS NOT NULL), tt.tenancyenddate DESC;
				") as $trow) {
					echo "				<li data-tenancyid=\"".$trow['tid']."\" data-tenantid=\"".$trow['ttid']."\" data-address=\"".$trow['lname']."\">".$trow['tname']." (".$trow['sname'].") [#".$trow['tid']."]</li>\n";
				};
				?>
				</ul>
			</div> 

			<?php } ?>
		</div>
		<input type="hidden" id="lastfocus" value="input_ref" />

	</body>
</html>
<?php } ?>