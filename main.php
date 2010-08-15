<?php
/*
Plugin Name: Wufoo phooey
Plugin URI: http://github.com/BaylorRae/Wufoo-phooey
Description: A complete Wufoo form manager
Version: 1.0
Author: Baylor Rae'
Author URI: http://baylorrae.com
  
*/

/*
  TODO Add the 'submit.php' so forms can be sent
  TODO Finish the generic_form stylesheet
*/

// Create the Menu
add_action('admin_menu', 'wufoo_navigation');

include 'wufoo-api/WufooApiWrapper.php';
include 'WufooFields.php';
include 'jg_cache.php';

$wufoo_cache = new JG_Cache(dirname(__FILE__) . '/cache');


function wufoo_navigation() {

  add_menu_page('Wufoo Phooey', 'Wufoo Phooey', 'manage_options', 'wufoo-phooey', 'wufoo_settings');
  add_submenu_page('wufoo-phooey', 'Settings &lsaquo; Wufoo Phooey', 'Settings', 'manage_options', 'wufoo-phooey');
  add_submenu_page('wufoo-phooey', 'Forms &lsaquo; Wufoo Phooey', 'Forms', 'manage_options', 'wufoo-phooey-forms', 'wufoo_forms');
  // add_submenu_page('wufoo-phooey', 'Entries &lsaquo; Wufoo Phooey', 'Entries', 'manage_options', 'wufoo-phooey-entries', 'wufoo_entries');
  add_submenu_page('wufoo-phooey', 'Reports &lsaquo; Wufoo Phooey', 'Reports', 'manage_options', 'wufoo-phooey-reports', 'wufoo_reports');
  add_submenu_page('wufoo-phooey', 'Users &lsaquo; Wufoo Phooey', 'Users', 'manage_options', 'wufoo-phooey-users', 'wufoo_users');

}

add_action('admin_head', 'wufoo_css');

add_filter('the_content', 'wufoo_filter_post');

function wufoo_filter_post($content) {
  
  if( preg_match('/<!--\swufoo_phooey\((\w+)(,.+)?\)\s-->/', $content, $matches) ) {
    $form_id = $matches[1];
    $params = explode(',', $matches[2]);
    
    $options = array();
    if( is_array($params) ) {
      foreach ($params as $value) {
        if( !empty($value) ) {
          $data = preg_match('/:(.+)\s?=\s?[\'"]?(.+)[\'"]?/', $value, $matches);
          if( !empty($matches) )
            $options[trim($matches[1])] = preg_replace('/\'/', '', $matches[2]);
        }
      }
    }    
    
    $form = wufoo_build_form($form_id, $options);
    $content = preg_replace('/<!--\swufoo_phooey\(.+\)\s-->/', $form, $content);
  }
  
  return $content;
}

