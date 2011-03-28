//skipfile
function SetDate() {
	var amount = $('#amount').raw().value;
	var denom = $('#denomination').raw().value;
	switch(denom) {
		case 'months' :
			amount *= 4.33333;
		case 'weeks' :
			amount *= 7;
		case 'days' :
			amount *= 24;
		case 'hours' :
			amount *= 60;
		case 'minutes' :
			amount *= 60;
			amount *= 1000; //millis
			break;
	}
	
	var d = new Date;
	d.setTime(d.getTime() + amount + (d.getTimezoneOffset() * 60 * 1000));
	
	//YYYY-MM-DD HH:MM:SS
	var out = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate() + " " + d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();

	$('#date').raw().value = out;
}

function AddOption() {
	var list = document.createElement("li");
		var effects = document.createElement("select");
		effects.name = "delay_effect[]";

			var enable = document.createElement("option");
			enable.value = "1";
			enable.innerHTML = "Enable";
			effects.appendChild(enable);

			var disable = document.createElement("option");
			disable.value = "0";
			disable.innerHTML = "Disable";
			effects.appendChild(disable);
		list.appendChild(effects);

		list.innerHTML += " ";

		var options = json.decode($('#delays_json').raw().value);
		var delays = document.createElement("select");
		delays.name = "delay[]";
		for(var option in options) {
			var delay = document.createElement("option");
			delay.value = option;
			delay.innerHTML = options[option][0].long;
			delays.appendChild(delay);
		}
		list.appendChild(delays);


	$('#delays_list').raw().appendChild(list);
}
