<?php

require_once('WufooApiWrapperBase.php');
require_once('WufooValueObjects.php');

/**
 * API Main.
 * @author Timothy S Sabat
 */
class WufooApiWrapper extends WufooApiWrapperBase {
	
	protected $apiKey;
	protected $subdomain;
	protected $domain = 'wufoo.com';
	
	protected $curl;
	
	/**
	 * Constructor.  Ignore the domain, as this is needed only for local testing
	 *
	 * @param string $apiKey 
	 * @param string $subdomain 
	 * @param string $domain 
	 * @author Timothy S Sabat
	 */
	public function __construct($apiKey, $subdomain, $domain = 'wufoo.com') {
		$this->apiKey = $apiKey;
		$this->subdomain = $subdomain;
		$this->domain = $domain;		
	}
	
	/* -------------------------------
			  PUBLIC GET CALLS
	------------------------------- */
	
	/**
	 * Gets all permitted users.  See http://wufoo.com/docs/api/v3/users/
	 *
	 * @return array of User Value Objects by Hash
	 * @author Timothy S Sabat
	 */			
	public function getUsers() {
		$url = $this->getFullUrl('users');
		return $this->getHelper($url, 'User', 'Users');
	}
	
	/**
	 * Gets all forms permitted to user.
	 *
	 * @param string $formIdentifier can be the url or hash.  Remember, the URL changes with the form title, so it's best to use the hash.
	 * @return array of Form Value Objects by hash
	 * @author Timothy S Sabat
	 */
	public function getForms($formIdentifier = null) {
		$url = ($formIdentifier) ? $this->getFullUrl('forms/'.$formIdentifier) : $this->getFullUrl('forms');
		return $this->getHelper($url, 'Form', 'Forms');
	}
	
	/**
	 * Gets all fields for a given form or report by url or hash.  Remember, the URL changes with the form/report title, so it's best to use the hash.
	 *
	 * @param string $formIdentifier A URL or Hash
	 * @param string $from can be left as 'forms'.  The call getReportFields uses this parameter.
	 * @return WufooFieldCollection Value Object
	 * @author Timothy S Sabat
	 */
	public function getFields($formIdentifier, $from = 'forms') {
		$url = $this->getFullUrl($from.'/'.$formIdentifier.'/fields');
		$this->curl = new WufooCurl();
		$fields = json_decode($this->curl->getAuthenticated($url, $this->apiKey));
		$fieldHelper = new WufooFieldCollection();
		
		foreach ($fields->Fields as $field) {
			$fieldHelper->Fields[$field->ID] = new WufooField($field);
			$fieldHelper->Hash[$field->ID] = $field;
			if ($field->SubFields) {
				foreach ($field->SubFields as $subfield) {
					$fieldHelper->Hash[$subfield->ID] = $subfield;
				}
			}
		}
		return $fieldHelper;
	}
	
	/**
	 * Gets all entries from a given form or report by url or hash.  Remember, the URL changes with the form/report title, so it's best to use the hash.
	 *
	 * @param string $identifier a URL or Hash
	 * @param string $from can be left as 'forms'.  The call getReportFields uses this parameter.
	 * @return array of Form/Report Value Objects by hash.
	 * @author Timothy S Sabat
	 */
	public function getEntries($identifier, $from = 'forms') {
		$url = $this->getFullUrl($from.'/'.$identifier.'/entries');
		return $this->getHelper($url, 'Entry', 'Entries', 'EntryId');
	}
	
	/**
	 * Gets entry count for a given form or report by url or hash.  Remember, the URL changes with the form/report title, so it's best to use the hash.
	 *
	 * @param string $identifier a URL or Hash
	 * @param string $from can be left as 'forms'.  The call getReportFields uses this parameter.
	 * @return int entry count
	 * @author Timothy S Sabat
	 */
	public function getEntryCount($identifier, $from = 'forms') {
		$url = $this->getFullUrl($from.'/'.$identifier.'/entries/count');
		$this->curl = new WufooCurl();
		$countObject = json_decode($this->curl->getAuthenticated($url, $this->apiKey));
		return $countObject->EntryCount;
	}
	