function wufoo_css($info) {
  
  if( isset($_GET['page']) ) {
    
    if( preg_match('/^wufoo-phooey/', $_GET['page']) == FALSE )
      return;
    
?>
<style>
  .large-font {
    font-size: 19px;
  }
  
  #wufoo-phooey-message.updated,
  #wufoo-phooey-message.error {
    padding: 5px;
  }
  
  #wufoo-phooey-message a {
    color: #333;
    text-decoration: underline;
  }
  
  .wufoo.wrap {
    width: 800px;
    background: #cb4408;
    margin: 30px 15px;
    padding: 10px;
    color: #fff;
    -webkit-border-radius: 10px;
    -moz-border-radius: 10px;
    border-radius: 10px;
    -webkit-box-shadow: 0 0 10px #333;
    -moz-box-shadow: 0 0 10px #333;
    box-shadow: 0 0 10px #333;
    text-shadow: 0 1px 0 #333;
    position: relative;
  }
  
  .wufoo.wrap h2, .wufoo.wrap label {
    color: #fff;
    text-shadow: 0 1px 0 #333;
  }
  
  #wufoo-phooey-title {
    background: url(<?php echo plugins_url('/images/wufoo-phooey.png', __FILE__) ?>) no-repeat;
    height: 40px;
    padding-left: 241px;
  }
  
  .wufoo.wrap .description {
    color: #ADD8E6;
  }
    
  .wufoo.wrap .updated,
  .wufoo.wrap .error {
    background: #F9DD67;
    color: #333;
    border: 1px solid #FFE364;
    -webkit-box-shadow: 0 10px 10px -10px #333;
    text-shadow: none;
  }
  
  .wufoo.wrap .error {
    background: #F9652F;
    border: 1px solid #FF642D;
    color: #222;
  }
  
  .wufoo.wrap a {
    color: #FFE16E;
    text-decoration: none;
  }
  
  .wufoo.wrap a:hover {
    color: #ADD8E6;
  }
  
  .wufoo.wrap .button-primary {
    background: #8EBC14;
    border: 1px solid #6D910E;
    color: #fff;
  }
  
  .wufoo.wrap .button-primary:hover {
    background: #95C613;
    color: #EAF2FA;
  }
  
  .wufoo.wrap .button-primary:active {
    -webkit-box-shadow: inset 0 0 3px #333;
    color: #526E09;
    text-shadow: none;
  }
  
  .wufoo.wrap .button {
    background: #FFDA68;
    border: 1px solid #FFF0A1;
    text-shadow: none;
    color: #464646;
  }
  
  .wufoo.wrap .button:hover {
    background: #FFEE91;
    color: #000;
  }
  
  .wufoo.wrap .button:active {
    -webkit-box-shadow: inset 0 0 2px #111;
    border: 1px solid #B1A770;
  }
  
  .wufoo.wrap table td {
    color: #333;
    text-shadow: none;
  }
  
  .wufoo.wrap table .form-name {
    width: 30%;
  }
  .wufoo.wrap table .form-description {
    width: 30%;
  }  
  .wufoo.wrap table .form-actions {
    width: 20%;
  }
  
  .wufoo.wrap table td a {
    color: #21759B;
  }
  
  .wufoo.wrap table td a:hover {
    color: #D54E21;
  }
  
  .wufoo.wrap table thead th,
  .wufoo.wrap table tfoot th {
    background: #B4D6FF;
  }
  
  .wufoo.wrap table.list tbody tr:hover {
    background: #FBFFC6;
  }
  
  .wufoo.wrap table.entries tbody td {
    border-right: 1px solid #dfdfdf;
  }
  
  .wufoo.wrap #popup {
    position: absolute;
    width: 300px;
    left: 50%;
    margin-left: -150px;
    top: 50px;
    background: #FFE16E;
    color: #333;
    padding: 15px 10px;
    -webkit-border-radius: 10px;
    -moz-border-radius: 10px;
    border-radius: 10px;
    -webkit-box-shadow: 0 0 10px #555;
    text-align: center;
    text-shadow: none;
  }
  
  .wufoo.wrap #popup #close-message {
    font-family: Verdana;
    position: absolute;
    display: block;
    left: -10px;
    top: -10px;
    color: #fff;
    text-decoration: none;
    background: #000;
    padding: 0;
    width: 19px;
    height: 19px;
    text-align: center;
    -moz-border-radius: 15px;
    -webkit-border-radius: 15px;
    -o-border-radius: 15px;
    -ms-border-radius: 15px;
    -khtml-border-radius: 15px;
    border-radius: 15px;
    border: 2px solid #fff;
    -moz-box-shadow: rgba(0, 0, 0, 0.9) 0 0 4px 0;
    -webkit-box-shadow: rgba(0, 0, 0, 0.9) 0 0 4px 0;
    -o-box-shadow: rgba(0, 0, 0, 0.9) 0 0 4px 0;
    box-shadow: rgba(0, 0, 0, 0.9) 0 0 4px 0;
  }
  
  .fields {
    position: absolute;
    right: 55px;
    background: #fff;
    width: 175px;
    top: 50px;
    border: 5px solid #95C613;
    z-index: 100;
    margin: 0;
    padding: 8px;
    -moz-box-shadow: rgba(0, 0, 0, 0.4) 0 0 4px 0;
    -webkit-box-shadow: rgba(0, 0, 0, 0.4) 0 0 4px 0;
    -o-box-shadow: rgba(0, 0, 0, 0.4) 0 0 4px 0;
    box-shadow: rgba(0, 0, 0, 0.4) 0 0 4px 0;
    -webkit-border-radius: 15px;
    -moz-border-radius: 15px;
    border-radius: 15px;
  }
  
  .fields li {
    margin: 0;
    padding: 0;
  }
  
  .fields a {
    color: #333 !important;
    display: block;
    padding: 7px 10px;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
    text-shadow: none;
  }
  
  .fields .selected a {
    background: url(<?php echo plugins_url('/images/check.png', __FILE__) ?>) no-repeat 150px center;
  }
  
  .fields li:last-child a {
    border: none;
  }
  
  .fields a:hover {
    color: #111 !important;
    background-color: #FFE66B;
  }
  
  .fields a:active {
    background-color: #FFD64C;
  }
