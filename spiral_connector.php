<?php

class spiralConnector
{
	const APIURL = "http://www.pi-pe.co.jp/api/locator";
	
	private  $apitoken;
	private  $apisecret;
	

	// ====================================
	//
	//  constract
	//
	// ====================================
	public function __construct($apitoken, $apisecret)
	{
		$this -> apitoken = $apitoken;
		$this -> apisecret = $apisecret;
		
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: locator/apiserver/request",
			"Content-Type: application/json; charset=UTF-8",
			);
				
		// 送信するJSONデータを作成
		$parameters = array();
		$parameters["spiral_api_token"] = $this -> apitoken;  //トークン
				
		// 送信用のJSONデータを作成します。
		$json = json_encode($parameters);
				
		// curlライブラリを使って送信します。
		$curl = curl_init(self::APIURL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST          , true);
		curl_setopt($curl, CURLOPT_POSTFIELDS    , $json);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER    , $api_headers);
		curl_exec($curl);
		
		// エラーがあればエラー内容を表示
		if (curl_errno($curl)) echo curl_error($curl);
		
		$response = curl_multi_getcontent($curl);
		curl_close($curl);
		
		$response = json_decode($response, true);
		// サービス用のURL (ロケータから取得できます)
		$this -> location = $response["location"];
	}
	
	
	// ====================================
	//
	//  send request
	//
	// ====================================
	public function sendRequest($api_headers, $parameters)
	{
		
		// 送信するJSONデータを作成
		$parameters["spiral_api_token"] = $this -> apitoken;       //トークン
		$parameters["passkey"]          = time();       //エポック秒
						
		// 署名を付けます
		$key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
		$parameters["signature"] = hash_hmac('sha1', $key, $this -> apisecret, false);
		
		
		// 送信用のJSONデータを作成します。
		$json = json_encode($parameters);
		
		
		// curlライブラリを使って送信します。
		$curl = curl_init($this -> location);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST      , true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
		curl_exec($curl);
		if (curl_errno($curl)) echo curl_error($curl);
		
		$response = curl_multi_getcontent($curl);
		
		curl_close($curl);
		return $response;
	}
	
	
	// ====================================
	//
	//  select database
	//
	// ====================================
	public function selectData($db_title, $columns, $condition = null, $sort = null, $group_by = false, $lines_per_page = "", $page = "", $format = null)
	{
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: database/select/request",
			"Content-Type: application/json; charset=UTF-8",
			);
		
		
		// 送信するJSONデータを作成
		$parameters = array();
		$parameters["db_title"]         = $db_title;
		$parameters["select_columns"]   = $columns;
		if($condition != null)
		{
			$parameters["search_condition"] = $condition;
		}
		if($sort != null)
		{
			$parameters["sort"] = $sort;
		}
		if($group_by == true)
		{
			$parameters["group_by"]         = $columns;
		}
		$parameters["lines_per_page"]   = $lines_per_page;
		$parameters["page"]             = $page;
		if($format != null)
		{
			$parameters["data_format"] = $format;
		}

		$response = $this -> sendRequest($api_headers, $parameters);
		$response = json_decode($response, true);
		//print_r($response);
		if($response['message'] == 'OK')
		{
			foreach($response['data'] as $key => $val)
			{
				for($i=0; $i<count($columns); $i++)
				{
					$response['data'][$key][$columns[$i]] = $response['data'][$key][$i];
				}
			}
			
		}

		return $response;
	}
	

	// ====================================
	//
	//  select express2
	//
	// ====================================
	public function selectExpress2($condition = null, $lines_per_page = "", $page = "")
	{
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: deliver_express2/list/request",
			"Content-Type: application/json; charset=UTF-8",
			);
		
		
		// 送信するJSONデータを作成
		$parameters = array();
		$parameters["search_condition"] = $condition;
		$parameters["lines_per_page"]   = $lines_per_page;
		$parameters["page"]             = $page;
				
		$response = $this -> sendRequest($api_headers, $parameters);
		$response = json_decode($response, true);
		if($response['message'] == 'OK')
		{
			foreach($response['data'] as $key => $val)
			{
				for($i=0; $i<count($response['header']); $i++)
				{
					$response['data'][$key][$response['header'][$i]] = $response['data'][$key][$i];
				}
			}
			
		}
		return $response;
	}
	
	// ====================================
	//
	//  insert database
	//
	// ====================================
	public function insertData($db_title, $data)
	{
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: database/insert/request",
			"Content-Type: application/json; charset=UTF-8",
			);
		
		
		// 送信するJSONデータを作成
		$parameters = array();
		$parameters["db_title"]         = $db_title;
		$parameters["data"]             = $data;
		
		$response = $this -> sendRequest($api_headers, $parameters);
		$response = json_decode($response, true);

		return $response;
	}


	
	// ====================================
	//
	//  update database
	//
	// ====================================
	public function updateData($db_title, $data, $condition = null)
	{
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: database/update/request",
			"Content-Type: application/json; charset=UTF-8",
			);
		
		
		// 送信するJSONデータを作成
		$parameters = array();
		$parameters["db_title"]         = $db_title;
		$parameters["data"]             = $data;
		$parameters["search_condition"] = $condition;
		
		$response = $this -> sendRequest($api_headers, $parameters);
		$response = json_decode($response, true);

		return $response;
	}
	

	
	// ====================================
	//
	//  myareaLogin
	//
	// ====================================
	public function myareaLogin($myarea_title, $id, $password)
	{
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: area/login/request",
			"Content-Type: application/json; charset=UTF-8",
			);

		// リクエストデータを作成
		$parameters = array();
		$parameters["my_area_title"]    = $myarea_title;
		$parameters["id"]               = $id; //会員識別キー（メールアドレス）
		$parameters["password"]         = $password; //パスワード
		$parameters["url_type"]         = "1";
		$parameters["auto_login"]		= "T"; // 自動ログインする
		
		$response = $this -> sendRequest($api_headers, $parameters);
		$response = json_decode($response, true);

		return $response;
	}
	
	

	
	// ====================================
	//
	//  tableCard
	//
	// ====================================
	public function tableCard($myarea_title, $card_title, $jsessionid, $ids)
	{
		// API用のHTTPヘッダ
		$api_headers = array(
			"X-SPIRAL-API: table/card/request",
			"Content-Type: application/json; charset=UTF-8",
			);

		// リクエストデータを作成
		$parameters = array();
		$parameters["my_area_title"]    = $myarea_title;
		$parameters["card_title"]       = $card_title;
		$parameters["jsessionid"]       = $jsessionid;
		$parameters["ids"]              = $ids;
		$parameters["url_type"]			= 2;
		
		$response = $this -> sendRequest($api_headers, $parameters);
		$response = json_decode($response, true);

		return $response;
	}
}

?>