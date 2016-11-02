<?php

if(isset($_GET['source'])) {
    highlight_file(__FILE__);
    die;
}

/*
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
*
*  @author            CERDAN Yohann <cerdanyohann@yahoo.fr>
*  @copyright      (c) 2009  CERDAN Yohann, All rights reserved
*  @ version         12:38 04/08/2009
*/

class GoogleAnalyticsAPI
{
	/** Google account login (email) **/
	private $login;
	
	/** Google account password **/
	private $password;
	
	/** Google analytics website ID (avalaible on your google analytics account url) **/
	private $ids;
	
	/** The login token to the google analytics service **/
	private $loginToken;
	
	/** The XML response send by the service **/
	private $response;
	
	/** Begin date of the displaying datas **/
	private $date_begin;
	
	/** End date of the displaying datas **/
	private $date_end;
	
	/** Sort the results **/
	private $sort;
	
	/** The param to sort (metrics or dimensions) **/
	private $sort_param;
	
	/**
          * Class constructor
          * 
          * @param string $login the login (email)
          * @param string $password the password
          * @param string $ids the IDs of the website (find it in the google analytics gui)
          * @param string $date_begin the begin date
          * @param string $date_end the end date
          * 
          * @return void
          */
		  
	public function __construct($login,$password,$ids,$date_begin,$date_end = null)
	{
		$this->login = $login;
		$this->password = $password;
		$this->ids = $ids;
		$this->date_begin = $date_begin;
		
		if (!$date_end) {
			$this->date_end = $date_begin;
		} else {
			$this->date_end = $date_end;
		}
		
		$this->sort = "-";
		$this->sort_param = "metrics";
		
		// Authentication
		$this->login();
	}
	
	/**
          * Set the result's sort by metrics
          * 
          * @param boolean $sort asc or desc sort
          * 
          * @return void
          */
		  
	public function setSortByMetrics ($sort)
	{
		if ($sort==true) {
			$this->sort = "";
		} else {
			$this->sort = "-";
		}
		$this->sort_param = 'metrics';
	}
	
	/**
          * Set the result's sort by dimensions
          * 
          * @param boolean $sort asc or desc sort
          * 
          * @return void
          */
		  
	public function setSortByDimensions ($sort)
	{
		if ($sort==true) {
			$this->sort = "";
		} else {
			$this->sort = "-";
		}
		$this->sort_param = 'dimensions';
	}
	
	/**
          * Set the IDs of the website
          * 
          * @param string $ids the IDs of the website (find it in the google analytics gui)
          * 
          * @return void
          */
		  
	public function setIds($ids)
	{
		$this->ids = $ids;
	}
	
	/**
          * Set the date of the export
          * 
          * @param string $date_begin the begin date
          * @param string $date_end the end date
          * 
          * @return void
          */
		  
	public function setDate ($date_begin,$date_end = null)
	{
		$this->date_begin = $date_begin;
		
		if (!$date_end) {
			$this->date_end = $date_begin;
		} else {
			$this->date_end = $date_end;
		}
	}
	
	/**
          * Login to the google server
          * See : http://google-data-api.blogspot.com/2008/05/clientlogin-with-php-curl.html
          * 
          * @return void
          */
	
	private function login()
	{
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");  
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
		  
		$data = array('accountType' => 'GOOGLE',  
				  'Email' => $this->login,  
				  'Passwd' => $this->password,  
				  'source'=>'php_curl_analytics',  
				  'service'=>'analytics');  

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  
		curl_setopt($ch, CURLOPT_POST, true);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  

		$hasil = curl_exec($ch);  
		curl_close($ch);
		
		// Get the login token
		// SID=DQA...oUE  
		// LSID=DQA...bbo  
		// Auth=DQA...Sxq  
		if (preg_match('/Auth=(.*)$/',$hasil,$matches)>0) {
			$this->loginToken = $matches[1];
		} else {
			trigger_error('Authentication problem',E_USER_WARNING);
			return null;
		}
	}
	
	/**
           * Get URL content using cURL.
          * 
          * @param string $url the url 
          * 
          * @return string the html code
          */
		  
	function getContent ($url) 
	{
		if (!extension_loaded('curl')) {
            throw new Exception('curl extension is not available');
        }
		
		$ch = curl_init($url); 
		
		$header[] = 'Authorization: GoogleLogin auth=' . $this->loginToken;

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
		curl_setopt($ch, CURLOPT_HEADER, false); 
		  
		$this->response = curl_exec($ch); 
		$infos = curl_getinfo($ch);
		curl_close($ch);
		
		return $infos['http_code'];
	}
	
	/**
          * Get the google analytics datas by dimensions and metrics
          * See : http://code.google.com/intl/fr/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
          * 
          * @param string $metrics the metrics
          * @param string $dimensions the dimensions
          * 
          * @return array
          */
		  
	public function getDimensionByMetric($metrics, $dimensions)
	{
		$url = "https://www.google.com/analytics/feeds/data?ids=ga:" . $this->ids . "&metrics=ga:" . $metrics . "&dimensions=ga:" . $dimensions . "&start-date=" . $this->date_begin . "&end-date=" . $this->date_end ."&sort=" . $this->sort . "ga:";
		
		if ($this->sort_param=='metrics') { // sort by metrics
			$url .= $metrics;
		}
		
		if ($this->sort_param=='dimensions') { // sort by dimensions
			$url .= $dimensions;
		}

		if($this->getContent($url) == 200) {
			$XML_object = simplexml_load_string($this->response);
			$labels_array = array();
			$datas_array = array();
			
			foreach($XML_object->entry as $m) {
				$dxp = $m->children('http://schemas.google.com/analytics/2009');
				$metric_att = $dxp->metric->attributes();
				$dimension_att = $dxp->dimension->attributes();
				$labels_array []= $dimension_att['value'] . ' (' . $metric_att['value'] . ')';
				$datas_array  []= (string)$metric_att['value'];
			}
			
			return array('labels' => $labels_array, 'datas' => $datas_array);
		} else {
			return null;
		}
	}
	
	/**
          * Get the google analytics datas by metrics
          * See : http://code.google.com/intl/fr/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
          * 
          * @param string $metrics the metrics
          * @param string $uri the url of the website page (ex : /myurl/)
          * 
          * @return array
          */
		  
	public function getMetric($metric,$uri=null)
	{
		$url = "https://www.google.com/analytics/feeds/data?ids=ga:" . $this->ids . "&metrics=ga:" . $metric . "&start-date=" . $this->date_begin . "&end-date=" . $this->date_end;  

		if ($uri) {
			$url .= "&dimensions=ga:pagePath&filters=ga:pagePath%3D%3D" . $uri;
		}
		
		if($this->getContent($url) == 200) {
			$XML_object = simplexml_load_string($this->response);
			$dxp = $XML_object->entry->children('http://schemas.google.com/analytics/2009');
			if (@count($dxp)>0) {
				$metric_att = $dxp->metric->attributes();
			}
			return $metric_att['value'] ? (string)$metric_att['value'] : 0;
		} else {
			return null;
		}
	}
		
}

?>
