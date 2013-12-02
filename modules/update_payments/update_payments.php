<?php

	class shopPkgUpdatePaymentsModule extends coreBaseModule {
		
		public function run($params=array()) {
			if (Application::getApplicationName() != 'cron') die();

			$orders = $this->getPendingOrders();
			
			if (!$orders) die();
			
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			
			foreach($orders as $order) {
				$payment_connector->writeLog($order->id, "Обновление статуса заказа по расписанию.");
				
				$current_order_status = $order->status; 
				$payment_status = $payment_connector->queryPaymentStatus($order);				
				if ($payment_status->result != 'ok') continue;

				$new_order_status = $payment_status->order_status;

				if ($current_order_status == $new_order_status) {
					$payment_connector->writeLog($order->id, "Заказ уже в статусе $current_order_status. Не меняем.");
					continue;
				}
				else {
					$order->status = $new_order_status;
					if ($order->save()) {
						$payment_connector->writeLog($order->id, "Статус заказа изменен на $new_order_status.");	
					}
					else {
						$payment_connector->writeLog($order->id, "Не удалось изменить статус заказа на $new_order_status.");
					}
				}
			}
		}
		
		
		protected function getPendingOrders() {
			$order = Application::getEntityInstance('order');
			$table = $order->getTableName();
			
			$params['where'][] = "$table.status IN('new', 'processing')";
			
			return $order->load_list($params);
		}
		
	}