(function() {
  
  var plugin_url = null, _ed, forms = null, notLoaded = true;
  
	tinymce.create('tinymce.plugins.WuPhooey', {
		init : function(ed, url) {
		  plugin_url = url;
		  _ed = ed;
		  
      var data = {
        action: 'get_forms_list_javascript'
      };
      
      jQuery.post(ajaxurl, data, function(data) {
        forms = data;
      }, 'json');
                        
		},
		
		createControl : function(n, cm) {
      switch (n) {
        case 'WuPhooey':
        var c = cm.createSplitButton('WuPhooey', {
          title : 'WuPhooey',
          image : plugin_url + '/button.gif',
          onclick : function() {
            _ed.execCommand('mceInsertContent', false, '[WuPhooey id=""]');
          }
        });
                
        c.onRenderMenu.add(function(c, m) {            
            m.add({title : 'Forms', 'class' : 'mceMenuItemTitle'}).setDisabled(1);

            for( id in forms ) {
              m.add({title: forms[id], onclick: function() {
                tinyMCE.execCommand('mceInsertContent', false, '[WuPhooey id="' + id + '"]');
              }});
            }                            
        });

            // Return the new splitbutton instance
            return c;
      }

          return null;
		},
		
		getInfo : function() {
			return {
				longname : "WuPhooey",
				author : "Baylor Rae'",
				authorurl : 'http://baylorrae.com/',
				infourl : 'http://baylorrae.com/',
				version : "1.0"
			};
		}
	});
	tinymce.PluginManager.add('WuPhooey', tinymce.plugins.WuPhooey);
})();