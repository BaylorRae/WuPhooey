<?php
/*
Plugin Name: Wufoo phooey
Plugin URI: http://github.com/BaylorRae/Wufoo-phooey
Description: A Wufoo Form Manager for WordPress
Version: 1.0
Author: Baylor Rae'
Author URI: http://baylorrae.com
  
*/

/*
  TODO Add reports if they are needed
*/

include 'wufoo-api/WufooApiWrapper.php';
include 'WufooFields.php';
include 'jg_cache.php';

$wufoo_cache = new JG_Cache(dirname(__FILE__) . '/cache');


// Creates the navigation links
function wufoo_navigation() {

  add_menu_page('Wufoo Phooey', 'Wufoo Phooey', 'manage_options', 'wufoo-phooey', 'wufoo_settings');
  add_submenu_page('wufoo-phooey', 'Settings &lsaquo; Wufoo Phooey', 'Settings', 'manage_options', 'wufoo-phooey');
  add_submenu_page('wufoo-phooey', 'Forms &lsaquo; Wufoo Phooey', 'Forms', 'manage_options', 'wufoo-phooey-forms', 'wufoo_forms');
  // add_submenu_page('wufoo-phooey', 'Reports &lsaquo; Wufoo Phooey', 'Reports', 'manage_options', 'wufoo-phooey-reports', 'wufoo_reports');
  add_submenu_page('wufoo-phooey', 'Help &lsaquo; Wufoo Phooey', 'Help', 'manage_options', 'wufoo-phooey-help', 'wufoo_help');

}
add_action('admin_menu', 'wufoo_navigation');

// Looks for the Wufoo Phooey shortcode
function wufoo_filter_post($atts, $content = null) {
  if( $atts['id'] )
    return wufoo_build_form($atts['id'], $atts);
}
add_shortcode('wufoo_phooey', 'wufoo_filter_post');


// ================
// = Deactivation =
// ================

register_deactivation_hook(__FILE__, 'wufoo_deactivate');
function wufoo_deactivate() {
  
  // Delete the options
  if( get_option('wufoo_phooey-use-css') )
    delete_option('wufoo_phooey-use-css');
    
  if( get_option('wufoo_phooey-cache-entries') )
    delete_option('wufoo_phooey-cache-entries');
    
  if( get_option('wufoo_phooey-cache-forms') )
    delete_option('wufoo_phooey-cache-forms');
    
  if( get_option('wufoo_phooey-api_key') )
    delete_option('wufoo_phooey-api_key');
  
  if( get_option('wufoo_phooey-username') )
    delete_option('wufoo_phooey-username');
    
  if( get_option('wufoo_phooey-secret_key') )
    delete_option('wufoo_phooey-secret_key');
    
  $dir = dirname(__FILE__) . '/cache/*';

  // Clears the cache files
  foreach(glob($dir) as $file)   {  
    if( !preg_match('/index\.php/', $file) )
      unlink($file);
  }
}


// ===================
// = TinyMCE Buttons =
// ===================

function add_WufooPhooey_button() {
   // Don't bother doing this stuff if the current user lacks permissions
   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
     return;
 
   // Add only in Rich Editor mode
   if ( get_user_option('rich_editing') == 'true') {
     add_filter("mce_external_plugins", "add_WufooPhooey_tinymce_plugin");
     add_filter('mce_buttons', 'register_WufooPhooey_button');
   }
}
 
function register_WufooPhooey_button($buttons) {
   array_push($buttons, "|", "wufoophooey");
   return $buttons;
}
 
// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_WufooPhooey_tinymce_plugin($plugin_array) {
   $plugin_array['wufoophooey'] = plugins_url('/tinymce-plugin/WufooPhooey.php', __FILE__);
   return $plugin_array;
}
 
function my_refresh_mce($ver) {
  $ver += 3;
  return $ver;
}

// init process for button control
add_filter( 'tiny_mce_version', 'my_refresh_mce');
add_action('init', 'add_WufooPhooey_button');

// Adds the HTML view button
add_action('admin_head', 'add_WufooPhooey_html_button');
function add_WufooPhooey_html_button() {
  
  if( !$wrapper = wufoo_login($echo = false) )
    return;
  
?>
  <script>
    edButtons[edButtons.length] = new edButton('ed_strong', 'Wufoo Form', '[wufoo_phooey id=""]', '', '', -1);
  </script>
<?php
}


