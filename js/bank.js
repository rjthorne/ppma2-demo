function isNumeric(n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
}

function initdropdown() {

	$('.previewline').each(function(i0) {
		$(this).unbind();
		var i = i0 + 1;
		$(this).click(function() {
			$(this).find('.dropdown').offset({left: event.pageX, top: event.pageY});
			$('#submenu'+i).toggleClass('submenu');
			$('.submenu').hide();
			$('#submenu'+i).toggleClass('submenu');
			$('#submenu'+i).show();
		});
	});

	$('.submenu').mouseup(function() {
		return false
	});
	
	$('.previewline').mouseup(function() {
		return false
	});

	$(document).mouseup(function() {
		$('.submenu').hide();
	});
	
	$('.submenu').each(function(i0) {
		var i = i0 + 1;
		$(this).unbind();
		$('#submenu'+i+' li').click(function(e){
			e.stopPropagation();
			if ($(this).attr('class') == 'markdupe') {
				// @@@@@@@@@@@ run ajax call to change line status and change tr class to not grey
			} else if ($(this).attr('class') == 'unmarkdupe') {
				// @@@@@@@@@@@ change line status to 'u', change tr class to not grey
			}
			
			$('#submenu'+i).hide();
		});
	});
	
}

function initPreviewFunctions() {
	
	$('.markdupe').click(function(){
		var thisli = $(this);
		var thistr = $(this).parent().parent().parent().parent().parent();
		$.post('ajax.php', {
			ajax: 'markdupe',
			id: thistr.find('.previewlineid').val()
		}, function(output) {
			if (output == 'd') {
				thistr.addClass('ignoreline');
				thistr.addClass('dupeline');
				thisli.html('Click to unmark this line as a duplicate');
			} else if (output == 'u') {
				thistr.removeClass('ignoreline');
				thistr.removeClass('dupeline');
				thisli.html('Click to mark this line as a duplicate');
			}
		});
	});
	
	$('.ignore').click(function(){
		
		var thisli = $(this);
		var thistr = $(this).parent().parent().parent().parent().parent();		
		$.post('ajax.php', {
			ajax: 'ignore',
			id: thistr.find('.previewlineid').val()
		}, function(output) {
			if (output == 'i') {
				thistr.addClass('ignoreline');
				thisli.html('Click to unignore this line');
			} else if (output == 'u') {
				thistr.removeClass('ignoreline');
				thisli.html('Click to ignore this line');
			} 
		});
	});

	
}

// function initReconUnignore(){
	// $('.reconunignore').unbind();
	// $('.reconunignore').click(function() {
		// var thiscont = $(this).parent().parent();
		// $.post('ajax.php', {
			// ajax: 'reconunignore',
			// id: $(this).parent().parent().find('.reconlineid').val()
		// }, function(output){
			// thiscont.append(output); 
		// });
	// });
// }

function initReconUndo() {
	$('.reconundo').unbind();
	$('.reconundo').click(function() {
		var thiscont = $(this).parent().parent();
		$.post('ajax.php', {
			ajax: 'reconundo',
			id: $(this).parent().find('.reconlineid').val()
		}, function(output){
			thiscont.append(output); 
		});		
	});
}

