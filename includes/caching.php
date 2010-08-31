<?php
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
    $expiration = time2seconds(get_option('WuPhooey-cache-forms', '30 minutes'));
    
  if( preg_match('/entries/', $key) || preg_match('/fields/', $key) )
    $expiration = time2seconds(get_option('WuPhooey-cache-entries', '30 minutes'));
          
  return $wufoo_cache->get($key, $expiration);
}
?>