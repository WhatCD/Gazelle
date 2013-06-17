var dragObjects = null;
var dragObjectPlaceholder = null;

function editOrdering() {
	$('#editlayout').ghide();
	$('#releasetypes').ghide();
	$('#linkbox').ghide();
	$('.sidebar').ghide();
	$('.main_column > .box').ghide(); // Artist info
	$('.main_column > #requests').ghide();

	$('#savelayout').gshow();
	$('#emptylinkbox').gshow();
	$('#torrents_allopenclose').gshow();

	dragObjects = new Array();

	var elems = $('#torrents_tables table').objects;
	for (i in elems) {
		var elemID = elems[i].id;
		if (elemID == undefined) {
			continue;
		}
		if (elemID.indexOf('torrents_') == 0) {
			$('#' + elemID).gshow();
			dragObjects[elemID] = new dragObject(elemID, elemID+'_handle', startDrag, moveDrag, endDrag);
			var classes = elems[i].className.split(' ');
			for (var j = 0; classes.length; j++) {
				if (classes[j].indexOf('releases_') == 0) {
					$('.'+classes[j].replace('_table', '')).ghide();
					$('.artist_editcol').gshow();
					$('.artist_normalcol').ghide();
					break;
				}
			}
		}
	}

	for (i in dragObjects) {
		dragObjects[i].StartListening();
	}
}

function saveOrdering() {
	$('#savelayout').ghide();
	$('#savinglayout').gshow();

	var elems = $('#torrents_tables table').objects;
	var releaseTypes = "{";
	for (i in elems) {
		var elemID = elems[i].id;
		var releaseType = null;
		if (elemID == undefined) {
			continue;
		}
		if (elemID.indexOf('torrents_') == 0) {
			var classes = elems[i].className.split(' ');
			for(var j=0; classes.length; j++) {
				if (classes[j] == null) {
					break;
				}
				if (classes[j].indexOf('releases_') == 0) {
					releaseType = classes[j].split('_')[1];
				}
			}
		}
		if (releaseType != null) {
			releaseTypes += '"' + releaseType + '":' + ($('#releases_' + releaseType + '_defaultopen').raw().checked ? 1 : 0) + ",";
		}
	}
	releaseTypes = releaseTypes.substring(0, releaseTypes.length-1) + '}';
	var postData = new Array();
	postData['layoutdefaults'] = releaseTypes;
	ajax.post("artist.php?action=ajax_edit_artistlayout", postData, saveOrderingCallback);
}

function saveOrderingCallback(response) {

	//Let's do what the user asked for, shall we.
	//Show/hide
	var releaseTypes = json.decode(response);
	for(releaseType in releaseTypes) {
		if (releaseTypes[releaseType] == 1) {
			setShow(releaseType, true);
		} else {
			setShow(releaseType, false);
		}
	}

	//Ordering in linkbox
	var prevOrderedLink = null;
	for (releaseType in releaseTypes) {
		var elem = $('#torrents_' + releaseType + '_anchorlink').raw();
		if (elem == undefined) {
			continue;
		}
		if (prevOrderedLink == null) {
			prevOrderedLink = elem;
		} else {
			prevOrderedLink.parentNode.insertBefore(elem, prevOrderedLink.nextSibling);
			prevOrderedLink = elem;
		}
	}

	//Now let's return to the non editing layout.
	var elems = $('#torrents_tables table').objects;
	for (i in elems) {
		var elemID = elems[i].id;
		if (elemID == undefined) {
			continue;
		}
		if (elemID.indexOf('torrents_') == 0) {
			var classes = elems[i].className.split(' ');
			var empty = false;
			for (var j = 0; classes.length; j++) {
				if (classes[j] == null) {
					break;
				}
				if (classes[j].indexOf('releases_') == 0) {
					$('.artist_editcol').ghide();
					$('.artist_normalcol').gshow();
				}
				if (classes[j].indexOf('empty') == 0) {
					empty = true;
				}
			}
			if (empty) {
				$('#' + elemID).ghide();
			}
		}
	}

	for (i in dragObjects) {
		dragObjects[i].StopListening();
	}
	dragObjects	= null;

	$('#savinglayout').ghide();
	$('#emptylinkbox').ghide();
	$('#torrents_allopenclose').ghide();

	$('#editlayout').gshow();
	$('#releasetypes').gshow();
	$('#linkbox').gshow();
	$('.sidebar').gshow();
	$('.main_column > .box').gshow(); // Artist info
	$('.main_column > #requests').gshow();
}

