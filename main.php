<?php
/*
Plugin Name: WuPhooey
Plugin URI: http://baylorrae.com/WuPhooey
Description: A Wufoo Form Manager for WordPress
Version: 1.4.4.1
Author: Baylor Rae'
Author URI: http://baylorrae.com
  
*/

/*
  TODO Add reports if they are needed
*/

include 'wufoo-api/WufooApiWrapper.php';
include 'WuPhooeyFields.php';
include 'jg_cache.php';

$wufoo_cache = new JG_Cache(dirname(__FILE__) . '/cache');


// Creates the navigation links
function wufoo_navigation() {

  add_menu_page('WuPhooey', 'WuPhooey' . wufoo_count_entries(), 'manage_options', 'WuPhooey', 'wufoo_settings');
  add_submenu_page('WuPhooey', 'Settings &lsaquo; WuPhooey', 'Settings', 'manage_options', 'WuPhooey');
  add_submenu_page('WuPhooey', 'Forms &lsaquo; WuPhooey', 'Forms', 'manage_options', 'WuPhooey-Forms', 'wufoo_forms');
  // add_submenu_page('wuphooey', 'Reports &lsaquo; WuPhooey', 'Reports', 'manage_options', 'wuphooey-reports', 'wufoo_reports');
  add_submenu_page('WuPhooey', 'Help &lsaquo; WuPhooey', 'Help', 'manage_options', 'WuPhooey-Help', 'wufoo_help');

}
add_action('admin_menu', 'wufoo_navigation');

// Looks for the WuPhooey shortcode
function wufoo_filter_post($atts, $content = null) {
  if( $atts['id'] )
    return wufoo_build_form($atts['id'], $atts);
}
add_shortcode('WuPhooey', 'wufoo_filter_post');

function wufoo_count_entries() {
  $chosenForms = unserialize(get_option('WuPhooey-forms_to_count'));
  
  if( count($chosenForms) == 0 )
    return '';
  
  if( $wrapper = wufoo_login($echo = false) ) {
    $total = wufoo_cache_get('entryCountToday', time2seconds('1 hour'));
    
    if( !$total ) {
      
      $total = 0;
      foreach( $chosenForms as $id => $value ) {
        $id = str_replace('id-', '', $id);
        
        $total += $wrapper->getEntryCountToday($id);        
      }
      
      wufoo_cache_set('entryCountToday', 'total-' . $total);
    }    
    
    $total = str_replace('total-', '', $total);
    $class = '';
    
    if( $total > 99 ) {
      $total = '99+';
      $class .= ' alot';
    }
    
    if( $total != 0)
      return ' <span title="Today\'s Entries" class="WuPhooey-today_entries update-plugins' . $class . '"><span class="plugins-count">' . $total . '</span></span>';
     
  }
  
  return '';
}

// ================
// = Deactivation =
// ================

register_deactivation_hook(__FILE__, 'wufoo_deactivate');
function wufoo_deactivate() {
  
  // Delete the options
  if( get_option('WuPhooey-use-css') )
    delete_option('WuPhooey-use-css');
    
  if( get_option('WuPhooey-cache-entries') )
    delete_option('WuPhooey-cache-entries');
    
  if( get_option('WuPhooey-cache-forms') )
    delete_option('WuPhooey-cache-forms');
    
  if( get_option('WuPhooey-api_key') )
    delete_option('WuPhooey-api_key');
  
  if( get_option('WuPhooey-username') )
    delete_option('WuPhooey-username');
    
  if( get_option('WuPhooey-secret_key') )
    delete_option('WuPhooey-secret_key');
    
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

function add_WuPhooey_button() {
   // Don't bother doing this stuff if the current user lacks permissions
   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
     return;
 
   // Add only in Rich Editor mode
   if ( get_user_option('rich_editing') == 'true') {
     add_filter("mce_external_plugins", "add_WuPhooey_tinymce_plugin");
     add_filter('mce_buttons', 'register_WuPhooey_button');
   }
}
 
function register_WuPhooey_button($buttons) {
   array_push($buttons, "|", "WuPhooey");
   return $buttons;
}
 
// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_WuPhooey_tinymce_plugin($plugin_array) {
  if( !$wrapper = wufoo_login($echo = false) )
    return;
  
  $plugin_array['WuPhooey'] = plugins_url('/tinymce-plugin/WuPhooey.js', __FILE__);
  
  return $plugin_array;
}
 
