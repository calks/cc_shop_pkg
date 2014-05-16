<?php

	class shopPkgOrderEntity extends coreBaseEntity {
		
		public $user_id;
		public $created;
		public $status;
		public $payment_method;
		public $amount;
		public $user_name;
		public $user_family_name;
		public $user_email;
		
		
		public function __construct() {
			$this->items = array();
		}
		
		public function getTableName() {
			return 'order';
		}
		
		
		public function order_by() {
			return 'created DESC';
		}
		
		public function getStatusOptions() {
			return array(
				'new' => 'Новый',
				'waiting_payment' => 'Ожидание оплаты',
				'payed' => 'Оплачен',
				'failed' => 'Ошибка оплаты',
				'processing' => 'Обрабатывается',
				'canceled' => 'Отменен'
			);
		}
		
		public function save() {			
			if (!$this->created) $this->created = date("Y-m-d H:i:s");
			
			$is_new = !$this->id;			
			if ($is_new && !$this->items) return null;

			$id = parent::save();
			
			if ($is_new && $id) {
				if (!$this->saveItems()) {
					$this->delete();
					return null;
				}
			}
			
			return $id;
		}
		
		
		protected function saveItems() {
			
			$saved = array();
			$everything_ok = true;
			foreach ($this->items as $item) {
				if (!$everything_ok) continue;
				$item->order_id = $this->id;
				if ($item->save()) {
					$saved[] = $item;
				}
				else {
					$everything_ok = false;
				}
			}
			
			if (!$everything_ok) {
				foreach ($saved as $saved) {
					$saved->delete();
					$saved->order_id = null;
				}
			}
			
			return $everything_ok;
		}
		
		
		public function load_list($params=array()) {
			$table = $this->getTableName();
			$user = Application::getEntityInstance('user');
			$user_table = $user->getTableName();
			$user_alias = 't_' . md5(uniqid());
			
			$params['from'][] = "
				LEFT JOIN $user_table $user_alias ON $user_alias.id = $table.user_id
			";
			$params['fields'][] = "IF($user_alias.name IS NOT NULL, $user_alias.name, $table.user_name) AS user_name";
			$params['fields'][] = "IF($user_alias.family_name IS NOT NULL, $user_alias.family_name, $table.user_family_name) AS user_family_name";
			$params['fields'][] = "IF($user_alias.email IS NOT NULL, $user_alias.email, $table.user_email) AS user_email";
			
			$list = parent::load_list($params);
			
			$status_options = $this->getStatusOptions();
			foreach($list as $item) {
				$item->status_str = isset($status_options[$item->status]) ? $status_options[$item->status] : 'Неизвестен';
				$item->amount_str = $item->amount;  
			}
			
			$this->addPaymentMethodNames($list);
			
			return $list;
		}
		
		protected function addPaymentMethodNames(&$list) {
			if (!$list) return;
			
						
			$mapping = array();
			foreach ($list as $item) {
				$item->payment_method_name = $item->payment_method ? $item->payment_method : 'Не задан';				 
				if (!$item->payment_method) continue;
				if (!isset($mapping[$item->payment_method])) $mapping[$item->payment_method] = array();
				$mapping[$item->payment_method][] = $item;
			}
						
			foreach ($mapping as $payment_method=>$items) {				
				$connector = shopPkgHelperLibrary::getPaymentInterfaceConnector($payment_method);
				foreach($items as $item) $item->payment_method_name = $connector->getName();
			}
			
		}
		
		
	}
	
	
	
	