// ============
// = Template =
// ============

add_action('admin_head', 'wufoo_css');
function wufoo_css($info) {
  
  if( isset($_GET['page']) ) {
    
    if( preg_match('/^wufoo-phooey/', $_GET['page']) == FALSE )
      return;
      
    echo '<link rel="stylesheet" href="' . plugins_url('/wufoo_phooey.css', __FILE__) . '" type="text/css" media="screen" title="no title" charset="utf-8" />';
  }
}

function wufoo_header($text = null, $update_message = null) {
  $title = '';
  
  if( !empty($text) )
    $title .= ' ' . $text;
    
  echo '<div class="wrap wufoo"><h2 id="wufoo-phooey-title">' . $title . '</h2>';
  
  if( $_GET['updated'] )
    echo '<div id="message" class="updated"><p>' . $update_message . '</p></div>';
  
  
}

function wufoo_footer() {
  echo '</div> <!-- .wufoo.wrap -->';
  ?>
    <script>
      jQuery('.disabled')
        .css({
          opacity: 0.5,
          cursor: 'default'
        })
        .click(function(e) {
          e.preventDefault();
        });
        
      jQuery('.wufoo.wrap #overlay').live('click', function() {
        $('.wufoo.wrap #overlay, .wufoo.wrap #popup').remove();
        $('.wufoo.wrap .fields').hide();
      })
    </script>
  <?php
}

// Creates the URI to links inside of Wufoo Phooey
function wufoo_link($page = null) {
  $blog = get_option('siteurl') . '/wp-admin/admin.php?page=';
  $page = (empty($page)) ? 'settings' : strtolower($page);
  
  switch ($page) {
    case 'settings' :
      return $blog . 'wufoo-phooey';
    break;
    
    case 'forms' :
      return $blog . 'wufoo-phooey-forms';
    break;
    
    case 'reports' :
      return $blog . 'wufoo-phooey-reports';
    break;
    
    case 'users' :
      return $blog . 'wufoo-phooey-users';
    break;
    
    default :
      return $blog . 'wufoo-phooey';
    break;
  }
}

// Creates the Wufoo API Wrapper
function wufoo_login($echo = true) {
  
  if( !get_option('wufoo_phooey-api_key') || !get_option('wufoo_phooey-username') ) {
    if( $echo )
      echo '<div id="wufoo-phooey-message" class="updated">Make sure you have filled in all the fields on the <a href="' . wufoo_link('settings') . '">Settings Page</a>.</div>';
    
    return false;
  }else {
    $api_key = get_option('wufoo_phooey-api_key');
    $username = get_option('wufoo_phooey-username');
    
    $wrapper = new WufooApiWrapper($api_key, $username);
    
    if( $login_data = wufoo_cache_get('login', 60*60*24*30) ) {
      if( $login_data['username'] == $username && $login_data['api_key'] == $api_key )
        return $wrapper;
      else {
        wufoo_cache_set('login', array(
            'api_key' => $api_key,
            'username' => $username
          ));
      }
    }
    
    try {
      $login = $wrapper->login($api_key);
      wufoo_cache_set('login', array(
          'api_key' => $api_key,
          'username' => $username
        ));
    } catch (Exception $e) {
      if( $echo )
        echo '<div id="wufoo-phooey-message" class="updated">Make sure you have added the right API Key on the <a href="' . wufoo_link('settings') . '">Settings Page</a>.</div>';
      
      return false;
    }
    
    return $wrapper;
  }
  
}

// Functions for caching in Wufoo Phooey
include 'includes/caching.php';

