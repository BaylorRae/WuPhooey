(function() {
          
  tinymce.create('tinymce.plugins.WuPhooey', {
    init : function(ed, url) {
      
      ed.addButton('WuPhooey', {
         title : 'WuPhooey',
         image : url + '/button.gif',
         onclick : function() {
           ed.execCommand('mceInsertContent', false, '[WuPhooey id=""]');
         }
       });
                   
      // var data = { action: 'get_forms_list_javascript' };
      // jQuery.post(ajaxurl, data);
    },
    createControl : function(n, cm) {
            
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