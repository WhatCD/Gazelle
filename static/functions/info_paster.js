$(document).ready(function() {
	$("#paster").click(function() {
		var info = $('#ustats_joined').text() +'\n' + $('#ustats_last').text() + '\n' + $('#ustats_upload').text() + '\n' + $('#ustats_download').text() + '\n' + $('#ustats_ratio').text() + '\n' + $('#ustats_required').text() +'\n';
		info += $('#personal_clients').text() + '\n' + $('#comm_upload').text().replace(/\s/g,"").slice(0.-12) + '\n' + $('#comm_perfectflac').text().replace(/\s/g,"").slice(0,-4) + '\n' + $('#comm_seeding').text().replace(/\s/g,"").slice(0,-12) + '\n';
		info += $('#comm_leeching').text().replace(/\s/g,"").slice(0,-4) + '\n' + $('#comm_snatched').text().replace(/\s/g,"").slice(0,-12) + '\n' + $('#comm_downloaded').text().replace(/\s/g,"").slice(0,-4);
		$('#Reason').val($('#Reason').val()+info);
		$('#Reason').height($('#Reason')[0].scrollHeight);
	});
});