// Loads common JS for Wufoo Phooey pages
function wufoo_message($subject) {
  
  switch ($subject) {
    
    case 'caching' :
?>
  <script>
    jQuery('#cache-message').click(function(e) {
      var $ = jQuery;
      $('<div />', {
        id: 'overlay',
      }).appendTo($('.wufoo.wrap'));
      
      $('#overlay', '.wufoo.wrap').css({
        width: $('.wufoo.wrap').outerWidth(),
        height: $('.wufoo.wrap').outerHeight(),
        position: 'absolute',
        background: '#fff',
        opacity: 0.3,
        top: 0,
        left: 0
      });
      
      $('<div />', {
        id: 'popup',
        html: '<p>Wufoo phooey uses caching. This means your items might not show up for a while.</p><p>But don&#x27;t worry. You can</p><a class="button-primary" href="#" id="reload-cache">Reload the Cache</a>'
      })
        .prepend('<a id="close-message" href="#">X</a>')
        .appendTo($('.wufoo.wrap'));
      
      e.preventDefault();
    });
    
    jQuery('#popup a').live('click', function(e) {
      var $ = jQuery;
      
      if( $(this).attr('id') == 'reload-cache' ) {
        window.location = window.location + '&reload_cache=true';
      }else {
        $('#popup, #overlay').remove();
      }
      
      e.preventDefault();
    });
  </script>
<?php
    break;
  }
  
}

// Builds the Wufoo form in HTML or loads the iframe
function wufoo_build_form($form, $options = null, $errors = null) {
  if( !$wrapper = wufoo_login() )
    return;
    
  $option = (is_array($options)) ? (object) $options : (object) array();
  
  if( isset($option->use_iframe) )
    return '<script type="text/javascript">var host = (("https:" == document.location.protocol) ? "https://secure." : "http://");document.write(unescape("%3Cscript src=\'" + host + "wufoo.com/scripts/embed/form.js\' type=\'text/javascript\'%3E%3C/script%3E"));</script><script type="text/javascript">var ' . $form . ' = new WufooForm();' . $form . '.initialize({\'userName\':\'baylorrae\', \'formHash\':\'' . $form . '\', \'autoResize\':true});' . $form . '.display();</script>';
    
  if( (!$data = wufoo_cache_get('fields-' . $form)) || isset($_GET['reload_cache']) ) {
    $data = array();
    
    $info = $wrapper->getForms($form);
    $fields = $wrapper->getFields($form);
    
    $data = wufoo_cache_set('fields-' . $form, array(
        'info' => $info,
        'fields' => $fields
      ));
  }
  
  if( empty($info) )
    $info = $data['info'];
  
  if( empty($fields) )
    $fields = $data['fields'];
  
  $output = '<form name="' . $form . '" id="' . $form . '" class="wufoo_phooey-form ' . $form . '" autocomplete="off" enctype="multipart/form-data" method="post" action="' . plugins_url('/submit.php', __FILE__) . '">';
  $output .= '<input type="hidden" name="form_id" value="' . $form . '" />';
  
  if( is_array($errors) ) {
    
    foreach( $errors as $field ) {
      if( isset($fields->Fields[$field->ID]) )
        $fields->Fields[$field->ID]->ErrorText = $field->ErrorText;
    }
    
  }
  
  $output .= '<div class="wufoo_form-info"><h2>' . $info[$form]->Name . '</h2><div>' . $info[$form]->Description . '</div></div>';
  
  $output .= '<ul>' . WufooFields::form_loop($fields->Fields) . '</ul>';
  
  $submit_class = (empty($option->submit_class)) ? 'button-primary' : $option->submit_class;
  $output .= '<input class="' . $submit_class . '" type="submit" name="submit" value="Submit" />';
  
  if( isset($option->cancel_link) && isset($option->cancel_location) ) {
    $cancel_class = (empty($option->cancel_class)) ? 'button' : $option->cancel_class;
    $output .= ' <a class="' . $cancel_class . '" href="' . $option->cancel_location . '">Cancel</a>';
  }
  
  $output .= '</form>';
  
  return $output;
}



// ================
// = Plugin Pages =
// ================

