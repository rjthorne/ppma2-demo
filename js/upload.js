$(function() {

	var settings = {
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
	$('#mulitplefileuploader').uploadFile(settings);
//	$('#debug').html('what');

});