function setDefaultShow(id, show) {
	if (id == 'all') {
		var elems = $('#torrents_tables table').objects;
		for (i in elems) {
			var elemID = elems[i].id;
			var releaseType = null;
			if (elemID == undefined) {
				continue;
			}
			if (elemID.indexOf('torrents_') == 0) {
				var classes = elems[i].className.split(' ');
				for (var j = 0; classes.length; j++) {
					if (classes[j] == null) {
						break;
					}
					if (classes[j].indexOf('releases_') == 0) {
						releaseType = classes[j].split('_')[1];
					}
				}
			}
			setDefaultShow(releaseType, show);
		}
	} else if (show) {
		$('#releases_'+id+'_openlink').ghide();
		$('#releases_'+id+'_closedlink').gshow();
		$('#releases_'+id+'_defaultopen').raw().checked = 'checked';
	} else {
		$('#releases_'+id+'_openlink').gshow();
		$('#releases_'+id+'_closedlink').ghide();
		$('#releases_'+id+'_defaultopen').raw().checked = '';
	}
}

function setShow(id, show) {
	if (show) {
		$('#releases_'+id+'_viewlink').ghide();
		$('#releases_'+id+'_hidelink').gshow();
		$('.releases_'+id).gshow();
	} else {
		$('#releases_'+id+'_viewlink').gshow();
		$('#releases_'+id+'_hidelink').ghide();
		$('.releases_'+id).ghide();
	}
}

function startDrag(element) {
	element.style.top = element.offsetTop + 'px';
	element.style.left = element.offsetLeft + 'px';
	element.style.height = element.offsetHeight + 'px';
	element.style.width = element.offsetWidth + 'px';
	element.style.position = 'absolute';
	element.style.zIndex = '100';

	$('body').objects[0].style.cursor = 'move';

	dragObjectPlaceholder = document.createElement('TABLE');
	dragObjectPlaceholder.style.backgroundColor = '#DDDDDD';
	dragObjectPlaceholder.style.height = element.style.height;
	dragObjectPlaceholder.style.width = element.style.width;
	element.parentNode.insertBefore(dragObjectPlaceholder, element);
}

function moveDrag(element) {
	if (
	  (element.offsetTop > (dragObjectPlaceholder.offsetTop + parseInt(dragObjectPlaceholder.style.height))) ||
	  ((element.offsetTop + parseInt(dragObjectPlaceholder.style.height)) < dragObjectPlaceholder.offsetTop)
	) {
		var bestItem = 'END';
		elems = element.parentNode.childNodes;

		for (var i = 0; i < elems.length; i++) {
			elem = elems[i];
			if (elem == element || elem.nodeName != 'TABLE') {
				continue;
			}

			if ((element.offsetTop > dragObjectPlaceholder.offsetTop) && (elem.offsetTop - element.offsetTop) > parseInt(element.style.height)) {
				bestItem = elem;
				break;
			} else if ((element.offsetTop < dragObjectPlaceholder.offsetTop) && (elem.offsetTop + parseInt(element.style.height)) > element.offsetTop) {
				bestItem = elem;
				break;
			}
		}
		if (bestItem == dragObjectPlaceholder) {
			return;
		}

		if (bestItem != 'END') {
			element.parentNode.insertBefore(dragObjectPlaceholder, element.parentNode.childNodes[i]);
		} else {
			element.parentNode.appendChild(dragObjectPlaceholder);
		}
	}
}

function endDrag(element) {
	$('body').objects[0].style.cursor = '';
	element.style.top = '';
	element.style.left = '';
	element.style.zIndex = '';
	element.style.position = '';

	element.parentNode.replaceChild(element, dragObjectPlaceholder);
	dragObjectPlaceholder = null;
}

//Slightly modified from: http://www.switchonthecode.com/tutorials/javascript-draggable-elements
function addEvent(element, eventName, callback) {
	if (element.addEventListener) {
		element.addEventListener(eventName, callback, false);
	} else if (element.attachEvent) {
		element.attachEvent("on" + eventName, callback);
	}
}

function removeEvent(element, eventName, callback) {
	if (element.removeEventListener) {
		element.removeEventListener(eventName, callback, false);
	} else if (element.detachEvent) {
		element.detachEvent("on" + eventName, callback);
	}
}

