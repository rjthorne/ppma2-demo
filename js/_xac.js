(function($) {
	$.fn.xac = function(options) {
		var thiis = this;
		var id = thiis.attr('id');
		var settings = $.extend({
			table: 'null',
			col: 'null'
		}, options);
		$.post('ajax.php', {
			ajax: 'xac',
			elementid: id,
			dbtable: settings.table,
			dbcol: settings.col
		}, function(output) {
			return thiis.each(function() {
				$(this).wrap('<div class="xaccont" id="'+id+'_xaccont"></div>');
				$('#'+id+'_xaccont').append(output);
				$('#'+id+'_xaccont, #'+id+'_xacmenu').width(thiis.width());
				thiis.width(thiis.width() - thiis.height() - 3);
				$('#'+id+'_xacarrow').height(thiis.height());
				$('#'+id+'_xacarrow').width(thiis.height());
				$('#'+id+'_xacarrow, #'+id+'_xacmenu').css('border', thiis.css('border'));
				$('#'+id+'_xacarrow').click(function() {
					thiis.keyup();
					$('#'+id+'_xacmenu').show();
					$('#'+id+'_xacmenu li').show();
				});
				$('#'+id+'_xacUL li').each(function() {
					$(this).click(function() {
						thiis.val(decodeURI($(this).html().replace(/&amp;/g, '&')));
						// refresh list
						thiis.focus();
						thiis.blur();
					});
					$(this).hover(function() {
						$('#'+id+'_xacsel').removeAttr('id');
						$(this).attr('id', id+'_xacsel');
					});
				});
				$(this).keyup(function(k) {
					if (k.which == 13) {
						if ($('#'+id+'_xacUL li:visible').length > 0) {
							thiis.val(decodeURI($('#'+id+'_xacsel').html().replace(/&amp;/g, '&')));
							// refresh list
						}
						thiis.blur();
					} else if (k.which == 40) {
						if ($('#'+id+'_xacsel').nextAll(':visible:first').length > 0) {
							// $(this).before('gayyyyyyy');
							var xacsel = $('#'+id+'_xacsel');
							$('#'+id+'_xacsel').removeAttr('id');
							xacsel.nextAll(':visible:first').attr('id', id+'_xacsel');
						}
					} else if (k.which == 38) {
						if ($('#'+id+'_xacsel').prevAll(':visible:first').length > 0) {
							var xacsel = $('#'+id+'_xacsel');
							$('#'+id+'_xacsel').removeAttr('id');
							xacsel.prevAll(':visible:first').attr('id', id+'_xacsel');
						}
					} else {
						var str = $(this).val().toLowerCase();
						$('#'+id+'_xacUL li').each(function() {
							$(this).removeAttr('id');
							if ($(this).is(':visible')) {
								if ($(this).html().toLowerCase().indexOf(str) < 0 || str == '') {
									$(this).hide();
								}
							} else {
								if ($(this).html().toLowerCase().indexOf(str) >= 0 && str != '') {
									$(this).show();
									$('#'+id+'_xacmenu').show();
								}
							}
						});
						if ($('#'+id+'_xacUL li:visible').length > 0) {
							$('#'+id+'_xacUL li:visible').first().attr('id', id+'_xacsel');
						} else {
							$('#'+id+'_xacmenu').hide();
						}
					}
				});
				$(this).keydown(function(k) {
					if (k.which == 9) {
						// $('#zoomd').html('twat');
						if ($('#'+id+'_xacUL li:visible').length > 0) {
							thiis.val(decodeURI($('#'+id+'_xacsel').html().replace(/&amp;/g, '&')));
							// $('#zoomd').html('cunt');
							// $('#'+id+'_xacUL').html('dick');
							// refresh list

						}
					}
				});
				$(this).blur(function() {
					setTimeout(function() {
						if (!thiis.is(':focus')) {
							$('#'+id+'_xacmenu').hide();
						}
					}, 100);
					setTimeout( function() {
						$.post('ajax.php', {
							ajax: 'xac',
							refresh: true,
							elementid: id,
							dbtable: settings.table,
							dbcol: settings.col
						}, function(output) {
							$('#'+id+'_xacUL').html(output);
							// $('#zoomd').html('dick');
						});
					}, 3000);
				});
			});
		});
	};
}(jQuery));