function wufoo_settings() {
  wufoo_header('Settings', 'Your settings have been saved!');
  
  if( !get_option('wufoo_phooey-cache-forms') )
    add_option('wufoo_phooey-cache-forms', '30 minutes');
    
  if( !get_option('wufoo_phooey-cache-entries') )
    add_option('wufoo_phooey-cache-entries', '30 minutes');
    
  // if( !get_option('wufoo_phooey-cache-reports') )
  //   add_option('wufoo_phooey-cache-reports', '1 week');
    
  if( !get_option('wufoo_phooey-use-css') )
    add_option('wufoo_phooey-use-css', 'true');
    
?>
  
  <p>Wufoo Phooey needs your Wufoo API-Key and subdomain to get your forms.</p>
  <p>If you need help finding your Wufoo API-Key please see the Wufoo Docs on <a target="_blank" href="http://wufoo.com/docs/api/v3/#key" title="Wufoo Docs &middot; Finding Your Key">Finding Your Key</a>.</p>
  
  <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
    
    <table class="form-table wufoo-phooey-form">
      
      <tr valign="top" class="form-field form-required">

        <th scope="row"><label for="username">Wufoo Subdomain</label></th>
        <td><input type="text" id="username" name="wufoo_phooey-username" class="regular-text large-font" value="<?php echo get_option('wufoo_phooey-username') ?>" /></td>

      </tr>
                  
      <tr valign="top" class="form-field form-required">
        
        <th scope="row"><label for="api-key">API Key</label></th>
        <td><input aria-required="true" type="text" id="api-key" name="wufoo_phooey-api_key" class="regular-text large-font" value="<?php echo get_option('wufoo_phooey-api_key') ?>" /></td>
        
      </tr>
                  
    </table>
        
    <a id="toggle-adv-opts" href="#">+ Advanced Options</a>
    <div id="adv-opts">
      
      <h3>Caching</h3>
      <table class="form-table wufoo-phooey-form">
        
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-forms">Forms</label></th>
          <td>
            <input type="text" id="caching-forms" name="wufoo_phooey-cache-forms" class="regular-text" value="<?php echo get_option('wufoo_phooey-cache-forms') ?>" />
          </td>
        </tr>
        
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-entries">Entries</label></th>
          <td>
            <input type="text" id="caching-entries" name="wufoo_phooey-cache-entries" class="regular-text" value="<?php echo get_option('wufoo_phooey-cache-entries') ?>" />
          </td>
        </tr>
        
        <?php /* ?>
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-reports">Reports</label></th>
          <td>
            <input type="text" id="caching-reports" name="wufoo_phooey-cache-reports" class="regular-text" value="<?php echo get_option('wufoo_phooey-cache-reports') ?>" />
          </td>
        </tr>
        <?php */ ?>
                
      </table> 
      
      <h3>Misc</h3>
      <table class="form-table wufoo-phooey-form">
        
        <tr valign="top" class="form-field">
          <th scope="row" style="width: 20px;"></th>
          <td>
            <a class="button" href="<?php echo plugins_url('/clear_cache.php', __FILE__) ?>">Clear Cache</a>
            <p style="color: #fff; text-shadow: 0 1px 0 #000">This will empty the cache folder. Clearing used disk space.</p>
          </td>
        </tr>
        
        
        <tr valign="top" class="form-field">
          <th scope="row" style="width: 20px;"></th>
          <td>
            <input type="checkbox" style="display: inline; width: inherit;" id="wufoo_phooey-use-css" name="wufoo_phooey-use-css" <?php echo (get_option('wufoo_phooey-use-css')) ? 'checked="checked"' : '' ?> value="true" />
            <label for="wufoo_phooey-use-css">Use Generic CSS</label>
            <p style="color: #fff; text-shadow: 0 1px 0 #000; padding: 2px; margin: 0;">This will autoload a <a target="_blank" href="<?php echo plugins_url('/generic_form.css', __FILE__) ?>">generic stylesheet</a> for Wufoo Forms</p>
          </td>
        </tr>
        
      </table>
      
    </div>
    
    <input type="hidden" name="wufoo_phooey-secret_key" value="<?php echo time() ?>" />
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="wufoo_phooey-api_key,wufoo_phooey-username,wufoo_phooey-secret_key,wufoo_phooey-cache-forms,wufoo_phooey-cache-entries<?php /* ,wufoo_phooey-cache-reports */ ?>,wufoo_phooey-use-css" />
    
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /> &#8212;
      <input type="button" id="wufoo-phooey-test_info" class="button" value="<?php _e('Test Info') ?>" />
      <img id="spinner" style="position: relative; top: 3px" src="<?php echo plugins_url('/images/ajax-loader-red.gif', __FILE__) ?>" />
      <span style="position: relative; left: -16px;" id="wufoo-phooey-message" class="description">Make sure it's right</span>
    </p>
    
  </form>
  
  <script>
    var $ = jQuery;
    var $test_btn = $('#wufoo-phooey-test_info'),
        $message = $('#wufoo-phooey-message'),
        $spinner = $('#spinner').css('opacity', 0);
        
    $test_btn.click(function() {
      
      $message.stop().animate({
        left: 0
      }, '', function() {
        $spinner.stop().animate({
          opacity: 1
        }, 250); 
      });
      
      $.post('<?php echo plugins_url('test_info.php', __FILE__) ?>', $('form').serialize(), function(data) {        
        if( data == 'Success!' )
          $message.text(data).removeClass('error').addClass('updated');
        else
          $message.text(data).removeClass('updated').addClass('error');
          
        $spinner.stop().css('opacity', 0);
        $message.stop().css('left', -16);
      });
      
    });
    
    $('#adv-opts').hide();
    
    $('#toggle-adv-opts').click(function(e) {
      var $link = $(this);
      
      if( $('#adv-opts').is(':visible') ) {
        $('#adv-opts').slideUp();
        $link.text('+ Advanced Options');
      }else {
        $('#adv-opts').slideDown();
        $link.html('- Advanced Options');
      }
      
      e.preventDefault();
    });
  </script>

<?php  
  wufoo_footer();
}

