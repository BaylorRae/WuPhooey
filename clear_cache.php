<?php

$dir = dirname(__FILE__) . '/cache/*';

// Open a known directory, and proceed to read its contents  
foreach(glob($dir) as $file)   {  
  if( !preg_match('/index\.php/', $file) )
    unlink($file);
}

header('Location: ' . $_GET['url']);

?>