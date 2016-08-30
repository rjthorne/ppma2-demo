$(function() {

	$('.pdflink').click(function() {
		var pdf = $(this).attr('id').substr(3);
		$('#receiptb').html('<h2>'+pdf+'</h2>');
		$('#receiptb').append('<p>Reconcile this receipt?</p>');
		$.post('ajax.php',{
			ajax: 'getmonths'
		}, function(output) {
			$('#receiptb').append(output);
			$('#recbutton').click(function() {
				$.post('ajax.php',{
					ajax: 'recreceipt',
					file: pdf,
					month: $('#monthselect').val()
				}, function(output2) {
					$('#receiptb').append(output2);
					$('#pdf'+pdf).parent().remove();
					$('#rec').select();
				});
			});
		});
	});

});