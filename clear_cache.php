<?php

$dir = dirname(__FILE__) . '/cache/*';

// Open a known directory, and proceed to read its contents  
foreach(glob($dir) as $file)   {  
  unlink($file);
}

header('Location: /wp-admin/admin.php?page=wufoo-phooey');

?>