$(function() {

	$('.tenancycont h3').click(function() {
		$(this).parent().find('div').toggleClass('tenancyshow tenancyhide');
	});

	$('.enddate').change(function() {
		var t = $(this).parent().parent().parent().find('.mcid').val();
		$('#t0_propertyid').html( $('#t'+t+'_propertyid').html());
		$('#t0_rent').val( $('#t'+t+'_mgmt').val());
		$('#t0_lease').val( $('#t'+t+'_lease').val());
		$('#t0_obal').val( $('#t'+t+'_obal').val());
		$('#t0_obaldate').val( $('#t'+t+'_obaldate').val());
		$.post('ajax.php', {
			ajax: 'newstartdate',
			enddate: $(this).val()
		}, function(output) {
			$('#t0_startdate').val(output);
		});
		
		// $('body').append(t);
		// $('body').append('balls');
	});

	$('#addmc').click(function() {
		$.post('ajax.php',{
			ajax: 'addmc',
			landlordid: $('#input_id').val(),	//check landlord belongs to client in ajax
			propertyid: $('#t0_propertyid').val(),
			startdate: $('#t0_startdate').val(),
			enddate: $('#t0_enddate').val(),
			mgmt: $('#t0_mgmt').val(),
			lease: $('#t0_lease').val(),
			obal: $('#t0_obal').val(),
			obaldate: $('#t0_obaldate').val(),
		}, function(output) {
			$('#addmc').parent().append(output);
		});
	});

});