<?php

class WuPhooeyFields {
  
  private $fields = array();
  
  public function store($fields) {
    $fields = $fields->Fields;
    
    $this->loop($fields);
   
   return $this->fields; 
  }
  
  public function set_fields($data) {
    $this->fields = $data;
  }
  
  public function get_fields() {
    return $this->fields;
  }
  
  public function list_fields($num = null) {
    $num = (empty($num)) ? 4 : $num;
    $output = array();
    
    if( is_int($num) ) {
      
      for( $i = 0; $i < $num; $i++ ) {
        if( isset($this->fields[$i]) )
          $output[$this->fields[$i]['id']] = $this->fields[$i];
          
      }
      
      return $output;
    }elseif( is_array($num) ) {
      
      foreach( $this->fields as $field ) {
        foreach( $num as $name ) {
         if( (strtolower($field['id']) == strtolower($name)) || (strtolower($field['title']) == strtolower($name)) )
          $output[$field['id']] = $field;
        }
      }
      return $output;
      
    }
  }
 
  public function field_info($field) {
    $field = (object) $field;
    
    switch ($field->id) {
      case 'EntryId' :
        return '#';
      break;
      default :
        return $field->title;
      break;
    }
  }
  
  public static function list_countries() {
    include 'countries.php';
    $output = '';
    
    foreach ($items as $item) {
      $output .= '<option value="' . $item['name'] . '">' . $item['name'] . '</option>';
    }
    
    return $output;
  }
  
  /**
   * Loop through the fields and save the id and title
   *
   * @param Wufoo fields object $data 
   * @return void
   * @author Baylor Rae'
   */
  private function loop($data) {
    
    foreach( $data as $key => $field ) {
      if( isset($field->SubFields) ) {
        $this->loop($field->SubFields);
      }else {
         $title = (isset($field->Label)) ? $field->Label : $field->Title;
         $this->fields[] = array(
            'id' => $field->ID,
            'title' => $title
           );
      }
    }
    
  }
  
