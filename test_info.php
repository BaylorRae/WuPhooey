<?php

if( isset($_POST['WuPhooey-username']) && isset($_POST['WuPhooey-api_key']) ) {
  $username = $_POST['WuPhooey-username'];
  $api_key = $_POST['WuPhooey-api_key'];
  
  if( empty($username) || empty($api_key) )
    echo 'Failed!';
  else {
    
    include 'wufoo-api/WufooApiWrapper.php';
    
    $wrapper = new WufooApiWrapper($api_key, $username);
    
    try {
      
      $login = $wrapper->login($api_key);
      
      echo 'Success!';
    }catch( Exception $e ) {
      echo 'Failed!';
    }
    
  }
  
}else
  echo 'Failed!';

?>