function wufoo_forms() {
  if( isset($_GET['entries']) )
    return wufoo_entries();
  
  wufoo_header('Forms');
  
  if( !$wrapper = wufoo_login() )
    return;
  
  if( (!$forms = wufoo_cache_get('forms')) || isset($_GET['reload_cache']) ) {
    $forms = $wrapper->getForms();
    foreach( $forms as $id => $form ) {
      $forms[$id]->EntryCount = $wrapper->getEntryCount($id);
    }
    wufoo_cache_set('forms', $forms);
  }
    
?>
  
  <p>Here are the forms you requested. Go ahead and look at the entries, or edit the form with the <a target="_blank" href="http://wufoo.com/docs/form-builder/" title="Wufoo Docs &middot; Form Builder">Wufoo Form Builder</a>.</p>
  
  <table class="widefat list">
    <thead>
      <tr>
        <th>Form ID</th>
        <th class="form-name">Form Name</th>
        <th class="form-description">Description</th>
        <th class="form-email">Email</th>
        <th class="form-actions">Actions</th>
      </tr>
    </thead>
    
    <tbody>
      
      <?php if( is_array($forms) ) : ?>
        <?php foreach( $forms as $id => $form ) : ?>
          <tr>
            <td><?php echo $id ?></td>
            <td class="form-name"><a target="_blank" href="http://<?php echo get_option('wufoo_phooey-username') ?>.wufoo.com/forms/<?php echo $form->Url ?>"><?php echo $form->Name ?></a></td>
            <td class="form-description"><?php echo stripslashes($form->Description) ?></td>
            <td class="form-email"><?php echo stripslashes($form->Email) ?></td>
            <td class="form-actions">
              <a href="<?php echo wufoo_link('forms') ?>&amp;entries=<?php echo $id ?>">Entries</a> (<?php echo $form->EntryCount; ?>) |
              <a target="_blank" href="http://<?php echo get_option('wufoo_phooey-username') ?>.wufoo.com/build/<?php echo $form->Url ?>">Edit on Wufoo!</a>
            </td>
          </tr> 
        <?php endforeach ?>
      <?php else : ?>
        <tr>
          <td colspan="4" align="center">You don't have any forms!</td>
        </tr>
      <?php endif ?>
            
    </tbody>
    
    <tfoot>
      <tr>
        <th>Form ID</th>
        <th class="form-name">Form Name</th>
        <th class="form-description">Description</th>
        <th class="form-email">Email</th>
        <th class="form-actions">Actions</th>
      </tr>
    </tfoot>
  </table>
  
  <p class="submit">
    <a class="button-primary" title="" target="_blank" href="http://<?php echo get_option('wufoo_phooey-username') ?>.wufoo.com/build/">New Form!</a>  &#8212;
    <a id="cache-message" class="button" href="#">Something Wrong?</a>    
  </p>
  
  <?php wufoo_message('caching') ?>
  
<?php
  wufoo_footer();  
}