</style>
<?php
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

function wufoo_login() {
  
  if( !get_option('wufoo_phooey-api_key') || !get_option('wufoo_phooey-username') ) {
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
      echo '<div id="wufoo-phooey-message" class="updated">Make sure you have added the right API Key on the <a href="' . wufoo_link('settings') . '">Settings Page</a>.</div>';
      return false;
    }
    
    return $wrapper;
  }
  
}

/**
 * Converts an English Time to seconds "30 minutes" = "1800 seconds"
 *
 * @param string $time 1 hour 30 minutes
 * @return $seconds
 * @author Baylor Rae'
 */
function time2seconds($time) {
  preg_match_all('/(\d+ [a-z]+)/', $time, $matches);
  $matches = $matches[0];
  
  $formats = array();
  
  foreach ($matches as $format) {
    preg_match('/(\d+)\s?([a-z]+)/', $format, $f);
    $time = $f[1];
    $type = $f[2];
    $formats[$type] = $time;
  }
  
  $output = array(
      'years' => 0,
      'months' => 0,
      'weeks' => 0,
      'days' => 0,
      'hours' => 0,
      'minutes' => 0,
      'seconds' => 0
    );
  
  foreach ($formats as $format => $time) {
    if( $time == 0 )
      continue;
    
    switch ($format) {
      case 'year' :
      case 'years' :
        $output['years'] = $time * 12 * 30 * 24 * 60 * 60;
      break;
      
      
      case 'month' :
      case 'months' :
        $output['months'] = $time * 30 * 24 * 60 * 60;
      break;
      
      case 'week' :
      case 'weeks' :
        $ouput['weeks'] = $time * 7 * 24 * 60 * 60;
      break;
      
      
      case 'day' :
      case 'days' :
        $output['days'] = $time * 24 * 60 * 60;
      break;
      
      
      case 'hour' :
      case 'hours' :
        $output['hours'] = $time * 60 * 60;
      break;
      
      
      case 'minute' :
      case 'minutes' :
        $output['minutes'] = $time * 60;
      break;
      
      
      case 'second' :
      case 'seconds' :
        $output['seconds'] = $time;
      break;
    }
    
  }
  
  return $output['years'] + $output['months'] + $output['weeks'] + $output['days'] + $output['hours'] + $output['minutes'] + $output['seconds'];
}

// JG caching aliases
function wufoo_cache_set($key, $data) {
  global $wufoo_cache;
  
  $wufoo_cache->set($key, $data);
  return $data;
}

function wufoo_cache_get($key, $expiration = 1800) {
  global $wufoo_cache;
    
  if( preg_match('/forms/', $key) )
    $expiration = time2seconds(get_option('wufoo_phooey-cache-forms', '30 minutes'));
    
  if( preg_match('/entries/', $key) || preg_match('/fields/', $key) )
    $expiration = time2seconds(get_option('wufoo_phooey-cache-entries', '30 minutes'));
          
  return $wufoo_cache->get($key, $expiration);
}

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
        html: '<p>Wufoo phooey caches items every 30 minutes. This means your item might not show up for a while.</p><p>But don&#x27;t worry. You can</p><a class="button-primary" href="#" id="reload-cache">Reload the Cache</a>'
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

