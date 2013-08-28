function charCount() {
	var count = document.getElementById("message").value.length;
	document.getElementById("length").innerHTML = count + "/100";
	if (count > 100) {
		document.getElementById("length").innerHTML = "<strong class=\"important_text\">Exceeded max rippy length!</strong>";
	}
}

function changeRippyImage() {
	var id = $("#rippy_image").val();
	var image = "";
	switch (parseInt(id)) {
		case 0:
			image = "rippy.png";
			break;
		case 1:
			image = "ripella.png";
			break;
		case 2:
			image = "rippy_bday.png";
			break;
		case 3:
			image = "rippy_halloween_1.png";
			break;
		case 4:
			image = "loggy.png";
			break;
		case 5:
			image = "rippy_gold.png";
			break;
		case 6:
			image = "cassie.png";
			break;
		default:
			image = "rippy.png";
			break;
	}
	$("#rippy_preview").raw().src = "static/rippy/" + image;

}