function wufoo_entries() {
  if( isset($_GET['new_entry']) )
    return wufoo_add_entry();
    
  wufoo_header('Entries');
  $WufooFields = new WufooFields;
    
    if( !$wrapper = wufoo_login() )
      return;
      
    if( (!$data = wufoo_cache_get('entries-' . $_GET['entries'])) || isset($_GET['reload_cache']) ) {
      $data = array();
      
      $fields = $wrapper->getFields($_GET['entries']);
      $entries = $wrapper->getEntries($_GET['entries']);
      
      // Save the list of fields
      $fields = $WufooFields->store($fields);
      
      $data = wufoo_cache_set('entries-' . $_GET['entries'], array(
          'fields' => $fields,
          'entries' => $entries
        ));
    }
    
    if( empty($entries) )
      $entries = $data['entries'];
      
    if( empty($fields) ) 
      $fields = $data['fields'];
    
    $WufooFields->set_fields($fields);
    
?>
  <?php if( is_array($entries) ) : ?>
    <h3>Click on an entry to see it</h3>
    <div id="entry-viewer">
      <?php foreach( $entries as $entry ) : ?>
        <div class="entry" id="entry-<?php echo $entry->EntryId ?>">
          <h2>Entry #<?php echo $entry->EntryId ?> <span class="date"><?php echo date('F j, Y',strtotime($entry->DateCreated)) ?></span> <span class="time">@ <?php echo date('g:i a',strtotime($entry->DateCreated)) ?></span></h2>
          <a class="close" href="#">x</a>
          <table class="widefat">
            <tbody>
              <?php foreach( $fields as $field ) : ?>
                <?php if( !in_array($field['id'], array('EntryId', 'CreatedBy', 'UpdatedBy', 'LastUpdated', 'DateCreated')) ) : ?>
                  <tr>
                    <td class="field"><?php echo $field['title'] ?></td>
                    <td class="value"><?php echo stripslashes($entry->$field['id']) ?></td>
                  </tr>
                <?php endif ?>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
  
  <p class="submit">
    <a class="button" href="<?php echo wufoo_link('forms') ?>">« Back</a>
    <a class="button-primary" href="<?php echo wufoo_link('forms') ?>&amp;entries=<?php echo $_GET['entries'] ?>&amp;new_entry">New Entry</a>
  </p>
  
  <ul class="fields">
    <?php
    
      foreach( $fields as $field ) {
        echo '<li class="selected"><a href="#" rel="' . $field['id'] . '">' . $field['title'] . '</a></li>';
      }

    ?>
  </ul>
  
  <table class="widefat list entries">
    <thead>
      <tr>
        <?php foreach( $fields as $field ) : ?>
          <th rel="<?php echo $field['id'] ?>"><?php echo $WufooFields->field_info($field) ?></th>
        <?php endforeach ?>
        <th class="edit" width="20">
          <a href="#" class="edit_fields"><img src="<?php echo plugins_url('/images/edit.png', __FILE__) ?>" /></a>
        </th>
      </tr>
    </thead>
    
    <tbody>
      
      <?php if( is_array($entries) ) : ?>
        <?php foreach( $entries as $entry ) : ?>
         
          <tr rel="<?php echo $entry->EntryId ?>">
            <?php 
              foreach ($fields as $field): ?>
              <td rel="<?php echo $field['id'] ?>"><?php echo stripslashes($entry->$field['id']) ?></td>
            <?php endforeach; ?>
          </tr>
          
        <?php endforeach ?>
      <?php else : ?>
        <tr>
          <td align="center" id="no-entries">You don't have any entries!</td>
        </tr>
      <?php endif ?>
            
    </tbody>
    
    <tfoot>
      <tr>
        <?php foreach( $fields as $field ) : ?>
          <th rel="<?php echo $field['id'] ?>"><?php echo $WufooFields->field_info($field) ?></th>
        <?php endforeach ?>
        <th class="edit" width="20">
          <a href="#" class="edit_fields"><img src="<?php echo plugins_url('/images/edit.png', __FILE__) ?>" /></a>
        </th>
      </tr>
    </tfoot>
  </table>
  
  <p class="submit">
    <a class="button" href="<?php echo wufoo_link('forms') ?>">« Back</a>
    <a class="button-primary" href="<?php echo wufoo_link('forms') ?>&amp;entries=<?php echo $_GET['entries'] ?>&amp;new_entry">New Entry</a>  &#8212;
    <a id="cache-message" class="button" href="#">Something Wrong?</a>
  </p>
  
  <script src="<?php echo plugins_url('/js/jquery.cookie.js', __FILE__) ?>"></script>
  <script>
    var $ = jQuery;
    
    $('.fields').hide();
        
    $('.edit_fields').click(function(e) {
      
      $('<div />', {
        id: 'overlay',
      }).appendTo($('.wufoo.wrap'));
      
      $('#overlay', '.wufoo.wrap').css({
        width: $('.wufoo.wrap').outerWidth(),
        height: $('.wufoo.wrap').outerHeight(),
        position: 'absolute',
        background: '#fff',
        opacity: 0.3,
        top: 0,
        left: 0
      });
      
      $('.wufoo.wrap .fields').show();
      
      e.preventDefault();
    });
    
    function reStyleColumns() {
      $('table.entries tbody tr').each(function() {
        $('td', this).attr('colspan', 1);
        $('td:visible:last', this).attr('colspan', '2');
      });
    }
    
    function removeColumn(id) {    
      $('table.entries th[rel=' + id + '], table.entries td[rel=' + id + ']').hide();
      
      $('.wufoo.wrap .fields a[rel=' + id + ']').parent().removeClass('selected');
    }
    
    function showColumn(id) {      
      $('table.entries th[rel=' + id + '], table.entries td[rel=' + id + ']').show();
      
      $('.wufoo.wrap .fields a[rel=' + id + ']').parent().addClass('selected');
    }
    
    // Show a default amount of columns
    
    if( !$.cookie('wufoo_phooey-entries-<?php echo $_GET['entries'] ?>') )
      $.cookie('wufoo_phooey-entries-<?php echo $_GET['entries'] ?>', [<?php
        for( $i = 0; $i < 4; $i++ ) {
          echo '"' . $fields[$i]['id'] . '",';
        }
      ?>]);
      
    var fields_to_show = $.cookie('wufoo_phooey-entries-<?php echo $_GET['entries'] ?>').replace(/,$/, '').split(',');
        
    (function() {
      // Hide all the columns
      $('.wufoo.wrap .fields a').each(function() {
        // console.log($(this).attr('rel'));
        removeColumn($(this).attr('rel'));
      });
      
      // Show some of the columns
      $.each(fields_to_show, function(index, id) {
        showColumn(id);
      });
      
      reStyleColumns();
      
    })();
    
    // Show or Hide a column
    $('.fields a').click(function(e) {
      var fields = '';
      
      if( $(this).parent().hasClass('selected') )
        removeColumn($(this).attr('rel'));
      else
        showColumn($(this).attr('rel'));
      
      reStyleColumns();
      
      // Save the cookie
      $('.fields .selected a').each(function() {
        fields += $(this).attr('rel') + ',';
      });
      
      fields = fields.replace(/,$/, '').split(',');
      
      $.cookie('wufoo_phooey-entries-<?php echo $_GET['entries'] ?>', fields);
      
      $('#no-entries').attr('colspan', fields.length + 1);
      
      e.preventDefault();
    });
    
    $('#no-entries').attr('colspan', fields_to_show.length + 1);
    
    $('#entry-viewer .entry').hide();
    
    $('table.entries tr')
      .css('cursor', 'pointer')
      .click(function() {
        var $el = $('#entry-viewer #entry-' + $(this).attr('rel'));
        if( $el.is(':visible') )
          $el.slideUp();
        else
          $el.slideDown();
      });
      
    $('#entry-viewer .close').click(function(e) {
      $(this).parent().slideUp();
      e.preventDefault();
    });
    
  </script>
  
<?php
  
  wufoo_message('caching');
  
  wufoo_footer();
}