  /**
   * Loop through the fields and create the form
   *
   * @param string $data 
   * @return void
   * @author Baylor Rae'
   */
  public static function form_loop($data) {
    
    $output = '';
    
    foreach( $data as $key => $field ) {
      
         if( in_array($field->ID, array('EntryId', 'CreatedBy', 'UpdatedBy', 'LastUpdated', 'DateCreated')) )
          continue;
        
          $class = $field->Type;
          if( isset($field->ErrorText) )
            $class .= ' field-error';
        
         $output .= '<li class="' . $class .  '">';
          
          if( $field->Type != 'likert' ) {
            $output .= '<label class="desc" for="' . $field->ID . '">';
              $output .= $field->Title;
              if( $field->IsRequired )
                $output .= '<span class="req">*</span>';
            $output .= '</label>';
          }
          
          $output .= '<div>';
            switch ($field->Type) {
              
              /**
               *  TEXT BOXES
               *
               * @author Baylor Rae'
               */
              case 'text' :
              case 'number' :
              case 'email' :
              case 'url' :
                $output .= '<input id="' . $field->ID . '" name="' . $field->ID . '" maxlength="255" type="text" class="field text" />';
              break;
              
              /**
               *  TEXTAREA
               *
               * @author Baylor Rae'
               */
              case 'textarea' :
                $output .= '<textarea id="' . $field->ID . '" name="' . $field->ID . '" class="field textarea" rows="10" cols="50"></textarea>';
              break;
              
              /**
               *  CHECKBOX
               *
               * @author Baylor Rae'
               */
              case 'checkbox' :
                foreach ($field->SubFields as $id => $f) {
                  $output .= '<span>';
                    $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="checkbox" class="field checkbox" />';
                    $output .= '<label class="desc" for="' . $f->ID . '">';
                      $output .= $f->Label;
                    $output .= '</label>';
                  $output .= '</span>';
                }
              break;
              
              /**
               *  RADIO
               *
               * @author Baylor Rae'
               */
              case 'radio' :
                foreach ($field->Choices as $id => $f) {
                  $output .= '<span>';
                    $output .= '<input id="' . $id . '" name="' . $field->ID . '" type="radio" class="field radio" />';
                    $output .= '<label class="desc" for="' . $id . '">';
                      $output .= $f->Label;
                    $output .= '</label>';
                  $output .= '</span>';
                }
              break;
              
              /**
               *  SELECT
               *
               * @author Baylor Rae'
               */
              case 'select' :
                $output .= '<select id="' . $field->ID . '" name="' . $field->ID . '" class="field select">';
                  foreach ($field->Choices as $id => $f) {
                    $output .= '<option value="' . $f->Label . '">' . $f->Label . '</option>';
                  }                  
                $output .= '</select>';
              break;
              
              /**
               *  SHORTNAME
               *
               * @author Baylor Rae'
               */
              case 'shortname' :                
                foreach ($field->SubFields as $id => $f) {
                  $output .= '<span>';
                    $className = ($f->Label == 'First') ? 'fn' : 'ln';
                    $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="text" class="field text ' . $className . '" />';
                    $output .= '<label for="' . $f->ID . '">' . $f->Label . '</label>';
                  $output .= '</span>';
                }
              break;
              
              /**
               *  ADDRESS
               *
               * @author Baylor Rae'
               */
              case 'address' :
                foreach ($field->SubFields as $id => $f) {
                  
                  switch ($f->Label) {
                    
                    case 'Street' :
                      $output .= '<span class="full addr1">';
                        $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="text" class="field text addr" />';
                        $output .= '<label for="' . $f->ID . '">Street Address</label>';
                      $output .= '</span>';
                    break;
                    
                    case 'Address Line 2' :
                      $output .= '<span class="full addr2">';
                        $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="text" class="field text addr" />';
                        $output .= '<label for="' . $f->ID . '">Line Address 2</label>';
                      $output .= '</span>';
                    break;
                    
                    case 'City' :
                      $output .= '<span class="left">';
                        $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="text" class="field text addr" />';
                        $output .= '<label for="' . $f->ID . '">City</label>';
                      $output .= '</span>';
                    break;
                    
                    case 'State' :
                      $output .= '<span class="right">';
                        $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="text" class="field text addr" />';
                        $output .= '<label for="' . $f->ID . '">State / Province / Region</label>';
                      $output .= '</span>';
                    break;
                    
                    case 'Zip' :
                      $output .= '<span class="left">';
                        $output .= '<input id="' . $f->ID . '" name="' . $f->ID . '" type="text" class="field text addr" />';
                        $output .= '<label for="' . $f->ID . '">Postal / Zip Code</label>';
                      $output .= '</span>';
                    break;
                    
                    case 'Country' :
                      $output .= '<span class="right">';
                        $output .= '<select id="' . $f->ID . '" name="' . $f->ID . '" class="field select addr">';
                          $output .= '<option value="" selected="selected"></option>';
                          $output .= WufooFields::list_countries();
                        $output .= '</select>';
                        $output .= '<label for="' . $f->ID . '">' . $f->Label . '</label>';
                      $output .= '</span>';
                    break;
                  }
                  
                }
              break;
              
              /**
               *  PHONE
               *
               * @author Baylor Rae'
               */
              case 'phone' :
                
                // Area Code
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '" name="' . $field->ID . '" type="text" size="3" maxlength="3" class="field text" />';
                  $output .= '<label for="' . $field->ID . '">###</label>';
                $output .= '</span>';
                $output .= '<span class="symbol">-</span>';
                
                // Part 2
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '-1" name="' . $field->ID . '-1" type="text" size="3" maxlength="3" class="field text" />';
                  $output .= '<label for="' . $field->ID . '-1">###</label>';
                $output .= '</span>';
                $output .= '<span class="symbol">-</span>';
                
                // Part 2
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '-2" name="' . $field->ID . '-2" type="text" size="4" maxlength="4" class="field text last"" />';
                  $output .= '<label for="' . $field->ID . '-2">####</label>';
                $output .= '</span>';
              break;
              
              /**
               *  MONEY
               *
               * @author Baylor Rae'
               */
              case 'money' :
              
                // Dollars
                $output .= '<span class="symbol">$</span>';
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '" name="' . $field->ID . '" type="text" size="10" class="field text currency" />';
                  $output .= '<label for="' . $field->ID . '">Dollars</label>';
                $output .= '</span>';
                $output .= '<span class="symbol decimal">.</span>';
                
                // Cents
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '-1" name="' . $field->ID . '-1" type="text" size="2" maxlength="2" class="field text" />';
                  $output .= '<label for="' . $field->ID . '-1">Cents</label>';
                $output .= '</span>';
                
              break;
              
              /**
               *  DATE
               *
               * @author Baylor Rae'
               */
               case 'date' :
               
                // Month
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '-1" name="' . $field->ID . '-1" type="text" size="2" maxlength="2" class="field text" />';
                  $output .= '<label for="' . $field->ID . '-1">MM</label>';
                $output .= '</span>';
                $output .= '<span class="symbol">/</span>';
                
                // Day
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '-2" name="' . $field->ID . '-2" type="text" size="2" maxlength="2" class="field text" />';
                  $output .= '<label for="' . $field->ID . '-2">DD</label>';
                $output .= '</span>';
                $output .= '<span class="symbol">/</span>';
                
                // Year
                $output .= '<span>';
                  $output .= '<input id="' . $field->ID . '" name="' . $field->ID . '" type="text" size="4" maxlength="4" class="field text year" />';
                  $output .= '<label for="' . $field->ID . '">YYYY</label>';
                $output .= '</span>';
               break;
               
               /**
                *  TIME
                *
                * @author Baylor Rae'
                */
                case 'time' :
                  
                  // Hours
                  $output .= '<span class="hours">';
                    $output .= '<input id="' . $field->ID . '" name="' . $field->ID . '" type="text" size="2" maxlength="2" class="field text" />';
                    $output .= '<label for="' . $field->ID . '">HH</label>';
                  $output .= '</span>';
                  $output .= '<span class="symbol minutes">:</span>';
                  
                  // Minutes
                  $output .= '<span class="minutes">';
                    $output .= '<input id="' . $field->ID . '-1" name="' . $field->ID . '-2" type="text" size="2" maxlength="2" class="field text" />';
                    $output .= '<label for="' . $field->ID . '-2">MM</label>';
                  $output .= '</span>';
                  $output .= '<span class="symbol seconds">:</span>';
                  
                  // Seconds
                  $output .= '<span class="seconds">';
                    $output .= '<input id="' . $field->ID . '-2" name="' . $field->ID . '-2" type="text" size="2" maxlength="2" class="field text" />';
                    $output .= '<label for="' . $field->ID . '-2">SS</label>';
                  $output .= '</span>';
                  
                  // AM/PM
                  $output .= '<span class="ampm">';
                    $output .= '<select id="' . $field->ID . '-3" name="' . $field->ID . '-3" class="field select">';
                      $output .= '<option selected="selected" value="AM">AM</option>';
                      $output .= '<option value="PM">PM</option>';
                    $output .= '</select>';
                    $output .= '<label for="' . $field->ID . '-3">AM/PM</label>';
                  $output .= '</span>';
                  
                break;
                
                /**
                 *  LIKERT
                 *
                 * @author Baylor Rae'
                 */
                case 'likert' :
                  
                  $output .= '<table cellspacing="0" class="widefat">';
                    $output .= '<caption id="' . $field->ID . '">' . $field->Title;
                    if( $field->IsRequired )
                      $output .= '<span class="req">*</span>';
                    $output .= '</caption>';
                    
                    $output .= '<thead>';
                      $output .= '<th>&nbsp;</th>';
                      foreach ($field->Choices as $choice) {
                        $output .= '<th>' . $choice->Label . '</th>';
                      }
                    $output .= '</thead>';
                    
                    $output .= '<tbody>';
                      foreach ($field->SubFields as $f) {
                        $output .= '<tr>';
                        $output .= '<th><label for="' . $f->Label . '">' . $f->Label . '</label></th>';
                        
                        foreach ($field->Choices as $choice) {
                          $output .= '<td><input id="' . $f->ID . '" name="' . $f->ID . '" type="radio" />';
                          $output .= '<span>' . $choice->Score . '</span></td>';
                        }
                        $output .= '</tr>';
                      }
                    $output .= '</tbody>';
                    
                  $output .= '</table>';
                  
                break;
              
            }
          $output .= '</div>';
          
          if( isset($field->ErrorText) )
            $output .= '<p class="field-error-text">' . $field->ErrorText . '</p>';
          
         $output .= '</li>';
         
    }
    
    return $output;
    
  }
}


?>