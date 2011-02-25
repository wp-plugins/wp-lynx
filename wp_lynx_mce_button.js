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
			ed.onInit.add(function(ed) {
				ed.plugins.add_link_print._hideButtons();
			});
			ed.onBeforeExecCommand.add(function(ed, cmd, ui, val) {
				ed.plugins.add_link_print._hideButtons();
			});
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