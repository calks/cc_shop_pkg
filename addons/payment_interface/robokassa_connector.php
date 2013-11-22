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
		
		
		public function loadSettings() {
			
			$this->test_mode = 1;
			
			$this->payment_url_base = $this->test_mode ? 'http://test.robokassa.ru/Index.aspx' : 'https://merchant.roboxchange.com/Index.aspx';
			$this->payment_password_1 = 'a722c63db8ec8625af6cf71cb8c2d939';
			$this->payment_password_2 = 'c1572d05424d0ecb2a65ec6a82aeacbf';
			$this->payment_merchant_login = 'cvt';
			$this->payment_currency_default = 'PCR';
			$this->payment_culture = 'ru';
			
			$this->xml_interface_payment_state_url = $this->test_mode ? 'http://test.robokassa.ru/WebService/Service.asmx/OpState' : 'https://merchant.roboxchange.com/WebService/Service.asmx/OpState';
			
		}
		
		public function getPaymentUrl($order, $description=null) {
			
			$url = $this->payment_url_base;
			
			$signature = "$this->payment_merchant_login:$order->amount:$order->id:$this->payment_password_1";
			
			$params = array();
			$params['MrchLogin'] = $this->payment_merchant_login;
			$params['OutSum'] = $order->amount;
			$params['InvId'] = $order->id;
			$params['Desc'] = $description;
			//$params['IncCurrLabel'] = $this->payment_currency_default;
			$params['Culture'] = $this->payment_culture;
			$params['Email'] = $order->user_email;
			$params['SignatureValue'] = md5($signature);
			
			$get = array();
			foreach ($params as $k=>$v) {
				$get[] = $k . '=' . rawurlencode($v);
			}
			
			$url .= '?' . implode('&', $get);
			
			return $url;
			
		}
		
		
		
		public function parseSuccessParams() {			
			$amount = Request::get('OutSum');
			$order_id = Request::get('InvId');
			
			$signature = Request::get('SignatureValue');			
			$expected_signature = md5("$amount:$order_id:$this->payment_password_1");
						
			$out = new stdClass();
			$out->order_id = $order_id;
			$out->amount_payed = $amount;
			$out->is_valid = $signature == $expected_signature;
			
			return $out;
			
		}
		
		
		public function parseFailParams() {
			return $this->parseSuccessParams();
		}
		
	}