function Add(input) {
	if(input.checked == false) {
		Cancel();
	} else {
		if(document.getElementById("choices").raw().value == "") {
			document.getElementById("choices").raw().value += input.name;
		} else {
			document.getElementById("choices").raw().value += "|" + input.name;
		}
	}
}

function Cancel() {
	var e=document.getElementsByTagName("input");
	for(i=0;i<e.length;i++){
		if(e[i].type=="checkbox"){
			e[i].checked=false;
		}
	}
	document.getElementById("choices").raw().value = "";
}

function CollageSubscribe(collageid) {
	ajax.get("userhistory.php?action=collage_subscribe&collageid=" + collageid + "&auth=" + authkey, function() {
		var subscribeLink = $("#subscribelink" + collageid).raw();
		if(subscribeLink) {
			if(subscribeLink.firstChild.nodeValue.substr(1,1) == 'U') {
				subscribeLink.firstChild.nodeValue = "[Subscribe]";
			} else {
				subscribeLink.firstChild.nodeValue = "[Unsubscribe]";
			}
		}
	});
}

var collageShow = {
	pg:0,
	pages:false,
	wrap:false,
	init:function(collagePages){
		this.wrap = document.getElementById('coverart');
		this.pages = collagePages;
		this.max = this.pages.length-1;
	},
	selected:function(){
		return $('.linkbox .selected').raw();
	},
	createUL:function(data){
		var ul = document.createElement('ul');
		$(ul).add_class('collage_images');
		ul.id = 'collage_page'+this.pg;
		$(ul).html(data);
		this.wrap.appendChild(ul);
		return ul;
	},
	page:function(num,el){
		var ul = $('#collage_page'+num).raw(), s = this.selected(), covers, lists, i;
		this.pg = num;
		
		if(!ul){
			covers = this.pages[num];
			if(covers){
				ul = this.createUL(covers);
			}
		}
		
		lists = $('.collage_images').objects;
		i = lists.length;
		while(i--){ $(lists[i]).hide(); }
		
		$(ul).show();
		if(s){ $(s).remove_class('selected'); }
		if(el){ $(el.parentNode).add_class('selected'); }
		
				
		// Toggle the page number links
		first = Math.max(0, this.pg - 2);
		if (this.max - this.pg < 2) {
			first = Math.max(this.max - 4, 0);
		}
		last  = Math.min(first + 4, this.max);
		for (i = 0; i < first; i++) {
			$('#pagelink' + i).hide();
		}
		for (i = first; i <= last; i++) {
			$('#pagelink' + i).show();
		}
		for (i = last + 1; i <= this.max; i++) {
			$('#pagelink' + i).hide();
		}
		
		// Toggle the first, prev, next, and last links
		if (this.pg > 0) {
			$('#prevpage').remove_class('invisible');
		} else {
			$('#prevpage').add_class('invisible');
		}
		if (this.pg > 1) {
			$('#firstpage').remove_class('invisible');
		} else {
			$('#firstpage').add_class('invisible');
		}
		if (this.pg < this.max) {
			$('#nextpage').remove_class('invisible');
		} else {
			$('#nextpage').add_class('invisible');
		}
		if (this.pg < this.max - 1) {
			$('#lastpage').remove_class('invisible');
		} else {
			$('#lastpage').add_class('invisible');
		}
		
		// Toggle the bar
		if ((last == this.max) && (this.pg != this.max)) {
			$('#nextbar').show();
		} else {
			$('#nextbar').hide();
		}
	},
	nextPage:function(){
		this.pg =  this.pg < this.max ? this.pg+1 : this.pg;
		this.pager();
	},
	prevPage:function(){
		this.pg = this.pg > 0 ? this.pg-1 : this.pg;
		this.pager();
	},
	pager:function(){
		this.page(this.pg,$('#pagelink'+this.pg).raw().firstChild);
	}
};