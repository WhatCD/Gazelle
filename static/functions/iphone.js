// We had to sacrafice a bit of the beauty of this structure to accommodate OS 1.1
// If/when we drop OS <2 just switch all onclick events to ontouchend events.

var elements; // Shortcut to handle html elements
var header = 0; // Used in swap_header
var method; //The method we use, touchend or onclick
var active_index;
var active_url;

// Get ourselves the method based on OS
method = cookie.get('method');
if (method === null) {
	if (document.createTouch) {
		method = 'touchend';
	} else {
		method = 'click';
	}
	cookie.set('method',method,365);
}

// Active
active_index = 0;
active_url = cookie.get('lastpage');
if (active_url === null) {
	active_url = 'start.php';
}

// Data sent in HTML comments
var title = null;
var back_url = null;
var back_name = null;

function main () {
	// Basic html structure utilized for transitions
	elements = {
		buttons:[$('#first_button'),$('#second_button')],
		titles:[$('#first_title'),$('#second_title')],
		pages:[$('#first_page'),$('#second_page')]
	};

	// Transform on load
	elements.pages[1].style.webkitTransform = 'translateX(100%)';

	// Set event handlers
	elements.titles[0].addEventListener(method, swap_header, false);
	elements.titles[1].addEventListener(method, swap_header, false);

	elements.buttons[0].addEventListener(method, go_back, false);
	elements.buttons[1].addEventListener(method, go_back, false);

	elements.pages[0].addEventListener('webkitTransitionEnd', transition_ended, false);
	Transitions.DEFAULTS.duration = 0.35;

	// Load the content
	ajax.get(active_url, function (response) {
		get_headers(response);
		elements.titles[0].innerHTML = title;
		elements.pages[0].innerHTML = response;
		if (back_name) {
			elements.buttons[0].textContent = back_name;
		}
	});

	// Hide the address bar
	setTimeout(function() {
		window.scrollTo(0, 1);
		setTimeout(function() {
			window.scrollTo(0, 0);
		},0);
	}, 500);
};

// Tap header to swap for ratio
function swap_header() {
	//$('#search').style.display = 'block';
}

// Back button alias
function go_back() {
	load(back_url,false);
}

// Get data from comments
function get_headers(response) {
	title = response.match(/\<\!\-\-Title\:(.+?)\-\-\>/i)[1];
	if (response.match(/\<\!\-\-Back\:(.+?)\:(.+?)\-\-\>/i)) {
		back_name = response.match(/\<\!\-\-Back\:(.+?)\:(.+?)\-\-\>/i)[1];
		back_url = response.match(/\<\!\-\-Back\:(.+?)\:(.+?)\-\-\>/i)[2];
	} else {
		back_name = null;
		back_url = null;
	}
}

// Load content
function load(url,forward,formid) {
	if (forward === undefined) {
		forward = true;
	}
	if (transitions_in_progress && document.createTouch) { // OS 2
		return;
	}
	if (moved_after_touch) {
		return;
	}
	if (formid === undefined) {
		ajax.get(url, function (response) {
			get_headers(response);
			transition_to_new_element(response, forward);
		});
		cookie.set('lastpage',url,7);
	} else {
		ajax.post(url,formid);
	}
};

// Moves
var moved_after_touch = false;
function touch_started () {
	moved_after_touch = false;
};
function touch_moved () {
	moved_after_touch = true;
};

// Transitions
var transitions_in_progress = false;
function transition_ended () {
	transitions_in_progress = false;
};
function transition_to_new_element (data, going_forward) {
	transitions_in_progress = true;

	var from_index = active_index;
	var to_index = (active_index == 1) ? 0 : 1;

	//Make other page visible
	//elements.pages[to_index].style.height = '';

	Transitions.DEFAULTS.properties = ['opacity', '-webkit-transform'];
	var transitions = new Transitions();

	transitions.add({
		element: elements.titles[from_index],
		duration: [0.5],
		properties: ['opacity'],
		from: [1],
		to: [0]
	});

	transitions.add({
		element : elements.titles[to_index],
		duration: [0.5],
		properties: ['opacity'],
		from : [0],
		to : [1]
	});

	transitions.add({
		element: elements.buttons[from_index],
		duration: [0.5],
		properties: ['opacity'],
		from: [1],
		to: [0]
	});

	transitions.add({
		element : elements.buttons[to_index],
		duration: [0.5],
		properties: ['opacity'],
		from : [0],
		to : [1]
	});

	// we only change the transform for the page transitions
	Transitions.DEFAULTS.properties = ['-webkit-transform'];

	transitions.add({
		element : elements.pages[from_index],
		from : ['translateX(0%)'],
		to : ['translateX(' + ((going_forward) ? -150 : 150) + '%)']
	});

	transitions.add({
		element : elements.pages[to_index],
		from : ['translateX(' + ((going_forward) ? 150 : -150) + '%)'],
		to : ['translateX(0%)']
	});

	elements.pages[to_index].textContent = '';
	elements.pages[to_index].innerHTML = data;
	elements.titles[to_index].textContent = title;
	elements.buttons[to_index].textContent = back_name;

	//Hide other page to avoid excess scroll at the bottom
	//elements.pages[from_index].style.height = '0px';

	active_index = to_index;

	transitions.apply();
};

// Initate main function
window.addEventListener('load', main, false);
