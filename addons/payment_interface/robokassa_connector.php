<?php

	class shopPkgPaymentInterfaceAddonRobokassaConnector extends shopPkgPaymentInterfaceAddonBaseConnector {
		
		protected $xml_interface_payment_state_url;
		
		protected $payment_url_base;
		protected $payment_password_1;
		protected $payment_password_2;
		protected $payment_merchant_login;
		protected $payment_currency_default;
		protected $payment_culture;
		protected $payment_culture_other;
		
		
		public function getName() {			
			return 'Робокасса';
		}
		
		public function loadSettings() {
			
			$this->test_mode = PAYMENT_TEST_MODE;
			
			$this->payment_url_base = $this->test_mode ? 'http://test.robokassa.ru/Index.aspx' : 'https://merchant.roboxchange.com/Index.aspx';
			$this->payment_password_1 = PAYMENT_PASS_1;
			$this->payment_password_2 = PAYMENT_PASS_2;
			$this->payment_merchant_login = PAYMENT_MERCHANT_LOGIN;
			$this->payment_currency_default = 'PCR';
			$this->payment_culture = 'ru';
			
			$this->xml_interface_payment_state_url = $this->test_mode ? 'http://test.robokassa.ru/WebService/Service.asmx/OpState' : 'https://merchant.roboxchange.com/WebService/Service.asmx/OpState';
			
		}
		
		
		public function getPaymentUrl($order, $description=null, $extra_params=array()) {
			
			$url = $this->payment_url_base;
			
			$signature = "$this->payment_merchant_login:$order->amount:$order->id:$this->payment_password_1";
			foreach ($extra_params as $k=>$v) {
				$signature .= ":shp$k=$v"; 	
			}			
			
			$params = array();
			$params['MrchLogin'] = $this->payment_merchant_login;
			$params['OutSum'] = $order->amount;
			$params['InvId'] = $order->id;
			$params['Desc'] = $description;
			//$params['IncCurrLabel'] = $this->payment_currency_default;
			$params['Culture'] = $this->payment_culture;
			$params['Email'] = $order->user_email;
			$params['SignatureValue'] = md5($signature);
			
			foreach ($extra_params as $k=>$v) {
				$params["shp$k"] = $v;	
			}
			
			$get = array();
			foreach ($params as $k=>$v) {
				$get[] = $k . '=' . rawurlencode($v);
			}
			
			$url .= '?' . implode('&', $get);
			
			return $url;
			
		}
		
		
		
		public function parseSuccessParams($log_title='SUCCESS URL', $use_pass=1) {
			
			$amount = Request::get('OutSum');
			$order_id = Request::get('InvId');
			
			$extra_params = array();
			foreach($_REQUEST as $k=>$v) {
				if (substr($k, 0, 3) != 'shp') continue;
				$extra_params[substr($k, 3)] = $v;
			}
			
			$signature = Request::get('SignatureValue');	
			$pass = $use_pass==1 ? $this->payment_password_1 : $this->payment_password_2;
			
			$expected_signature = "$amount:$order_id:$pass";
			foreach($extra_params as $k=>$v) {
				$expected_signature .= ":shp$k=$v";
			}
			
			$expected_signature = md5($expected_signature);			
			$signature_valid = strtolower($signature) === strtolower($expected_signature);
			
			$log[] = "Переход по $log_title";
			$log[] = "Сумма (OutSum): $amount";
			foreach ($extra_params as $k=>$v) {
				$log[] = "$k: $v";	
			}
			
			if ($signature_valid) {
				$log[] = "Подпись правильная";	
			}
			else {
				$log[] = "Подпись не прошла проверку";
			}
			
						
			$out = new stdClass();
			$out->order_id = $order_id;
			$out->amount_payed = $amount;
			$out->is_valid = $signature_valid;
			$out->extra_params = $extra_params;
			
			
			$this->writeLog($order_id, implode("\n", $log));
			
			return $out;			
		}
		
		
		public function parseFailParams() {
			return $this->parseSuccessParams('FAIL URL');
		}
		
		
		public function parseResultParams() {
			return $this->parseSuccessParams('RESULT URL', 2);
		}
		
		
		
		protected function getStatusCodeDescription($status_code) {
			$desc = array(
    			-5 => "Значение для параметра 'StateCode' не входит в диапазон допустимых значений",
    			1 => "Неверная цифровая подпись запроса",
    			3 => "Информация об операции с таким InvoiceID не найдена",
				5 => "Только инициирована, деньги не получены",
				10 => "Деньги не были получены, операция отменена",
				50 => "Деньги получены, ожидание решение пользователя о платеже",
				60 => "Деньги после получения были возвращены пользователю",				
				80 => "Исполнение операции приостановлено",
				100 => "Операция завершена успешно"
			);	
			
			return isset($desc[$status_code]) ? $desc[$status_code] : 'Неизвестный статус'; 
		}
		
		
		protected function getNewOrderStatus($status_code) {
			$translation = array(
				-5 => null,
				1 => null,
				3 => null,
				5 => 'processing',
				10 => 'canceled',
				50 => 'processing',
				60 => 'canceled',
				80 => 'processing',
				100 => 'payed'			
			);
			
			return isset($translation[$status_code]) ? $translation[$status_code] : null;
		}
		
		
		
		public function queryPaymentStatus($order) { 
			$ch = curl_init();
			
			$signature = md5("$this->payment_merchant_login:$order->id:$this->payment_password_2");
			// Для сервиса критично, чтобы данные были в виде url-encoded строки 
			$post_data = "MerchantLogin=$this->payment_merchant_login&InvoiceID=$order->id&Signature=$signature";

			// StateCode используется при работе с тестовым сервером
			// Это код состояния оплаты, который мы хотим получить
			if ($this->test_mode) $post_data .= "&StateCode=100";
			
			$options = array(
				CURLOPT_URL => $this->xml_interface_payment_state_url,
				CURLOPT_HEADER => false,
				CURLOPT_POST => true,				
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_POSTFIELDS => $post_data				
			);
			
			// В живом режиме используется HTTPS
			if (!$this->test_mode) {
				$options[CURLOPT_SSL_VERIFYPEER] = false;
				$options[CURLOPT_SSL_VERIFYHOST] = false;
			}
			
			curl_setopt_array($ch, $options);
			
			
			$xml = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curl_error = curl_error($ch);
			
			curl_close($ch);
			
			$out = new stdClass();
			$out->order_status = null;
			$out->raw_payment_info = array();			
			
			$log[] = "Запрос статуса платежа";
			if (!$xml || $http_code!=200 || $curl_error) {
				
				$log[] = "Запрос не удался.";
				$log[] = "HTTP $http_code";
				
				if ($curl_error) $log[] = "Ошибка CURL: $curl_error";
				
				if ($xml) {
					$log[] = "Получено:\n" . $xml;	
				}
				else {
					"Получен пустой ответ";
				}
				$this->writeLog($order->id, implode("\n", $log));
				
				$out->result = 'error';
				$out->message = 'Не удалось запросить статус платежа';
				return $out;
			}
			
			$data = coreXmlLibrary::getFromString($xml);
			
			if ($data === false) {
				$log[] = "Ошибка парсера: " . coreXmlLibrary::getLastError();
				$log[] = "Данные XML:\n" . $xml;
				$this->writeLog($order->id, implode("\n", $log));

				$out->result = 'error';
				$out->message = 'Не удалось запросить статус платежа';
				return $out;
			}
			
			//print_r($data);
			$result_code = (string)$data->Result->Code;			
			$state_code = isset($data->State) ? (string)$data->State->Code : 0;
			
			$code = $result_code ? $result_code : $state_code;
			$code_description = $this->getStatusCodeDescription($code);
			
			
			$log[] = "Получен код $code ($code_description)";
			
			if (in_array($code, array(-5, 1, 3))) {
				$out->result = 'error';
				$out->message = 'Не удалось запросить статус платежа';
			}
			else {
				$out->order_status = $this->getNewOrderStatus($code);
				$out->result = 'ok';
				$out->raw_payment_info = isset($data->Info) ? $this->collectPaymentInfo($data->Info) : array();
				
				$log[] = "RequestDate: " . (string)$data->State->RequestDate;
				$log[] = "StateDate: " . (string)$data->State->StateDate;
				foreach ($out->raw_payment_info as $k=>$v) $log[] = "$k: $v";
			}
			
			$this->writeLog($order->id, implode("\n", $log));
			
			return $out;
		
		}
		
		protected function collectPaymentInfo($info_node, $prefix = '') {			
			$out = array();
			foreach($info_node->children() as $name=>$node) {
				if(count($node->children())) {
					$children = $this->collectPaymentInfo($node, $name . '::');
					foreach($children as $c_name => $c) {
						$out[$c_name] = $c;
					}
				}
				else {
					$out[$prefix . $name] = (string)$node;
				}
			}
			return $out;
		}
		
	}
	
	
	
	
	