function my_refresh_mce($ver) {
  $ver += 3;
  return $ver;
}

// Ajax for the button
add_action('wp_ajax_get_forms_list_javascript', 'return_forms_list');
function return_forms_list() {
  $wrapper = wufoo_login($echo = false);
  
  
  if( !$forms = wufoo_cache_get('forms') ) {
    $forms = $wrapper->getForms();
    foreach( $forms as $id => $form ) {
      $forms[$id]->EntryCount = $wrapper->getEntryCount($id);
    }
    wufoo_cache_set('forms', $forms);
  }
  
  $output = array();
  
  foreach( $forms as $id => $form ) {
    $output[$id] = $form->Name;
  }
  
  echo json_encode($output);
  
  die();
}

// init process for button control
add_filter( 'tiny_mce_version', 'my_refresh_mce');
add_action('init', 'add_WuPhooey_button');

// Adds the HTML view button
add_action('admin_head', 'add_WuPhooey_html_button');
function add_WuPhooey_html_button() {
  
  if( !$wrapper = wufoo_login($echo = false) )
    return;
  
?>
  <script>
    edButtons[edButtons.length] = new edButton('ed_strong', 'WuPhooey', '[WuPhooey id=""]', '', '', -1);
  </script>
<?php
}


// ============
// = Template =
// ============

add_action('admin_head', 'wufoo_css');
function wufoo_css($info) {
  
  if( isset($_GET['page']) ) {
    
    if( preg_match('/^WuPhooey/', $_GET['page']) == FALSE )
      return;
      
    echo '<link rel="stylesheet" href="' . plugins_url('/WuPhooey.css', __FILE__) . '" type="text/css" media="screen" title="no title" charset="utf-8" />';
  }
}

function wufoo_header($text = null, $update_message = null) {
  $title = '';
  
  if( !empty($text) )
    $title .= ' ' . $text;
    
  echo '<div class="wrap wufoo"><h2 id="wuphooey-title">' . $title . '</h2>';
  
  if( $_GET['updated'] )
    echo '<div id="message" class="updated"><p>' . $update_message . '</p></div>';
  
  
}

function wufoo_footer() {
  echo '</div> <!-- .wufoo.wrap -->';
  echo '<a id="wufoo-link" href="http://wufoo.com">Made For Wufoo</a>';
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

// Creates the URI to links inside of WuPhooey
function wufoo_link($page = null) {
  $blog = get_option('siteurl') . '/wp-admin/admin.php?page=';
  $page = (empty($page)) ? 'settings' : strtolower($page);
  
  switch ($page) {
    case 'settings' :
      return $blog . 'WuPhooey';
    break;
    
    case 'forms' :
      return $blog . 'WuPhooey-Forms';
    break;
    
    case 'reports' :
      return $blog . 'WuPhooey-reports';
    break;
    
    case 'users' :
      return $blog . 'WuPhooey-users';
    break;
    
    default :
      return $blog . 'WuPhooey';
    break;
  }
}

// Creates the Wufoo API Wrapper
function wufoo_login($echo = true) {
  
  if( !get_option('WuPhooey-api_key') || !get_option('WuPhooey-username') ) {
    if( $echo )
      echo '<div id="wuphooey-message" class="updated">Make sure you have filled in all the fields on the <a href="' . wufoo_link('settings') . '">Settings Page</a>.</div>';
    
    return false;
  }else {
    $api_key = get_option('WuPhooey-api_key');
    $username = get_option('WuPhooey-username');
    
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
        echo '<div id="wuphooey-message" class="updated">Make sure you have added the right API Key on the <a href="' . wufoo_link('settings') . '">Settings Page</a>.</div>';
      
      return false;
    }
    
    return $wrapper;
  }
  
}

// Functions for caching in WuPhooey
include 'includes/caching.php';

// Loads common JS for WuPhooey pages
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
        html: '<p>WuPhooey uses caching. This means your items might not show up for a while.</p><p>But don&#x27;t worry. You can</p><a class="button-primary" href="#" id="reload-cache">Reload the Cache</a>'
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

