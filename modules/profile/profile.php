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
			
		}
		
	}