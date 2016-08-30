function zoom(div, w, h) {
	$.colorbox({
		scrolling: false,
		href: div,
		inline: true,
		width: w,
		height: h
	});
}

$(function() {



	$('#editutility').click(function() {
		zoom('#zoom','300px','400px');
	});
	
	$('#newutility').click(function() {
		zoom('#zoomnew','300px','400px');
	});

	$('#datatable tbody tr').click(function() {
		if ($(this).find('.xID').html() != $('#zoomID').val()) {
			$(this).find('td').each(function() {
				if ($(this).attr('class') == 'xID') {
					$('#zoomID').val($(this).html());
				} else if ($(this).attr('class') == 'tbl_property') {
					var propadd = $(this).html();
					$('#input_property option').each(function() {
						if ($(this).html() == propadd) {
							$(this).attr('selected', 'selected');
						}
					});
				} else if ($(this).attr('class') == 'tbl_term') {
					var term = $(this).html();
					$('#input_term option').each(function() {
						if ($(this).html() == term) {
							$(this).attr('selected', 'selected');
						}
					});
				} else if ($(this).attr('class') != 'x') {
					var colname = $(this).attr('class').substr(4);
					$('#input_'+colname).val($(this).html());
				}
			});
			var selclass = $('#selected').attr('class');
			if (selclass != 'terminated')
				$('#selected').css('background-color', 'transparent');
			$('#selected').removeAttr('id');
			var thisclass = $(this).attr('class');
			if (thisclass != 'terminated')
				$(this).css('background-color', '#ffb');
			$(this).attr('id', 'selected');
		}
	});	

	
	$('#input_type').xac({
		table: 'utilities',
		col: 'type'
	});

	$('#new_type').xac({
		table: 'utilities',
		col: 'type'
	});

	$('#input_company').xac({
		table: 'utilities',
		col: 'company'
	});

	$('#new_company').xac({
		table: 'utilities',
		col: 'company'
	});
	
	$('#new_sdate').datepicker({
		dateFormat: "dd M yy",
		showAnim: ""
	});

	$('#addutility').click(function(){ 
		var valid = true;
		if ($('#new_property').val() == 'blank') {
			$('#new_property').parents('p').css('background-color', '#fbb');
			valid = false;
		}
		if ($('#new_type').val() == '') {
			$('#new_type').parents('p').css('background-color', '#fbb');
			valid = false;
		}
		if (valid == true) {
			$('#zoomnew p').css('background-color', 'transparent');
			$.post('ajax.php',{
				ajax: 'utiladd',
				propid: $('#new_property').val(),
				type: $('#new_type').val(),
				company: $('#new_company').val(),
				ref: $('#new_ref').val(),
				amount: $('#new_amount').val(),
				term: $('#new_term').val(),
				sdate: $('#new_sdate').val(),
				notes: $('#new_notes').val()
			},function(output) {
				$('body').append(output);
			});
		}
	});
	
	$('#datatable tbody tr').first().click();	
	
});