$(function() {
	
	$('.bankcol').sortable({
		connectWith: '.bankcol',
		handle: ".handle",
		stop: function() {
			$('.bankaccount').each(function(i) {
				// $('body').append($(this).find('h3').html());
				$.post('ajax.php', {
					ajax: 'updatebankaccountorder',
					account: $(this).find('.bankaccountid').val(),
					order: i + 1
				}, function(output) {
					$('body').append(output);
				});
			});
		}
	});	
	
	$('#addnewbankaccount').click(function() {
		// remember to validate php-side!
		$.post('ajax.php', {
			ajax: 'addnewbankaccount',
			name: $('#newacc-name').val(),
			bank_s: $('#newacc-bank').val(), 
			bank_l: $('#newacc-bank option:selected').text(),
			notes: $('#newacc-notes').val(),
			order: $('#newacc-order').val()
		}, function(output) {
			$('body').append(output);
		});
	});
	
	$('.amendbankaccount').click(function() {
		$(this).parent().find('.amendbankaccountfields').toggle();
		$(this).toggle();
	});
	
	$('.cancelchanges').click(function() {
		$(this).parent().parent().find('.amendbankaccount').toggle();
		$(this).parent().toggle();
	});
	
	$('.submitchanges').click(function() {
		$.post('ajax.php', {
			ajax: 'amendbankaccount',
			name: $(this).parent().find('.acc-name').val(),
			bank_s: $(this).parent().find('.acc-bank').val(), 
			bank_l: $(this).parent().find('.acc-bank option:selected').text(),
			notes: $(this).parent().find('.acc-notes').val(),
			id: $(this).parent().parent().find('.bankaccountid').val()
		}, function(output) {
			$('body').append(output);
		});		
	});
	
	var uploadSettings = {
		url: 'ajax.php',
		method: 'POST',
		allowedTypes:'csv',
		fileName: 'statement',
		multiple: true,
		onSuccess:function(files,data,xhr) {
			$('#status').show();
			$('#status').html('Upload successful. <a href="bank.php?s=statements">Click here to import uploaded statements</a>.');
		},
		onError: function(files,status,errMsg) {		
			$('#status').show();
			$('#status').html('<span class="error">Upload failed</span>');
		}
	}
	
	$('#mulitplefileuploader').uploadFile(uploadSettings);
	
	$('.ignorefirstline').change(function() {
		$(this).parent().parent().find('.statementpreview').find('tr:first').toggleClass('ignoreline');
		// $('body').append('khksdjfhksdf');
	});
	
	$('.deletenewstatement').each(function(i0) {
		var i = i0 + 1;
		$(this).click(function(){
			$.post('ajax.php', {
				ajax: 'deletenewstatement',
				filename: $('#name'+i).val()
			}, function(output) {
				$('body').append(output);
			});
		});
	});
	
	$('.importstatement').each(function(i0) {
		var i = i0 + 1;
		$(this).click(function(){
			$('#statementcontstatus'+i).html('<img src="img/loading2.gif" />');
			$(this).attr('disabled','disabled');
			$.post('statementimport.php', {
				ajax: 'importstatement',
				statementno: i,
				account: $('#selectaccount'+i).val(),
				ignorefirst: $('#ignorefirstline'+i).val(),
				filename: $('#name'+i).val()
			}, function(output) {
				$('#statementcontstatus'+i).html(output);
				$('#importstatement'+i).removeAttr('disabled');
				if ($('#status'+i).val() == 'e1') {
					$('#statementcontstatus'+i).html('<span class="error">ERROR: Please select account</span>');
				} else if ($('#status'+i).val() == 'e2') {
					$('#statementcontstatus'+i).html('<span class="error">ERROR: invalid account</span>');
				} else if ($('#status'+i).val() == 'e3') {
					$('#statementcontstatus'+i).html('<span class="error">ERROR: incorrect number of columns for bank type</span>');
				} else if ($('#status'+i).val() == 'e4') {
					$('#statementcontstatus'+i).html('<span class="error">ERROR: bank not supported</span>');
				} else if ($('#status'+i).val() == 's') {
					$('#statementcont'+i).html('Statement imported successfully. <a href="bank.php?s=statements">Click here to reload page</a>.');
				}
			});			
		});			
	});
	
	$('#statementtable tbody tr').hover(function() {
		var cl = $(this).attr('class');
		var id = $(this).attr('id');
		if (cl != 'selected') {
			$(this).addClass('hover');
		}
	}, function() {
		var cl = $(this).attr('class');
		var id = $(this).attr('id');
		if (cl != 'selected') {
			$(this).removeClass('hover');
		}
	});		
	
	$('#statementtable tbody tr').click(function() {
		$('.selected').removeClass('selected');
		$(this).removeClass('hover');
		$(this).addClass('selected');
		$('#statementviewer').html('Loading... <img src="img/loading2.gif" />');
		$.post('ajax.php', {
			ajax: 'loadstatement',
			id: $(this).find('.statementid').val()
		}, function(output) {
			$('#statementviewer').html(output);
			$('#deletestatement').click(function() {
				$(this).parent().append('&nbsp;&nbsp;&nbsp;<span class="error">Deleting statements is not currently supported.</span>');
			});
			initdropdown();
			initPreviewFunctions();
			
			$('.previewline').hover(function() {
				$(this).addClass('hover');
			}, function() {
				$(this).removeClass('hover');
			});	
						
		});
		// $('#selectedid').val($(this).attr('id'));
		// $('#detail').unwrap();
		// var url = [location.protocol, '//', location.host, location.pathname].join('');
		// $('#detail').wrap('<a href="'+url+'?s=detail&d='+$('#selectedid').val()+'"></a>');
	});
	
	$('#statementtable tbody tr').first().click();

	$('#statementtable tbody tr').dblclick(function() {
		$.colorbox({
			href: '#statementviewer',
			width:'800px',
			height:'90%',
			transition: 'none',
			fadeOut: 1,
			inline:true
		})
	});
	
	initPreviewFunctions();

	$('#reconbanksel').change(function(){
		document.location = 'bank.php?s=reconciliation&b='+$(this).val();
	});
	
	$('#statementsel').change(function(){
		document.location = 'bank.php?s=statements&b='+$(this).val();
	});	
	
	// ==================== XAC =========================

	$('.xacmenu').html($('#xaclookuplist').html());
	
	$('.xacmenu li').click(function() {
		$(this).parent().parent().parent().find('.reconxac').removeAttr('data-clicked');
		$(this).parent().parent().parent().find('.reconxac').attr('data-clicked', 'yes');
		$(this).parent().parent().parent().find('.reconxac').val($(this).html());
		if ($(this).data('contacttype') == 't') {
			// $('#main').prepend('this is a tenant');
			$(this).parent().parent().parent().parent().find('.contacttype').val('t');
			$(this).parent().parent().parent().parent().find('.contactid').val($(this).data('tenancyid'));
			$(this).parent().parent().parent().parent().find('.reconxacinfo').html('<a href="tenants.php?s=detail&d='+$(this).data('tenantid')+'" target="new">Tenant at '+$(this).data('address')+'</a>');
		} else if ($(this).data('contacttype') == 'f') {
			// $('#main').prepend('this is a fee');
			$(this).parent().parent().parent().parent().find('.contacttype').val('f');
			$(this).parent().parent().parent().parent().find('.contactid').val($(this).data('feeid'));
			$(this).parent().parent().parent().parent().find('.reconxacinfo').html('<a href="tenants.php?s=feesexp&d='+$(this).data('tenancyid')+'" target="new">Tenant fee</a>');			
		} else {
			// $('#main').append('this is a landlord');
			$(this).parent().parent().parent().parent().find('.contacttype').val('l');
			$(this).parent().parent().parent().parent().find('.contactid').val($(this).data('mcid'));
			$(this).parent().parent().parent().parent().find('.reconxacinfo').html('<a href="landlords.php?s=detail&d='+$(this).data('landlordid')+'" target="new">Landlord for '+$(this).data('address')+'</a>');
		}
		if ($(this).parent().parent().parent().parent().find('.recongen').val() == 'c') {
			var linetotal = parseFloat($(this).parent().parent().parent().parent().parent().find('.reconamount').html().replace(',', ''));
			var splittotal = 0;
			$(this).parent().parent().parent().parent().parent().find('.reconsplitamount').each(function() {
				splittotal += parseFloat($(this).val().replace(',', ''));
			});
			// $('#reconbankselcont').append('linetotal is '+linetotal+', splittotal is '+splittotal);
			if (splittotal.toFixed(2) == linetotal){
				$(this).parent().parent().parent().parent().find('.recon').removeClass('rsdisabled');	
			}
		} else {
			$(this).parent().parent().parent().parent().find('.recon').removeClass('rsdisabled');	
		}
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
		$(this).parent().parent().hide();
	});
	
	$('.xacmenu li').hover(function() {
		$(this).parent().find('li').removeClass('xacsel');
		$(this).addClass('xacsel');
	}, function() {});
	
	$('.reconxac').keyup(function(k) {
		if (k.which == 13) {
			$(this).parent().find('.xacmenu').hide();
			$(this).parent().find('li').hide();
			$(this).parent().find('li').removeClass('xacsel');
			//key specific stuff
		} else if (k.which == 40) {

		} else if (k.which == 38) {

		} else {
			$(this).parent().find('li').each(function() {
				var str = $(this).parent().parent().parent().find('.reconxac').val().toLowerCase();
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
	
	$('.reconxac').keydown(function(k) {
		$(this).removeAttr('data-clicked');
		$(this).attr('data-clicked', 'no');
		if (k.which == 9) {
			k.preventDefault();
			$(this).parent().find('.xacsel').click();
			$(this).parent().parent().find('.recon').focus();
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
	
	$(document).keyup(function(k) {
		if (k.which == 27) {
			$('.xacmenu').hide();
			$('.xacsel').removeClass('xacsel');
		}
	});

	// $(document).on('click', function(event) {
		// $('.reconline').first().prepend( "pageX: " + event.pageX + ", pageY: " + event.pageY + "<br>");
	// });
	
	var pageX, pageY; //Declare these globally
	$(window).mousemove(function(e){
		pageX = e.pageX;
		pageY = e.pageY;
	});
	
	$(document).on('click', function(e) {
		var focused = $(':focus');
		if (focused.hasClass('reconxac')) {
			e.preventDefault();
			var offsX = $(focused).offset().left;
			var offsY = $(focused).offset().top;
			//check not scrolling
			// $(focused).parent().parent().append("<br>" + pageX + " - " + offsX + " / " + pageY + " - " + offsY);
			if (pageX < offsX || pageX > (offsX + 250) || pageY < offsY || pageY > (offsY + 120)) {
				$(focused).parent().find('.xacsel').click();
				$(focused).parent().find('.xacmenu').hide();
				$(focused).parent().find('li').hide();
				$(focused).parent().find('li').removeClass('xacsel');
				if ($(focused).attr('data-clicked') == 'no') {
					$(focused).parent().parent().find('.reconxacinfo').html('<a href="tenants.php?s=add" target="new">New tenant</a> / <a href="landlords.php?s=add" target="new">landlord</a>');
					$(focused).parent().find('.recon').addClass('rsdisabled');
				}
				$(focused).focusout();
			} else {
				$(focused).focus();
			}	
		}
	});	
	
	// $('.reconxac').focusout(function() {
		// var offsX = $(this).offset().left;
		// var offsY = $(this).offset().top;
		// if (pageX < offsX || pageX > (offsX + 250) || pageY < offsY || pageY > (offsY + 120)) {
			// $(this).parent().find('.xacsel').click();
			// $(this).parent().find('.xacmenu').hide();
			// $(this).parent().find('li').hide();
			// $(this).parent().find('li').removeClass('xacsel');
			// if ($(this).attr('data-clicked') == 'no') {
				// $(this).parent().parent().find('.reconxacinfo').html('<a href="tenants.php?s=add" target="new">New tenant</a> / <a href="landlords.php?s=add" target="new">landlord</a>');
				// $(this).parent().find('.recon').addClass('rsdisabled');
			// }
		// } else {
			// $(this).focus();
		// }
	// });

	$('.reconxac').keydown();
	$('.reconxac').keyup();
	
	// ===================== SPLITS ==========================
	
	$('.reconsplitamount').keyup(function(k) {
		if (isNumeric($(this).val().replace(',', ''))) {	//check if numeric
			var linetotal = parseFloat($(this).parent().parent().find('.reconamount').html().replace(',', ''));
			// $(this).parent().append('<br>'+linetotal);
			var splittotal = 0;
			$(this).parent().parent().find('.reconsplitamount').each(function() {
				splittotal += parseFloat($(this).val().replace(',', ''));
			});
			/* if (splittotal < linetotal) {	
				$(this).parent().find('.reconsplit').removeClass('rsdisabled');
				$(this).parent().find('.recon').addClass('rsdisabled');
				if (k.which == 13) {
					$(this).parent().find('.reconsplit').click();
				}
			} else if (splittotal == linetotal) {
				$(this).parent().find('.reconsplit').addClass('rsdisabled');
				if ($(this).parent().find('.reconxac').attr('data-clicked') == 'yes') {
					$(this).parent().find('.recon').removeClass('rsdisabled');
				}
			} else {
				$(this).parent().find('.reconsplit').addClass('rsdisabled');
				$(this).parent().find('.recon').addClass('rsdisabled');
			} */ //this only works with positive numbers, more freedom needed...
			if (splittotal == linetotal) {
				$(this).parent().find('.reconsplit').addClass('rsdisabled');
				if ($(this).parent().find('.reconxac').attr('data-clicked') == 'yes') {
					$(this).parent().find('.recon').removeClass('rsdisabled');
				}
			} else {
				$(this).parent().find('.reconsplit').removeClass('rsdisabled');
				$(this).parent().find('.recon').addClass('rsdisabled');
				if (k.which == 13) {
					$(this).parent().find('.reconsplit').click();
				}			
			}
			
		} else {
			$(this).parent().find('.reconsplit').addClass('rsdisabled');
			$(this).parent().find('.recon').addClass('rsdisabled');
		} 
	});
	
	$('.reconsplitamount').keyup();
	
	$('.reconsplit').click(function() {
		if ($(this).hasClass('rsdisabled')) {
			// do nothing
		} else {
			var splittotal = 0;
			$(this).parent().parent().find('.reconsplitamount').each(function() {
				splittotal += parseFloat($(this).val().replace(',', ''));
			});
			// $(this).parent().parent().append('button triggered...<br>');
			var thiscont = $(this).parent().parent();
			$.post('ajax.php', {
				ajax: 'split',
				id: $(this).parent().find('.reconlineid').val(),
				gen: $(this).parent().find('.recongen').val(),
				splitamount: parseFloat($(this).parent().find('.reconsplitamount').val().replace(',', '')),
				ltotal: parseFloat($(this).parent().parent().find('.reconamount').html().replace(',', '')),
				stotal: splittotal
			}, function(output) {
				// $(this).parent().parent().append(output);
				thiscont.append(output);
			});
		}
	});
	
	$('.unsplit').click(function() {
		// $(this).parent().parent().append( $(this).parent().parent().first('.reconsubline').find('.reconlineid').val() );
		var thiscont = $(this).parent().parent();
		$.post('ajax.php', {
			ajax: 'unsplit',
			child: $(this).parent().parent().first('.reconsubline').find('.reconlineid').val()
		}, function(output) {
			thiscont.append(output);
		});
	});
	
	// ===================== RECONCILE ==========================
	
	

	$('.recon').click(function() {
		if ($(this).hasClass('rsdisabled')) {
			// do nothing
		} else {
			var thiscont = $(this).parent().parent();
			var thissub = $(this).parent();
			// $(this).parent().parent().hide();	//temp fix
			$(this).addClass('rsdisabled');
			thissub.find('.reconignore').addClass('rsdisabled');
			thiscont.find('.reconloading').html('<img src="img/loading2.gif" />');
			// $('.reconxac:visible').next().focus(); //ahhhhhhhhhhh
			var lines = thiscont.parent().find('.reconxac');
			var nextxac = lines.eq(lines.index(thissub.find('.reconxac'))+1);
			$(window).scrollTop(nextxac.offset().top + (window.innerheight / 2));
			nextxac.focus();			
			$.post('ajax.php', {
				ajax: 'reconcile',
				sline: $(this).parent().find('.reconlineid').val(),
				gen: $(this).parent().find('.recongen').val(),
				ctype: $(this).parent().find('.contacttype').val(),
				cid: $(this).parent().find('.contactid').val(),
				pdate: $(this).parent().find('.reconpaymentdate').val(),
				ldate: $(this).parent().parent().find('.recondate').html(),	//in case pdate not entered
				samount: $(this).parent().find('.reconsplitamount').val().replace(',', ''),
				lamount: $(this).parent().parent().find('.reconamount').html().replace(',', '')
			}, function(output){
				// thiscont.append(output); // will eventually grey out line
				thiscont.find('.reconloading').html('');
				thissub.html(output);
				// thissub.find('.reconciledchild').addClass('reconciledbg');
				// if gen = n, green out thiscont. else, search thiscont for recon buttons, if none then green out else do nothing
				if (thissub.find('.recongen').val() == 'n') {
					thiscont.addClass('reconciledbg');
				} else {
					var splitarr = new Array();
					thiscont.find('.recon').each(function() {
						splitarr.push($(this));
					});
					// thiscont.append(splitarr.length);
					if (splitarr.length == 0) {
						thiscont.addClass('reconciledbg');
					}
				}
				initReconUndo();
			});
		}			
	});
	
	$('.reconignore').click(function() {
		if ($(this).hasClass('rsdisabled')) {
			// do nothing
		} else {
			var thiscont = $(this).parent().parent();
			var thissub = $(this).parent();
			$(this).addClass('rsdisabled');
			thissub.find('.reconignore').addClass('rsdisabled');
			thiscont.find('.reconloading').html('<img src="img/loading2.gif" />');
			// $('.reconxac:visible').next().focus(); //ahhhhhhhhhhh
			var lines = thiscont.parent().find('.reconxac');
			var nextxac = lines.eq(lines.index(thissub.find('.reconxac'))+1);	
			$(window).scrollTop(nextxac.offset().top + (window.innerheight / 2));
			nextxac.focus();
			$.post('ajax.php', {
				ajax: 'reconignore',
				id: $(this).parent().find('.reconlineid').val(),
				gen: $(this).parent().find('.recongen').val(),
				samount: $(this).parent().find('.reconsplitamount').val()
			}, function(output){
				// thiscont.append(output); // will eventually grey out line
				thiscont.find('.reconloading').html('');
				thissub.html(output);	
				if (thissub.find('.recongen').val() == 'n') {
					thiscont.addClass('ignoredbg');
				} else {
					var splitarr = new Array();
					thiscont.find('.recon').each(function() {
						splitarr.push($(this));
					});
					// thiscont.append(splitarr.length);
					if (splitarr.length == 0) {
						thiscont.addClass('ignoredbg');
					}
				}
				initReconUndo();				
			});
		}
	});
	
	// $('.reconunignore').click(function() {
		// var thiscont = $(this).parent().parent();
		// $.post('ajax.php', {
			// ajax: 'reconunignore',
			// id: $(this).parent().parent().find('.reconlineid').val()
		// }, function(output){
			// thiscont.append(output); 
		// });
	// }); 						// this is at the top of the page now
	
	
	initReconUndo();
	
	Mousetrap.bind(['command+q', 'ctrl+q'], function(e) {
		var selectedel = $(':focus');
		
		if (selectedel.hasClass('reconxac')) {
			selectedel.parent().parent().find('.reconignore').click();
		}
		// $('input:focus').val('gay');
		// $('body').append('gay');
		return false;
	});
	
	// ===================== PAYMENTS ==========================
	
	$('#paymentctypesel').change(function(){
		document.location = 'bank.php?s=payments&c='+$(this).val();
	});

	$('#undopayment').click(function() {
		$.post('ajax.php', {
			ajax: 'undopayment',
			id: $('#input_id').val()
		}, function(output) {
			$('#main').prepend(output);
		});
	});
	
	// ===== Xero epoxrt
	
	$('#exportstatement').click(function() {
		var accountsarr = [];
		$('.statementexbankcheck').each(function() {
			if ($(this).attr('checked')) {
				accountsarr.push($(this).data('accountid'));
			}
		});
		$.post('statementexport.php', {
			ajax: 'exportstatement',
			accounts: accountsarr,
			start: $('#statementex_startdate').val(),
			end: $('#statementex_enddate').val(),
		}, function(output) {
			$('#output').html(output);
		});
	});
	
});