function wufoo_build_form($form, $options = null) {
  if( !$wrapper = wufoo_login() )
    return;
    
  $option = (is_array($options)) ? (object) $options : (object) array();
  
  if( isset($option->use_iframe) )
    return '<script type="text/javascript">var host = (("https:" == document.location.protocol) ? "https://secure." : "http://");document.write(unescape("%3Cscript src=\'" + host + "wufoo.com/scripts/embed/form.js\' type=\'text/javascript\'%3E%3C/script%3E"));</script><script type="text/javascript">var ' . $form . ' = new WufooForm();' . $form . '.initialize({\'userName\':\'baylorrae\', \'formHash\':\'' . $form . '\', \'autoResize\':true,\'height\':\'514\'});z7x4a9.display();</script>';
    
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

// Plugin Pages
function wufoo_settings() {
  wufoo_header('Settings', 'Your settings have been saved!');
  
  if( !get_option('wufoo_phooey-cache-forms') )
    add_option('wufoo_phooey-cache-forms', '30 minutes');
    
  if( !get_option('wufoo_phooey-cache-entries') )
    add_option('wufoo_phooey-cache-entries', '30 minutes');
    
  if( !get_option('wufoo_phooey-cache-reports') )
    add_option('wufoo_phooey-cache-reports', '1 week');
    
  if( !get_option('wufoo_phooey-cache-users') )
    add_option('wufoo_phooey-cache-users', '1 month');
    
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
        
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-reports">Reports</label></th>
          <td>
            <input type="text" id="caching-reports" name="wufoo_phooey-cache-reports" class="regular-text" value="<?php echo get_option('wufoo_phooey-cache-reports') ?>" />
          </td>
        </tr>
        
        <tr valign="top" class="form-field form-required">
          <th scope="row"><label for="caching-users">Users</label></th>
          <td>
            <input type="text" id="caching-users" name="wufoo_phooey-cache-users" class="regular-text" value="<?php echo get_option('wufoo_phooey-cache-users') ?>" />
          </td>
        </tr>
        
        <tr valign="top" class="form-field">
          <th scope="row"><a class="button" href="<?php echo plugins_url('/clear_cache.php', __FILE__) ?>">Clear Cache</a></th>
          <td><p style="color: #fff; text-shadow: 0 1px 0 #000">This will empty the cache folder. Clearing used disk space.</p></td>
        </tr>
        
      </table> 
      
    </div>
    
    <input type="hidden" name="wufoo_phooey-secret_key" value="<?php echo time() ?>" />
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="wufoo_phooey-api_key,wufoo_phooey-username,wufoo_phooey-secret_key,wufoo_phooey-cache-forms,wufoo_phooey-cache-entries,wufoo_phooey-cache-reports,wufoo_phooey-cache-users" />
    
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
            <td class="form-name"><a target="_blank" href="http://<?php echo get_option('wufoo_phooey-username') ?>.wufoo.com/forms/<?php echo $form->Url ?>"><?php echo $form->Name ?></a></td>
            <td class="form-description"><?php echo $form->Description ?></td>
            <td class="form-email"><?php echo $form->Email ?></td>
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
         
          <tr>
            <?php 
              foreach ($fields as $field): ?>
              <td rel="<?php echo $field['id'] ?>"><?php echo $entry->$field['id'] ?></td>
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
    
  </script>
  
<?php
  
  wufoo_message('caching');
  
  wufoo_footer();
}

add_action('admin_head', 'wufoo_form_css');

function wufoo_form_css() {
  echo '<link rel="stylesheet" href="' . plugins_url('/generic_form.css', __FILE__) . '" type="text/css" media="screen" title="no title" charset="utf-8" />';
}

function wufoo_add_entry() {
  wufoo_header('New Entry');
    
  echo wufoo_build_form($_GET['entries'], array(
      'cancel_link' => true,
      'cancel_location' => wufoo_link('forms') . '&entries=' . $_GET['entries']
    ));
}
function wufoo_reports() {}
function wufoo_users() {
  
  wufoo_header('Users');
    
  wufoo_footer();
  
}
?>
