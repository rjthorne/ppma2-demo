function resizeDocViewer() {
	var vpw = $(window).width();
	var hspace = vpw - 504 - 16;
	var vph = $(window).height();
	var vspace = vph - 8;
	
	var ddw = 473;
	var ifh = 640;
	
	if (hspace > ddw){
		if (vspace > ifh) {
			if (vspace / hspace < 1.354) {
				ifh = vspace;
				ddw = Math.round(vspace / 1.354) - 5; // HACK ATTACK
			} else {
				ddw = hspace;
				ifh = Math.round(hspace * 1.354);
			}
			$('#docdiv').css('width', ddw+'px');
			$('#docdiv img').prop('width', ddw);
			$('iframe').css('height', ifh+'px');
		}
	}
	
	$('#ddw').val(ddw);
	$('#ifh').val(ifh);
}

$(function() {

	$('tbody tr:visible').first().find('td').css('border-top', '0');
	
	$('tbody tr').each(function() {
	
		$(this).hover(function() {
			var cl = $(this).attr('class');
			var id = $(this).attr('id');
			if (id != 'selected' && cl != 'deleted' && cl != 'reconciled') {
				$(this).css('background-color', '#ffffe8');
			}
		}, function() {
			var cl = $(this).attr('class');
			var id = $(this).attr('id');
			if (id != 'selected' && cl != 'deleted' && cl != 'reconciled') {
				$(this).css('background-color', 'transparent');
			}
		});		
		
		$(this).click(function() {
			var filename = $(this).find('.Filename').html();
			var ext = filename.substr(filename.length - 3);
			if (ext == 'jpg' || ext == 'jpeg' || ext == 'png') {
				$('#docloader').html('<img src="db/docs/'+filename+'" width="'+$('#ddw').val()+'" />');
			} else if (ext == 'pdf') {
				$('#docloader').html('<iframe src="db/docs/'+filename+'" name="iframe" seamless="seamless" style="height:'+$('#ifh').val()+'px"></iframe>');
			}
			if ($('#selected').attr('class') != 'reconciled')
				$('#selected').css('background-color', 'transparent');
			$('#selected').removeAttr('id');
			if ($(this).attr('class') != 'reconciled')
				$(this).css('background-color', '#ffd');
			$(this).attr('id', 'selected');
			resizeDocViewer();
		//	$(window).scrollTop($(this).position().top) - console error :(
		});
		
	});
	
	$('.Amount').each(function() {
	
		var thishtml = $(this).html();

		$(this).click(function() {	
			if (!$(this).find('.Amount_i').is(':focus')) {
				if (thishtml) {
					$(this).html('<input class="Amount_i" value="'+thishtml+'" />');
				} else {
					$(this).html('<input class="Amount_i" value="" />');
					thishtml = '';
				}
				$(this).find('.Amount_i').select();
			}
			$(this).find('.Amount_i').focusout(function() {
				$('#lastfocus').val('Amount');
				var oldthishtml = thishtml;
				thishtml = $(this).val();
				if (thishtml != oldthishtml) {
					var r = Math.floor(Math.random()*1000000);
					$(this).attr('id', 'ajaxtemp'+r);
					var functionName = 'd'+r;
					var thisfilename = $(this).parent().parent().find('.Filename').html();
					window[functionName] = window.setInterval(function() {
						$.post('ajax.php', {
							ajax: 'docupdate',
							filename: thisfilename,
							field: 'amount',
							value: thishtml,
							fn : functionName
						}, function(output) {
							$('#ajaxtemp'+r).val(output);
							$('#ajaxtemp'+r).parent().html(output); 
							$('#ajaxtemp'+r).removeAttr('id');
							window.clearInterval(window[functionName]);
						});
					}, 500);
				} else {
					$(this).parent().html(thishtml);
				}
			});
		});		
		
	});

	$('.PaidDate').each(function() {
		
		var thishtml = $(this).html();
				
		$(this).click(function() {
			if (thishtml) {
				$(this).html('<input class="PaidDate_i" value="'+thishtml+'" />');
			} else {
				$(this).html('<input class="PaidDate_i" />');
				thishtml = '';
			}			
			$(this).find('.PaidDate_i').datepicker({
				dateFormat: "dd M yy",
				showAnim: "",
				showButtonPanel: true,
				onClose: function(d) {
					$('#lastfocus').val('PaidDate');
					var oldthishtml = thishtml;
					thishtml = $(this).val();
					if (thishtml != oldthishtml) {					
						var r = Math.floor(Math.random()*1000000);
						$(this).attr('id', 'ajaxtemp'+r);
						var functionName = 'd'+r;
						var thisfilename = $(this).parent().parent().find('.Filename').html();
						window[functionName] = window.setInterval(function() {
							$.post('ajax.php', {
								ajax: 'docupdate',
								filename: thisfilename,
								field: 'pdate',
								value: d
							}, function() {
								$('#ajaxtemp'+r).parent().html(d);
								$('#ajaxtemp'+r).removeAttr('id');
								thishtml = d;
							});
						}, 500);
					} else {
						$(this).parent().html(d);
					}
				}
			});
			$(this).find('.PaidDate_i').focus();		
		});

	});
	
	$('.InvoiceDate').each(function() {

		var thishtml = $(this).html();
				
		$(this).click(function() {
			if (thishtml) {
				$(this).html('<input class="InvoiceDate_i" value="'+thishtml+'" />');
			} else {
				$(this).html('<input class="InvoiceDate_i" />');
				thishtml = '';
			}			
			$(this).find('.InvoiceDate_i').datepicker({
				dateFormat: "dd M yy",
				showAnim: "",
				showButtonPanel: true,
				onClose: function(d) {
					$('#lastfocus').val('InvoiceDate');
					var oldthishtml = thishtml;
					thishtml = $(this).val();
					if (thishtml != oldthishtml) {
						var r = Math.floor(Math.random()*1000000);
						$(this).attr('id', 'ajaxtemp'+r);
						var functionName = 'd'+r;
						var thisfilename = $(this).parent().parent().find('.Filename').html();
						window[functionName] = window.setInterval(function() {
							$.post('ajax.php', {
								ajax: 'docupdate',
								filename: thisfilename,
								field: 'idate',
								value: d
							}, function() {
								$('#ajaxtemp'+r).parent().html(d);
								$('#ajaxtemp'+r).removeAttr('id');
								thishtml = d;
							});
						}, 500);
					} else {
						$(this).parent().html(d);
					}
				}
			});
			$(this).find('.InvoiceDate_i').focus();		
		});

	});
	
	$('.Notes').each(function() {
	
		var thishtml = $(this).html();
		
		$(this).click(function() {
			if (!$(this).find('.Notes_i').is(':focus')) {
				if (thishtml) {
					$(this).html('<input class="Notes_i" value="'+thishtml+'" />');
				} else {
					$(this).html('<input class="Notes_i" value="" />');
					thishtml = '';
				}
				$(this).find('.Notes_i').select();
			}
			$(this).find('.Notes_i').focusout(function() {
				$('#lastfocus').val('Notes');
				var oldthishtml = thishtml;
				thishtml = $(this).val();
				if (thishtml != oldthishtml) {
					var r = Math.floor(Math.random()*1000000);
					$(this).attr('id', 'ajaxtemp'+r);
					var functionName = 'd'+r;
					var thisfilename = $(this).parent().parent().find('.Filename').html();
					window[functionName] = window.setInterval(function() {
						$.post('ajax.php', {
							ajax: 'docupdate',
							filename: thisfilename,
							field: 'notes',
							value: thishtml
						}, function(output) {
							$('#ajaxtemp'+r).val(output);
							$('#ajaxtemp'+r).parent().html(thishtml);
							$('#ajaxtemp'+r).removeAttr('id');
							window.clearInterval(window[functionName]);
						});
					}, 500);
				} else {
					$(this).parent().html(thishtml);
				}
			});
		});
		
	});
	
	$('#deleteconfirmation').dialog({
		autoOpen: false,
		resizable: false,
		buttons: {
			"Delete" : function() {
				$(this).dialog('close');
				if ($('#selected').next().length > 0) {
					$('#selected').next().click();
					$('#selected').prev().attr('class', 'deleted');
				} else {
					$('#selected').prev().click();
					$('#selected').next().attr('class', 'deleted');
				}
				$('.deleted').css('background-color', '#fdd');
				var r = Math.floor(Math.random()*1000000);
				var functionName = 'd'+r;
				window[functionName] = window.setInterval(function() {				
					$.post('ajax.php', {
						ajax: 'docdelete',
						filename: $('.deleted:visible').find('.Filename').html()
					}, function() {
						$('.deleted').hide();
						window.clearInterval(window[functionName]);
						// document.location.reload(true);
					});
				}, 500);
			},
			"Cancel" : function() {
				$(this).dialog('close');
			}
		},
	});
	
	$('.X').click(function() {
		$('#deleteconfirmation').dialog('open');
	});

	$(this).keyup(function(e) {
		var focused = $('input:focus').attr('class');
		if (focused) {
			if (focused == 'filter') {
				return;
			} else {
				if (focused == 'Notes_i') {
					var nextcl = '.Notes';
				} else if (focused.indexOf('PaidDate_i') >= 0 ) {
					var nextcl = '.PaidDate';
				} else if (focused.indexOf('InvoiceDate_i') >= 0 ) {
					var nextcl = '.InvoiceDate';
				} else {
					var nextcl = '.Amount';
				}
			}
		} else {
			var nextcl = '.'+$('#lastfocus').val();
		}
		if (e.which == 38) { //up
			var success;
			$('#selected').prevAll().each(function() {	
				if (!success) {
					if ($(this).is(':visible')) {
						$(this).find(nextcl).click();
						success = 1;
					}
				}
			});
		} else if (e.which == 40) { //down
			var success;
			$('#selected').nextAll().each(function() {	
				if (!success) {
					if ($(this).is(':visible')) {
						$(this).find(nextcl).click(); 
						success = 1;
					}
				}
			});
		} else if (e.which == 13) { //enter
			if (focused) {
				$('input:focus').focusout();
			}
		} else {
			return;
		}
		e.preventDefault();
		
	});
	
	$('.filter').keyup(function(e) {
		if (e.which == 40) {
			var id = $('input:focus').attr('id');
			$('#selected').find('.'+id).click();
		} else {
			$('tbody tr:visible').first().find('td').css('border', '1px solid #bbb');
			var str = $(this).val().toLowerCase();
			var col = $(this).attr('id');
			$('.'+col).each(function() {
				if ($(this).is(':visible')) {
					if ($(this).html().toLowerCase().indexOf(str) < 0) {
						$(this).parent().attr('class',col+'filtered');
						$(this).parent().hide();
					}
				} else {
					if ($(this).html().toLowerCase().indexOf(str) >= 0) {
						if ($(this).parent().attr('class') == col+'filtered')
							$(this).parent().show();
					}
				}
			});
			$('tbody tr:visible').first().find('td').css('border-top', '0');
			$('tbody tr:visible').first().click();		
		}
	});	
	
	$('th').click(function() {
		var th = $(this).attr('id');
		if (th == 'Filename_th') {
			var docsort = 'id';
		} else if (th == 'Amount_th') {
			var docsort = 'amount';
		} else if (th == 'PaidDate_th') {
			var docsort = 'pdate';
		} else if (th == 'InvoiceDate_th') {
			var docsort = 'idate';
		} else if (th == 'Notes_th') {
			var docsort = 'notes';
		} 
		if (!docsort) {
			return;
		} else {
			$.post('ajax.php',{
				ajax: 'sortcols',
				col: docsort
			},function(output){
				$('body').append(output);
			});
		}
	});
	
	$(window).resize(function() {
		resizeDocViewer();
	});
	
	resizeDocViewer();

	$('.filter').attr('placeholder', 'Search...');
	
	$('tbody tr').first().click();
	
	var uploadSettings = {
		url: 'ajax.php',
		method: 'POST',
		allowedTypes:'jpg,jpeg,png,pdf',
		fileName: 'myfile',
		multiple: true,
		onSuccess:function(files,data,xhr) {
			$('#status').html('<font color="green">Upload successful</font>');
		},
		onError: function(files,status,errMsg) {		
			$('#status').html('<font color="red">Upload failed</font>');
		}
	}
	
	$('#mulitplefileuploader').uploadFile(uploadSettings);
	
	$('#uploadbtn').colorbox({
		inline:true,
		// width:'50%',
		height:'50%',
		onClosed: function() {
			document.location.reload(true);
		}
	});
	
	$('#fullscreen').click(function() {
		$('#docdiv2').html($('#docloader').html());
		$('#docdiv2').css('height', '100%');
		$('iframe').css('height', '100%');
		$('#docdiv2 img').removeProp('width');
		$('#docdiv2 img').css('width', '100%');
		$.colorbox({
			href: '#docdiv2',
			inline: true,
			width:'100%',
			height:'100%',
			title: $('#selected').find('.Filename').html(),
			onClosed: function() {
				$('iframe').css('height', $('#ifh').val()+'px');
			}
		});
	});
	
	$('#reconcile').click(function() {
		$.post('ajax.php',{
			ajax: 'reconcile',
			filename: $('#selected').find('.Filename').html()
		},function() {
			$('#selected').attr('class', 'reconciled');
			$('#selected').css('background-color', '#bfb');
		});
	});
	
});