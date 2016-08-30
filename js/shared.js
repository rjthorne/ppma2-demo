var decodeEntities = (function() {
	var element = document.createElement('div');
	function decodeHTMLEntities (str) {
		if (str && typeof str === 'string') {
			str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
			str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
			element.innerHTML = str;
			str = element.textContent;
			element.textContent = '';
		}
		return str;
	}
	return decodeHTMLEntities;
})();

function getFile(){
	document.getElementById("upfile").click();
}

function sub(obj){
	var file = obj.value;
	var fileName = file.split("\\");
	document.getElementById("yourBtn").innerHTML = fileName[fileName.length-1];
	document.myForm.submit();
	event.preventDefault();
}

// ========================== ROW NAVIGATION FUNCTIONS ==================================

function nextRow() {
	var success;
	$('#selected').nextAll().each(function() {	
		if (!success) {
			if ($(this).is(':visible')) {
				$(this).click(); 
				$('#'+$('#lastfocus').val()).focus();
				$('#'+$('#lastfocus').val()).select();
				$('#zoom td').css('background-color', 'transparent');
				success = 1;
				
			}
		}
	});
}

function prevRow() {
	var success;
	$('#selected').prevAll().each(function() {	
		if (!success) {
			if ($(this).is(':visible')) {
				$(this).click(); 
				$('#'+$('#lastfocus').val()).focus();
				$('#'+$('#lastfocus').val()).select();
				$('#zoom td').css('background-color', 'transparent');
				success = 1;
			}
		}
	});
}

function xRow(rf) {
	if (window['isfocused'] == true) {
		var f = $('input:focus');
		$('#zoomd').html(f.parent().prop('class'));
		if (f.parent().css('background-color') != 'rgb(187, 255, 187)' && f.parent().css('background-color') != 'rgba(0, 0, 0, 0)') {
			f.focusout();
			var wait = window.setInterval(function() {
				if (f.parent().css('background-color') == 'rgb(187, 255, 187)') {
					window.clearInterval(wait);
					rf();
				}
			}, 1000);
		} else {
			rf();
		}	
	} else {
		rf();
	}
}

