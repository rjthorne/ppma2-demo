$(function() {

	$('.filter').keyup(function() {
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
	});

});