// Loads the Wufoo form in an iframe
function wufoo_build_form($form, $options = null, $errors = null) {
  if( !$wrapper = wufoo_login() )
    return;
        
  $option = (is_array($options)) ? (object) $options : (object) array();
  $subdomain = get_option('WuPhooey-username');
    
  $autoResize = (!isset($option->autoresize)) ? 'true' : $option->autoresize;
  $height = (!isset($option->height)) ? '514' : $option->height;
  
  /**
   * Deleted the option to not use iframe
   * My form builder had many bugs
   *
   * @author Baylor Rae'
   * @version 1.1
   */
  
  // if( isset($option->use_iframe) )
    // return '<script type="text/javascript">var host = (("https:" == document.location.protocol) ? "https://secure." : "http://");document.write(unescape("%3Cscript src=\'" + host + "wufoo.com/scripts/embed/form.js\' type=\'text/javascript\'%3E%3C/script%3E"));</script><script type="text/javascript">var ' . $form . ' = new WufooForm();' . $form . '.initialize({\'userName\':\'baylorrae\', \'formHash\':\'' . $form . '\', \'autoResize\':true});' . $form . '.display();</script>';
  
  return '<script type="text/javascript">var host = (("https:" == document.location.protocol) ? "https://secure." : "http://");document.write(unescape("%3Cscript src=\'" + host + "wufoo.com/scripts/embed/form.js\' type=\'text/javascript\'%3E%3C/script%3E"));</script>

    <script type="text/javascript">
    var ' . $form . ' = new WufooForm();
    ' . $form . '.initialize({
    \'userName\':\'' . $subdomain .  '\', 
    \'formHash\':\'' . $form . '\', 
    \'autoResize\':' .  $autoResize . ',
    \'height\':\'' . $height . '\'});
    ' . $form . '.display();
    </script>';
    
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
  
  $output = '<form name="' . $form . '" id="' . $form . '" class="WuPhooey-form ' . $form . '" autocomplete="off" enctype="multipart/form-data" method="post" action="' . plugins_url('/submit.php', __FILE__) . '">';
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
    $cancel_text = (emptY($option->cancel_link)) ? 'Cancel' : $option->cancel_link;
    $output .= ' <a class="' . $cancel_class . '" href="' . $option->cancel_location . '">' . $cancel_text . '</a>';
  }
  
  $output .= '</form>';
  
  return $output;
}



// ================
// = Plugin Pages =
// ================

function wufoo_settings() {
  wufoo_header('Settings', 'Your settings have been saved!');
  
  $forms = wufoo_cache_get('forms');
  
  if( !get_option('WuPhooey-cache-forms') )
    add_option('WuPhooey-cache-forms', '30 minutes');
    
  if( !get_option('WuPhooey-cache-entries') )
    add_option('WuPhooey-cache-entries', '30 minutes');    
  
  // if( isset($_POST['WuPhooey-forms_to_count']) ) {
  //   update_option('WuPhooey-forms_to_count', serialize($_POST['WuPhooey-forms_to_count']));
  // }else {
  //   update_option('WuPhooey-forms_to_count', '');
  // }
  
  if( !empty($_POST) ) {
    
    update_option('WuPhooey-username', $_POST['WuPhooey-username']);
    update_option('WuPhooey-api_key', $_POST['WuPhooey-api_key']);
    update_option('WuPhooey-cache-forms', $_POST['WuPhooey-cache-forms']);
    update_option('WuPhooey-cache-entries', $_POST['WuPhooey-cache-entries']);
    update_option('WuPhooey-forms_to_count', serialize($_POST['WuPhooey-forms_to_count']));
        
  }
    
?>
  
  <p>WuPhooey needs your Wufoo API-Key and subdomain to get your forms.</p>
  <p>If you need help finding your Wufoo API-Key please see the Wufoo Docs on <a target="_blank" href="http://wufoo.com/docs/api/v3/#key" title="Wufoo Docs &middot; Finding Your Key">Finding Your Key</a>.</p>
  
  <form method="post" action="<?php echo wufoo_link('settings') ?>&amp;updated=true">
    <?php wp_nonce_field('update-options'); ?>
    
    <table class="form-table wuphooey-form">
      
      <tr valign="top" class="form-field form-required">

        <th scope="row"><label for="username">Wufoo Subdomain</label></th>
        <td><input type="text" id="username" name="WuPhooey-username" class="regular-text large-font" value="<?php echo get_option('WuPhooey-username') ?>" /></td>

      </tr>
                  
      <tr valign="top" class="form-field form-required">
        
        <th scope="row"><label for="api-key">API Key</label></th>
        <td><input aria-required="true" type="text" id="api-key" name="WuPhooey-api_key" class="regular-text large-font" value="<?php echo get_option('WuPhooey-api_key') ?>" /></td>
        
      </tr>
                  
    </table>
        
    <a id="toggle-adv-opts" href="#">+ Advanced Options</a>
    <div id="adv-opts">
      
      <h3>Caching</h3>
      <table class="form-table wuphooey-form">
        
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-forms">Forms</label></th>
          <td>
            <input type="text" id="caching-forms" name="WuPhooey-cache-forms" class="regular-text" value="<?php echo get_option('WuPhooey-cache-forms') ?>" />
          </td>
        </tr>
        
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-entries">Entries*</label></th>
          <td>
            <input type="text" id="caching-entries" name="WuPhooey-cache-entries" class="regular-text" value="<?php echo get_option('WuPhooey-cache-entries') ?>" />
          </td>
        </tr>
        
        <?php /* ?>
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-reports">Reports</label></th>
          <td>
            <input type="text" id="caching-reports" name="WuPhooey-cache-reports" class="regular-text" value="<?php echo get_option('WuPhooey-cache-reports') ?>" />
          </td>
        </tr>
        <?php */ ?>
                
      </table> 
      
      <h3>Misc</h3>
      <table class="form-table wuphooey-form">
        
        <tr valign="top" class="form-field">
          <th scope="row" style="width: 20px;"></th>
          <td>
            <a class="button" href="<?php echo plugins_url('/clear_cache.php', __FILE__) ?>?url=<?php echo urlencode($_SERVER['REQUEST_URI']) ?>">Clear Cache</a>
            <p style="color: #fff; text-shadow: 0 1px 0 #000">This will empty the cache folder. Clearing used disk space.</p>
          </td>
        </tr>
        
        <?php /*
        <tr valign="top" class="form-field">
          <th scope="row" style="width: 20px;"></th>
          <td>
            <input type="checkbox" style="display: inline; width: inherit;" id="WuPhooey-use-css" name="WuPhooey-use-css" <?php echo (get_option('WuPhooey-use-css')) ? 'checked="checked"' : '' ?> value="true" />
            <label for="WuPhooey-use-css">Use Generic CSS</label>
            <p style="color: #fff; text-shadow: 0 1px 0 #000; padding: 2px; margin: 0;">This will autoload a <a target="_blank" href="<?php echo plugins_url('/generic_form.css', __FILE__) ?>">generic stylesheet</a> for Wufoo Forms</p>
          </td>
        </tr>
        */ ?>
        
      </table>
      
      <?php if( $wrapper = wufoo_login($echo = false) ) : ?>
        <h3>Count Today&#x27;s Entries</h3>
        <p>Choose the forms you would like to have counted.</p>
        <div id="forms-list">
          <ul>
            
            <?php
            
              if( !$forms = wufoo_cache_get('forms-tec', time2seconds('10 minutes')) ) {
                $forms = $wrapper->getForms();
                wufoo_cache_set('forms-tec', $forms);
              }
              
              $chosenForms = unserialize(get_option('WuPhooey-forms_to_count'));
            ?>
            
            <?php foreach( $forms as $id => $form ) : ?>
              <?php $selected = (isset($chosenForms['id-' . $id])) ? ' checked="checked"' : ' '; ?>
              <li>
                <input<?php echo $selected ?> type="checkbox" id="WuPhooey-forms_to_count[id-<?php echo $id ?>]" name="WuPhooey-forms_to_count[id-<?php echo $id ?>]" value="selected" />
                <label for="WuPhooey-forms_to_count[id-<?php echo $id ?>]"><?php echo $form->Name ?></label>
              </li>
            <?php endforeach ?>
            
          </ul>
        </div>
      <?php else : ?>
        <h3 class="disabled">Count Today&#x27;s Entries</h3>
        <p>This feature will be enabled after you add your Wufoo Subdomain and API Key.</p>
      <?php endif ?>
      
    </div>
    
    <input type="hidden" name="WuPhooey-secret_key" value="<?php echo time() ?>" />
    <!-- <input type="hidden" name="action" value="update" /> -->
    <!-- <input type="hidden" name="page_options" value="WuPhooey-api_key,WuPhooey-username,WuPhooey-secret_key,WuPhooey-cache-forms,WuPhooey-cache-entries<?php /* ,WuPhooey-cache-reports,WuPhooey-use-css */ ?>" /> -->
    
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /> &#8212;
      <input type="button" id="wuphooey-test_info" class="button" value="<?php _e('Test Info') ?>" />
      <img id="spinner" style="position: relative; top: 3px" src="<?php echo plugins_url('/images/ajax-loader-blue.gif', __FILE__) ?>" />
      <span style="position: relative; left: -16px;" id="wuphooey-message" class="description">Make sure it's right</span>
    </p>
    
  </form>
  
  <p>* This will not apply when totaling "Count Today's Entries". Today's entries update every hour.</p>
  
  <script src="<?php echo plugins_url('/js/jquery.cookie.js', __FILE__) ?>"></script>
  <script>
    var $ = jQuery;
    var $test_btn = $('#wuphooey-test_info'),
        $message = $('#wuphooey-message'),
        $spinner = $('#spinner').css('opacity', 0),
        
        // Advanced options
         $link = $('#toggle-adv-opts'),
        advOpts_status = $.cookie('WuPhooey-Settings-adv_opts');
    
    if( !advOpts_status ) {
      $.cookie('WuPhooey-Settings-adv_opts', 'closed');
      advOpts_status = $.cookie('WuPhooey-Settings-adv_opts');
    }
    
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
    
    if( advOpts_status == 'closed' )
      $('#adv-opts').hide();
    else
      $link.text('- Advanced Options');
    
      $link.click(function(e) {
      
      if( $('#adv-opts').is(':visible') ) {
        $('#adv-opts').slideUp();
        $link.text('+ Advanced Options');
        $.cookie('WuPhooey-Settings-adv_opts', 'closed');
      }else {
        $('#adv-opts').slideDown();
        $link.html('- Advanced Options');
        $.cookie('WuPhooey-Settings-adv_opts', 'opened');
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
            <td class="form-name"><a target="_blank" href="http://<?php echo get_option('WuPhooey-username') ?>.wufoo.com/forms/<?php echo $form->Url ?>"><?php echo $form->Name ?></a></td>
            <td class="form-description"><?php echo stripslashes($form->Description) ?></td>
            <td class="form-email"><?php echo stripslashes($form->Email) ?></td>
            <td class="form-actions">
              <a href="<?php echo wufoo_link('forms') ?>&amp;entries=<?php echo $id ?>">Entries</a> |
              <a target="_blank" href="http://<?php echo get_option('WuPhooey-username') ?>.wufoo.com/build/<?php echo $form->Url ?>">Edit on Wufoo!</a>
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
    <a class="button-primary" title="" target="_blank" href="http://<?php echo get_option('WuPhooey-username') ?>.wufoo.com/build/">New Form!</a>  &#8212;
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
  $WufooFields = new WuPhooeyFields;
    
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
    
    if( !$.cookie('WuPhooey-entries-<?php echo $_GET['entries'] ?>') )
      $.cookie('WuPhooey-entries-<?php echo $_GET['entries'] ?>', [<?php
        for( $i = 0; $i < 4; $i++ ) {
          echo '"' . $fields[$i]['id'] . '",';
        }
      ?>]);
      
    var fields_to_show = $.cookie('WuPhooey-entries-<?php echo $_GET['entries'] ?>').replace(/,$/, '').split(',');
        
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
      
      $.cookie('WuPhooey-entries-<?php echo $_GET['entries'] ?>', fields);
      
      $('#no-entries').attr('colspan', fields.length + 1);
      
      e.preventDefault();
    });
    
    $('#no-entries').attr('colspan', fields_to_show.length + 1);
    
    $('#entry-viewer .entry').hide();
    
    $('table.entries tr')
      .css('cursor', 'pointer')
      .click(function() {
        $('#entry-viewer #entry-' + $(this).attr('rel')).slideToggle(300);
      });
      
    $('#entry-viewer .close').click(function(e) {
      $(this).parent().slideUp();
      e.preventDefault();
    });
    
    $.fn.pager = function(options) {
      
      options = $.extend({
        perPage: 3,
        items: 'tr',
        navigation: '#pager-nav'
      }, options);
      
      return $(this).each(function() {
        var rows = $(this).find(options.items),
            nav = $(options.navigation).hide(), page = 0;
            
        // Show the first page
        if( page = window.location.hash.match(/page-(\d+)/) ) {
          var offset = (page[1] - 1) * options.perPage;
          rows.hide().slice(offset, offset+options.perPage).show();
        }else
          rows.hide().slice(0, options.perPage).show();
        
        // Create the navigation
        if( Math.ceil(rows.length / options.perPage) > 1 ) {
          nav.show();
          
          for( var i = 0, len = Math.ceil(rows.length / options.perPage); i < len; i++ ) {
            nav.append('<li><a href="#page-' + (i + 1) + '">' + (i + 1) + '</a></li>');
          }
        }
        
        // "Activate" the navigation
        nav.find('a').click(function(e) {
          var num = $(this).text() - 1;
          
          var offset = num * options.perPage;
          
          rows.hide().slice(offset, offset+options.perPage).show();
          
          nav.find('a').removeClass('current');
          $('a:contains(' + $(this).text() + ')', nav).addClass('current');
        });
        
      });
      
    };
    
    // Paging
    var numEntries = 3,
        rows = $('table.entries tbody tr'),
        length;
        
    $('.submit').append('<select id="pager-amount"><option value="3">3</option><option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option></select><ul class="pager-nav"><li>Page: </li></ul>');
            
    $('table.entries tbody').pager({
      perPage: 3,
      navigation: '.pager-nav'
    });
    
    $('#pager-amount').change(function() {
      var amount = $(this).val();
      
      $.cookie('WuPhooey-entries-count', amount);
      
      $('.pager-nav').find('li').filter(':not(:first-child)').remove();
      
      $('table.entries tbody').pager({
        perPage: amount,
        navigation: '.pager-nav'
      });
    });
    
    if( $.cookie('WuPhooey-entries-count') ) {
      var amount = $.cookie('WuPhooey-entries-count');
      $('#pager-amount').val(amount).change();
    }
    
  </script>
  
<?php
  
  wufoo_message('caching');
  
  wufoo_footer();
}

// add_action('admin_head', 'wufoo_form_css');
// add_action('wp_head', 'wufoo_form_css');

// Includes the generic stylesheet
function wufoo_form_css() {
  if( !get_option('WuPhooey-use-css') )
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
    <p>
      If you are using the HTML editor, you will need to add this tag.<br />
      <code>[WuPhooey id=""]</code><br /><br />
      And place the form id inside the quotes. You can find the form id in the <a href="<?php echo wufoo_link('forms') ?>">WuPhooey &gt; Forms</a> page.
    </p>
  </div>
  
  <h3>WuPhooey is Mega Slow</h3>  
  <div class="help">
    <p>
      If WuPhooey is running slow, even with cacheing. You may need to make sure WuPhooey can write to the
      <br /><code>wp-content/plugins/WuPhooey/cache</code> folder.
      <br />I recommend "755"
    </p>
    <p>
      Incase your wondering how to do that, WordPress has a great article on <a target="_blank" href="http://codex.wordpress.org/Changing_File_Permissions">Changing File Permissions</a>.
    </p>
  </div>
  
  <h3>Customizing the Form</h3>
  <div class="help">
    <p>
      You can adjust the embed code to help the form match your site. Such as <a target="blank" href="http://wufoo.com/2010/08/19/using-wufoo-forms-in-fixed-height-containers/">Using Wufoo Forms in Fixed Height Containers</a>
    </p>
    <p>
      These are the parameters you can change.
      <ul>
        <li>autoResize (defaults to <em>true</em>)</li>
        <li>height (defaults to <em>514</em>)</li>
      </ul>
    </p>
    <p>
      <h4>Example</h4>
      <p>This example will make the form 350 pixels tall and will not automatically resize the form.</p>
      <code>[WuPhooey id=&quot;z7x4a9&quot; autoResize=&quot;false&quot; height=&quot;350&quot;]</code>
    </p>
  </div>
  
  <h3>About Cacheing</h3>
  <div class="help">
    <p>
      WuPhooey uses cacheing to ease the load on Wufoo. You can adjust how long different items are cached to suit your needs.
    </p>
    <p>
      If you want to change the cache time go to <a href="<?php echo wufoo_link('settings') ?>">WuPhooey &gt; Settings</a> and under Advanced settings you'll see the options.
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
    
  <script type="text/javascript">var host = (("https:" == document.location.protocol) ? "https://secure." : "http://");document.write(unescape("%3Cscript src='" + host + "wufoo.com/scripts/embed/form.js' type='text/javascript'%3E%3C/script%3E"));</script>

  <script type="text/javascript">
  var k7x2w5 = new WufooForm();
  k7x2w5.initialize({
  'userName':'baylorrae', 
  'formHash':'k7x2w5', 
  'autoResize':true,
  'height':'574'});
  k7x2w5.display();
  </script>
  
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
      
    $('.wufoo.wrap .help:last').css('borderBottom', 'none');
  </script>
  
<?php  
  wufoo_footer();
}
?>