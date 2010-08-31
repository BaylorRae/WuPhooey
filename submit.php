<?php

  include '../../../wp-blog-header.php';
  
  $error_message = '';
  
  if( !$wrapper = wufoo_login($echo = false) )
    $error_message = 'Uh Oh! The Form Failed To Send';
  
  $fields = array();
  $output = '';
  foreach( $_POST as $key => $value ) {
    if( preg_match('/(Field\d+)(-\d+)?/', $key, $matches) ) {
      if( isset($matches[2]) )
        $fields[$matches[1]] .= $_POST[$matches[0]];
      else
        $fields[$matches[1]] = $value;
    }
  }
  
  $new_fields = array();
  foreach( $fields as $id => $value ) {
    $new_fields[] = new WufooSubmitField($id, $value);
  }
  
  $sent = $wrapper->entryPost($_POST['form_id'], $new_fields);  
      
  if( isset($sent->FieldErrors) )
    $error_message = $sent->ErrorText;
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>WuPhooey - Form Submitter</title>
<link rel="stylesheet" href="<?php echo plugins_url('/generic_form.css', __FILE__) ?>" type="text/css" media="screen" title="no title" charset="utf-8" />
<style>
  * { margin: 0; padding: 0; }
  
  body {
    background: #E9E9E9;
    font-family: Verdana;
  }
  
  .message {
    margin: 40px auto;
    width: 700px;
    background: #fff;
    border: 1px solid #aaa;
    padding: 20px;
    -webkit-border-radius: 15px;
    -moz-border-radius: 15px;
    border-radius: 15px;
    text-align: center;
  }
  
  .message h3 {
    color: #555;
    margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #aaa;
  }
  
  .message .button,
  .message .button-primary {
    text-decoration: none;
    -webkit-border-radius: 11px;
    padding: 2px;
    font-size: 11px;
    display: inline-block;
    line-height: 13px;
    padding: 3px 8px;
    text-align: center;
    font-family: 'Lucida Grand', 'Verdana';
    cursor: pointer;
  }
  
  .message .button-primary {
    background: #8EBC14;
    border: 1px solid #6D910E;
    color: #fff;
    font-weight: bold;
  }
  
  .message .button-primary:hover {
    background: #95C613;
    color: #EAF2FA;
  }
  
  .message .button-primary:active {
    -webkit-box-shadow: inset 0 0 3px #333;
    -moz-box-shadow: inset 0 0 3px #333;
    box-shadow: inset 0 0 3px #333;
    color: #526E09;
    text-shadow: none;
  }
  
  .message .button {
    background: #FFDA68;
    border: 1px solid #AAA06B;
    text-shadow: none;
    color: #464646;
  }
  
  .message .button:hover {
    background: #FFEE91;
    color: #000;
  }
  
  .message .button:active {
    -webkit-box-shadow: inset 0 0 2px #111;
    -moz-box-shadow: inset 0 0 2px #111;
    box-shadow: inset 0 0 2px #111;
    border: 1px solid #B1A770;
  }
  
  .WuPhooey-form {
    text-align: left;
  }
  
  .WuPhooey-form table { border: 1px solid #aaa; border-bottom: none; border-right: none; }
  .WuPhooey-form table th { font-weight: normal; }
  .WuPhooey-form table thead th,
  .WuPhooey-form table tbody th,
  .WuPhooey-form table tbody td { background: #fff; border-bottom: 1px solid #aaa; border-right: 1px solid #aaa; padding: 4px 10px; }
  
  .WuPhooey-form table thead th { background: #eee; text-align: center; }
    
</style>
</head>
<body>
  
  <?php if( !empty($error_message) ) : ?>
    <div class="message">
      <h3><?php echo $error_message ?></h3>
      
      <?php echo wufoo_build_form($_POST['form_id'], array(
          'submit_class' => 'button-primary',
          'cancel_link' => true,
          'cancel_location' => get_bloginfo('url')
        ), $sent->FieldErrors) ?>
      
    </div>
    
  <?php else : ?>
    
    <div class="message">
      <h3>Thank You for Filling out My Form</h3>
      
      <a class="button-primary" href="<?php bloginfo('url') ?>">Return To My Site!</a>
    </div>
    
  <?php endif ?>
  
</body>
</html>