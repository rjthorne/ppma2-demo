function initResolvenote() {
	$('.resolvenote').click(function() {
		var thiscont = $(this).parent().parent().parent().parent();
		var thisli = $(this).parent();
		$.post('ajax.php', {
			ajax: 'resolvenote',
			noteid: thisli.data('id')
		}, function(output) {
			thisli.addClass('resolved');
			// thiscont.append(output);
		});
	});	
}

function initCanceleditnote() {
	$('.canceleditnote').click(function() {
		$(this).parent().prev('.hiddenli').removeClass('hiddenli');
		$(this).parent().remove();
	});
}

function initSubmiteditednote() {
	$('.submiteditednote').click(function() {
		var thiscont = $(this).parent().parent().parent().parent();
		var thisli = $(this).parent();
		var notecontent = thisli.find('.editarrearsnote').val();
		$.post('ajax.php', {
			ajax: 'submiteditednote',
			noteid: thisli.data('id'),
			updatedcontent: notecontent
		}, function(output) {
			thisli.prev('.hiddenli').find('.notecontent').text(output);
			thisli.find('.canceleditnote').click();
			// thiscont.append(output);
		});
	});		
	$('.editarrearsnote').keyup(function(k){
		if (k.which == 13) {
			$(this).parent().find('.submiteditednote').click();
		}
	});	
}

function initEditnote() {
	$('.editnote').click(function() {
		var thisli = $(this).parent();
		var noteid = thisli.data('id');
		var notecontent = thisli.find('.notecontent').text();
		thisli.addClass('hiddenli');
		thisli.after('<li class="editli" data-id="'+noteid+'"><input class="editarrearsnote" value="'+notecontent+'"> <span class="submiteditednote">Submit changes</span> | <span class="canceleditnote">Cancel</span></li>');
		initCanceleditnote();
		initSubmiteditednote();
	});
}

