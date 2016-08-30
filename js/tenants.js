$(function() {

	$('.tenancycont h3').click(function() {
		$(this).parent().find('div').toggleClass('tenancyshow tenancyhide');
	});

	$('.tenancyinput1').each(function(i) {
		var t = $('#id'+i).val();	
		// $('body').append(('#t'+t+'_property').val);
		$(this).change(function() {
			$('#t'+t+'_roomid').prop( "disabled", true );
			// $('body').append('#t'+t+'_roomid');
			$.post('ajax.php',{
				ajax: 'updaterooms',
				pid: $('#t'+t+'_property').val()
			},function(output) {	
				$('#t'+t+'_roomid').prop( "disabled", false );
				$('#t'+t+'_roomid').html(output);
				// $('body').append(output);
			});
		});			
	});
	
	$('.enddate').change(function() {
		var t = $(this).parent().parent().parent().parent().parent().find('.tenancyid').val();
		$('#t0_property').html( $('#t'+t+'_property').html());
		$('#t0_roomid').html( $('#t'+t+'_roomid').html());
		$('#t0_minenddate').val( $('#t'+t+'_minenddate').val());
		$('#t0_rent').val( $('#t'+t+'_rent').val());
		$('#t0_period').html( $('#t'+t+'_period').html());
		$('#t0_obal').val( $('#t'+t+'_obal').val());
		$('#t0_obaldate').val( $('#t'+t+'_obaldate').val());
		$.post('ajax.php', {
			ajax: 'newstartdate',
			enddate: $(this).val()
		}, function(output) {
			$('#t0_startdate').val(output);
		});
		
		// $('body').append(t);
		// $('body').append('balls');
	});
	
	$('#t0_appfeecheck').change(function() {
		if ($(this).is(':checked')) {
			$('#t0_appfee').prop('disabled', false);
		} else {
			$('#t0_appfee').val('');
			$('#t0_appfee').prop('disabled', true);
		}
	});
	
	$('#t0_letfeecheck').change(function() {
		if ($(this).is(':checked')) {
			$('#t0_letfee').prop('disabled', false);
		} else {
			$('#t0_letfee').val('');
			$('#t0_letfee').prop('disabled', true);
		}
	});	
	
	$('.feecheck').change(function() { //should really replace the above two
		if ($(this).is(':checked')) {
			$(this).parent().find('.fee').prop('disabled', false);
		} else {
			$(this).parent().find('.fee').val('');
			$(this).parent().find('.fee').prop('disabled', true);
		}		
	});
	
	$('.feecheck').change();
	
	
	
	$('#addtenancy').click(function() {
		$.post('ajax.php',{
			ajax: 'addtenancy',
			tenantid: $('#input_id').val(),	//check tenant belongs to client in ajax
			roomid: $('#t0_roomid').val(),
			startdate: $('#t0_startdate').val(),
			minenddate: $('#t0_minenddate').val(),
			enddate: $('#t0_enddate').val(),
			rent: $('#t0_rent').val(),
			period: $('#t0_period').val(),
			obal: $('#t0_obal').val(),
			obaldate: $('#t0_obaldate').val(),
			appfee: $('#t0_appfee').val(),
			letfee: $('#t0_letfee').val()
		}, function(output) {
			$('#addtenancy').parent().append(output);
		});
	});
	
	var latesttenancy = $('.tenancyshow').first().parent().find('.tenancyid').val();
	if (typeof latesttenancy !== 'undefined') {
		$('#feesexp').unwrap();
		var url = [location.protocol, '//', location.host, location.pathname].join('');
		$('#feesexp').wrap('<a href="'+url+'?s=feesexp&d='+latesttenancy+'"></a>');
	}
	
	// ==================== XAC ========================
	
	if ($('.subtab_active').attr('id') == 'feesexp') {
		var xacfield = '#tenantfeesxac';
	} else if ($('.subtab_active').attr('id') == 'adjustments') {
		var xacfield = '#adjustmentsxac';
	}
	
	$('.xacmenu').html($('#xaclookuplist').html());

	$('.xacmenu li').click(function() {
		$(this).parent().parent().parent().find(xacfield).val($(this).html());
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
		$(this).parent().parent().hide();
		if ($('.subtab_active').attr('id') == 'feesexp') {
			location = 'tenants.php?s=feesexp&d='+$(this).data('tenancyid');
		} else if ($('.subtab_active').attr('id') == 'adjustments') {
			location = 'tenants.php?s=adjustments&d='+$(this).data('tenancyid');
		}
	});
	
	$(xacfield).keyup(function(k) {
		if (k.which == 13) {
			$(this).parent().find('.xacsel').click();
			//key specific stuff
		} else if (k.which == 40) {

		} else if (k.which == 38) {

		} else {
			$(this).parent().find('li').each(function() {
				var str = $(this).parent().parent().parent().find(xacfield).val().toLowerCase();
				// $(this).removeAttr('id');
				if ($(this).is(':visible')) {
					if ($(this).html().toLowerCase().indexOf(str) < 0 /*|| str == '' */) {
						$(this).hide();
					}
				} else {
					if ($(this).html().toLowerCase().indexOf(str) >= 0 && str != '') {
						$(this).show();
						$(this).parent().parent().show();
					}
				}
			});
		$(this).parent().find('li').removeClass('xacsel');
		$(this).parent().find('li:visible:first').addClass('xacsel');
		}
	});
	
	$(xacfield).keydown(function(k) {
		$(this).removeAttr('data-clicked');
		$(this).attr('data-clicked', 'no');
		if (k.which == 9) {
			$(this).parent().find('.xacsel').click();
		} else if (k.which == 40) {
			if ($(this).parent().find('.xacsel').nextAll(':visible:first').length > 0) {
				var xacsel = $(this).parent().find('.xacsel');
				xacsel.removeClass('xacsel');
				xacsel.nextAll(':visible:first').addClass('xacsel');
			}
		} else if (k.which == 38) {
			if ($(this).parent().find('.xacsel').prevAll(':visible:first').length > 0) {
				var xacsel = $(this).parent().find('.xacsel');
				xacsel.removeClass('xacsel');
				xacsel.prevAll(':visible:first').addClass('xacsel');
			}
		}
	});		

	$(xacfield).focusout(function() {
		$(this).parent().find('.xacsel').click();
		$(this).parent().find('.xacmenu').hide();
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
	});

	$(xacfield).keydown();
	$(xacfield).keyup();
	
	// ============================ FEES ============================

	if ($('.subtab_active').attr('id') == 'feesexp') {
		if ($('#getd').val() != 0) {
			$('#detail').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#detail').wrap('<a href="'+url+'?s=detail&d='+$('#gett').val()+'"></a>');
			$('#adjustments').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#adjustments').wrap('<a href="'+url+'?s=adjustments&d='+$('#getd').val()+'"></a>');		
		}
	}
	
	
	
	$('#addfee').click(function() {
		var thiscont = $(this).parent().parent();
		if ($('#addfeepayabletolandlord').is(':checked')) {
			var payableto = 'l';
		} else {
			var payableto = 'c';
		}
		$.post('ajax.php', {
			ajax: 'addtenantfee',
			date: $('#addfeedate').val(),
			tenancy: $('#getd').val(),
			amount: $('#addfeeamount').val(),
			ptl: payableto,
			desc: $('#addfeedesctenant').val()
		}, function(output){
			thiscont.append(output);
		});
	});
	
	$('#addfeedesctenant').keyup(function(k){
		if (k.which == 13) {
			$('#addfee').click();
		}
	});
	
	$('#addfeedate').focus();	
	
	// =========================== ADJUSTMENTS ============================
	
	if ($('.subtab_active').attr('id') == 'adjustments') {
		if ($('#getd').val() != 0) {
			$('#detail').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#detail').wrap('<a href="'+url+'?s=detail&d='+$('#gett').val()+'"></a>');
			$('#feesexp').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#feesexp').wrap('<a href="'+url+'?s=feesexp&d='+$('#getd').val()+'"></a>');			
		}
	}
	
	$('#addadjustment').click(function() {
		var thiscont = $(this).parent().parent();
		if ($('#addadjustmentmgmt').is(':checked')) {
			var applymgmt = 'y';
		} else {
			var applymgmt = 'n';
		}
		$.post('ajax.php', {
			ajax: 'addadjustment',
			date: $('#addadjustmentdate').val(),
			tenancy: $('#getd').val(),
			amount: $('#addadjustmentamount').val(),
			amf: applymgmt,
			desc: $('#addadjustmentdesc').val()
		}, function(output){
			thiscont.append(output);
		});
	});	

	$('#addadjustmentdesc').keyup(function(k){
		if (k.which == 13) {
			$('#addadjustment').click();
		}
	});
	
	$('#addadjustmentdate').focus();	
	
	// ======================
	
	if ($('.subtab_active').attr('id') == 'detail') {
		var latesttenancyid = $('#id1').val();
		if (latesttenancyid != 0) {
			$('#adjustments').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#adjustments').wrap('<a href="'+url+'?s=adjustments&d='+latesttenancyid+'"></a>');
			$('#feesexp').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#feesexp').wrap('<a href="'+url+'?s=feesexp&d='+latesttenancyid+'"></a>');			
		}
	}	
	
});