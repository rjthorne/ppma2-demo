$(function() {

	$('#feespropertyselect').change(function() {
		location = 'properties.php?s=feesexp&d='+$(this).val();
	});

	// ==================== XAC ========================
	
	$('.xacmenu').html($('#xaclookuplist').html());

	$('.xacmenu li').click(function() {
		$(this).parent().parent().parent().find('#propfeesxac').val($(this).html());
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
		$(this).parent().parent().hide();
		location = 'properties.php?s=feesexp&d='+$(this).data('mcid');
	});
	
	$('#propfeesxac').keyup(function(k) {
		if (k.which == 13) {
			$(this).parent().find('.xacsel').click();
			//key specific stuff
		} else if (k.which == 40) {

		} else if (k.which == 38) {

		} else {
			$(this).parent().find('li').each(function() {
				var str = $(this).parent().parent().parent().find('#propfeesxac').val().toLowerCase();
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
	
	$('#propfeesxac').keydown(function(k) {
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

	$('#propfeesxac').focusout(function() {
		$(this).parent().find('.xacsel').click();
		$(this).parent().find('.xacmenu').hide();
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
	});

	$('.reconxac').keydown();
	$('.reconxac').keyup();
	
	// ========================================================
	
	if ($('.subtab_active').attr('id') == 'feesexp') {
		if ($('#getd').val() != 0) {
			$('#detail').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#detail').wrap('<a href="'+url+'?s=detail&d='+$('#gett').val()+'"></a>');
		}
	}
	
	if ($('.subtab_active').attr('id') == 'detail') {
		if ($('#getf').val() != 0) {
			$('#feesexp').unwrap();
			var url = [location.protocol, '//', location.host, location.pathname].join('');
			$('#feesexp').wrap('<a href="'+url+'?s=feesexp&d='+$('#getf').val()+'"></a>');
		}
	}	
	
	$('#addfee').click(function() {
		var thiscont = $(this).parent().parent();
		$.post('ajax.php', {
			ajax: 'addpropertyfee',
			date: $('#addfeedate').val(),
			mc: $('#getd').val(),
			amount: $('#addfeeamount').val(),
			// property: $('#addfeeproperty').val(),
			desc: $('#addfeedesc').val(),
			type: $('#addfeetype').val()
		}, function(output){
			thiscont.append(output);
		});
	});
	
	$('#addfeedesc').keyup(function(k){
		if (k.which == 13) {
			$('#addfee').click();
		}
	});
	
	$('#addfeedate').focus();
	
	$('.tdedit').click(function() {
		$('.error').hide();
		$('#addfeediv').hide();
		$('#editfeediv').show();
		$('#editfeediv h2').html('Edit fee for '+$(this).parent().find('td').eq(1).html());
		$('#editfeeid').val($(this).parent().attr('data-id'));
		$('#editfeedate').val($(this).parent().find('td').eq(0).html());
		$('#editfeeamount').val($(this).parent().find('td').eq(2).html());
		$('#editfeedesc').val($(this).parent().find('td').eq(3).html());
		$('#editfeetype option').removeAttr('selected');
		if ($(this).parent().find('td').eq(4).html() == 'Maintenance') {
			$('#editfeetype option').prop('disabled', false);
			$('#editfeetype option[value="m"]').attr('selected', 'selected');
			$('#editfeetype option[value="l"]').prop('disabled', true);
		} else if ($(this).parent().find('td').eq(4).html() == 'Letting fee') {
			$('#editfeetype option').prop('disabled', false);
			$('#editfeetype option[value="l"]').attr('selected', 'selected');
			$('#editfeetype option:not(:selected)').prop('disabled', true);
		} else {
			$('#editfeetype option').prop('disabled', false);
			$('#editfeetype option[value="o"]').attr('selected', 'selected');
			$('#editfeetype option[value="l"]').prop('disabled', true);
			
		}
	});	
	
	$('#editfee').click(function() {
		var thiscont = $(this).parent().parent().parent();
		$.post('ajax.php', {
			ajax: 'editpropertyfee',
			id: $('#editfeeid').val(),
			date: $('#editfeedate').val(),
			amount: $('#editfeeamount').val(),
			desc: $('#editfeedesc').val(),
			type: $('#editfeetype').val()
		}, function(output){
			thiscont.append(output);
		});		
	});

	$('#editfeedesc').keyup(function(k){
		if (k.which == 13) {
			$('#editfee').click();
		}
	});	
	
	$('#deletefee').click(function() {
		var thiscont = $(this).parent().parent().parent();
		var thiscont = $(this).parent().parent().parent();
		$.post('ajax.php', {
			ajax: 'deletepropertyfee',
			id: $('#editfeeid').val()
		}, function(output){
			thiscont.append(output);
		});			
	});
	
	$('#canceleditfee').click(function() {
		$('#editfeediv').hide();
		$('#addfeediv').show();
	});
	
});