function cancelEvent(e) {
	e = e ? e : window.event;
	if (e.stopPropagation) {
		e.stopPropagation();
	}
	if (e.preventDefault) {
		e.preventDefault();
	}
	e.cancelBubble = true;
	e.cancel = true;
	e.returnValue = false;
	return false;
}

function Position(x, y) {
	this.X = x;
	this.Y = y;

	this.Add = function(val) {
		var newPos = new Position(this.X, this.Y);
		if (val != null) {
			if (!isNaN(val.X)) {
				newPos.X += val.X;
			}
			if (!isNaN(val.Y)) {
				newPos.Y += val.Y;
			}
		}
		return newPos;
	}

	this.Subtract = function(val) {
		var newPos = new Position(this.X, this.Y);
		if (val != null) {
			if (!isNaN(val.X)) {
				newPos.X -= val.X;
			}
			if (!isNaN(val.Y)) {
				newPos.Y -= val.Y;
			}
		}
		return newPos;
	}

	this.Check = function() {
		var newPos = new Position(this.X, this.Y);
		if (isNaN(newPos.X)) {
			newPos.X = 0;
		}
		if (isNaN(newPos.Y)) {
			newPos.Y = 0;
		}
		return newPos;
	}

	this.Apply = function(element, horizontal, vertical) {
		if (!isNaN(this.X) && horizontal) {
			element.style.left = this.X + 'px';
		}
		if (!isNaN(this.Y) && vertical) {
			element.style.top = this.Y + 'px';
		}
	}
}

function absoluteCursorPostion(eventObj) {
	eventObj = eventObj ? eventObj : window.event;

	if (isNaN(window.scrollX)) {
		return new Position(eventObj.clientX + document.documentElement.scrollLeft + document.body.scrollLeft, eventObj.clientY + document.documentElement.scrollTop + document.body.scrollTop);
	} else {
		return new Position(eventObj.clientX + window.scrollX, eventObj.clientY + window.scrollY);
	}
}

function dragObject(element, handlerElement, startCallback, moveCallback, endCallback) {
	if (typeof(element) == "string") {
		element = $('#' + element).raw();
	}
	if (element == null) {
		return;
	}

	if (typeof(handlerElement) == "string") {
		handlerElement = $('#' + handlerElement).raw();
	}
	if (handlerElement == null) {
		handlerElement = element;
	}

	var cursorStartPos = null;
	var elementStartPos = null;
	var dragging = false;
	var listening = false;
	var disposed = false;

	function dragStart(eventObj) {
		if (dragging || !listening || disposed) {
			return;
		}
		dragging = true;

		cursorStartPos = absoluteCursorPostion(eventObj);
		elementStartPos = new Position(parseInt(element.offsetLeft), parseInt(element.offsetTop));
		elementStartPos = elementStartPos.Check();

		if (startCallback != null) {
			startCallback(element);
		}

		addEvent(document, "mousemove", dragGo);
		addEvent(document, "mouseup", dragStopHook);

		return cancelEvent(eventObj);
	}

	function dragGo(eventObj) {
		if (!dragging || disposed) {
			return;
		}

		var newPos = absoluteCursorPostion(eventObj);
		newPos = newPos.Add(elementStartPos).Subtract(cursorStartPos);
		newPos.Apply(element, false, true);
		if (moveCallback != null) {
			moveCallback(element);
		}

		return cancelEvent(eventObj);
	}

	function dragStop() {
		if (!dragging || disposed) {
			return;
		}
		removeEvent(document, "mousemove", dragGo);
		removeEvent(document, "mouseup", dragStopHook);
		cursorStartPos = null;
		elementStartPos = null;

		if (endCallback != null) {
			endCallback(element);
		}
		dragging = false;
	}

 	function dragStopHook(eventObj) {
		dragStop();
		return cancelEvent(eventObj);
	}

	this.Dispose = function() {
		if (disposed) {
			return;
		}
		this.StopListening(true);
		element = null;
		handlerElement = null
		startCallback = null;
		moveCallback = null
		endCallback = null;
		disposed = true;
	}

	this.StartListening = function() {
		if (listening || disposed) {
			return;
		}
		listening = true;
		addEvent(handlerElement, "mousedown", dragStart);
	}

	this.StopListening = function(stopCurrentDragging) {
		if (!listening || disposed) {
			return;
		}
		removeEvent(handlerElement, "mousedown", dragStart);
		listening = false;

		if (stopCurrentDragging && dragging) {
			dragStop();
		}
	}
}
