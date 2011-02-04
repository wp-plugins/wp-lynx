(function(){
	var DOM = tinymce.DOM;
	tinymce.create('tinymce.plugins.add_link_print', {
		init : function(ed, url) {
			ed.addButton('add_link_print', {
				title : 'Add Link Prink',
				image : url + '/llynx20x20.png',
				onclick : function() {
					tb_show('', tinymce.DOM.get('add_link_print').href);
					tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
				}
			});
			// Add Media buttons to fullscreen and handle align buttons for image captions
			ed.onBeforeExecCommand.add(function(ed, cmd, ui, val, o) {
				var DOM = tinymce.DOM, n, DL, DIV, cls, a, align;
				if ( 'mceFullScreen' == cmd ) {
					if ( 'mce_fullscreen' != ed.id && DOM.get('add_link_print'))
						ed.settings.theme_advanced_buttons1 += ',add_link_print';
				}
			});
			ed.onInit.add(function(ed) {
				ed.plugins.add_link_print._hideButtons();
			});
			ed.onBeforeExecCommand.add(function(ed, cmd, ui, val) {
				ed.plugins.add_link_print._hideButtons();
			});
		},
		_showButtons : function(n, id) {
			var ed = tinyMCE.activeEditor, p1, p2, vp, DOM = tinymce.DOM, X, Y;

			vp = ed.dom.getViewPort(ed.getWin());
			p1 = DOM.getPos(ed.getContentAreaContainer());
			p2 = ed.dom.getPos(n);

			X = Math.max(p2.x - vp.x, 0) + p1.x;
			Y = Math.max(p2.y - vp.y, 0) + p1.y;

			DOM.setStyles(id, {
				'top' : Y+5+'px',
				'left' : X+5+'px',
				'display' : 'block'
			});

			if ( this.mceTout )
				clearTimeout(this.mceTout);

			this.mceTout = setTimeout( function(){ed.plugins.add_link_print._hideButtons();}, 5000 );
		},

		_hideButtons : function() {
			/*if ( !this.mceTout )
				return;*/

			if ( document.getElementById('content_add_link_print') )
				tinymce.DOM.hide('content_add_link_print');

			clearTimeout(this.mceTout);
			this.mceTout = 0;
		},
	});
	tinymce.PluginManager.add('add_link_print', tinymce.plugins.add_link_print)
})();