$(function() {

	$('#assign').click(function() {
		$.post('ajax.php',{
			ajax: 'assign',
			clientid: $('#clientid').val()
		},function(output) {
			$('body').append(output);
		});
	});
	
});