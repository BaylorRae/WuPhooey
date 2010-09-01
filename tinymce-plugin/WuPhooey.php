<?php  
  include '../../../../wp-blog-header.php';
    
  if( !$wrapper = wufoo_login($echo = false) )
    die;
  
  if( !$forms = wufoo_cache_get('forms') ) {
    $forms = $wrapper->getForms();
    foreach( $forms as $id => $form ) {
      $forms[$id]->EntryCount = $wrapper->getEntryCount($id);
    }
    wufoo_cache_set('forms', $forms);
  }
  
  header('Content-type: application/javascript');
 ?>
(function() {
  
  var plugin_url = null, _ed;
  
	tinymce.create('tinymce.plugins.WuPhooey', {
		init : function(ed, url) {
		  plugin_url = url;
		  _ed = ed;
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
          
          <?php
            foreach( $forms as $id => $form ) {
          ?>
          m.add({title : '<?php echo $form->Name ?>', onclick : function() {
            _ed.execCommand('mceInsertContent', false, '[WuPhooey id="<?php echo $id ?>"]');
          }});
          <?php
            }
          ?>
          
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