<?php
//--------------------------------------------------------------
// Class: Markninja API
//--------------------------------------------------------------

class MPH_API {

	public $api_key;
	public $api_secret;
	public $cache_handler = null;
	public $was_cached    = false;
	public $no_ssl_verify = true; // disable ssl verification for curl
	public $timeout       = false;
	public $last_url;
	public $error;
	public $debug = false;

	// --- API Methods

	public function __construct($api_key=null, $api_secret=null, $debug=false) {
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->debug = $debug;
	}

	public function loadMeta($website, $id) {
		$homeUrls = parse_url($website);
		$host = $homeUrls['host'];
		$dateV = gmdate('Y-m-d H:i:s');

		$str = $host . "|" . $dateV . "|meta|wilson16|";
		$hash = hash('sha256', $str);

		$url = $website . '/wp-admin/admin-ajax.php?action=mph_meta&i=' . $id . '&h=' . $hash . '&dt=' . urlencode($dateV);
		echo $url . "\n";
		return json_decode($this->curl($url), true);
	}

	public function sendPost($website, $post=array()) {
		$homeUrls = parse_url($website);
		$host = $homeUrls['host'];
		$dateV = gmdate('Y-m-d H:i:s');

		$str = $host . "|" . $dateV . "|post|wilson16|" . $post['post_title'];
		$hash = hash('sha256', $str);


		$data = array(
			'p' => $post,
			'dt' => $dateV,
			'h' => $hash
		);

		$curlOpt = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($data),
		);

		$url = $website . '/wp-admin/admin-ajax.php?action=mph_insert';
		$response = $this->curl($url, $curlOpt);
		
		return json_decode($response, true);
	}

	// curlDownloadFile is the curl equivalent of simpleDownloadFile.
	private function curl($url, $opt = array()) {

		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_FOLLOWLOCATION => true,
		);

		$options = $options + $opt;

		$response = $this->curlExecute($options);
		return $response;
	}

	// curlExecute handles generic curl execution, for DRYing the two other
	// functions that rely on curl.
	private function curlExecute($options) {

		$strHeaders = array(
	        'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
	        'accept-encoding:gzip, deflate, br',
			'accept-language:en-US,en;q=0.8',
	        'user-agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
	    );

		$strHeaders = array(
		    "Host: www.financialstrend.com",
	        "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
	        "Accept: application/json, text/javascript, */*; q=0.01",
	        "Accept-Language: en-us,en;q=0.5",
	        "Accept-Encoding: gzip, deflate",
	        "Connection: keep-alive",
	        "X-Requested-With: XMLHttpRequest",
	        "Referer: http://www.financialstrend.com/"
        );

		$curl = curl_init();

		curl_setopt_array($curl, $options);

		// curl_setopt($curl, CURLOPT_HTTPHEADER, $strHeaders);
        // curl_setopt($curl, CURLOPT_HEADER, false);

        // curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIESESSION, false);

		$agent = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36";        
		curl_setopt($curl, CURLOPT_USERAGENT, $agent);

		/*
		$cookie_file = dirname(__FILE__) . '/cookie.txt';
		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEFILE,$cookie_file);
        */

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        /*
		curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		*/


		if ($this->timeout)	curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
		if ($this->no_ssl_verify)	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		// curl_setopt($ch, CURLOPT_VERBOSE, true);

		$response = curl_exec($curl);
		$error = curl_error($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		curl_close($curl);

		if ($http_code == "404") {
			$response = false;
			$this->error = "Invalid URL";
		}
		else if ($error) {
			$response = false;
			$this->error = $error;
		}

		// echo $error;
		// echo $response;


		return $response;
	}
}