$(function() {

//	================================= XAC ==========================================

	$('.xacmenu').html($('#xaclookuplist').html());
	
	$('.xacmenu li').click(function() {
		$(this).parent().parent().parent().find('#tenantstatementxac').val($(this).html());
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
		$(this).parent().parent().hide();
		location = 'reports.php?s=tenant&d='+$(this).data('tenantid');
	});	

	$('.xacmenu li').hover(function() {
		$(this).parent().find('li').removeClass('xacsel');
		$(this).addClass('xacsel');
	}, function() {});	
	
	$('#tenantstatementxac').keyup(function(k) {
		if (k.which == 13) {
			$(this).parent().find('.xacsel').click();
			//key specific stuff
		} else if (k.which == 40) {

		} else if (k.which == 38) {

		} else {
			$(this).parent().find('li').each(function() {
				var str = $(this).parent().parent().parent().find('#tenantstatementxac').val().toLowerCase();
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
	
	$('#tenantstatementxac').keydown(function(k) {
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
	
	$('#tenantstatementxac').focusout(function() {
		$(this).parent().find('.xacsel').click();
		$(this).parent().find('.xacmenu').hide();
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
	});

	$('.tenantstatementxac').keydown();
	$('.tenantstatementxac').keyup();	

//	==========================================================================	
	
	// $('.genericdate').datepicker({		//now in shared.js
		// showOn: "button",
		// dateFormat: "d M yy",
		// buttonImage: "img/cal.png",
		// buttonImageOnly: true,
		// buttonText: "Select date"
	// });
	
	$('#reporttselect').change(function() {
		var d = $('#getd').val();
		var t = $(this).val();
		if (t == 0) {
			location = 'reports.php?s=tenant&d='+d;
		} else {
			location = 'reports.php?s=tenant&d='+d+'&t='+t;
		}
	});
	
	$('#landlordreportupdate').click(function() {
		var thiscont = $(this).parent().parent();
		$.post('ajax.php', {
			ajax: 'landlordreportupdate',
			d: $('#reportdselect').val(),
			start: $('#reportstartdate').val(),
			end: $('#reportenddate').val()
		}, function(output) {
			thiscont.append(output);
		});
		// var d = $('#reportdselect').val()
		// start: $('#reportstartdate-start').val(),
		// document.location = 'reports.php?s=landlord&d='+d+'&start='++'&end='+;
	});
	
	$('#reportdselect').change(function() {
		if ($('.subtab_active').attr('id') == 'landlord') {
			$('#landlordreportupdate').click();
		}
	});

	$('#landlordreportxero').click(function() {
		var thiscont = $(this).parent().parent();
		var lines = [];
		var line = [];
		var ltype;
		// $('#reportcont').prepend('gaaaaaay');
		// $('#reportcont').prepend($('#reportcont').find('tr').eq(3).find('td').eq(2).text());
		$('tr').each(function() {
			ltype = $(this).data('type');
			if (ltype == 'rent' || ltype == 'let' || ltype == 'man' || ltype == 'main' || ltype == 'other') {
				line = [
					$(this).find('td').eq(0).text(),
					$(this).find('td').eq(1).text(),
					$(this).find('td').eq(2).text().replace(',', ''),
					ltype,
					$(this).closest('table').find('.pid').val()
				]
				lines.push(line);
			}
		});
		$.post('ajax.php', {
			ajax: 'landlordreportxero',
			contact: $('#llname').val(),
			invref: $('#invref').val(),
			invdate: $('#reportenddate').val(),
			data: lines
		}, function(output) {
			thiscont.append(output);
			location = 'db/reports/'+$('#invref').val()+'.csv';
		});
	});
	
	$('#landlordreportprint').click(function() {
		var d = $('#reportdselect').val();
		var	start = $('#reportstartdate').val();
		var end = $('#reportenddate').val();
		window.open('temp-landlordreport.php?d='+d+'&start='+start+'&end='+end, '_blank');
	});
	
	$('#tenantreportupdate').click(function() {
		var thiscont = $(this).parent().parent();
		$.post('ajax.php', {
			ajax: 'tenantreportupdate',
			d: $('#getd').val(),
			t: $('#reporttselect').val(),
			start: $('#reportstartdate').val(),
			end: $('#reportenddate').val()
		}, function(output) {
			thiscont.append(output);
		});
	});	

	$('#tenantreportprint').click(function() {
		var d = $('#reportdselect').val();
		var	start = $('#reportstartdate').val();
		var end = $('#reportenddate').val();
		window.open('temp-tenantreport.php?d='+d+'&start='+start+'&end='+end, '_blank');
	});
	
	$('#roomtableupdate').click(function() {
		var thiscont = $(this).parent().parent();
		$.post('ajax.php', {
			ajax: 'roomtableupdate',
			type: $('#roomtablepropertytype').val(),
			mode: $('#roomtablemode').val(),
			date: $('#roomtabledate').val()
		}, function(output) {
			thiscont.append(output);
		});
	});		
	
	//	====== arrears ======
	
	$('.astatsel').change(function() {
		var v = $(this).val();
		if (v != 0) {
			var c = $(this).find('option[value='+v+']').data('color');
			// $(this).after(c);
			$(this).parent().parent().parent().css('background-color', '#'+c);
			$.post('ajax.php', {
				ajax: 'updatearrearsstatus',
				tenantid: $(this).parent().parent().parent().data('id'),
				statusid: $(this).val()
			});
		}
	});
	
	$('.astatsel').change();
	
	$('.addnote').click(function() {
		$(this).parent().find('.newarrearsnoteli').toggle();
		if ($(this).text() == 'Add note') {
			$(this).text('Cancel');
		} else {
			$(this).text('Add note');
			$(this).parent().find('.newarrearsnoteli input').val('');
		}
	});
	
	$('.submitarrearsnote').click(function() {
		var thiscont = $(this).parent().parent().parent().parent();
		$.post('ajax.php', {
			ajax: 'submitarrearsnote',
			tenantid: thiscont.data('id'),
			notecontent: $(this).parent().find('.newarrearsnote').val()
		}, function(output) {
			thiscont.find('ul').prepend(output);
			thiscont.find('.addnote').click();
			initResolvenote();
			initEditnote();
		});
	});
	
	$('.newarrearsnote').keyup(function(k){
		if (k.which == 13) {
			$(this).parent().find('.submitarrearsnote').click();
		}
	});
	
	initResolvenote();
	initEditnote();
	
	$('.showresolved').click(function() {
		$(this).parent().find('.resolved').toggle();
	});
	
	if ($('#arrears').hasClass('subtab_active')) {
		$('#totalarrears').append($('#runningtotal').val());
	}
	

});