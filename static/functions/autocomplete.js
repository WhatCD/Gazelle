/*
Spent hours debugging opera, turns out they reserve the global variable autocomplete. Bitches.
*/
"use strict";
var autocomp = {
	id: "",
	value: "",
	href: null,
	timer: null,
	input: null,
	list: null,
	pos: -1,
	cache: [],
	start: function (id) {
		this.id = id;
		this.cache[id] = ["",[],[],[]];
		this.input = document.getElementById(id + "search");
		this.list = document.getElementById(id + "complete");
		listener.set(document.body,'click',function(){
			autocomp.value = autocomp.input.value;
			autocomp.end();
		});
	},
	end: function () {
		//this.input.value = this.value;
		this.href = null;
		this.highlight(-1);
		this.list.style.visibility = 'hidden';
		clearTimeout(this.timer);
	},
	keyup: function (e) {
		clearTimeout(this.timer);
		var key = (window.event)?window.event.keyCode:e.keyCode;
		switch (key) {
			case 27: //esc
				break;
			case 8: //backspace
				this.href = null;
				this.list.style.visibility = 'hidden';
				this.timer = setTimeout("autocomp.get('" + escape(this.input.value) + "');",500);
				break;
			case 38: //up
			case 40: //down
				this.highlight(key);
				if(this.pos !== -1) {
					this.href = this.list.children[this.pos].href;
					this.input.value = this.list.children[this.pos].textContent || this.list.children[this.pos].value;
				}
				break;
			case 13:
				if(this.href != null) {
					window.location = this.href;
				}
				return 0;
			default:
				this.href = null;
				this.timer = setTimeout("autocomp.get('" + escape(this.input.value) + "');",300);
				return 1;
		}
		return 0;
	},
	keydown: function (e) {
		switch ((window.event)?window.event.keyCode:e.keyCode) {
			case 9: //tab
				this.value = this.input.value;
			case 27: //esc
				this.end();
				break;
			case 38:
				e.preventDefault();
				break;
			case 13: //enter
				return 0;
		}
		return 1;
	},
	highlight: function(change) {
		//No highlights on no list
		if (this.list.children.length === 0) {
			return;
		}

		//Show me the
		this.list.style.visibility = 'visible';

		//Remove the previous highlight
		if (this.pos !== -1) {
			this.list.children[this.pos].className = "";
		}

		//Change position
		if (change === 40) {
			++this.pos;
		} else if (change === 38) {
			--this.pos;
		} else {
			this.pos = change;
		}

		//Wrap arounds
		if (this.pos >= this.list.children.length) {
			this.pos = -1;
		} else if (this.pos < -1) {
			this.pos = this.list.children.length-1;
		}

		if (this.pos !== -1) {
			this.list.children[this.pos].className = "highlight";
		} else {
			this.href = null;
			this.input.value = this.value;
		}
	},
	get: function (value) {
		this.pos = -1;
		this.value = unescape(value);

		if (typeof this.cache[this.id+value] === 'object') {
			this.display(this.cache[this.id+value]);
			return;
		}

		ajax.get(this.id+'.php?action=autocomplete&name='+this.input.value,function(jstr){
			var data = json.decode(jstr);
			autocomp.cache[autocomp.id+data[0]] = data;
			autocomp.display(data);
		});
	},
	display: function (data) {
		var i,il,li;
		this.list.innerHTML = '';
		for (i=0,il=data[1].length;i<il;++i) {
			li = document.createElement('li');
			li.innerHTML = data[1][i];
			li.i = i;
			li.href = data[3][i];
			listener.set(li,'mouseover',function(){
				autocomp.highlight(this.i);
			});
			listener.set(li,'click',function(){
				window.location = this.href;
			});
			this.list.appendChild(li);
		}
		if (i > 0) {
			this.list.style.visibility = 'visible';
		} else {
			this.list.style.visibility = 'hidden';
		}
	}
};
