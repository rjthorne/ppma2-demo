<?php
include_once 'defs/db_connect.php';
include_once 'defs/functions.php';
require 'defs/inc_accountcheck.php';
 
if ($displaycontent == true) {
	$pagetitle = 'Settings';
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require 'defs/inc_jscss.php' ?>
		<script type="text/javascript" src="js/admin.js"></script>
	</head>
	<body>
		<?php require 'defs/inc_header.php' ?>
		<div id="subheader">
			<div id="innersubheader">
				<?php if (isset($_GET['s'])) {
					if ($_GET['s'] == 'xero') {
						$s = 'xero';
					} else if ($_GET['s'] == 'admin') {
						$s = 'admin';
					} else {
						$s = 'general';
					}
				} else {
					$s = 'general';
				}?>
				<span class="<?php echo ($s == 'general' ? 'subtab_active' : 'subtab') ?>" id="general">
					General
				</span>
				<span class="<?php echo ($s == 'xero' ? 'subtab_active' : 'subtab') ?>" id="xero">
					Xero
				</span>
				<?php if ($usertype == 'a') { ?>
				<span class="<?php echo ($s == 'admin' ? 'subtab_active' : 'subtab') ?>" id="admin">
					Admin
				</span>
				<?php } ?>
			</div>
		</div>
		<div id="main">
			<?php if ($s == 'general') { ?>
			
			<p>
				User-defined settings to be added...
			</p>
			
			<?php } else if ($s == 'xero') { ?>

			<p>
				View only, admin privileges required to edit.
			</p>
			
			<h2>Xero Account Numbers</h2>
			
			<?php
			$q = $db->query("SELECT * FROM `clients` WHERE `id` = $clientid");
			$r = $q->fetch(PDO::FETCH_ASSOC);
			?>
			
			<p>
				<label class="xero_acc_labels" for="xero_acc_rentm">Landlord's Rent (managed properties):</label>
				<input class="xero_acc_inputs" id="xero_acc_rentm" value="<?php echo $r['xero_acc_rentm'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_rentr">Retained Rent (leased properties):</label>
				<input class="xero_acc_inputs" id="xero_acc_rentr" value="<?php echo $r['xero_acc_rentr'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_appfee">Tenant app. fees (managed properties):</label>
				<input class="xero_acc_inputs" id="xero_acc_appfee" value="<?php echo $r['xero_acc_appm'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_appfee">Tenant app. fees (leased properties):</label>
				<input class="xero_acc_inputs" id="xero_acc_appfee" value="<?php echo $r['xero_acc_appr'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_letfee">Letting fees:</label>
				<input class="xero_acc_inputs" id="xero_acc_letfee" value="<?php echo $r['xero_acc_letfee'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_mgmtfee">Management fees:</label>
				<input class="xero_acc_inputs" id="xero_acc_mgmtfee" value="<?php echo $r['xero_acc_mgmtfee'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_main">Maintenance expenses:</label>
				<input class="xero_acc_inputs" id="xero_acc_main" value="<?php echo $r['xero_acc_main'] ?>" />
			</p>
			<p>
				<label class="xero_acc_labels" for="xero_acc_other">Other fees/expenses:</label>
				<input class="xero_acc_inputs" id="xero_acc_other" value="<?php echo $r['xero_acc_other'] ?>" />
			</p>
			<p>&nbsp;</p>
			
			<h2>Xero Tracking Categories</h2>

			<p>
				<label class="xero_track_labels" for="xero_tracking1">Category 1:</label>
				<input class="xero_track_inputs" id="xero_tracking1" value="<?php echo $r['xero_tracking1'] ?>" />
			</p>			

			<p>
				<label class="xero_track_labels" for="xero_tracking2">Category 2:</label>
				<input class="xero_track_inputs" id="xero_tracking2" value="<?php echo $r['xero_tracking2'] ?>" />
			</p>			
			
			<?php } else if ($s == 'admin') { ?>
			<?php if ($usertype == 'a') { ?>
			<h3>Change Client ID</h3>
			<select id="changeclientID"><?php 
			echo "				<option value=\"0\"";
			if ($clientid == '0')
				echo " selected=\"selected\"";
			echo ">[None]</option>\n";
			foreach($db->query("SELECT * FROM clients") as $row) {
				echo "				<option value=\"".$row['id']."\"";
				if ($clientid == $row['id'])
					echo " selected=\"selected\"";					
				echo ">".$row['name']."</option>\n";
			}
			?>
			</select>
			<?php } ?>
			<?php } ?>
			
		</div>
		
		<input type="hidden" id="lastfocus" value="input_ref" />
		
	</body>
</html>
<?php } ?>