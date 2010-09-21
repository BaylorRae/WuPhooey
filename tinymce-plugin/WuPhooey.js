(function() {
  
  var _ed = null, plugin_url = null;
  
  function addButton(m, title, id) {
    m.add({title: title, onclick: function() {
      _ed.execCommand('mceInsertContent', false, '[WuPhooey id="'+id+'"]');
    }});
  }
            
  tinymce.create('tinymce.plugins.WuPhooey', {
    init : function(ed, url) {
      
      // ed.addButton('WuPhooey', {
      //    title : 'WuPhooey',
      //    image : url + '/button.gif',
      //    onclick : function() {
      //      ed.execCommand('mceInsertContent', false, '[WuPhooey id=""]');
      //    }
      //  });
      
      _ed = ed;
      plugin_url = url;
                   
      // var data = { action: 'get_forms_list_javascript' };
      // jQuery.post(ajaxurl, data);
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
            m.add({title : 'Forms (Loading ...)', 'class' : 'mceMenuItemTitle', id: 'WuPhooeyButtonTitle'}).setDisabled(1);
                        
            var data = { action: 'get_forms_list_javascript' };
            jQuery.post(ajaxurl, data, function(forms) {
              
              for( id in forms ) {
                addButton(m, forms[id], id);
              }
              
              jQuery('#WuPhooeyButtonTitle .mceText').attr('title', 'Forms').text('Forms');
            }, 'json');                           
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