add_action('admin_head', 'wufoo_form_css');
add_action('wp_head', 'wufoo_form_css');

// Includes the generic stylesheet
function wufoo_form_css() {
  if( !get_option('wufoo_phooey-use-css') )
    return;
  
  echo '<link rel="stylesheet" href="' . plugins_url('/generic_form.css', __FILE__) . '" type="text/css" media="screen" title="no title" charset="utf-8" />';
}

function wufoo_add_entry() {
  wufoo_header('New Entry');
    
  echo wufoo_build_form($_GET['entries'], array(
      'cancel_link' => true,
      'cancel_location' => wufoo_link('forms') . '&entries=' . $_GET['entries']
    ));
}

function wufoo_reports() {
  wufoo_header('Reports');
  
  wufoo_footer();
}

function wufoo_help() {
  wufoo_header('Help');
?>
  
  <h3>Adding Forms to Your Posts/Pages</h3>
  <div class="help">
    <p>
      <img src="<?php echo plugins_url('/images/help-1.jpg', __FILE__) ?>" width="128" height="78" alt="Help 1" />
      When editing a post or page, click on the arrow next to the Wufoo icon and choose your form.
    </p>
  </div>
  
  <h3>Customizing the form</h3>
  <div class="help">
    <p>
      There are a couple of options that you can use to customize your form.<br />
      Usage:<br />
      <code>[wufoo_phooey id=&quot;form_id&quot; option_name=&quot;option_value&quot;]</code>
    </p>
    
    <h4 style="margin-bottom: 2px">Options</h4>
    <dl>
      
      <dt>use_iframe ( true/false )</dt>
      <dd>
        This will load the Wufoo iFrame instead of rendering the form in HTML.<br />
        If you use this, the options below won't be used.
        <span class="default">( defaults to: <em>false</em> )</span>
      </dd>
      
      <dt>submit_class</dt>
      <dd>
        The class name to add to the submit button
        <span class="default">( defaults to: <em>button-primary</em> )</span>
      </dd>
      
      <dt>cancel_link</dt>
      <dd>
        This will add a cancel link just after the submit button. It requires you to add <code>cancel_location</code>
        <span class="default">( defaults to: <em>false</em> )</span>
      </dd>
      
      <dt>cancel_location</dt>
      <dd>
        Where to go when the cancel link is clicked.
        <span class="default">( only used with <em>cancel_link</em> )</span>
      </dd>
      
      <dt>cancel_class</dt>
      <dd>
        The class name to add to the cancel link
        <span class="default">( defaults to <em>button</em> )</span>
      </dd>
      
    </dl>
  </div>
  
  <h3>About Cacheing</h3>
  <div class="help">
    <p>
      Wufoo Phooey uses cacheing to ease the load on Wufoo. You can adjust how long different items are cached to suit your needs.
    </p>
    <p>
      If you want to change the cache time go to <code>Wufoo Phooey &gt; Settings</code> and under Advanced settings you'll see the options.
    </p>
    <p>
      When changing the time, you can use these periods.<br />
      You can even combine them!
      <ul>
        <li>Year</li>
        <li>Month</li>
        <li>Week</li>
        <li>Day</li>
        <li>Hour</li>
        <li>Minute</li>
        <li>Second</li>
      </ul>
    </p>
    <p>
      <h4>Example</h4>
      <code>1 hour 30 minutes</code>
    </p>
  </div>
  
  <h3>Clearing the Cache</h3>
  <div class="help">
    <p>
      In the settings page you may notice a button to clear the cache. Incase you're wondering, this will remove the files left on the server from the previous caches.
    </p>
    <p>
      While these files won't take up a lot of space, it's just good house cleaning to remove those files every once and a while.
    </p>
  </div>
  
  <h3>Using the Generic Stylesheet</h3>
  <div class="help">
    <p>
      Wufoo Phooey comes with a basic stylesheet to style forms. You don't have to use it, but it acts as a good template to work from.
    </p>
  </div>
  
  <script>
    var $ = jQuery;
    $('.help').hide();
    
    $('.wufoo.wrap h3')
      .css('cursor', 'pointer')
      .click(function() {
        // $(this).next().slideDown();
        if( $(this).next().is(':visible') )
          $(this).next().slideUp();
        else
          $(this).next().slideDown();
      });
  </script>
  
<?php  
  wufoo_footer();
}
?>