  /**
    * Gets the entry count for a specific day.
    *  
    *
    * @param string $identifier a URL or Hash
    * @param string $from can be left as 'forms'.  The call getReportFields uses this parameter.
    * @return int today's entry count
    * @author Baylor Rae'
    */
	public function getEntryCountToday($identifier, $from ='forms') {
    $url = $this->getFullUrl($from.'/'.$identifier) . '?includeTodayCount=true';
    $this->curl = new WufooCurl();
    $countObject = json_decode($this->curl->getAuthenticated($url, $this->apiKey));
    // return $countObject->EntryCountToday;
    return $countObject->Forms[0]->EntryCountToday;
  }
	
	/**
	 * Gets all reports permitted to user.
	 *
	 * @param string $reportIdentifier can be the url or hash.  Remember, the URL changes with the report title, so it's best to use the hash.
	 * @return array of Report Value Objects by hash.
	 * @author Timothy S Sabat
	 */
	public function getReports($reportIdentifier) {
		$url = ($reportIdentifier) ? $this->getFullUrl('reports/'.$reportIdentifier) : $this->getFullUrl('reports');
		return $this->getHelper($url, 'Report', 'Reports');
	}
	
	/**
	 * Gets all widgets permitted to user.
	 *
	 * @param string string $reportIdentifier can be the url or hash.  Remember, the URL changes with the report title, so it's best to use the hash.
	 * @return array of Widget Value Objects by hash.
	 * @author Timothy S Sabat
	 */
	public function getWidgets($reportIdentifier) {
		$url = $this->getFullUrl('reports/'.$reportIdentifier.'/widgets');
		return $this->getHelper($url, 'Widget', 'Widgets');
	}
	
	/**
	 * Gets all fields for a given report by url or hash.  Notice this is a facade for getFields() call.  
	 *
	 * @param string $reportIdentifier can be the url or hash.  Remember, the URL changes with the report title, so it's best to use the hash.
	 * @return array of Field Value Objects by hash.
	 * @author Timothy S Sabat
	 */
	public function getReportFields($reportIdentifier) {
		return $this->getFields($reportIdentifier, 'reports');
	}
	
	/**
	 * Gets all entries for a given report by url or hash.  Notice this is a facade for getFields() call. 
	 *
	 * @param string $reportIdentifier can be the url or hash.  Remember, the URL changes with the report title, so it's best to use the hash.
	 * @return array of Entry Value Objects by EntryId.
	 * @author Timothy S Sabat
	 */
	public function getReportEntries($reportIdentifier) {
		return $this->getEntries($reportIdentifier, 'reports');
	}
	
	/**
	 * Gets entry count for a given report by url or hash.  Notice this is a facade for getEntryCount.
	 *
	 * @param string $reportIdentifier can be the url or hash.  Remember, the URL changes with the report title, so it's best to use the hash.
	 * @return array of Entry Value Objects by EntryId.
	 * @author Timothy S Sabat
	 */
	public function getReportEntryCount($reportIdentifier) {
		return $this->getEntryCount($reportIdentifier, 'reports');
	}
	
	/**
	 * Gets comments for a given form and (optionally) entry.	
	 *
	 * @param string $formIdentifier 
	 * @param string $entryId (optional).  If provided, narrows the filter to the entry id.
	 * @return array of Comment Value Objects by EntryId
	 * @author Timothy S Sabat
	 */
	public function getComments($formIdentifier, $entryId = null) {
		if ($entryId) {
			$url = $this->getFullUrl('forms/'.$formIdentifier.'/comments/'.$entryId);
		} else {
			$url = $this->getFullUrl('forms/'.$formIdentifier.'/comments');
		}
		return $this->getHelper($url, 'Comment', 'Comments', 'CommentId');
	}
	
	public function entryPost($formIdentifier, $wufooSubmitFields) {
		$url = $this->getFullUrl('forms/'.$formIdentifier.'/entries');
		$postParams = array();
		foreach ($wufooSubmitFields as $field) {
			$postParams[$field->getId()] = $field->getValue();
		}
		$curl = new WufooCurl();
		$response = $curl->postAuthenticated($postParams, $url, $this->apiKey);
		return new PostResponse($response);
	}
	
  // CHANGED I changed this function to work with my plugin. It does not work with the login section of Wufoo's API
	public function login($apiKey) {
    // $url = 'http://wufoo.com/api/v3/login/';
    $url = $this->getFullUrl('users');
    
    $curl = new WufooCurl();
    $response = $curl->getAuthenticated($url, $apiKey);
    return new PostResponse($response);
	}

}

?>