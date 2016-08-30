function resizeDocViewer() {
	$('#docloader').css('width', '100%');
	$('#docloader img').css('width', '100%');
	ifh = Math.round($('#docloader').width() * 1.26);
	$('#docloader iframe').css('height', ifh);
}

function zoom() {
	var vpw = $(window).width();
	if (vpw > 830) {
		var w = 830;
	} else {
		var w = '100%';
	}
	$.colorbox({
		href: '#zoom',
		inline: true,
		width: w,
		height: '100%',
		onOpen: function() {
			$('body').css('position', 'fixed');
		},
		onComplete: function() {
			resizeDocViewer();
		},
		onCleanup: function() {
			$('body').css('position', 'static');
		}
	});
}

$(function() {

	$('#fullscreen').click(function() {
		zoom();
	});

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
	
	$('#uploadbtn').click(function() {
		$.colorbox({
			href: '#uploader',
			inline:true,
			width:'500px',
			height:'50%',
			onClosed: function() {
				document.location.reload(true);
			}
		});
	});
	
	$('#reconcile, #zoomrec').click(function() {
		$.post('ajax.php',{
			ajax: 'reconcile',
			filename: $('#selected').find('.tbl_filename').html()
		},function(output) {
			$('#selected').attr('class', 'reconciled');
			$('#selected').css('background-color', '#bfb');
			$('#zoomfilename').css('background-color', '#bfb');
			$('#debug').html(output);
		});
	});
	
	$('#datatable tbody tr').dblclick(function() {
		zoom();
	});
	
	$('#datatable tbody tr').click(function() {
		if ($(this).find('.tbl_filename').html() != $('#zoomfilename').html()) {
			$('#zoom td').css('background-color', 'transparent');
			$(this).find('td').each(function() {
				if ($(this).attr('class') == 'tbl_filename') {
					$('#zoomfilename').html($(this).html());
					if ($(this).parent().attr('class') == 'reconciled') {
						$('#zoomfilename').css('background-color', '#bfb');
					} else {
						$('#zoomfilename').css('background-color', 'transparent');
					}
				} else if ($(this).attr('class') == 'xID') {
					$('#zoomID').val($(this).html());
				} else if ($(this).attr('class') != 'x') {
					var colname = $(this).attr('class').substr(4);
					$('#input_'+colname).val(decodeEntities($(this).html()));
				}
			});
			var filename = $(this).find('.tbl_filename').html();
			var ext = filename.substr(filename.length - 3);
			if (ext == 'jpg' || ext == 'peg' || ext == 'png') {
				$('#docloader').html('<img src="db/docs/'+filename+'" />');
			} else if (ext == 'pdf') {
				$('#docloader').html('<iframe src="db/docs/'+filename+'" name="iframe" seamless="seamless"></iframe>');
			}
			var selclass = $('#selected').attr('class');
			if (selclass != 'reconciled' && selclass != 'deleted')
				$('#selected').css('background-color', 'transparent');
			$('#selected').removeAttr('id');
			var thisclass = $(this).attr('class');
			if (thisclass != 'reconciled' && thisclass != 'deleted')
				$(this).css('background-color', '#ffb');
			$(this).attr('id', 'selected');
			resizeDocViewer();
		}
	});
	
	$('#input_company').xac({
		table: 'docs',
		col: 'company'
	});	
	
	$('#input_doctype').xac({
		table: 'docs',
		col: 'doctype'
	});	
	
	$('#docdeleteconfirmation').dialog({
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
				var f = 'd'+r;
				window[f] = window.setInterval(function() {				
					$.post('ajax.php', {
						ajax: 'docdelete',
						filename: $('.deleted:visible').find('.tbl_filename').html()
					}, function() {
						$('.deleted').hide();
						window.clearInterval(window[f]);
					});
				}, 500);
			},
			"Cancel" : function() {
				$(this).dialog('close');
			}
		},
	});
	
	$('.x').click(function() {
		$.colorbox.close();
		$('#docdeleteconfirmation').dialog('open');
	});
	
	$(window).resize(function() {
		resizeDocViewer();
	});
	
	resizeDocViewer();
	
	$('#datatable tbody tr').first().click();	
	
});