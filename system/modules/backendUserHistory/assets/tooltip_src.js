Tips.Contao = new Class(
{
	Extends: Tips,

	options: {
		id: 'tip',
		onShow: function(){
			this.tip.setStyle('display', 'block');
		},
		onHide: function(){
			this.tip.setStyle('display', 'none');
		},
		title: 'title',
		text: '',
		showDelay: 1000,
		hideDelay: 100,
		className: 'backend-user-history',
		offset: {x:16, y:16},
		windowPadding: {x:0, y:0},
		fixed: true,
		waiAria: true
	},

	position: function(event) {
		if (!this.tip) document.id(this);

		var size = window.getSize(), scroll = window.getScroll(),
			tip = {x: this.tip.offsetWidth, y: this.tip.offsetHeight},
			props = {x: 'left', y: 'top'},
			bounds = {y: false, x2: false, y2: false, x: false},
			obj = {};

		for (var z in props){
			obj[props[z]] = event.page[z] + this.options.offset[z];
			if (obj[props[z]] < 0) bounds[z] = true;
			if ((obj[props[z]] + tip[z] - scroll[z]) > size[z] - this.options.windowPadding[z]){
				if (z == 'x') // Ignore vertical boundaries
					obj[props[z]] = event.page[z] - this.options.offset[z] - tip[z];
				bounds[z+'2'] = true;
			}
		}

		var top = this.tip.getElement('div.tip-top');

		// Adjust the arrow on left/right aligned tips
		if (bounds.x2) {
			obj['margin-left'] = '24px';
			top.setStyles({'left': 'auto', 'right': '9px'});
		} else {
			obj['margin-left'] = '-9px';
			top.setStyles({'left': '9px', 'right': 'auto'});
		}

		this.fireEvent('bound', bounds);
		this.tip.setStyles(obj);
	},

	hide: function(element) {
		if (!this.tip) document.id(this);
		this.fireEvent('hide', [this.tip, element]);
	}
});

window.addEvent('domready', function() {

$$('a.user-history[title]').each(function(el) {
			new Tips.Contao($$(el).filter(function(i) {
				return i.title != '';
			}), {
				offset: {x:0, y:26}
			});
		});
});