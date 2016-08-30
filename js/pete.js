function initxac() {
	$('.xacmenu').html($('#xaclookuplist').html());

	$('.xacmenu li').click(function() {
		$(this).parent().parent().parent().find('.petexac').val($(this).html());
		$(this).parent().parent().parent().find('.xeroname').val($(this).data('xero'));
		$(this).parent().find('li').hide(); // unsure
		$(this).parent().find('li').removeClass('xacsel');
		$(this).parent().parent().hide();
	});

	$('.petexac').keyup(function(k) {
		if (k.which == 13) {
			$(this).parent().find('.xacsel').click();
			//key specific stuff
		} else if (k.which == 40) {

		} else if (k.which == 38) {

		} else {
			$(this).parent().find('li').each(function() {
				var str = $(this).parent().parent().parent().find('.petexac').val().toLowerCase();
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

	$('.petexac').keydown(function(k) {

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
	
	$('.petexac').focusout(function() {
		$(this).parent().find('.xacsel').click();
		$(this).parent().find('.xacmenu').hide();
		$(this).parent().find('li').hide();
		$(this).parent().find('li').removeClass('xacsel');
	});
	
}

function initdp() {
	$('.genericdate').datepicker({
		showOn: "button",
		dateFormat: "d M yy",
		buttonImage: "img/cal.png",
		buttonImageOnly: true,
		buttonText: "Select date"
	});		
}

function initgen() {
	$('#generate').click(function() {
		var lines = [];
		var line = [];
		$('.jobdesc').each(function() {
			line = [
				$(this).val(),
				$(this).parent().find('.xeroname').val()
			]
			lines.push(line);
		});

		$.post('pete-ajax.php', {
			ajax: 'petexero',
			amount: $('#amount').val(),
			// nolines: $('.jobdesc').length,
			invref: $('#invref').val(),
			invdate: $('#date').val(),
			officeid: $('#officeid').val(),
			data: lines
		}, function(output) {
			$('#main').append(output);
			location = 'db/reports/'+$('#invref').val()+'.csv';
		});
	});
}

$(function() {
	
	$('#clickme').click(function() {
		var wall = $('#textarea').val();
		$('#main').html('');
		var arr = wall.split("\n");
		var l = arr.length - 1;
		// print_r(arr, true);
		for (var i = 0; i < l; i++) {
			var i1 = i + 1;
			if (i1 == 1) {
				$('#main').append('<div class="jobline"> '+i1+'. <input class="jobdesc" id="first" value="'+arr[i].trim()+'" /> Select property: <span class="xaccont"><input class="petexac" /><div class="xacmenu"></div><input type="hidden" class="xeroname" value="null"/></span></div>');

				// $('#main').append('		<input class="petexac" />');
				// $('#main').append('		<div class="xacmenu">');
				// $('#main').append('		</div>');
				// $('#main').append('	</span>');
				// $('#main').append('<br />');
			} else {
				$('#main').append('<div class="jobline"> '+i1+'. <input class="jobdesc" value="'+arr[i].trim()+'" /> Select property: <span class="xaccont"><input class="petexac" /><div class="xacmenu"></div><input type="hidden" class="xeroname" value="null"/></span></div>');				
				// $('#main').append(i1+'. <input class="jobdesc" value="'+arr[i]+'" />');
				// $('#main').append(' Select property: ');
				// $('#main').append('<span class="xaccont"><input class="petexac" /><div class="xacmenu"></div><input type="hidden" class="xeroname" value="null"/></span>');
				// $('#main').append('<br />');
			}
		}
		$('#first').select();
		initxac();
		
		
		$('#main').append('<br /><br />');
		$('#main').append('<p>Enter overall labour amount: <input id="amount" /></p>');
		$('#main').append('<p>Enter invoice date: <span class="datecont"> <input class="genericdate" id="date" /></span></p>');
		$('#main').append('<p>Enter invoice number: <input id="invref" /></p>');
		$('#main').append('<p><input type="button" class="button" id="generate" value="Generate CSV" /></p>');
		
		initdp();
		initgen();
		
	});
	
	$('#office').change(function() {
		window.location = 'pete.php?office='+$(this).val();
	});
	
	initxac();
	
});