$(function() {

// ========================== HEADER ================================== 

	$('.checkspan input').change(function() {
		if ($(this).is(':checked')) {
			var checked = 'y';
		} else {
			var checked = 'n';
		}
		$.post('ajax.php',{
			ajax: $(this).attr('id'),
			show: checked
		},function(output){
			$('body').append(output);
		});
	});

	$('.subtab').each(function() {
		var url = [location.protocol, '//', location.host, location.pathname].join('');
		var id = $(this).attr('id');
		$(this).wrap('<a href="'+url+'?s='+id+'"></a>');
	});	
	
	$('#changeclientID').change(function() {
		$.post('ajax.php',{
			ajax: 'changeclientID',
			cid: $('#changeclientID').val()
		},function() {
			document.location.reload(true);
		});
	});
	
// ========================== TABLE ================================== 

	// $('#datatable th').click(function() {		// might reintroduce this eventually
		// var th = $(this).attr('class');
		// if (th == 'x_th') {
			// return;
		// } else if (th == 'tbl_filename_th') {
			// var colsort = 'id';
		// } else {
			// var colsort = th.substr(4,(th.length - 7));
		// }
		// if (!colsort) {
			// return;
		// } else {
			// $.post('ajax.php',{
				// ajax: 'sortcols',
				// page: $('#thispage').val(),
				// col: colsort
			// },function(output){
				// $('body').append(output);
			// });
		// }
	// });
	

	$('#datatable tbody tr').hover(function() {
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
	
	$('#datatable tbody tr').click(function() {
		$('.selected').removeClass('selected');
		$(this).removeClass('hover');
		$(this).addClass('selected');
		$('#selectedid').val($(this).attr('id'));
		$('#detail').unwrap();
		// $('#feesexp').unwrap();
		var url = [location.protocol, '//', location.host, location.pathname].join('');
		$('#detail').wrap('<a href="'+url+'?s=detail&d='+$('#selectedid').val()+'"></a>');
		// $('#feesexp').wrap('<a href="'+url+'?s=feesexp&d='+$('#selectedid').val()+'"></a>');
	});
	
	$('#datatable tbody tr').first().click();

	$('#datatable tbody tr').dblclick(function() {
		$('#detail').click();
	});
	
// ========================== ZOOM ================================== 	
	
	// $('#prevrow').click(function() {
		// prevRow();
	// });
	
	// $('#nextrow').click(function() {
		// nextRow();
	// });
	
	// window['isfocused'] = false;
	
	// $('input').focus(function() {
		// window['isfocused'] = true;
		// $('#isfocued').val = 'y';
	// });
	
	// $('input').focusout(function() {
		// window['isfocused'] = false;
		// $('#isfocued').val = 'n';
	// });

	// $('#zoom input').keyup(function(e) {
		// if (e.which == 38) {
			// return;
		// } else if (e.which == 40) {
			// return;
		// } else if (e.which == 13) {
			// $(this).focusout();
		// } else {
			// var colname = $(this).attr('id').substr(6);
			// if ($(this).val() != $('#selected').find('.tbl_'+colname).html()) {
				// $(this).parents('p, td').css('background-color', '#fbb');
			// } else {
				// $(this).parents('p, td').css('background-color', 'transparent');
			// }
		// }
	// });
	
	// $('.zoom_inputs').focusout(function() {
		// $('#lastfocus').val($(this).attr('id'));
		// var colname = $(this).attr('id').substr(6);
		// if ($(this).val() != $('#selected').find('.tbl_'+colname).html()) {
			// $(this).parents('p').css('background-color', '#fb0');
			// var r = Math.floor(Math.random()*1000000);
			// var f = 'f'+r;
			// var s = 's'+r;
			// window[f] = window.setInterval(function() {
				// $.post('ajax.php', {
					// ajax: 'update',
					// prefix: $('#ajaxprefix').val(),
					// id: $('#zoomID').val(),
					// field: colname,
					// value: encodeURIComponent($('#input_'+colname).val())
				// }, function(output) {
					// window.clearInterval(window[f]);
					// if (!window[s]) {
						// window[s] = true;
						// $('#input_'+colname).val(output);
						// $('#selected').find('.tbl_'+colname).html(output);	
						// $('#input_'+colname).parents('p, td').css('background-color', '#bfb');
					// }
				// });
			// }, 1000);
		// } 
	// });
	
	// $('.zoom_inputs_d').each(function() {
		// $(this).focusout(function() {
			// $('#lastfocus').val($(this).attr('id'));
		// });
		// var colname = $(this).attr('id').substr(6);
		// $(this).datepicker({
			// dateFormat: "dd M yy",
			// showAnim: "",
			// showButtonPanel: true,
			// onClose: function(d) {
				// $('#lastfocus').val($(this).attr('id'));
				// if ($(this).val() != $('#selected').find('.tbl_'+colname).html()) {
					// $(this).parents('p, td').css('background-color', '#fb0');
					// var r = Math.floor(Math.random()*1000000);
					// var f = 'f'+r;
					// var s = 's'+r;
					// window[f] = window.setInterval(function() {
						// $.post('ajax.php', {
							// ajax: 'update',
							// prefix: $('#ajaxprefix').val(),
							// id: $('#zoomID').val(),
							// field: colname,
							// value: d
						// }, function(output) {
							// window.clearInterval(window[f]);
							// if (!window[s]) {
								// window[s] = true;
								// $('#input_'+colname).val(output); // check ajax outputs
								// $('#selected').find('.tbl_'+colname).html(output);					
								// $('#input_'+colname).blur();
								// $('#input_'+colname).parents('p, td').css('background-color', '#bfb');
							// }
						// });
					// }, 1000);				
				// }
			// }
		// });
	// });	

	// $('.zoom_inputs_s').change(function() {
		// $('#lastfocus').val($(this).attr('id'));
		// var colname = $(this).attr('id').substr(6);
		// if ($(this).val() != $('#selected').find('.tbl_'+colname).html()) {
			// $(this).parents('p').css('background-color', '#fb0');
			// var r = Math.floor(Math.random()*1000000);
			// var f = 'f'+r;
			// var s = 's'+r;
			// window[f] = window.setInterval(function() {
				// $.post('ajax.php', {
					// ajax: 'update',
					// prefix: $('#ajaxprefix').val(),
					// id: $('#zoomID').val(),
					// field: colname,
					// value: $('#input_'+colname).val()
				// }, function(output) {
					// window.clearInterval(window[f]);
					// if (!window[s]) {
						// window[s] = true;
						// $('#selected').find('.tbl_'+colname).html(output);					
						// $('#input_'+colname).blur();
						// $('#input_'+colname).parents('p').css('background-color', '#bfb');
					// }
				// });
			// }, 500);
		// } 
	// });

// ========================== DELETES (not used by docbrowser) ================================== 
	
	// $('.x').click(function() {
		// $.colorbox.close();
		// $('#deleteconfirmation').dialog('open');
	// });	
	
	// if ($('#ajaxprefix').val() == 'prop') {
		// var item = 'property';
	// } else if ($('#ajaxprefix').val() == 'util') {
		// var item = 'utility';
	// } else if ($('#ajaxprefix').val() == 'ten') {
		// var item = 'tenant';
	// } else {
		// var item = 'undefined';
	// }
	
	// $('#deleteconfirmation').dialog({
		// autoOpen: false,
		// resizable: false,
		// width: '600px',
		// open: function() {
			// $('#tdate').focus();
		// },
		// buttons: {
			// "Terminate X" : function() {
				// var r = Math.floor(Math.random()*1000000);
				// var f = 'd'+r;
				// window[f] = window.setInterval(function() {				
					// $.post('ajax.php', {
						// ajax: $('#ajaxprefix').val()+'terminate',
						// uid: $('#selected').find('.xID').html(),
						// tdate: $('#tdate').val()
					// }, function(output) {
						// window.clearInterval(window[f]);
						// $('body').append(output);
					// });
				// }, 500);
			// },
			// "Delete X" : function() {
				// $('#deletedoubleconfirmation').dialog('open');
			// },
			// "Cancel" : function() {
				// $(this).dialog('close');
			// }
		// },
	// });	

	// $('.ui-button-text').each(function() {
		// if ($(this).html() == 'Terminate X') {
			// $(this).html('Terminate '+item);
		// } else if ($(this).html() == 'Delete X') {
			// $(this).html('Delete '+item);
		// }
	// });
	
	// $('#deletedoubleconfirmation').dialog({
		// autoOpen: false,
		// resizable: false,
		// buttons: {
			// "Delete" : function() {
				// var r = Math.floor(Math.random()*1000000);
				// var f = 'd'+r;
				// window[f] = window.setInterval(function() {				
					// $.post('ajax.php', {
						// ajax: $('#ajaxprefix').val()+'del',
						// uid: $('#selected').find('.xID').html()
					// }, function(output) {
						// window.clearInterval(window[f]);
						// $('body').append(output);
					// });
				// }, 500);				
			// },
			// "Cancel" : function() {
				// $(this).dialog('close');
			// }
		// }
	// });	

	// $('#tdatediv').datepicker({
		// altField: '#tdate',
		// altFormat: 'dd M yy'
	// });

// ========================== IMPORTS ===============================
	
	$('#importbtn').click(function() {
		$.post('ajax.php',{
			ajax: 'import',
			importtype: $('#thispage').val(),
			upload: $('#uploadid').val(),
			importopeningdate: $('#importopeningdate').val()
		},function(output) {
			$('body').append(output);
		});
		// $('body').append('ddddee');
	});
	

	
// ========================== MISC ================================== 

	$('.genericdate').datepicker({
		showOn: "button",
		dateFormat: "d M yy",
		buttonImage: "img/cal.png",
		buttonImageOnly: true,
		buttonText: "Select date"
	});
	
	$(this).keyup(function(e) {
		if (e.which == 38) {
			if (window['isfocused'] == true) {
				var f = $('input:focus');
				if (f.parent().prop('class') != 'xaccont') {
					e.preventDefault();
					xRow(prevRow);
				}
			} else {
				e.preventDefault();
				xRow(prevRow);
			}
		} else if (e.which == 40) {
			// $('#zoomd').html('down! ');
			if (window['isfocused'] == true) {
				// $('#zoomd').html('focused!! ');
				var f = $('input:focus');
				if (f.parent().prop('class') != 'xaccont') {
					e.preventDefault();
					xRow(nextRow);
				} else {
					// $('#zoomd').html('else... ');
				}
			} else {
				e.preventDefault();
				xRow(nextRow);
			}
		}
	});
	
	

});