<?php

	class shopPkgProfileModule extends coreProfileModule {
		
		protected function getMenuItems() {
			$items = parent::getMenuItems();
			
			$out = array();
			
			foreach ($items as $code => $item) {
				if ($code == 'logout') {
					$out['orders'] = array(
						'name' => 'Заказы',
						'link' => Application::getSeoUrl("/{$this->getName()}/orders"),
						'active' => $this->task == 'orders'
					);
				}
				$out[$code] = $item;
			}
			
			
			return $out;			
		}

		
		protected function taskOrders($params=array()) {
			$order_id = (int)@array_shift($params);
			$order = Application::getEntityInstance('order');
			$order_table = $order->getTableName(); 
			
			$smarty = Application::getSmarty();
			
			if ($order_id) {
				$order = $order->load($order_id);
				if (!$order || $order->user_id != $this->user->id) {
					Application::stackError("Заказ не найден");
					Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/orders"));
				}
				
				shopPkgHelperLibrary::prepareOrder($order);
				shopPkgHelperLibrary::loadOrderItems($order);
				$smarty->assign('order', $order);
				$smarty->assign('back_link', Application::getSeoUrl("/{$this->getName()}/orders"));
			}
			else {				
				$order_params[] = "$order_table.user_id={$this->user->id}";
				$order_list = $order->load_list($order_params);
				
				shopPkgHelperLibrary::prepareOrder($order_list);
				
				foreach($order_list as $o) {
					$o->link = Application::getSeoUrl("/{$this->getName()}/orders/$o->id");
				}
				
				$smarty->assign('order_list', $order_list);
			}
			
			$order = Application::getEntityInstance('order');
			
		}
		
		
		
	}
	
	
	
	
	
	
	
	