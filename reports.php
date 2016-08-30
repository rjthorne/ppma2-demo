<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {
	$pagetitle = 'Reports';

?>
<!DOCTYPE html>
<html>
	<head>
		<?php require 'defs/inc_jscss.php' ?>
		<script type="text/javascript" src="js/reports.js"></script>
	</head>
	<body>
		<?php require 'defs/inc_header.php' ?>
		<div id="subheader">
			<div id="innersubheader">
				<?php if (isset($_GET['s'])) {
					if ($_GET['s'] == 'property') {
						$s = 'property';
					} else if ($_GET['s'] == 'landlord') {
						$s = 'landlord';
					} else if ($_GET['s'] == 'tenant') {
						$s = 'tenant';
					} else if ($_GET['s'] == 'arrears') {
						$s = 'arrears';
					} else if ($_GET['s'] == 'voids') {
						$s = 'voids';
					} else {
						$s = 'roomtable';
					}
				} else {
					$s = 'roomtable';
				}?>
				<span class="<?php echo ($s == 'roomtable' ? 'subtab_active' : 'subtab') ?>" id="roomtable">
					Room Table
				</span>
				<?php /*<span class="<?php echo ($s == 'property' ? 'subtab_active' : 'subtab') ?>" id="property">
					Property
				</span>*/?>
				<span class="<?php echo ($s == 'landlord' ? 'subtab_active' : 'subtab') ?>" id="landlord">
					Landlord
				</span>
				<span class="<?php echo ($s == 'tenant' ? 'subtab_active' : 'subtab') ?>" id="tenant">
					Tenant
				</span>
				<span class="<?php echo ($s == 'arrears' ? 'subtab_active' : 'subtab') ?>" id="arrears">
					Arrears
				</span>
				<?php /* <span class="<?php echo ($s == 'voids' ? 'subtab_active' : 'subtab') ?>" id="voids">
					Voids
				</span> */?>
			</div>
		</div>
		<div id="main">
			<?php if ($s == 'roomtable') { ?>
			
			<?php 
			
			if (isset($_GET['date'])) {
				if (strtotime($_GET['date']) != 0) {
					$date = date('j M Y', strtotime($_GET['date']));
				} else {
					$date = date('j M Y');
				}
			} else {
				$date = date('j M Y');
			}
			
			$dbdate = date('Y-m-d', strtotime($date));
			
			if (isset($_GET['mode'])) {
				if ($_GET['mode'] == 'o' || $_GET['mode'] == 's') {
					$mode = $_GET['mode'];
				} else {
					$mode = 'd';
				}
			} else {
				$mode = 'd';
			}
			
			if (isset($_GET['type'])) {
				if ($_GET['type'] == 'l' || $_GET['type'] == 'm') {
					$type = $_GET['type'];
				} else {
					$type = 'a';
				}
			} else {
				$type = 'a';
			}
			
			function showline ($start, $end, $date, $mode) {
				$s = strtotime($start);
				$e = strtotime($end);
				$d = strtotime($date);
				if ($s == 0) {
					return true;
				} else {
					if ($mode == 's') {
						if ($s <= $d && ($e == 0 || $e >= $d) ) {
							return true;
						} else {
							return false;
						}
					} else if ($mode == 'o') {
						if ($e == 0 || $e >= $d) {
							return true;
						} else {
							return false;
						}
					} else {
						if ($s <= strtotime($date.' +1month') && ($e == 0 || $e >= $d)) {
							return true;
						} else {
							return false;
						}
					}
				}
			}
			
			function choosecolor ($start, $end, $date) {
				$s = strtotime($start);
				$e = strtotime($end);
				$d = strtotime($date);
				if ($s == 0) {
					return 'rtr';
				} else if ($e != 0 && $e <= strtotime($date.' +1month')) {
					return 'rto';
				} else if ($s > $d) {
					return 'rty';
				} else {
					return 'rtg';
				}
			}
			
			
			?>

			<div class="lfieldset" id="reportoptions">
				<h2>Report options</h2>
				<div class="optionline">
					<label for="roomtablepropertytype">Property type:</label>
					<select id="roomtablepropertytype">
						<option value="a"<?php echo ($type == 'a' ? " selected=selected" : "") ?>>All</option>
						<option value="l"<?php echo ($type == 'l' ? " selected=selected" : "") ?>>Leased</option>
						<option value="m"<?php echo ($type == 'm' ? " selected=selected" : "") ?>>Managed</option>
					</select>
					<label for="roomtablemode">Mode:</label>
					<select id="roomtablemode">
						<option value="d"<?php echo ($mode == 'd' ? " selected=selected" : "") ?>>Default (+1 month)</option>
						<option value="s"<?php echo ($mode == 's' ? " selected=selected" : "") ?>>Snapshot</option>
						<option value="o"<?php echo ($mode == 'o' ? " selected=selected" : "") ?>>Open-ended</option>
					</select>
					<span class="datecont">
						<label for="roomtabledate">Date:</label>
						<input id="roomtabledate" class="genericdate" value="<?php echo $date ?>" />
					</span>
					<span class="reportbuttons">
						<input type="button" class="button" id="roomtableupdate" value="Update" />
						<input type="button" class="button" id="roomtableprint" value="Print" />
					</span>
				</div>
			</div>			
			<div id="roomtable">
				<?php
				$q = $db->prepare("
					SELECT
						mp.property AS `property`,
						mp.propertyid AS `propertyid`,
						mp.mcid AS `mcid`,
						rooms.no AS `roomno`,
						tt.tenancyid AS `tenancyid`,
						tt.rent AS `rent`,
						tt.period AS `period`,
						tt.tenantname AS `name`,
						tt.tenantid AS `tenantid`,
						tt.startdate AS `startdate`,
						tt.enddate AS `enddate`,
						tt.phone1 AS `phone1`,
						tt.phone2 AS `phone2`,
						tt.email1 AS `email1`,
						tt.email2 AS `email2`,
						tt.dob AS `dob`
					FROM (
						SELECT
							tenancies.id AS `tenancyid`,
							tenancies.tenantid AS `tenantid`,
							tenancies.roomid AS `roomid`,
							tenancies.rent AS `rent`,
							tenancies.period AS `period`,
							tenants.name AS `tenantname`,
							tenancies.startdate AS `startdate`,
							tenancies.enddate AS `enddate`,
							tenants.phone1 AS `phone1`,
							tenants.phone2 AS `phone2`,
							tenants.email1 AS `email1`,
							tenants.email2 AS `email2`,
							tenants.dob AS `dob`
						FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
						WHERE (tenancies.enddate >= '$dbdate' OR tenancies.enddate IS NULL)
					) tt RIGHT JOIN (
						`rooms` LEFT JOIN (
							SELECT 
								properties.id AS `propertyid`,
								CONCAT (properties.no,' ',properties.street) AS `property`,
								properties.no AS `propertyno`,
								properties.clientid AS `clientid`,
								mgmtcontracts.id AS `mcid`
							FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id
							WHERE mgmtcontracts.startdate <= '$dbdate'
							AND (mgmtcontracts.enddate >= '$dbdate' OR mgmtcontracts.enddate IS NULL)
						) mp ON rooms.propertyid = mp.propertyid
					) ON tt.roomid = rooms.id
					WHERE mp.clientid = $clientid
					AND rooms.del = 'n'
					ORDER BY mp.propertyno, mp.property, rooms.no
				");
				$q->execute();
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
				$rc = $q->rowCount();
				// p($r);
				
				$property = 0;
				foreach ($r as $row) {
					if (showline($row['startdate'], $row['enddate'], $date, $mode)) {
						if ($row['propertyid'] != $property) {
							if ($property != 0) { ?>
						</tbody>
					</table>
					<br />
					<?php }	?> 
					<table>
						<thead>
							<tr>
								<?php if (isset($_GET['jupix']) && $_GET['jupix'] == 'yes') { ?>
								<th colspan="9"><?php echo $row['property'];
								$lq = $db->prepare("SELECT landlords.name AS `name` FROM `mgmtcontracts` LEFT JOIN `landlords` on mgmtcontracts.landlordid = landlords.id WHERE mgmtcontracts.id = ?");
								$lq->execute(array($row['mcid']));
								$lr = $lq->fetch();
								echo " (".$lr['name'].")";
								?></th>
								<?php } else { ?>
								<th colspan="6"><?php echo $row['property'] ?></th>
								<?php } ?>
							</tr>
							<tr>
								<th>Room</th>
								<th>Rent</th>
								<th>Period</th>
								<th>Tenant</th>
								<?php if (isset($_GET['jupix']) && $_GET['jupix'] == 'yes') { ?>
									<th>Phone #</th>
									<th>Email</th>
									<th>D.o.B.</th>
								<?php } ?>
								<th>Start date</th>
								<th>End date</th>
							</tr>
						</thead>
						<tbody>	<?php } ?>
							<tr class="rt <?php echo choosecolor($row['startdate'], $row['enddate'], $date) ?>">
								<td><?php echo $row['roomno'] ?></td>
								<td><?php echo (strlen($row['rent']) > 0 ? number_format($row['rent'] / 100, 2, '.', ',') : "&nbsp") ?></td>
								<td><?php echo $row['period'] ?></td>
								<td><a href="tenants.php?s=detail&d=<?php echo $row['tenantid'] ?>"><?php echo $row['name'] ?></a></td>
								<?php if (isset($_GET['jupix']) && $_GET['jupix'] == 'yes') { ?>
									<td><?php echo $row['phone1'];
									if (strlen($row['phone2']) > 0) {
										echo " / ".$row['phone2'];
									}?></td>
									<td><?php echo $row['email1'];
									if (strlen($row['email2']) > 0) {
										echo "; ".$row['email2'];
									}?></td>
									<td><?php if (strtotime($row['dob']) != 0) {
										echo date('j M Y', strtotime($row['dob']));
									} ?></td>
								<?php } ?>								
								<td><?php echo (strtotime($row['startdate']) != 0 ? date('j M Y', strtotime($row['startdate'])) : "") ?></td>
								<td><?php echo (strtotime($row['enddate']) != 0 ? date('j M Y', strtotime($row['enddate'])) : "") ?></td>
							</tr><?php
					$property = $row['propertyid'];
					}
				}
				if ($rc > 0) { ?>
						</tbody>
					</table>
					<br />
				<?php }	?>
			</div>

			<?php } else if ($s == 'property') { ?>

			<p>Property</p>

			<?php } else if ($s == 'landlord') { ?>

			<?php 

			if (isset($_GET['d'])) {
				$q = $db->prepare("SELECT * FROM `landlords` WHERE `clientid` = $clientid AND `id` = ?");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					// $d = $r['id'];
				} else {
					$q = $db->query("SELECT * FROM `landlords` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
					$r = $q->fetch();
					// $d = $r['id'];
				}
			} else {
				$q = $db->query("SELECT * FROM `landlords` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
				$r = $q->fetch();
				// $d = $r['id'];
			}

			$lid = $r['id']; //temp (?)

			if (isset($_GET['start'])) {
				if (strtotime($_GET['start']) != 0) {
					$start = date('j M Y', strtotime($_GET['start']));
				} else {
					$start = date('j M Y', strtotime("first day of previous month"));
				}
			} else {
				$start = date('j M Y', strtotime("first day of previous month"));
			}
			$dbstart = date('Y-m-d', strtotime($start));

			if (isset($_GET['end'])) {
				if (strtotime($_GET['end']) != 0) {
					$end = date('j M Y', strtotime($_GET['end']));
				} else {
					$end = date('j M Y', strtotime("last day of previous month"));
				}
			} else {
				$end = date('j M Y', strtotime("last day of previous month"));
			}
			$dbend = date('Y-m-d', strtotime($end));


			?>

			<div class="lfieldset" id="reportoptions">
				<h2>Landlord Report for <?php echo $r['name'] ?></h2>
				<div class="optionline">
					<label for="reportdselect">Choose landlord:</label>
					<select id="reportdselect"><?php echo "\n";
						foreach ($db->query("SELECT * FROM `landlords` WHERE `clientid` = $clientid ORDER BY `name`") as $row) {
							echo "					<option value=\"".$row['id']."\"";
							echo ($row['id'] == $r['id'] ? " selected=\"selected\"" : "");
							echo ">".$row['name']."</option>";
						} ?>
					</select>
					<span class="datecont">
						<label for="reportstartdate">Start:</label>
						<input id="reportstartdate" class="genericdate" value="<?php echo $start ?>" />
					</span>
					<span class="datecont">
						<label for="reportenddate">End:</label>
						<input id="reportenddate" class="genericdate" value="<?php echo $end ?>" />
					</span>
					<span class="reportbuttons">
						<input type="button" class="button" id="landlordreportupdate" value="Update" />
						<input type="button" class="button" id="landlordreportxero" value="Xero" />
						<input type="button" class="button" id="landlordreportprint" value="Print" />
					</span>
				</div>
			</div>

			<?php // ===========================================================================================   start copy ?>
			<div id="reportcont">
				<p id="reportaddress" ><?php echo "\n";
					echo "				".$r['name']."<br />\n";
					echo "				".$r['address1']."<br />\n";
					if (strlen(trim($r['address2'])) != 0 ) {
						echo "				".$r['address2']."<br />\n";
					}
					echo "				".$r['town']."<br />\n";
					echo "				".$r['postcode']."<br />\n";
				?>
				</p>
				<p id="reportdate">
					<?php echo date('j M Y', strtotime($end)) ?>
				</p>
				<h1>Landlord Report for <?php echo $r['name']?></h1>
				<input type="hidden" id="llname" value="<?php echo $r['name']?>" /><?php //omg gay
				if (!empty($r['sname'])) {
					$lastname = $r['sname'];
				} else {
					$namearr = explode(' ', strtolower($r['name']));
					$lastname = array_pop($namearr);						
				}		
				?>		
				<input type="hidden" id="invref" value="<?php echo 'lr-'.date('Ymd', strtotime($end)).'-'.$lastname; ?>" />
				<h2>Date range: <?php echo date('j M Y', strtotime($start)) ?> - <?php echo date('j M Y', strtotime($end)) ?></h2><?php echo "\n";
				$pbalances = array();
				

				$mcquery = $db->prepare("
					SELECT
						mgmtcontracts.id AS `id`,
						mgmtcontracts.propertyid AS `pid`,
						mgmtcontracts.startdate AS `sdate`,
						mgmtcontracts.enddate AS `edate`,
						mgmtcontracts.mgmt AS `mgmt`,
						mgmtcontracts.lease AS `lease`
					FROM `mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id
					WHERE landlords.id = ?
				");
				$mcquery->execute(array($lid));
				$mcr = $mcquery->fetchAll(PDO::FETCH_ASSOC);
				$mcps = array();	//properties array
				// p($mcr);
				$debugcount = 0;
				foreach ($mcr as $mc) {
					if (!array_key_exists($mc['pid'], $mcps)) {
						// echo $debugcount."<br>";
						$mcps[$mc['pid']] = array();	//each entry contains a subarray for each property
					}
					// echo "if (".strtotime($mc['sdate'])." <= ".strtotime($end)." && (".strtotime($mc['edate'])." >= ".strtotime($start)." || ".strtotime($mc['edate'])." == 0 ) )<br>";
					if (strtotime($mc['sdate']) <= strtotime($end) && (strtotime($mc['edate']) >= strtotime($start) || strtotime($mc['edate']) == 0 ) ) { // if (started yet AND (not finished OR no end date))
						// p($mcps);
						// p($mc);
						// echo $debugcount."<br>";
						$mcps[$mc['pid']][$mc['id']] = array(); //which in turn contains a subarray for each mgmtcontract IF it's in date
						$mcps[$mc['pid']][$mc['id']]['sdate'] = $mc['sdate'];
						$mcps[$mc['pid']][$mc['id']]['edate'] = $mc['edate'];
						$mcps[$mc['pid']][$mc['id']]['mgmt'] = $mc['mgmt'];
						$mcps[$mc['pid']][$mc['id']]['lease'] = $mc['lease'];
						// p($mcps);
					}
					// p($mcps);
					$debugcount++;
				}
				// p($mcps);

				$ptlarray = array();
				$pcount = 0;
				// p($mcps);
				foreach($mcps as $pid => $p) { 	//property loop FOR EACH [PORTFOLIO] AS [PROPERTY ID] [IN DATE MC ARRAY]
					if (count($p) > 0) {
						// p($p);
						// echo count($p);
						$pcount++; 
						foreach ($db->query("SELECT * FROM `properties` WHERE `id` = $pid") as $pinfo) {
							echo "					<hr /> \n";
							echo "					<div class=\"nobreak\">\n";
							echo "					<h3>".$pinfo['no']." ".$pinfo['street']."</h3>";
						}

						?>
						
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Tenant payments</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php $tptotal = 0;
							$tpstartbal = 0;
							// AND payments.date BETWEEN '$dbstart' AND '$dbend'
							// AND payments.date BETWEEN '$dbstart' AND '$dbend'
							// ==== NEW 5 JAN ====
							unset($mcstart_tp);
							unset($mcend_tp);

							foreach ($p as $mcid => $mc) {
							// p($mc);
								if (!isset($mcstart_tp)) {
									$mcstart_tp = $mc['sdate'];
								} else {
									if ($mc['sdate'] < $mcstart_tp) {
										$mcstart_tp = $mc['sdate'];
									}
								}
								if (!isset($mcend_tp)) {
									$mcend_tp = (strtotime($mc['edate']) > 0 ? $mc['edate'] : '9999-12-31');
								} else {
									if ($mc['edate'] > $mcend_tp) {
										$mcend_tp = (strtotime($mc['edate']) > 0 ? $mc['edate'] : '9999-12-31');
									}
								}	
							}
							if (!isset($mcstart_tp)) {
								$mcstart_tp = 0;
							}
							if (!isset($mcend_tp)) {
								$mcend_tp = '9999-12-31';
							}
							
							
							// echo $mcstart_tp;
							// echo "<br>";
							// echo $mcend_tp;
							

							// ==== NEW 5 JAN ====
							foreach ($db->query("
								(
									SELECT
										CONCAT ('rent payment') AS `desc`,
										tt.tenantname AS `name`,
										tt.tenantid AS `tenantid`,
										rooms.no AS `room`,
										payments.id AS `paymentid`,
										payments.date AS `date`,
										payments.amount AS `amount`
									FROM `payments`
										LEFT JOIN ((
											SELECT
												tenancies.id AS `tenancyid`,
												tenancies.roomid AS `roomid`,
												tenants.name AS `tenantname`,
												tenants.id AS `tenantid`,
												tenancies.startdate AS `startdate`
											FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
											LEFT JOIN (`rooms` 
												LEFT JOIN `properties` 
												ON rooms.propertyid = properties.id)
											ON tt.roomid = rooms.id)
										ON payments.contactid = tt.tenancyid
									WHERE properties.id = $pid
									AND payments.contacttype = 't'

								) UNION (
									SELECT
										CONCAT ('fee: ', tenantfees.desc) AS `desc`,
										tt.tenantname AS `name`,
										tt.tenantid AS `tenantid`,
										rooms.no AS `room`,
										payments.id AS `paymentid`,
										payments.date AS `date`,
										payments.amount AS `amount`
									FROM `payments`	LEFT JOIN (
										`tenantfees` LEFT JOIN (
											(
												SELECT
													tenancies.id AS `tenancyid`,
													tenancies.roomid AS `roomid`,
													tenants.name AS `tenantname`,
													tenants.id AS `tenantid`,
													tenancies.startdate AS `startdate`
												FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
											) tt LEFT JOIN (
												`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id
											) ON tt.roomid = rooms.id
										) ON tenantfees.tenancyid = tt.tenancyid
									) ON payments.contactid = tenantfees.id
									WHERE properties.id = $pid
									AND payments.contacttype = 'f'
									AND tenantfees.payableto = 'l'

								)
								ORDER BY `date`
							") as $payment) {

								if ($payment['date'] >= $mcstart_tp && $payment['date'] <= $mcend_tp ) {		//mc range
									if ($payment['date'] >= $dbstart && $payment['date'] <= $dbend ) {		//report range
										$tptotal += $payment['amount'];
								?>
									<tr data-type="rent">
										<td class="date"><?php echo date('j M Y', strtotime($payment['date']))?></td>
										<td><?php echo "Room ".$payment['room']." - <a href=\"reports.php?s=tenant&d=".$payment['tenantid']."\">".$payment['name']."</a> (".$payment['desc'].")" ?></td>
										<td class="amount"><a href="bank.php?s=detail&d=<?php echo $payment['paymentid'] ?>"><?php echo number_format($payment['amount'] / 100, 2, '.', ',') ?></a></td>
									</tr>
								<?php 
									} else if ($payment['date'] < $dbstart) {
										$tpstartbal += $payment['amount'];
									}
								} else if ($payment['date'] < $mcstart_tp) {		//fixing bernhard error (this counts property transactions from before the MC start date but doesn't take into account the landlord might be different)
									$tpstartbal += $payment['amount'];
									// echo "DERP";
								}

							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total tenant payments:</td>
									<td class="total amount"><?php echo number_format($tptotal / 100, 2, '.', ',') ?></td>
								</tr>
							</tfoot>
						</table>
						</div>

						<br />

						<p class="nobreak">
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Management fees</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php  
							// p($p);
							$mgmttotal = 0;
							$mgmtstartbal = 0;
							foreach ($p as $mcid => $mc) {
								if ($mc['mgmt'] != 100) {	//@@@@@@@@@to come back to leases
									$mcstart = $mc['sdate'];
									$mcend = (strtotime($mc['edate']) > 0 ? $mc['edate'] : '9999-12-31');
									$mcpaymenttotal = 0;
									$mcpaymentstartbal = 0;
									// echo $mcstart;
									// echo "<br>";
									// echo $mcend;
									foreach ($db->query("
										SELECT
											payments.date AS `date`,
											payments.amount AS `amount`
										FROM `payments`
											LEFT JOIN ((
												SELECT
													tenancies.id AS `tenancyid`,
													tenancies.roomid AS `roomid`
												FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
												LEFT JOIN (`rooms` 
													LEFT JOIN `properties` 
													ON rooms.propertyid = properties.id)
												ON tt.roomid = rooms.id)
											ON payments.contactid = tt.tenancyid
										WHERE properties.id = $pid
										AND payments.contacttype = 't'
										AND (payments.date BETWEEN '$mcstart' AND '$mcend')
									") as $payment) {
										if ($payment['date'] >= $dbstart && $payment['date'] <= $dbend ) {		//report range
											$mcpaymenttotal += $payment['amount'] * $mc['mgmt'] / 100;
										} else if ($payment['date'] < $dbstart) {
											// $mcpaymentstartbal += $payment['amount'] * $mc['mgmt'] / 100;	//does not look for MCs before report date, new function after p loop
										}
									}
									
							
								

									?>
								<tr data-type="man">
									<td class="date"><?php echo date('j M Y', strtotime($end))?></td>
									<td>Management fees at <?php echo $mc['mgmt'] ?>% on rent taken</td>
									<td class="amount"><?php echo number_format($mcpaymenttotal / 100 * -1, 2, '.', ',') ?></td>
								</tr>
									<?php

									// ADJUSTMENTS
									foreach ($db->query("
										SELECT
											adjustments.date AS `date`,
											adjustments.amount AS `amount`,
											adjustments.desc AS `desc`,
											tt.tenantname AS `tenantname`
										FROM `adjustments`
											LEFT JOIN ((
												SELECT
													tenancies.id AS `tenancyid`,
													tenants.name AS `tenantname`,
													tenancies.roomid AS `roomid`
												FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
												LEFT JOIN (`rooms` 
													LEFT JOIN `properties` 
													ON rooms.propertyid = properties.id)
												ON tt.roomid = rooms.id)
											ON adjustments.tenancyid = tt.tenancyid
										WHERE properties.id = $pid
										AND (adjustments.date BETWEEN '$mcstart' AND '$mcend')
										AND adjustments.applymgmt = 'y'
										ORDER BY adjustments.date
									") as $adjustment) {
										if ($adjustment['date'] >= $dbstart && $adjustment['date'] <= $dbend ) {		//report range
								?>		
								<tr data-type="man">
									<td class="date"><?php echo date('j M Y', strtotime($adjustment['date']))?></td>
									<td>Management fee at <?php echo $mc['mgmt'] ?>% on adjustment: <?php echo $adjustment['tenantname'] ?> - <?php echo $adjustment['desc'] ?> </td>
									<td class="amount"><?php echo number_format($adjustment['amount'] * $mc['mgmt'] / 100 / 100 * -1, 2, '.', ',') ?></td>
								</tr>									
								<?php		
											$mcpaymenttotal += $adjustment['amount'] * $mc['mgmt'] / 100;
										} else if ($adjustment['date'] < $dbstart) {
											// $mcpaymentstartbal += $adjustment['amount'] * $mc['mgmt'] / 100;		//does not look for MCs before report date, new function after p loop
										}
									}										
									$mgmttotal += $mcpaymenttotal;
									// $mgmtstartbal += $mcpaymentstartbal;			//does not look for MCs before report date, new function after p loop					
								}
							}	
							
							//new mc startbal function
							$allrentpayments = array();
							foreach ($db->query("
								SELECT
									payments.date AS `date`,
									payments.amount AS `amount`
								FROM `payments`
									LEFT JOIN ((
										SELECT
											tenancies.id AS `tenancyid`,
											tenancies.roomid AS `roomid`
										FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
										LEFT JOIN (`rooms` 
											LEFT JOIN `properties` 
											ON rooms.propertyid = properties.id)
										ON tt.roomid = rooms.id)
									ON payments.contactid = tt.tenancyid
								WHERE properties.id = $pid
								AND payments.contacttype = 't'
							") as $propertyrentpayment) {		//this gives me a list of all payments against the property, need to save it as an array then loop through all mgmtcontracts checking the array
								$allrentpayments[] = $propertyrentpayment;
							}
							// p($allrentpayments);
							
							$alladjustments = array();
							foreach ($db->query("
								SELECT
									adjustments.date AS `date`,
									adjustments.amount AS `amount`,
									adjustments.desc AS `desc`,
									tt.tenantname AS `tenantname`
								FROM `adjustments`
									LEFT JOIN ((
										SELECT
											tenancies.id AS `tenancyid`,
											tenants.name AS `tenantname`,
											tenancies.roomid AS `roomid`
										FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) tt
										LEFT JOIN (`rooms` 
											LEFT JOIN `properties` 
											ON rooms.propertyid = properties.id)
										ON tt.roomid = rooms.id)
									ON adjustments.tenancyid = tt.tenancyid
								WHERE properties.id = $pid
								AND adjustments.applymgmt = 'y'
							") as $propertyadjustment) {
								$alladjustments[] = $propertyadjustment;
							}
							
							//	this next bit originally had:
							//	WHERE landlords.id = ".$r.['id']."
							//	...at the end. taken it out as it might mess some other balances out, to access and possibly correct when we get a property transfer
							foreach ($db->query("
								SELECT
									mp.startdate AS `startdate`,
									mp.enddate AS `enddate`,
									mp.mgmt AS `mgmt`
								FROM (
									SELECT 
										mgmtcontracts.landlordid AS `landlordid`,
										mgmtcontracts.startdate AS `startdate`,
										mgmtcontracts.enddate AS `enddate`,
										mgmtcontracts.mgmt AS `mgmt`
									FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id WHERE properties.id = $pid
								) mp LEFT JOIN `landlords` ON mp.landlordid = landlords.id
							") as $mcrentpaymentcheck) {
								// echo "meep";
								if (empty($mcrentpaymentcheck['enddate'])) {
									$mcrentpaymentcheck['enddate'] = '9999-12-31';
								}
								foreach ($allrentpayments as $propertyrentpayment) {
									if ($propertyrentpayment['date'] >= $mcrentpaymentcheck['startdate'] && $propertyrentpayment['date'] <= $mcrentpaymentcheck['enddate'] && $propertyrentpayment['date'] < $dbstart) {
										if ($mcrentpaymentcheck['mgmt'] != 100) {
											$mcpaymentstartbal += $propertyrentpayment['amount'] * $mcrentpaymentcheck['mgmt'] / 100; 
										}
									}
								}
								foreach ($alladjustments as $propertyadjustment) {
									if ($propertyadjustment['date'] >= $mcrentpaymentcheck['startdate'] && $propertyadjustment['date'] <= $mcrentpaymentcheck['enddate'] && $propertyadjustment['date'] < $dbstart) {
										if ($mcrentpaymentcheck['mgmt'] != 100) {
											$mcpaymentstartbal += $propertyadjustment['amount'] * $mcrentpaymentcheck['mgmt'] / 100; 
										}
									}								
								}
							}
							// p($mcrentpaymentcheck);
							$mgmtstartbal += $mcpaymentstartbal;
							
							?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total management fee deduction:</td>
									<td class="total amount"><?php echo number_format($mgmttotal / 100 * -1, 2, '.', ','); ?></td>
								</tr>
							</tfoot>
						</table>
						</p>

						

						<p class="nobreak">
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Letting fees</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php $lftotal = 0; $lfstartbal = 0;
							/* foreach ($db->query("SELECT * FROM `propertyfees` WHERE `propertyid` = $pid AND type = 'l' AND `date` between '$dbstart' AND '$dbend' ORDER BY `date`") as $lfee) { */
							foreach ($db->query("
								SELECT 
									propertyfees.id AS `id`,
									propertyfees.date AS `date`,
									CONCAT(properties.no, ' ', properties.street) AS `property`,
									propertyfees.amount AS `amount`,
									propertyfees.desc AS `desc`
								FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id
								WHERE properties.id = $pid
								AND propertyfees.type = 'l'
								ORDER BY propertyfees.date
							") as $lfee) {	// @@@@@@@@@@@@@@@@@@@@ NEED TO ADD THE $mcstart_tp / $mcend_tp check in here! @@@@@@@@@@@@@@@@@@@@
								if ($lfee['date'] >= $dbstart && $lfee['date'] <= $dbend) {
									$lftotal += $lfee['amount'];
							?>
								<tr data-type="let">
									<td class="date"><?php echo date('j M Y', strtotime($lfee['date']))?></td>
									<td><?php echo $lfee['desc'] ?></td>
									<td class="amount"><?php echo number_format($lfee['amount'] / 100 * -1, 2, '.', ',') ?></td></td>
								</tr>
							<?php
								} else if ($lfee['date'] < $dbstart) {
									$lfstartbal += $lfee['amount'];
								}
							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total letting fees:</td>
									<td class="total amount"><?php echo number_format($lftotal / 100 * -1, 2, '.', ',') ?></td>
								</tr>
							</tfoot>
						</table>
						</p>

						

						<p class="nobreak">
						<table>
							<thead>
								<tr>
									<th colspan="3"><h4>Other fees & expenses</h4></th>
									<input type="hidden" class="pid" value="<?php echo $pid ?>" />
								</tr>
							</thead>
							<tbody>
							<?php $ftotal = 0; $fstartbal = 0;
							/* foreach ($db->query("SELECT * FROM `propertyfees` WHERE `propertyid` = $pid AND type = 'o' AND `date` between '$dbstart' AND '$dbend' ORDER BY `date`") as $fee) { */
							foreach ($db->query("
								SELECT 
									propertyfees.id AS `id`,
									propertyfees.date AS `date`,
									propertyfees.amount AS `amount`,
									propertyfees.desc AS `desc`,
									propertyfees.type AS `type`
								FROM `propertyfees` LEFT JOIN (`mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id) ON propertyfees.mcid = mgmtcontracts.id
								WHERE properties.id = $pid
								AND (propertyfees.type = 'o' OR propertyfees.type = 'm')
								ORDER BY propertyfees.date
							") as $fee) {	// @@@@@@@@@@@@@@@@@@@@ NEED TO ADD THE $mcstart_tp / $mcend_tp check in here! @@@@@@@@@@@@@@@@@@@@
								if ($fee['date'] >= $dbstart && $fee['date'] <= $dbend) {
								$ftotal += $fee['amount'];
							?>
								<tr data-type="<?php echo ($fee['type'] == 'm' ? 'main' : 'other') ?>">
									<td class="date"><?php echo date('j M Y', strtotime($fee['date']))?></td>
									<td><?php echo $fee['desc'] ?></td>
									<td class="amount"><?php echo number_format($fee['amount'] / 100 * -1, 2, '.', ',') ?></td></td>
								</tr>
							<?php
								} else if ($fee['date'] < $dbstart) {
									$fstartbal += $fee['amount'];
								}
							} ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
									<td class="total">Total other fees & expenses:</td>
									<td class="total amount"><?php echo number_format($ftotal / 100 * -1, 2, '.', ',') ?></td>
								</tr>
							</tfoot>
						</table>
						</p>

						<br />
						<?php 	//landlord payments here so we can use them for start balances
						// AND landlords.id = $lid
						$llpayments = 0; $llpaystartbal = 0;
						foreach ($db->query("
							SELECT
								payments.amount AS `amount`,
								payments.date AS `date`
							FROM `payments` LEFT JOIN (
								(
									SELECT
										mgmtcontracts.id AS `mcid`,
										mgmtcontracts.landlordid AS `lid`,
										properties.id AS `pid`
									FROM `mgmtcontracts` LEFT JOIN `properties` ON mgmtcontracts.propertyid = properties.id
								) mp LEFT JOIN `landlords` ON mp.lid = landlords.id
							) ON payments.contactid = mp.mcid
							WHERE payments.contacttype = 'l'
							
							AND mp.pid = $pid
						") as $llpayment) {
							if ($llpayment['date'] >= $dbstart && $llpayment['date'] <= $dbend) {
								$llpayments += $llpayment['amount'];
							} else if ($llpayment['date'] < $dbstart) {
								$llpaystartbal += $llpayment['amount'];
							}
						}
						// p($llpaystartbal);
						?>
						<table>

							<tr>
								<td class="borderless total alignright" colspan="2">Property total income:</td>
								<td class="borderless total amount"><?php echo number_format($tptotal / 100, 2, '.', ',') ?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Property total deductions:</td>
								<td class="borderless total amount"><?php echo number_format((($mgmttotal / 100) + ($lftotal / 100) + ($ftotal / 100)) * -1 , 2, '.', ',') ?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Property report total:</td>
								<td class="borderless total amount"><?php
								$prb = round(($tptotal / 100) - ($mgmttotal / 100) - ($lftotal / 100) - ($ftotal / 100), 2);
								echo number_format($prb, 2, '.', ',') ?> <br /><br /></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Property opening balance:</td>
								<td class="borderless total amount"><?php
								// $pob = 610.24; //dave
								// $pob = 1388.99; //andrew
								// $pob = 1489.00; //niraj
								// $pob = 166372; //tsen
								$pob = round($tpstartbal - $mgmtstartbal - $lfstartbal - $fstartbal + $llpaystartbal);
								// echo "$tpstartbal - $mgmtstartbal - $lfstartbal - $fstartbal + $llpaystartbal = \n";
								echo number_format($pob / 100, 2, '.', ',');

								?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Less payments made to Landlord:</td>
								<td class="borderless total amount"><?php 

								echo number_format($llpayments / 100, 2, '.', ',');

								?></td>
							</tr>
							<tr>
								<td class="borderless total alignright" colspan="2">Payable to Landlord:</td>
								<td class="borderless total amount"><?php 
								$ptl = ($pob / 100) + $prb + ($llpayments / 100);
								echo number_format($ptl, 2, '.', ',');
								$ptlarray[$pinfo['no']." ".$pinfo['street']] = number_format($ptl, 2, '.', '');
								?></td>
							</tr>
						</table>

						
						

						<?php
						// echo "prb: ".$prb." <br>";
						// echo "pob: ".$pob." <br>";
						// echo "llp: ".$llpayments." <br>";

						// d(($tptotal / 100) ." ". ($mgmttotal / 100) ." ". ($lftotal / 100) ." ". ($ftotal / 100));

						$pbalance = array($pinfo['no']." ".$pinfo['street'], ($tptotal / 100) - ($mgmttotal / 100) - ($lftotal / 100) - ($ftotal / 100));
						$pbalances[] = $pbalance;
					}
				}	// property loop

				if ($pcount > 1) { ?>

				<table style="display:none"> 	<!-- depreciated, being lazy here -->
					<tr>
						<th colspan="3"><h4>Total balances</h4></th>
					</tr>
					<?php $cbalance = 0;
					foreach ($pbalances as $pbalance) { ?>
					<tr>
						<td class="date"><?php echo date('j M Y', strtotime($end))?></td>
						<td><?php echo $pbalance[0] ?></td>
						<td class="amount"><?php echo number_format($pbalance[1], 2, '.', ',') ?></td>
					</tr>
					<?php  $cbalance += $pbalance[1]; }	?>
					<tr>
						<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
						<td class="total">Cumulative balance:</td>
						<td class="total amount"><?php echo number_format($cbalance, 2, '.', ',') ?></td>
					</tr>
				</table>						<!-- end of depreciated table -->
				
				<table>
					<tr>
						<th colspan="3"><h4>Total payable to landlord</h4></th>
					</tr>
					<?php $tptl = 0;
					foreach ($ptlarray as $ptlprop => $ptlamount) { ?>
					<tr>
						<td class="date"><?php echo date('j M Y', strtotime($end))?></td>
						<td><?php echo $ptlprop ?></td>
						<td class="amount"><?php echo number_format($ptlamount, 2, '.', ',') ?></td>
					</tr>
					<?php  $tptl += $ptlamount; }	?>		
					<tr>
						<td class="total date"><?php echo date('j M Y', strtotime($end))?></td>
						<td class="total">Total:</td>
						<td class="total amount"><?php echo number_format($tptl, 2, '.', ',') ?></td>
					</tr>					
				</table>

				<br />

				<table style="display:none"> <!-- depreciated, being lazy here -->
					<tr>
						<td class="borderless total alignright" colspan="2">Total payable to Landlord:</td>
						<td class="borderless total amount"><?php echo number_format($cbalance, 2, '.', ',') ?></td>
					</tr>
				</table>
				<?php } ?>
			</div>
			<?php // =================================================================================================== end copy ?>

			<?php } else if ($s == 'tenant') { ?>

			<?php 

			if (isset($_GET['d'])) {
				$q = $db->prepare("SELECT * FROM `tenants` WHERE `clientid` = $clientid AND `id` = ?");
				$q->execute(array($_GET['d']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
				} else {
					$q = $db->query("SELECT * FROM `tenants` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
					$r = $q->fetch();
				}
			} else {
				$q = $db->query("SELECT * FROM `tenants` WHERE `clientid` = $clientid ORDER BY `id` DESC LIMIT 1");
				$r = $q->fetch();
			}

			$tid = $r['id'];
			$tname = $r['name'];

			if (isset($_GET['t'])) {
				$q = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid AND `id` = ?");
				$q->execute(array($_GET['t']));
				$rc = $q->rowCount();
				if ($rc == 1) {
					$r = $q->fetch();
					$tstart = $r['startdate'];
				} else {
					$q2 = $db->query("SELECT * FROM `tenancies` WHERE `tenantid` = $tid");
					$r2 = $db->fetchAll();
					foreach ($r2 as $row) {
						if (!isset($earliesttenancy)) {
							$earliesttenancy = $row['startdate'];
							$earliesttenancyid = $row['id'];
							$earliesttenancyobal = $row['obal'];
							$earliesttenancyobaldate = $row['obaldate'];
						} else {
							if ($row['startdate'] < $earliesttenancy) {
								$earliesttenancy = $row['startdate'];
								$earliesttenancyid = $row['id'];
								$earliesttenancyobal = $row['obal'];
								$earliesttenancyobaldate = $row['obaldate'];
							}
						}
					}
					if (isset($earliesttenancy)) {
						$tstart = $earliesttenancy;
					} else {
						$tstart = date('Y-m-d');
					}
				}
			} else {
				$q2 = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid");
				$q2->execute();
				$r2 = $q2->fetchAll(PDO::FETCH_ASSOC);
				// p($r2);
				foreach ($r2 as $row) {
					if (!isset($earliesttenancy)) {
						$earliesttenancy = $row['startdate'];
						$earliesttenancyid = $row['id'];
						$earliesttenancyobal = $row['obal'];
						$earliesttenancyobaldate = $row['obaldate'];
					} else {
						if ($row['startdate'] < $earliesttenancy) {
							$earliesttenancy = $row['startdate'];
							$earliesttenancyid = $row['id'];
							$earliesttenancyobal = $row['obal'];
							$earliesttenancyobaldate = $row['obaldate'];
						}
					}
				}
				if (isset($earliesttenancy)) {
					$tstart = $earliesttenancy;
				} else {
					$tstart = date('Y-m-d');
				}
				$_GET['t'] = 0; //so hacky
			}

			if (isset($_GET['start'])) {
				if (strtotime($_GET['start']) != 0) {
					$start = date('j M Y', strtotime($_GET['start']));
				} else {
					$start = date('j M Y', strtotime($tstart));
				}
			} else {
				$start = date('j M Y', strtotime($tstart));
			}
			$dbstart = date('Y-m-d', strtotime($start));

			if (isset($_GET['end'])) {
				if (strtotime($_GET['end']) != 0) {
					$end = date('j M Y', strtotime($_GET['end']));
				} else {
					$end = date('j M Y');
				}
			} else {
				$end = date('j M Y');
			}
			$dbend = date('Y-m-d', strtotime($end));

			?>
			<input type="hidden" id="reportdselect" value="<?php echo $tid ?>" />
			<div class="lfieldset" id="reportoptions">
				<h2>Tenant Statement for <?php echo $tname ?></h2>
				<div class="optionline">
					<label for="tenantstatementxac">Select tenant:</label>
					<span class="xaccont">
						<input id="tenantstatementxac" />
						<div class="xacmenu">
							Javascript fail
						</div>
					</span>
					<span class="datecont">
						<label for="reportstartdate">Start:</label>
						<input id="reportstartdate" class="genericdate" value="<?php echo $start ?>" />
					</span>
					<span class="datecont">
						<label for="reportenddate">End:</label>
						<input id="reportenddate" class="genericdate" value="<?php echo $end ?>" />
					</span>
				</div>
				<br />
				<div class="optionline">
					<label for="reporttselect">Choose tenancy:</label>
					<select id="reporttselect">
						<option value="0">All tenancies</option><?php echo "\n";
						foreach ($db->query("SELECT * FROM `tenancies` WHERE `tenantid` = $tid") as $row) {
							echo "					<option value=\"".$row['id']."\"";
							echo ($row['id'] == $_GET['t'] ? " selected=\"selected\"" : "");
							echo ">#".$row['id']."</option>\n";	//need some more info in here really, room, property, start date, end date
						} ?>
					</select>
					<span class="reportbuttons">
						<input type="button" class="button" id="tenantreportupdate" value="Update" />
						<input type="button" class="button" id="tenantreportprint" value="Print" />
					</span>
				</div>
			</div>


			<?php

			$pq = $db->prepare("
				(
					SELECT
						CONCAT ('payment') AS `type`,
						CONCAT ('payment') AS `link`,
						payments.id AS `id`,
						payments.date AS `date`,
						payments.amount AS `amount`,
						CONCAT ('Payment: rent') AS `desc`,
						tenancies.id AS `tenancy`
					FROM `payments` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON payments.contactid = tenancies.id
					WHERE payments.contacttype = 't'
					AND tenants.id = $tid
				) UNION (
					SELECT
						CONCAT ('payment') AS `type`,
						CONCAT ('payment') AS `link`,
						payments.id AS `id`,
						payments.date AS `date`,
						payments.amount AS `amount`,
						CONCAT ('Payment: ', tenantfees.desc) AS `desc`,
						tenancies.id AS `tenancy`
					FROM `payments` LEFT JOIN (`tenantfees` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON tenantfees.tenancyid = tenancies.id) ON payments.contactid = tenantfees.id
					WHERE payments.contacttype = 'f'
					AND tenants.id = $tid
				)
			");

			$pq->execute();
			$apr = $pq->fetchAll(PDO::FETCH_ASSOC);	//all payments, all tenancies
			$pr = array();
			if (isset($_GET['t']) && $_GET['t'] != 0) {
				$tenancycheck = $db->prepare("SELECT * FROM `tenancies` WHERE `id` = ? AND `tenantid` = $tid");
				$tenancycheck->execute(array($_GET['t']));
				$rc = $tenancycheck->rowCount();
				if ($rc == 1) {
					$tcr = $tenancycheck->fetch();
					foreach ($apr as $ap) {
						if ($ap['tenancy'] == $tcr['id']){
							$pr[] = $ap;
						}
					}
				} else {
					$pr = $apr;
				}
			} else {
				$pr = $apr;
			}

			if (!isset($pr)) {
				$pr = $apr;
			}

			//pre-sort ensures payments always come first when matching a due date - tenants paying on time shouldn't have negative balances:

			function pre(array $a, array $b) { 
				if ($a['type'] == 'due') {
					return 1;
				} else {
					return -1;
				}
			}
			
			// sort by date

			function cmp(array $a, array $b) {
				if ($a['date'] < $b['date']) {
					return -1;
				} else if ($a['date'] > $b['date']) {
					return 1;
				} else {
					return 1;
				}
			}

			usort($pr, 'cmp');

			// p($pr);		// sorted array of actual payments. could have done this in SQL but just practising


			if (isset($_GET['t']) && $_GET['t'] != 0) {
				$tq = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid AND `id` = ?");
				$tq->execute(array($_GET['t']));
			} else {
				$tq = $db->prepare("SELECT * FROM `tenancies` WHERE `tenantid` = $tid");
				$tq->execute();

			}
			$tr = $tq->fetchAll(PDO::FETCH_ASSOC);

			$due = array();

			foreach ($tr as $t) {
				if (is_null($t['enddate'])) {
					$t['enddate'] = '9999-12-31';
				}
				$duedate = $t['startdate'];

				if ($t['period'] == 'W') {
					$period = '1 week';
					$dayrate = $t['rent'] / 7;
				} else if ($t['period'] == 'F') {
					$period = '2 weeks';
					$dayrate = $t['rent'] / 14;
				} else if ($t['period'] == '4') {
					$period = '4 weeks';
					$dayrate = $t['rent'] / 28;
				} else {
					$period = '1 month';
					$dayrate = $t['rent'] * 12/365;
				}
				
				//due step 1 - rent due based on period

				while ($duedate <= $dbend && $duedate <= $t['enddate']) {
					
					$nextduedate = date('Y-m-d', strtotime('+'.$period, strtotime($duedate)));
					if ($period == '1 month') {
						if (date('d', strtotime($nextduedate)) !== date('d', strtotime($t['startdate']))) {	//day mismatch
							if (date('d', strtotime($nextduedate)) < 5) {	//jumped to start of next month
								// echo "1: ".$nextduedate;
								$nextduedate = date('Y-m-d', strtotime('last day of previous month', strtotime($nextduedate)));
								// echo "; 2: ".$nextduedate;
							} else {	//dropped days
								$correctday = date('d', strtotime($t['startdate']));
								$correctmonth = date('m', strtotime($nextduedate));
								$correctyear = date('Y', strtotime($nextduedate));
								$nextduedate = $correctyear.'-'.$correctmonth.'-'.$correctday;
							}
						}
					}

					// echo "Comparing ".date('Y-m-d', strtotime($duedate.' +'.$period.' -1 day'))." to ".$t['enddate']."<br>";
					
					if (date('Y-m-d', strtotime($nextduedate.' -1 day')) <= $t['enddate']) {
						$due[] = array(
							'type' => 'due',
							'link' => 'tenant',
							'id' => $t['id'],
							'date' => $duedate,
							'amount' => $t['rent'],
							'desc' => 'Rent due ('.date('j M Y', strtotime($duedate)).' - '.date('j M Y', strtotime($nextduedate.' -1 day')).')',
							'tenancy' => $t['id']
						);
					} else {
						$daysremaining = floor((strtotime('+1 day', strtotime($t['enddate'])) - strtotime($duedate)) / (60*60*24));
						// echo "Days remaining: ".$daysremaining;
						$due[] = array(
							'type' => 'due',
							'link' => 'tenant',
							'id' => $t['id'],
							'date' => $duedate,
							'amount' => round($daysremaining * $dayrate),
							'desc' => 'Rent due ('.date('j M Y', strtotime($duedate)).' - '.date('j M Y', strtotime($t['enddate'])).')',
							'tenancy' => $t['id']
						);
					}			
					$duedate = $nextduedate;
				}
				
				//due step 2 - tenant fees

				foreach ($db->query("SELECT * FROM `tenantfees` LEFT JOIN `tenancies` ON tenantfees.tenancyid = tenancies.id WHERE tenancies.id = ".$t['id']." AND tenantfees.date BETWEEN '$dbstart' AND '$dbend'") as $tf) {
					$due[] = array(
						'type' => 'due',
						'link' => 'tenantfees',
						'id' => $tf['id'],
						'date' => $tf['date'],
						'amount' => $tf['amount'],
						'desc' => 'Fee/expense: '.$tf['desc']
					);
				}
				
				//due step 3 - adjustments
				
				foreach ($db->query("SELECT * FROM `adjustments` LEFT JOIN `tenancies` ON adjustments.tenancyid = tenancies.id WHERE tenancies.id = ".$t['id']." AND adjustments.date BETWEEN '$dbstart' AND '$dbend'") as $a) {
					$due[] = array(
						'type' => 'due',
						'link' => 'adjustments',
						'id' => $a['id'],
						'date' => $a['date'],
						'amount' => $a['amount'] * -1,
						'desc' => 'Adjustment: '.$a['desc']
					);
				}				

			}
			usort($due, 'cmp');

			// p($due);
			// echo "<hr>";
			?>
			<div id="reportcont">
				<h1>Tenant statement for <a href="tenants.php?s=detail&d=<?php echo $tid ?>"><?php echo $tname?></a></h1>
				<h2>Date range: <?php echo date('j M Y', strtotime($start)) ?> - <?php echo date('j M Y', strtotime($end)) ?></h2>
				<?php // opening balance
				
				if (isset($earliesttenancyobal)) {
					$bal = $earliesttenancyobal;
					$odate = date('Y-m-d', strtotime($earliesttenancyobaldate));
				} else {
					$bal = 0;
					$odate = '1970-01-01';
				}
				
				
				foreach ($pr as $p) {
					if ($p['date'] < $dbstart && $p['date'] >= $odate) {
						$bal += $p['amount'];
					}
				}
				foreach ($due as $d) {
					if ($d['date'] < $dbstart && $d['date'] >= $odate) {
						$bal -= $d['amount'];
					}
				}

				?>
				<h3>Statement opening balance: <?php echo number_format($bal / 100, 2, '.', ',') ?></h3>

				<br />
				<table>
					<thead>
						<tr>
							<th><h4>Date</h4></th>
							<th><h4>Description</h4></th>
							<th class="alignright"><h4>Due</h4></th>
							<th class="alignright"><h4>Paid</h4></th>
							<th class="alignright"><h4>Balance</h4></th>
						</tr>
					</thead>
					</tbody><?php echo "\n";
					$list = array();
					foreach ($pr as $p) {
						if ($p['date'] >= $dbstart && $p['date'] <= $dbend) {
							$list[] = $p;
						}
					}
					foreach ($due as $d) {
						if ($d['date'] >= $dbstart && $d['date'] <= $dbend) {
							$list[] = $d;
						}
					}
					// usort($list, 'pre');
					usort($list, 'cmp');
					
					// p($list);
					
					$listcount = 0;
					foreach ($list as $li) {
						if ($li['date'] >= $odate) {
							//@@@@@@@@@ look at link value, assign link to page location and give desc <a> tags
							echo "						<tr>\n";
							echo "							<td>".date('j M Y', strtotime($li['date']))."</td>";
							if ($li['link'] == 'payment') {
								echo "							<td><a href=\"bank.php?s=detail&d=".$li['id']."\">".$li['desc']."</a></td>";
							} else {
								echo "							<td>".$li['desc']."</td>";
							}
							echo "							".($li['type'] == 'due' ? "<td class=\"alignright\">".number_format($li['amount'] / 100, 2, '.', ',')."</td>\n" : "<td>&nbsp</td>\n");
							echo "							".($li['type'] == 'payment' ? "<td class=\"alignright\">".number_format($li['amount'] / 100, 2, '.', ',')."</td>\n" : "<td>&nbsp</td>\n");
							echo "							".($li['type'] == 'due' ? "<td class=\"alignright\">".number_format(($bal - $li['amount']) / 100, 2, '.', ',')."</td>\n" : "<td class=\"alignright\">".number_format(($bal + $li['amount']) / 100, 2, '.', ',')."</td>\n");
							echo "						</tr>\n";
							if ($li['type'] == 'due') {
								$bal -= $li['amount'];
							} else {
								$bal += $li['amount'];
							}
						}
						// $li['bal'] = $bal;	//feeding balance back into array for arrears calculation - php doesn't assign variables this way, see next line
						$list[$listcount]['bal'] = $bal;
						$listcount++;
					}
			?>
					</tbody>
				</table>
				<h3>Statement closing balance: <?php echo number_format($bal / 100, 2, '.', ',') ?></h3>
				<?php 
				$list = array_reverse($list);
				
				// p($list);
				
				$whilebal = $list[0]['bal'];
				$whilecount = 0;
				while ($whilebal < 0 && $whilecount < 1000) {	//debugging
					$whilebal = $list[$whilecount]['bal'];
					if ($whilebal < 0) {
						$arrearsdate = new DateTime($list[$whilecount]['date']);
					}
					if (array_key_exists($whilecount + 1, $list)) {
						if ($list[$whilecount + 1]['date'] >= $odate) {
							$whilecount++;
						} else {
							$whilebal = 0;
						}
					} else {
						$whilebal = 0;
					}
				}
				// echo $arrearsdate;
				$curdate = new DateTime(date('Y-m-d'));
				if (!isset($arrearsdate)) {
					$arrearsdate = new DateTime(date('Y-m-d'));
				}
				$daysinarrears = $arrearsdate->diff($curdate);
				
				?>
				<h3>Days in arrears (current date): <?php echo $daysinarrears->days ?></h3>

			</div>

			<div id="xaclookuplist">
				<ul><?php
				echo "\n";
				foreach ($db->query("SELECT * FROM `tenants` WHERE `clientid` = $clientid") as $trow) {
					echo "				<li data-tenantid=\"".$trow['id']."\">".$trow['name']."</li>\n";
				};
				?>
				</ul>
			</div> 

			<input type="hidden" id="getd" value="<?php echo $tid ?>" />

			<?php } else if ($s == 'arrears') { ?>

			<p id="totalarrears">Total arrears: </p>
			<br />
			
			<?php 
			
			$tenants = array();
			
			foreach ($db->query("
				SELECT
					tt.tenantid AS `tenantid`,
					tt.name AS `name`,
					tt.status AS `status`,
					tt.tenancyid AS `tenancyid`,
					tt.startdate AS `startdate`,
					tt.obal AS `obal`,
					tt.obaldate AS `obaldate`,
					tt.enddate AS `enddate`,
					tt.rent AS `rent`,
					tt.period AS `period`,
					tt.roomid AS `roomid`,
					CONCAT (properties.no,' ',properties.street) AS `property`
				FROM (
					SELECT
						tenants.id AS `tenantid`,
						tenants.name AS `name`,
						tenants.status AS `status`,
						tenancies.id AS `tenancyid`,
						tenancies.startdate AS `startdate`,
						tenancies.obal AS `obal`,
						tenancies.obaldate AS `obaldate`,
						tenancies.enddate AS `enddate`,
						tenancies.rent AS `rent`,
						tenancies.period AS `period`,
						tenancies.roomid AS `roomid`
					FROM `tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
					WHERE tenants.clientid = $clientid
				) tt LEFT JOIN (
					`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id
				) on tt.roomid = rooms.id
				ORDER BY tt.tenantid
			") as $row) {
				if (!array_key_exists($row['tenantid'], $tenants)) {
					$tenants[$row['tenantid']] = array();
					$tenants[$row['tenantid']]['info'] = array();
					$tenants[$row['tenantid']]['info']['name'] = $row['name'];
					$tenants[$row['tenantid']]['info']['status'] = $row['status'];
				}
				$tenants[$row['tenantid']][$row['tenancyid']] = array(
					'startdate' => $row['startdate'],
					'obal' => $row['obal'],
					'obaldate' => $row['obaldate'],
					'enddate' => $row['enddate'],
					'rent' => $row['rent'],
					'period' => $row['period'],
					'property' => $row['property']
				);
			}
			
			$tenants2 = array();

			foreach ($tenants as $tenantid => $tenant) {
				$tenantkeys = array_keys($tenant);
				if (sizeof($tenant) == 2) {
					$tenant['info']['earliesttenancyid'] = $tenantkeys[1];
					$tenant['info']['earliesttenancystartdate'] = $tenant[$tenantkeys[1]]['startdate'];
					$tenant['info']['earliesttenancyobal'] = $tenant[$tenantkeys[1]]['obal'];
					$tenant['info']['earliesttenancyobaldate'] = $tenant[$tenantkeys[1]]['obaldate'];
					$tenant['info']['mostrecentproperty'] = $tenant[$tenantkeys[1]]['property'];
				} else {
					foreach ($tenant as $tenancyid => $tenancy) {
						if ($tenancyid !== 'info') {
							if (!isset($tenant['info']['earliesttenancystartdate'])) {
								$tenant['info']['earliesttenancyid'] = $tenancyid;
								$tenant['info']['earliesttenancystartdate'] = $tenancy['startdate'];
								$tenant['info']['earliesttenancyobal'] = $tenancy['obal'];
								$tenant['info']['earliesttenancyobaldate'] = $tenancy['obaldate'];
								$tenant['info']['mostrecentproperty'] = $tenancy['property'];
							} else {
								if ($tenancy['startdate'] < $tenant['info']['earliesttenancystartdate']) {
									$tenant['info']['earliesttenancyid'] = $tenancyid;
									$tenant['info']['earliesttenancystartdate'] = $tenancy['startdate'];
									$tenant['info']['earliesttenancyobal'] = $tenancy['obal'];
									$tenant['info']['earliesttenancyobaldate'] = $tenancy['obaldate'];
								} else {
									$tenant['info']['mostrecentproperty'] = $tenancy['property'];
								}
							}
						}
					}
				}
				$tenants2[$tenantid] = $tenant;
			}
			
			// p($tenants2);
			
			$pq = $db->prepare("
				(
					SELECT
						payments.id AS `id`,
						payments.date AS `date`,
						payments.amount AS `amount`,
						tenancies.id AS `tenancy`,
						tenants.id AS `tenant`
					FROM `payments` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON payments.contactid = tenancies.id
					WHERE payments.contacttype = 't'
					AND tenants.clientid = $clientid
				) UNION (
					SELECT
						payments.id AS `id`,
						payments.date AS `date`,
						payments.amount AS `amount`,
						tenancies.id AS `tenancy`,
						tenants.id AS `tenant`
					FROM `payments` LEFT JOIN (`tenantfees` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON tenantfees.tenancyid = tenancies.id) ON payments.contactid = tenantfees.id
					WHERE payments.contacttype = 'f'
					AND tenants.clientid = $clientid
				) ORDER BY `tenant`, `date`
			");			
			
			$pq->execute();
			$pr = $pq->fetchAll(PDO::FETCH_ASSOC);
			
			$payments = array();
			
			foreach ($pr as $payment) {
				$payments[$payment['tenant']][] = array(
					'type' => 'payment',
					'date' => $payment['date'],
					'amount' => $payment['amount'],
					'tenancy' => $payment['tenancy']
				);
			}
			
			// p($payments);
			
			$feesadjs = array();
			
			foreach ($db->query("
				(
					SELECT
						tenantfees.date AS `date`,
						tenantfees.amount AS `amount`,
						tenancies.id AS `tenancy`,
						tenants.id AS `tenant`
					FROM `tenantfees` LEFT JOIN (
						`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
					) ON tenantfees.tenancyid = tenancies.id
					WHERE tenants.clientid = $clientid
				) UNION (
					SELECT
						adjustments.date AS `date`,
						adjustments.amount * -1 AS `amount`,
						tenancies.id AS `tenancy`,
						tenants.id AS `tenant`
					FROM `adjustments` LEFT JOIN (
						`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
					) ON adjustments.tenancyid = tenancies.id
					WHERE tenants.clientid = $clientid				
				) ORDER BY `tenant`
			") as $feeadj) {		
				$feesadjs[$feeadj['tenant']][] = array(
					'date' => $feeadj['date'],
					'amount' => $feeadj['amount'],
					'tenancy' => $feeadj['tenancy']
				);
			}
			
			// p($feesadjs);

			function pre(array $a, array $b) { 		// puts payments before dues on the same date IF IT BLOODY FEELS LIKE IT
				if ($a['type'] == 'rent' || $a['type'] == 'feeadj') {
					return -1;
				} else {
					return 1;
				}
			}

			function cmp(array $a, array $b) {			// SORT BY DATE, this time to be used on due[$tenantid];
				if ($a['date'] < $b['date']) {
					return -1;
				} else if ($a['date'] > $b['date']) {
					return 1;
				} else {
					return 1;
				}
			}
			
			$due = array();
			
			//this next bit (in consistency with the tenant statement) will work out all due dates between the start and end dates; the opening balance and date will only come into effect when working out the tenant balance
			
			$tenants3 = array();
			
			foreach ($tenants2 as $tenantid => $tenant) {
				// p($tenant);
				$due[$tenantid] = array();
				foreach ($tenant as $tenancyid => $tenancy) {
					if ($tenancyid !== 'info') {
						$t = $tenancy;
						if (is_null($t['enddate'])) {
							$t['enddate'] = '9999-12-31';
						}
						$duedate = $t['startdate'];
						
						if ($t['period'] == 'W') {
							$period = '1 week';
							$dayrate = $t['rent'] / 7;
						} else if ($t['period'] == 'F') {
							$period = '2 weeks';
							$dayrate = $t['rent'] / 14;
						} else if ($t['period'] == '4') {
							$period = '4 weeks';
							$dayrate = $t['rent'] / 28;
						} else {
							$period = '1 month';
							$dayrate = $t['rent'] * 12/365;
						}						
						
						//due step 1 - rent due based on period
						
						

						while ($duedate <= $t['enddate'] && $duedate <= date('Y-m-d') ) { 	// to implement end dates
						
							$nextduedate = date('Y-m-d', strtotime('+'.$period, strtotime($duedate)));
							if ($period == '1 month') {
								if (date('d', strtotime($nextduedate)) !== date('d', strtotime($t['startdate']))) {	
									if (date('d', strtotime($nextduedate)) < 5) {
										// echo "1: ".$nextduedate;
										$nextduedate = date('Y-m-d', strtotime('last day of previous month', strtotime($nextduedate)));
										// echo "; 2: ".$nextduedate;
									} else {
										$correctday = date('d', strtotime($t['startdate']));
										$correctmonth = date('m', strtotime($nextduedate));
										$correctyear = date('Y', strtotime($nextduedate));
										$nextduedate = $correctyear.'-'.$correctmonth.'-'.$correctday;
									}
								}
							}
						
							if (date('Y-m-d', strtotime($nextduedate.' -1 day')) <= $t['enddate']) {
								$due[$tenantid][] = array(
									'type' => 'rent',
									'date' => $duedate,
									'amount' => $t['rent'],
									'tenancy' => $tenancyid
								);
							} else {
								$daysremaining = floor((strtotime('+1 day', strtotime($t['enddate'])) - strtotime($duedate)) / (60*60*24));
								$due[$tenantid][] = array(
									'type' => 'rent',
									'date' => $duedate,
									'amount' => round($daysremaining * $dayrate),
									'tenancy' => $tenancyid
								);
							}
							$duedate = $nextduedate;
						}
						
						//due steps 2 & 3 (now combined!) fees & adjustments:
						
						if (array_key_exists($tenantid, $feesadjs)) {
							foreach ($feesadjs[$tenantid] as $feeadj) {
								if ($feeadj['tenancy'] == $tenancyid) {
									$due[$tenantid][] = array(
										'type' => 'feeadj',
										'date' => $feeadj['date'],
										'amount' => $feeadj['amount'],
										'tenancy' => $feeadj['tenancy']
									);	
								}
							}
						}
					}
				}
				usort($due[$tenantid], 'cmp');
				
				if (isset($tenant['info']['earliesttenancyobal'])) {
					$bal = $tenant['info']['earliesttenancyobal'];
					$odate = date('Y-m-d', strtotime($tenant['info']['earliesttenancyobaldate']));
				} else {
					$bal = 0;
					$odate = '1970-01-01';
				}
				
				$list = array();		//list created for counting backwards through to work out days in arrears
				
				// ignore payments & dues before opening date for working out balance
				
				
				if (array_key_exists($tenantid, $payments)) {
					unset($lastpaymentdate);
					foreach ($payments[$tenantid] as $p) {
						if ($p['date'] >= $odate) {
							// $bal += $p['amount'];
							// $p['bal'] = $bal;							
							$list[] = $p;
							
							if (!isset($lastpaymentdate)) {
								$lastpaymentdate = $p['date'];
							} else {
								if ($p['date'] > $lastpaymentdate) {
									$lastpaymentdate = $p['date'];
								}
							}
							$tenant['info']['lastpaymentdate'] = $lastpaymentdate;
						}
					}
				}
				
				if (array_key_exists($tenantid, $due)) {
					unset($lastduedate);
					foreach ($due[$tenantid] as $d) {
						if ($d['date'] >= $odate) {
							// $bal -= $d['amount'];
							// $d['bal'] = $bal;
							$list[] = $d;
							
							if (!isset($lastduedate)) {
								$lastduedate = $d['date'];
							} else {
								if ($d['date'] > $lastduedate) {
									$lastduedate = $d['date'];
								}
							}
							$tenant['info']['lastduedate'] = $lastduedate;							
						}
					}				
				}
				
				if (!isset($tenant['info']['lastpaymentdate'])) {
					$tenant['info']['lastpaymentdate'] = 0;
				}
				
				if (!isset($tenant['info']['lastduedate'])) {
					$tenant['info']['lastduedate'] = 0;
				}
				
				
				// echo $tenant['info']['name'].": ".number_format($bal / 100, 2, '.', ',')."<br>";

				
				// usort($list, 'pre');
				usort($list, 'cmp');

				$licount = 0;
				foreach ($list as $li) {
					if ($li['type'] == 'rent' || $li['type'] == 'feeadj') {
						$bal -= $li['amount'];
					} else {
						$bal += $li['amount'];
					}
					$list[$licount]['bal'] = $bal;
					$licount++;
				}
				
				$tenant['info']['bal'] = $bal;
				
				$list = array_reverse($list);
				
				// p($list);
				
				unset($arrearsdate);
				if (array_key_exists(0, $list)) {
					$whilebal = $list[0]['bal'];
					$whilecount = 0;
					while ($whilebal < 0) {
						if (array_key_exists($whilecount, $list)) {
							$whilebal = $list[$whilecount]['bal'];
						} else {
							$whilebal = 0;
						}
						
						if ($whilebal < 0) {
							$arrearsdate = new DateTime($list[$whilecount]['date']);
						}
						$whilecount++;
					}					
				}

				$curdate = new DateTime(date('Y-m-d'));
				if (!isset($arrearsdate)) {
					$arrearsdate = new DateTime(date('Y-m-d'));
				}
				$daysinarrears = $arrearsdate->diff($curdate);
				
				$tenant['info']['dia'] = $daysinarrears->days;
				$tenant['info']['since'] = $arrearsdate->format('j M Y');
				
				$tenants3[$tenantid] = $tenant;
				
			}
			
			// p($due);
			// p($tenants3);
			
			function arrearslist(array $a, array $b) {
				if ($a['info']['dia'] > $b['info']['dia']) {
					return -1;
				} else if ($a['info']['dia'] < $b['info']['dia']) {
					return 1;
				} else {
					return 0;
				}
			}
			
			uasort($tenants3, 'arrearslist');
			// p($tenants3);
			
			?>
			
			<?php  if (isset($_GET['report']) &&  $_GET['report'] == 'table') { // old arrears report ?>
			<table>
				<thead>
					<tr>
						<th>Tenant Name</th>
						<th>Property</th>
						<th>Balance</th>
						<th>Since</th>
						<th>Days in Arrears</th>
					</tr>
				</thead>
				<tbody><?php echo "\n";
				foreach ($tenants3 as $tenantid => $tenant) {
					if ($tenant['info']['dia'] > 0) {
						echo "					<tr>\n";
						echo "						<td><a href=\"reports.php?s=tenant&d=".$tenantid."\">".$tenant['info']['name']."</a></td>\n";
						echo "						<td>".$tenant['info']['mostrecentproperty']."</td>\n";
						echo "						<td>".number_format($tenant['info']['bal'] / 100, 2, '.', ',')."</td>\n";
						echo "						<td>".$tenant['info']['since']."</td>\n";
						echo "						<td>".$tenant['info']['dia']."</td>\n";
						echo "					</tr>\n";
					}
				}?>
				</tbody>
			</table>
			<?php }	// end old arrears report ?>

			<?php  if (isset($_GET['report']) &&  $_GET['report'] == 'startbal') { // startbalances 1 jun 16 ?>
			<table>
				<thead>
					<tr>
						<th>Tenant Name</th>
						<th>Property</th>
						<th>Balance</th>
						<th>Since</th>
						<th>Days in Arrears</th>
					</tr>
				</thead>
				<tbody><?php echo "\n";
				foreach ($tenants3 as $tenantid => $tenant) {
					
						echo "					<tr>\n";
						echo "						<td><a href=\"reports.php?s=tenant&d=".$tenantid."\">".$tenant['info']['name']."</a></td>\n";
						echo "						<td>".$tenant['info']['mostrecentproperty']."</td>\n";
						echo "						<td>".number_format($tenant['info']['bal'] / 100, 2, '.', ',')."</td>\n";
						echo "						<td>".$tenant['info']['since']."</td>\n";
						echo "						<td>".$tenant['info']['dia']."</td>\n";
						echo "					</tr>\n";
					
				}?>
				</tbody>
			</table>
			<?php }	// startbalances 1 jun 16 ?>
			
			
			<?php 
			
			
			
			// ob_start(); // -- depreciated
			// echo "<select class=\"astatsel\">\n";
				// echo "								<option value=\"0\">Change status</option>\n";
				// foreach ($db->query("SELECT * FROM `arrearsstatuses`") as $row) {
					// echo "								<option style=\"background-color:#".$row['color']."\" data-color=\"".$row['color']."\" value=\"".$row['id']."\">".$row['status']."</option>\n";
				// }
			// echo "</select>\n";
			// $statsel = ob_get_clean();
			
			
			
			// p($tenants3);
			$runningtotal = 0;
			foreach ($tenants3 as $tenantid => $tenant) {
				if ($tenant['info']['dia'] > 0) {
					$runningtotal += $tenant['info']['bal'];
					// d($runningtotal);
				?>
				<div class="arrearsline lfieldset" data-id="<?php echo $tenantid ?>">
					<h3><a href="reports.php?s=tenant&d=<?php echo $tenantid ?>"><?php echo $tenant['info']['name'] ?></a> | <?php echo $tenant['info']['mostrecentproperty'] ?></h3>
					<div class="arrearsinfo">
						<span class="arrearsbalance"><label>Balance</label><br /> <?php echo number_format($tenant['info']['bal'] / 100, 2, '.', ',') ?></span>
						<span class="arrearssince"><label>In arrears since</label><br /> <?php echo  $tenant['info']['since']." (".$tenant['info']['dia']." days)" ?></span>
						<span class="arrearslp"><label>Last payment</label><br /> <?php echo (strtotime($tenant['info']['lastpaymentdate']) == 0 ? "-" : date('j M Y', strtotime($tenant['info']['lastpaymentdate']))) ?></span>
						<span class="arrearsli"><label>Last rent increment</label><br /> <?php echo (strtotime($tenant['info']['lastduedate']) == 0 ? "-" : date('j M Y', strtotime($tenant['info']['lastduedate']))) ?></span>
						<span class="arrearsstatus">
							<?php 
							echo "<select class=\"astatsel\">\n";
								foreach ($db->query("SELECT * FROM `arrearsstatuses`") as $row) {
									// if tenant status = rowid, selected
									if ($row['id'] == $tenant['info']['status']) {
										echo "								<option selected=\"selected\" data-color=\"".$row['color']."\" value=\"".$row['id']."\">".$row['status']."</option>\n";
									} else {
										echo "								<option data-color=\"".$row['color']."\" value=\"".$row['id']."\">".$row['status']."</option>\n";
									}
								}
							echo "</select>\n";
							?>
						</span>
					</div>
					<div class="arrearscomments">
						<ul>
							<li class="newarrearsnoteli"><input class="newarrearsnote" /> [<span class="submitarrearsnote">Submit</span>]</li>
							<?php 
							$q = $db->prepare("SELECT * FROM `arrearsnotes` WHERE `tenantid` = ? ORDER BY `timestamp` DESC");
							$q->execute(array($tenantid));
							$r = $q->fetchall(PDO::FETCH_ASSOC);
							foreach ($r as $row) {
								$resolved = ($row['status'] == 'r' ? ' class="resolved"' : '');
								$datetime = date('j M Y, g:ia', strtotime($row['timestamp']));
								$notecontent = stripslashes($row['text']);
								$q2 = $db->prepare("SELECT `username` FROM `users` WHERE `id` = ?");
								$q2->execute(array($row['userid']));
								$r2 = $q2->fetch();
								$user = $r2['username'];
								echo "							<li".$resolved." data-id=\"".$row['id']."\"><strong>".$datetime."</strong>: <span class=\"notecontent\">".$notecontent."</span> -<strong>".$user."</strong>";
								if ($row['status'] == 'u') {
									echo " | <span class=\"editnote\">Edit</span> | <span class=\"resolvenote\">Resolve</span>";
								} else {
									echo " (resolved on ".date('j M Y, g:ia', strtotime($row['timestamp2'])).")";
								}
								echo "</li>";
							}
							
							?>
						</ul>
						<span class="addnote">Add note</span> | <span class="showresolved">Show resolved</span>
					</div>
				
				</div>
				<?php
				} else {	// tenant no longer in arrears, clear status
					$q = $db->prepare("UPDATE `tenants` SET `status` = 1 WHERE `id` = ?");
					$q->execute(array($tenantid));
				}
			}
			
			//THE PLAN
			
			// - test colors
			// - figure out what the ob_start function does!
			// - test if colors are reset after arrears case is resolved
			
			// - put in a quick fix for comments (variable-sized textbox)
			
			
			
			
			
			?>
			<input type="hidden" id="runningtotal" value="<?php echo number_format($runningtotal / 100, 2, '.', ',') ?>" />
			
			<?php } else if ($s == 'voids') { ?>

			<p>Voids</p>

			<?php } ?>

		</div>

		<input type="hidden" id="lastfocus" value="input_ref" />

	</body>
</html>
<?php } ?>