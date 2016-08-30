<?php

include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {
	$pagetitle = 'Bank';
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require 'defs/inc_jscss.php' ?>
		<script type="text/javascript" src="js/bank.js"></script>
	</head>
	<body>
		<?php require 'defs/inc_header.php' ?>
		<div id="subheader">
			<div id="innersubheader">
				<?php if (isset($_GET['s'])) {
					if ($_GET['s'] == 'statements') {
						$s = 'statements';
					} else if ($_GET['s'] == 'reconciliation') {
						$s = 'reconciliation';
					} else if ($_GET['s'] == 'payments') {
						$s = 'payments';
					} else if ($_GET['s'] == 'detail') {
						$s = 'detail';
					} else if ($_GET['s'] == 'statementexport') {
						$s = 'statementexport';
					} else if ($_GET['s'] == 'accounts') {
						$s = 'accounts';
					} else {
						$s = 'statements';
					}
				} else {
					$s = 'statements';
				}?>
				<span class="<?php echo ($s == 'accounts' ? 'subtab_active' : 'subtab') ?>" id="accounts">
					Accounts
				</span>
				<span class="<?php echo ($s == 'statements' ? 'subtab_active' : 'subtab') ?>" id="statements">
					Statements
				</span>
				<span class="<?php echo ($s == 'reconciliation' ? 'subtab_active' : 'subtab') ?>" id="reconciliation">
					Reconciliation
				</span>
				<span class="<?php echo ($s == 'payments' ? 'subtab_active' : 'subtab') ?>" id="payments">
					Payments
				</span>
				<span class="<?php echo ($s == 'detail' ? 'subtab_active' : 'subtab') ?>" id="detail">
					Payment detail
				</span>
				<span class="<?php echo ($s == 'statementexport' ? 'subtab_active' : 'subtab') ?>" id="statementexport">
					Statement export
				</span>
			</div>
		</div>
		<div id="main">
			<?php if ($s == 'accounts') { ?>

			<div class="bankcol">
				<?php

				$q = $db->query("SELECT * FROM `bankaccounts` WHERE `clientid` = $clientid ORDER BY `order`");
				$r = $q->fetchAll(PDO::FETCH_ASSOC);
				foreach ($r as $row) {
					$id = $row['id'];
					?>
					<div class="bankaccount">
						<input type="hidden" class="bankaccountid" value="<?php echo $id ?>" />
						<h3 class="handle"><?php echo $row['name']." (".$row['bank_l'].")" ?></h3>
						<p>Notes: <?php echo $row['notes'] ?></p>
						<!-- <p>View statements</p>
						<p>Reconcile</p> -->
						<span class="amendbankaccount">Amend details</span>
						<div class="amendbankaccountfields">
							<p>
								<label class="zoomlabel" for="acc-name<?php echo $id ?>">Account name:</label>
								<input id="acc-name<?php echo $id ?>" class="acc-name" value="<?php echo $row['name'] ?>" />
								<label class="zoomlabel" for="acc-bank<?php echo $id ?>">Bank name:</label>
								<select id="acc-bank<?php echo $id ?>" class="acc-bank">
									<?php
									foreach ($bankaccounts as $s => $l) {
										if ($s == $row['bank_s']) {
											echo "<option value=\"$s\" selected=\"selected\">$l</option>\n";
										} else {
											echo "<option value=\"$s\">$l</option>\n";
										}
									}
									?>
								</select>
								<label class="zoomlabel" for="acc-notes<?php echo $id ?>">Notes:</label>
								<input id="acc-notes<?php echo $id ?>" class="acc-notes" value="<?php echo $row['notes'] ?>" />
							</p>
							<input type="button" class="button submitchanges" value="Submit changes" />
							<input type="button" class="button cancelchanges" value="Cancel" />
						</div>
					</div>
					<?php
				}
				$rowcount = $q->rowCount();

				?>
			</div>

			<div class="bankaccount-new">
				<h3>Add new bank account</h3>
				<p>
					<label class="zoomlabel" for="newacc-name">Account name:</label>
					<input id="newacc-name" />
				</p>
				<p>
					<label class="zoomlabel" for="newacc-bank">Bank name:</label>
					<select id="newacc-bank">
						<?php
						foreach ($bankaccounts as $s => $l) {
							echo "<option value=\"$s\">$l</option>\n";
						}
						?>
					</select>
				</p>
				<p>					
					<label class="zoomlabel" for="newacc-notes">Notes:</label>
					<input id="newacc-notes" />
					<input type="hidden" id="newacc-order" value="<?php echo $rowcount + 1 ?>" />
				</p>
				<input type="button" class="button" id="addnewbankaccount" value="Submit new bank account" />
			</div>

			<?php } else if ($s == 'statements') { ?>

			<?php // check for uploaded statements, if found display allocation section at top 

			if (is_dir('db/statements/'.$clientid)) {
				$c = 0;
				foreach (preg_grep('/^([^.])/', scandir('db/statements/'.$clientid)) as $file) {
					$c++;
					$csv = file('db/statements/'.$clientid.'/'.$file);
					$rows = array_map("str_getcsv", $csv);
					// print "<pre>";
					// print_r($rows);
					// print "</pre>";
					?>

					<div class="statementcont" id="statementcont<?php echo $c ?>">
						<input type="hidden" id="name<?php echo $c ?>" value="<?php echo $file ?>" />
						<h3>Allocate <?php echo $file ?> to a bank account:</h3>
						<div class="statementpreviewcont">
							<table class="statementpreview">
							<?php	
							unset($error);
							if (sizeof($rows) == 1) {
								$cc = sizeof($rows[0]);
							} else if (sizeof($rows) > 1) {
								$cc = sizeof($rows[1]); //length of second row, header sometimes has extra cell at end
							} else {
								$rows = array(array('Error: statement is empty or incorrectly formatted'));
								$cc = 1;
								$error = 1;
							}
							$rc = 0; //row count
							foreach ($rows as $row) {
								$rc++;		
								$il = ($rc == 1 ? " class=\"ignoreline\"" : "");
								echo "								<tr$il>\n";
								for ($x = 0; $x < $cc; $x++) {
									echo "									<td>";
									echo preg_replace("/&nbsp;/",'',htmlentities(trim($row[$x])));
									echo "</td>\n";
								}
								echo "								</tr>\n";
							}
							?>
							</table>
						</div>
						<?php if (!isset($error)) { ?>
						<label for="selectaccount<?php echo $c ?>">Select an account:</label>
						<select id="selectaccount<?php echo $c ?>" class="selectaccount">
							<option></option>
							<?php
							foreach ($db->query("SELECT * FROM `bankaccounts` WHERE `clientid` = $clientid ORDER by `order`") as $row) {
								echo "<option value=\"".$row['id']."\">".$row['name']."</option>\n";
							}
							?>
						</select>
						<span class="checkboxcont">
							<label for="ignorefirstline<?php echo $c ?>">Ignore first line because my data has headers</label>
							<input type="checkbox" class="checkbox" id="ignorefirstline<?php echo $c ?>" checked="checked" class="ignorefirstline" />
						</span>
						<input type="button" class="button importstatement" id="importstatement<?php echo $c ?>" value="Import" />
						<?php } ?>
						<input type="button" class="button deletenewstatement" value="Delete statement" />
						<span id="statementcontstatus<?php echo $c ?>"></span>
					</div>

					<?php
				}
			}

			?>


			<?php // display uploader ?>

			<div id="uploader">
				<div id="mulitplefileuploader">Click to upload statements</div>
				<div id="status"></div>
			</div>

			<div id="reconbankselcont">
				<?php if (isset($_GET['b'])) {
					$q = $db->prepare("SELECT `name`, `id` FROM `bankaccounts` WHERE `id` = ? AND `clientid` = $clientid");
					$q->execute(array($_GET['b']));
					$rc = $q->rowCount();
					if ($rc == 1) {
						$r = $q->fetch();
						$accountid = $r['id'];
						$h3 = $r['name'];
					} else {
						$h3 = 'all accounts';
					}
				} else {
					$h3 = 'all accounts';
				}?><h3>Currently displaying statements from <?php echo $h3 ?></h3>
				<label for="statementsel">Select an account:</label>
				<select id="statementsel"><?php
					echo "\n";
					// echo ($h3 == 'all accounts' ? "					<option></option>\n" : "");
					echo "<option value=\"\">All accounts</option>\n";
					foreach ($db->query("SELECT * FROM `bankaccounts` WHERE `clientid` = $clientid ORDER by `order`") as $row) {
						echo "					<option value=\"".$row['id']."\"";
						if ($row['name'] == $h3) {
							echo " selected=\"selected\"";
						}
						echo ">".$row['name']."</option>\n";
					}
					?>
				</select>
				<br /><br />
				<span>Double-click on a statement below to load it.</span>
			</div>
			
			
			<?php
			
			if (isset($accountid)) {
				$whereclause = "bankaccounts.id = $accountid";
			} else {
				$whereclause = "bankaccounts.clientid = $clientid";
			}

			$q = $db->query("
				SELECT
					statements.id AS `id`,
					bankaccounts.name AS `name`,
					statements.importdate AS `importdate`,
					statements.startdate AS `startdate`,
					statements.enddate AS `enddate`,
					statements.lines AS `lines`
				FROM `statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id
				WHERE $whereclause
				ORDER BY `enddate` DESC, `importdate` DESC, `name`
			");
			
			$scount = $q->rowCount();
			if ($scount > 0) { ?>
				<table id="statementtable">
					<thead>
						<tr>
							<th>Account</th>
							<th>Import Date</th>
							<th>Opening Date</th>
							<th>Closing Date</th>
							<th>Lines</th>
						</tr>
					</thead>
					<tbody><?php echo "\n";
					$r = $q->fetchAll(PDO::FETCH_ASSOC);
					foreach ($r as $row) {
						echo "						<tr>\n";
						echo "							<input type=\"hidden\" class=\"statementid\" value=\"".$row['id']."\" />\n";
						echo "							<td>".$row['name']."</td>";
						echo "							<td>".date('j M Y', strtotime ($row['importdate']))."</td>";
						echo "							<td>".date('j M Y', strtotime ($row['startdate']))."</td>";
						echo "							<td>".date('j M Y', strtotime ($row['enddate']))."</td>";
						echo "							<td>".$row['lines']."</td>";
						echo "						</tr>\n";
					}
					?></tbody>
				</table>
			<?php }?>
			
			<div style="display:none">
				<div id="statementviewer"></div>
			</div>	

			<?php } else if ($s == 'reconciliation') { ?>
			
			<div id="reconbankselcont">
				<?php if (isset($_GET['b'])) {
					$q = $db->prepare("SELECT `name`, `id` FROM `bankaccounts` WHERE `id` = ? AND `clientid` = $clientid");
					$q->execute(array($_GET['b']));
					$rc = $q->rowCount();
					if ($rc == 1) {
						$r = $q->fetch();
						$accountid = $r['id'];
						$h3 = $r['name'];
					} else {
						$h3 = 'all accounts';
					}
				} else {
					$h3 = 'all accounts';
				}?><h3>Currently reconciling <?php echo $h3 ?></h3>
				<label for="reconbanksel">Select an account:</label>
				<select id="reconbanksel"><?php
					echo "\n";
					// echo ($h3 == 'all accounts' ? "					<option></option>\n" : "");
					echo "<option value=\"\">All accounts</option>\n";
					foreach ($db->query("SELECT * FROM `bankaccounts` WHERE `clientid` = $clientid ORDER by `order`") as $row) {
						echo "					<option value=\"".$row['id']."\"";
						if ($row['name'] == $h3) {
							echo " selected=\"selected\"";
						}
						echo ">".$row['name']."</option>\n";
					}
					?>
				</select>		
			</div><?php
				echo "\n";
				if (isset($accountid)) {
					$whereclause = "bankaccounts.id = $accountid";
				} else {
					$whereclause = "bankaccounts.clientid = $clientid";
				}
				$pageloadrowcount = 0;
				foreach ($db->query("
					SELECT
						statementlines.id AS `id`,
						statementlines.date AS `date`,
						statementlines.desc AS `desc`,
						statementlines.amount AS `amount`,
						statementlines.status AS `status`,
						statementlines.generation AS `generation`
					FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
					WHERE $whereclause
					AND statementlines.status = 'u'
					AND statementlines.generation != 'c'
					ORDER BY statementlines.date, statementlines.id
				") as $row) {
					// if ($pageloadrowcount < 50) {			//LIMIT PER PAGE. TO IMPLEMENT PROPERLY
					if ($pageloadrowcount >= 0) {			//NO LIMIT
				?>
			<div class="reconline">
				<span class="recondate"><?php echo date('j M Y', strtotime($row['date'])) ?></span>
				<span class="reconloading"></span>
				<span class="reconamount"><?php echo number_format(preg_replace("/([^0-9\\.-])/i", "", $row['amount'] / 100), 2, '.', ',') ?></span>
				<div class="recondesc"><?php echo utf8_decode($row['desc']) ?></div><?php 
				echo "\n";
				if ($row['generation'] == 'p') {		// look for children
					foreach ($db->query("
						SELECT
							statementlines.id AS `id`,
							statementlines.amount AS `amount`,
							statementlines.status AS `status`
						FROM `statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id
						WHERE $whereclause
						AND statementlines.generation = 'c'
						AND statementlines.parentid = ".$row['id']."
						ORDER BY statementlines.id							
					") as $child){		// loop through children
						if ($child['status'] == 'u') {		//child is unreconciled
				?>
				<div class="reconsubline">
					<input type="hidden" class="recongen" value="c" />
					<input type="hidden" class="reconlineid" value="<?php echo $child['id'] ?>" />
					<span class="xaccont">
						<input class="reconxac mousetrap" data-clicked="no" placeholder="Start typing a contact name..." />
						<div class="xacmenu">
							Javascript fail
						</div>
					</span>
					<span class="reconxacinfo"><a href="tenants.php?s=add" target="new">New tenant</a> / <a href="landlords.php?s=add" target="_new">landlord</a></span>
					<input type="hidden" class="contacttype" value="" />
					<input type="hidden" class="contactid" value="" />
					<span class="datecont">
						<input class="reconpaymentdate genericdate" value="<?php echo date('j M Y', strtotime($row['date'])) ?>" />
					</span>
					<input type="button" class="recon button rsdisabled" value="Reconcile" />
					<input class="reconsplitamount" value="<?php echo number_format(preg_replace("/([^0-9\\.-])/i", "", $child['amount'] / 100), 2, '.', '') ?>"/> <input type="button" class="reconsplit button" value="Split" />
					<input type="button" class="reconignore button" value="Ignore" />
				</div>					
				<?php } else if ($child['status'] == 'r') {
				$q = $db->prepare("SELECT payments.id AS `id` FROM `payments` LEFT JOIN `statementlines` ON payments.statementlineid = statementlines.id WHERE statementlines.id = ?");
				$q->execute(array($child['id']));
				$r = $q->fetch();
				?>
				<div class="reconsubline">
					<input type="hidden" class="recongen" value="c" />
					<input type="hidden" class="reconlineid" value="<?php echo $child['id'] ?>" />
					<span class="reconciledchild" ><a href="bank.php?s=detail&d=<?php echo $r['id'] ?>">Reconciled to payment #<?php echo $r['id'] ?></a></span>
					<input class="reconsplitamount" value="<?php echo number_format(preg_replace("/([^0-9\\.-])/i", "", $child['amount'] / 100), 2, '.', ',') ?>" disabled="disabled" /> <input type="button" class="button rsdisabled dischmabled" value="Split" />
				</div>
				<?php } else {			// ignored ?>
				<div class="reconsubline">
					<input type="hidden" class="recongen" value="c" />
					<input type="hidden" class="reconlineid" value="<?php echo $child['id'] ?>" />
					<span class="ignoredchild" >Split line ignored. <a class="reconundo" href="javascript:void(0)">Click here to unignore</a>.</span>
					<input class="reconsplitamount" value="<?php echo number_format(preg_replace("/([^0-9\\.-])/i", "", $child['amount'] / 100), 2, '.', ',') ?>" disabled="disabled" /> <input type="button" class="button rsdisabled dischmabled" value="Split" />
				</div>
				<?php }
				} ?>
				<div class="unsplitdiv">
					<input type="button" class="unsplit button" value="Unsplit all" />
				</div>

				<?php
				} else { ?>
				<div class="reconsubline">
					<input type="hidden" class="recongen" value="n" />
					<input type="hidden" class="reconlineid" value="<?php echo $row['id'] ?>" />
					<span class="xaccont">
						<input class="reconxac mousetrap" data-clicked="no" placeholder="Start typing a contact name..." />
						<div class="xacmenu">
							Javascript fail
						</div>
					</span>
					<span class="reconxacinfo"><a href="tenants.php?s=add" target="_new">New tenant</a> / <a href="landlords.php?s=add" target="_new">landlord</a></span>
					<input type="hidden" class="contacttype" value="" />
					<input type="hidden" class="contactid" value="" />
					<span class="datecont">
						<input class="reconpaymentdate genericdate" value="<?php echo date('j M Y', strtotime($row['date'])) ?>" />
					</span>
					<input type="button" class="recon button rsdisabled" value="Reconcile" />
					<input class="reconsplitamount" /> <input type="button" class="button reconsplit rsdisabled" value="Split" />
					<input type="button" class="reconignore button" value="Ignore" />
				</div><?php echo "\n"; } ?>
			</div><?php echo "\n"; flush();} $pageloadrowcount++; } ?>
			<br /><br /><br /><br /><br /><br />
			<input type="hidden" class="reconxac" />
			<div id="xaclookuplist">
				<ul><?php
				echo "\n";
				//create list of tenant fees				
				$tenantfees = array();
				foreach ($db->query("
				SELECT
					tenantfees.id AS `id`,
					tenantfees.tenancyid AS `tenancyid`,
					tenantfees.amount AS `amount`,
					tenants.name AS `name`,
					tenantfees.desc AS `desc`
				FROM `tenantfees` LEFT JOIN (
					`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
				) ON tenantfees.tenancyid = tenancies.id
				WHERE tenants.clientid = $clientid
				ORDER BY tenantfees.id
				") as $frow) {
					$tenantfees[$frow['id']] = array(
						'tenancyid' => $frow['tenancyid'],
						'amount' => $frow['amount'],
						'name' => $frow['name'],
						'desc' => $frow['desc']
					);
				}
				
				//create list of payments
				$feepayments = array();
				foreach ($db->query("
					SELECT
						payments.contactid AS `id`,
						payments.amount AS `amount`
					FROM `payments` LEFT JOIN (
						`tenantfees` LEFT JOIN (
							`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id
						) ON tenantfees.tenancyid = tenancies.id
					) ON payments.contactid = tenantfees.id
					WHERE payments.contacttype = 'f'
					AND tenants.clientid = $clientid
					ORDER BY payments.contactid
				") as $prow) {
					$feepayments[$prow['id']] = $prow['amount'];
				}
				
				//loop through fees array to check if paid
				foreach ($tenantfees as $fid => $farr) {
					if (isset($feepayments[$fid])) {	//corresponding payment
						$feebalance = $farr['amount'] - $feepayments[$fid];
					} else {
						$feebalance = $farr['amount'];
					}
					if ($feebalance > 0) {
						echo "				<li data-contacttype=\"f\" data-feeid=\"".$fid."\" data-tenancyid=\"".$farr['tenancyid']."\" >FEE: £".number_format($feebalance / 100, 2, '.', ',')." - ".$farr['name']." [#".$farr['tenancyid']."] - ".$farr['desc']."</li>\n";
					}
				}
				
				//tenant query
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
					echo "				<li data-contacttype=\"t\" data-tenancyid=\"".$trow['tid']."\" data-tenantid=\"".$trow['ttid']."\" data-address=\"".$trow['lname']."\">".$trow['tname']." (".$trow['sname'].") [#".$trow['tid']."]</li>\n";
				};
				// goodbye simple landlord query!
				// foreach ($db->query("SELECT * FROM `landlords` WHERE `clientid` = $clientid") as $lrow) {
					// echo "				<li data-contacttype=\"l\" data-landlordid=\"".$lrow['id']."\">".$lrow['name']."</li>\n";
				// }
				foreach ($db->query("
					SELECT
						mp.mcid AS `mcid`,
						mp.landlordname AS `llname`,
						properties.sname AS `sname`,
						mp.landlordid AS `lid`,
						CONCAT (properties.no,' ',properties.street) AS `lname`
					FROM (
						SELECT
							mgmtcontracts.id AS `mcid`,
							mgmtcontracts.landlordid AS `landlordid`,
							mgmtcontracts.propertyid AS `propertyid`,
							landlords.name AS `landlordname`,
							mgmtcontracts.enddate AS `mcenddate`
						FROM `mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id
					) mp LEFT JOIN `properties` ON mp.propertyid = properties.id
					WHERE properties.clientid = $clientid
					ORDER BY mp.landlordname, (mp.mcenddate IS NOT NULL), mp.mcenddate DESC;
				") as $lrow) {
					echo "				<li data-contacttype=\"l\" data-mcid=\"".$lrow['mcid']."\" data-landlordid=\"".$lrow['lid']."\" data-address=\"".$lrow['lname']."\">".$lrow['llname']." (".$lrow['lname'].") [#".$lrow['mcid']."]</li>\n";
				}
			
				
				?>
				</ul>
			</div> 
			<?php echo "\n"; } else if ($s == 'payments') { ?>
				
			<?php if (isset($_GET['c'])) {
				if ($_GET['c'] == 'l') {
					$ctype = 'landlord';
				} else {
					$ctype = 'tenant';
				}
			} else {
				$ctype = 'tenant';
			} ?>
			
			<div id="paymentctypeselcont">
				<h3>Currently viewing <?php echo $ctype ?> transactions</h3>
				<label for="paymentctypesel">Choose contact type:</label>
				<select id="paymentctypesel">
					<option value="l"<?php echo ($ctype == 'landlord' ? ' selected="selected"' : '')?>>Landlords</option>
					<option value="t"<?php echo ($ctype == 'tenant' ? ' selected="selected"' : '')?>>Tenants</option>
				</select>
				<br /><br />
				<span>Double-click on a payment below to view details.</span>
			</div>
			
			<table id="datatable">
				<thead>
					<tr>
						<th>Date</th>
						<th>Contact</th>
						<th>Amount</th>
					</tr>
				</thead>
				<tbody> <?php echo "\n";
					if ($ctype == 'tenant') {
						$q = $db->prepare("
							(
								SELECT 
									payments.id AS `id`,
									payments.date AS `date`,
									tenants.name AS `name`,
									CONCAT ('Rent payment') AS `desc`,
									payments.amount AS `amount`
								FROM `payments` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON payments.contactid = tenancies.id 
								WHERE tenants.clientid = $clientid
								AND payments.contacttype = 't'
							) UNION (
								SELECT 
									payments.id AS `id`,
									payments.date AS `date`,
									tenants.name AS `name`,
									tenantfees.desc AS `desc`,
									payments.amount AS `amount`
								FROM `payments` LEFT JOIN (`tenantfees` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON tenantfees.tenancyid = tenancies.id) ON payments.contactid = tenantfees.id 
								WHERE tenants.clientid = $clientid
								AND payments.contacttype = 'f'								
							)
							ORDER BY `date` DESC
							
						");
					} else {
						$q = $db->prepare("
							SELECT 
								payments.id AS `id`,
								payments.date AS `date`,
								landlords.name AS `name`,
								CONCAT ('landlord payment') AS `desc`,
								payments.amount AS `amount`
							FROM `payments` LEFT JOIN (`mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id) ON payments.contactid = mgmtcontracts.id 
							WHERE landlords.clientid = $clientid
							and payments.contacttype = 'l'
							ORDER BY payments.date DESC
						");						
						// $q = $db->prepare("
							// SELECT 
								// payments.id AS `id`,
								// payments.date AS `date`,
								// landlords.name AS `name`,
								// payments.amount AS `amount`
							// FROM `payments` LEFT JOIN `landlords` ON payments.contactid = landlords.id 
							// WHERE landlords.clientid = $clientid
						// ");							
					}
					$q->execute();
					$r = $q->fetchAll(PDO::FETCH_ASSOC);
					foreach ($r as $row) {
						echo "					<tr id=\"".$row['id']."\">\n";
						echo "						<td>".date('j M Y', strtotime($row['date']))."</td>\n";
						echo "						<td>".$row['name']." (".$row['desc'].")</td>\n";
						echo "						<td>".number_format(preg_replace("/([^0-9\\.-])/i", "", $row['amount'] / 100), 2, '.', ',')."</td>\n";
						echo "					</tr>\n";
					}
				?>
				</tbody>
				<input type="hidden" id="selectedid" />
			</table>
			
			<?php } else if ($s == 'detail') { ?>

			<?php 

			if (!isset($_GET['d'])) {
				foreach ($db->query("SELECT payments.id FROM `payments` LEFT JOIN (`statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id) ON payments.statementlineid = statementlines.id WHERE `clientid` = '$clientid' ORDER BY `id` DESC LIMIT 1") as $row) {
					echo "<script> document.location = '".$_SERVER['REQUEST_URI']."&d=".$row['id']."' </script>";
				}
			}

			if (isset($_POST['submit'])) {
				// print_r($_POST);
				$id = $_POST['input_id'];
				$error = 0;

				if (strtotime($_POST['paymentchangedate']) != 0) {
					// @@@@ need to check payment is ours first
					$q = $db->prepare("UPDATE `payments` SET `date` = ? WHERE `id` = ?");
					$q->execute(array(date('Y-m-d', strtotime($_POST['paymentchangedate'])), $_POST['input_id']));
				} else {
					$error |= 1;
				}
				
				if ($error == 0) {
					echo "<p>All changes submitted successfully!</p>\n";
				} else {
					echo "		<p class=\"error\">ERROR</p>";
					if ($error & 1) {
						echo "		<p>Invalid date entered</p>";
					}
				}
			}

			?>

			<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST">
				<fieldset class="lfieldset" id="paymentfieldset">
					<legend>Edit payment details:</legend>
					<input type="hidden" name="input_id" id="input_id" value="<?php echo $_GET['d'] ?>" /> <?php 
					
					$q = $db->prepare("SELECT `contacttype` FROM `payments` WHERE `id` = ?");
					$q->execute(array($_GET['d']));
					$r = $q->fetch();
					$ctype = $r['contacttype'];
					
					if ($ctype == 't') {	//tenant
						$q = $db->prepare("
							SELECT 
								payments.id AS `id`,
								payments.date AS `date`,
								tenants.name AS `name`,
								payments.amount AS `amount`,
								tenants.id AS `cid`,
								tenancies.id AS `tenancy`
							FROM `payments` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON payments.contactid = tenancies.id 
							WHERE tenants.clientid = $clientid
							AND payments.id = ?
						");
					} else if ($ctype == 'l') {	//landlord
						$q = $db->prepare("
							SELECT 
								payments.id AS `id`,
								payments.date AS `date`,
								landlords.name AS `name`,
								payments.amount AS `amount`,
								landlords.id AS `cid`,
								mgmtcontracts.id AS `mc`
							FROM `payments` LEFT JOIN (`mgmtcontracts` LEFT JOIN `landlords` ON mgmtcontracts.landlordid = landlords.id) ON payments.contactid = mgmtcontracts.id 
							WHERE landlords.clientid = $clientid
							AND payments.id = ?
						");
						// $q = $db->prepare("
							// SELECT 
								// payments.id AS `id`,
								// payments.date AS `date`,
								// landlords.name AS `name`,
								// landlords.id AS `cid`,
								// payments.amount AS `amount`
							// FROM `payments` LEFT JOIN `landlords` ON payments.contactid = landlords.id 
							// WHERE tenants.clientid = $clientid
							// AND payments.id = ?
						// ");							
					} else {		//fee
						$q = $db->prepare("
							SELECT 
								payments.id AS `id`,
								payments.date AS `date`,
								tenants.name AS `name`,
								payments.amount AS `amount`,
								tenants.id AS `cid`,
								tenancies.id AS `tenancy`
							FROM `payments` LEFT JOIN (`tenantfees` LEFT JOIN (`tenancies` LEFT JOIN `tenants` ON tenancies.tenantid = tenants.id) ON tenantfees.tenancyid = tenancies.id) ON payments.contactid = tenantfees.id
							WHERE tenants.clientid = $clientid
							AND payments.id = ?
						");						
					}

					$q->bindValue(1, $_GET['d']);
					$q->execute();
					$rc = $q->rowCount();
					if ($rc == 1) {
						$r = $q->fetch();
						$q2 = $db->prepare("SELECT bankaccounts.name AS `name`, statementlines.id AS `sline` FROM `payments` LEFT JOIN (`statementlines` LEFT JOIN (`statements` LEFT JOIN `bankaccounts` ON statements.accountid = bankaccounts.id) ON statementlines.statementid = statements.id) ON payments.statementlineid = statementlines.id WHERE payments.id = ?");
						$q2->execute(array($r['id']));
						$r2 = $q2->fetch();
						?>
					<p><label class="zoomlabel">Payment ID:</label> <?php echo $r['id'] ?></p>
					<p>
						<label class="zoomlabel" for="paymentchangedate">Payment date:</label>
						<input id="paymentchangedate" name="paymentchangedate" value="<?php echo date('j M Y', strtotime($r['date'])) ?>"/>
					</p>
					<p><label class="zoomlabel">Amount:</label> <?php echo number_format($r['amount'] / 100, 2, '.', ',') ?></p>
					<p><label class="zoomlabel">Bank Account:</label> <?php echo $r2['name'] ?></p>
					<p>
						<label class="zoomlabel" for="paymentchangecontact"> <?php echo ($ctype == 'l' ? 'Landlord' : 'Tenant') ?> name:</label>
						<a href="<?php echo ($ctype == 'l' ? 'landlords' : 'tenants') ?>.php?s=detail&d=<?php echo $r['cid'] ?>"><?php echo $r['name'] ?></a>
					</p>
					<?php if ($ctype == 't') {
						$q3 = $db->prepare("
							SELECT
								CONCAT (properties.no,' ', properties.street) as `property`,
								rooms.no AS `room`
							FROM `tenancies` LEFT JOIN (`rooms` LEFT JOIN `properties` ON rooms.propertyid = properties.id) ON tenancies.roomid = rooms.id
							WHERE tenancies.id = ?
						");
						$q3->execute(array($r['tenancy']));
						$r3 = $q3->fetch();
					?>
					<p><label class="zoomlabel">Tenancy:</label> Room <?php echo $r3['room'].", ".$r3['property']." (#".$r['tenancy'].")" ?></p>
					<?php } ?>
					<p><label class="zoomlabel">Original statement line:</label> <?php $q4 = $db->prepare("SELECT * FROM `statementlines` WHERE `id` = ?"); 
					$q4->execute(array($r2['sline']));
					$r4 = $q4->fetch();
					echo date('j M Y', strtotime($r4['date']))." | ".$r4['desc']." | ".number_format($r4['amount'] / 100, 2, '.', ',');
					?></p>
					<?php } ?>
				</fieldset>
				<p class="detailsubmit">
					<input type="submit" name="submit" value="Submit changes" class="button"/>
					<input type="button" class="button" id="undopayment" value="Undo payment"/>
				</p>
			</form> 
			
			<?php } else if ($s == 'statementexport') { ?>
			
			<div class="statementexbankcont">
				<h3>Select accounts to export to Xero-friendly format:</h3>
			<?php echo "\n";
			foreach ($db->query("SELECT * FROM `bankaccounts` WHERE `clientid` = $clientid ORDER by `order`") as $row) {
				echo "				<p>\n";
				echo "					<span class=\"statementexbankcheckcont\">\n";
				echo "						<input type=\"checkbox\" class=\"statementexbankcheck\" id=\"statementexbankcheck_".$row['id']."\" data-accountid=\"".$row['id']."\" checked=\"checked\" />\n";
				echo "						<label class=\"statementexbankchecklabel\">".$row['name']."</label>\n";
				echo "					</span>\n";
				echo "				</p>\n";
			}
			?>
			</div>
			
			<div class="statementexbankcont">
				<h3>Choose date range:</h3>
				<p>
					<span class="datecont">
						<label class="tenancylabel2" for="statementex_startdate">Start date:</label>
						<input class="genericdate" id="statementex_startdate" value="<?php echo date('j M Y', strtotime('-8 days', strtotime(date('Y-m-d')))) ?>" />
					</span>
				</p>
				<p>
					<span class="datecont">
						<label class="tenancylabel2" for="statementex_enddate">End date:</label>
						<input class="genericdate" id="statementex_enddate" value="<?php echo date('j M Y', strtotime('-1 day', strtotime(date('Y-m-d')))) ?>" />
					</span>
				</p>
			</div>
			
			<input type="button" class="button" id="exportstatement" value="Export statements" />
			<div id="output"></div>
			<?php echo "\n"; } ?>
			
		</div>
		<input type="hidden" id="lastfocus" value="input_ref" />
	</body>
</html>
<?php } ?>