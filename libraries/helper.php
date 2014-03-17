<?php

	if (!defined('CURRENCY_LABEL')) {
		define('CURRENCY_LABEL', ' руб.');
	}


	class shopPkgHelperLibrary {
		
		public static function getCartInstance() {
			$cart_id = 'cart_' . md5(__FILE__);
			$cart = Application::getEntityInstance('cart');
			$existing = $cart->load($cart_id);
			
			if ($existing) {
				$existing->reloadContent();
				return $existing;	
			}
				
			$cart->id = $cart_id;
			$cart->save();
			
			return $cart;
		}
		
		
		public static function getPaymentInterfaceConnector($connector_name='robokassa') {
			
			$addon_name = $connector_name . '_connector';
			$addons_available = coreResourceLibrary::getAvailableFiles(APP_RESOURCE_TYPE_ADDON, 'payment_interface', "/$addon_name.php");
			
			if (!$addons_available) {
				throw new Exception("No $connector_name interface connector", 999);
				return null;
			}
			$file_path = coreResourceLibrary::getAbsolutePath($addons_available[$addon_name]->path);
			
			$class_name = $addons_available[$addon_name]->class;
			$connector = new $class_name();
			$connector->loadSettings();
			
			return $connector;
		}
		
		
		public static function prepareOrder(&$order_or_array) {
			if (!$order_or_array) return;
			
			$array_given = is_array($order_or_array);
			if (!$array_given) $order_or_array = array($order_or_array);
			
			foreach($order_or_array as $o) {
				$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector($o->payment_method);
				$o->created_str = coreFormattingLibrary::formatDate($o->created);
				$o->amount_str = coreFormattingLibrary::formatCurrency($o->amount, CURRENCY_LABEL);
				$o->pay_link = $o->status=='new' ? $payment_connector->getPaymentUrl($o) : null;
			}
			
			if (!$array_given) $order_or_array = array_shift($order_or_array);
		}
		
				
		public static function loadOrderItems(&$order_or_array) {
			if (!$order_or_array) return;

			$array_given = is_array($order_or_array);
			if (!$array_given) $order_or_array = array($order_or_array);
			
			$mapping = array();
			foreach($order_or_array as $o) {
				$o->items = array();				
				$mapping[$o->id] = $o;
			}
			
			$ids = implode(',', array_keys($mapping));
			
			$order_item = Application::getEntityInstance('order_item');
			$order_item_table = $order_item->getTableName();
			$params['where'][] = "$order_item_table.order_id IN($ids)"; 
			
			$data = $order_item->load_list($params);
			
			self::loadProductData($data);
			
			foreach($data as $d) {
				$d->price_str = coreFormattingLibrary::formatCurrency($d->price, CURRENCY_LABEL);
				$d->cost_str = coreFormattingLibrary::formatCurrency($d->cost, CURRENCY_LABEL);
				$mapping[$d->order_id]->items[] = $d;
			}
			
			if (!$array_given) $order_or_array = array_shift($order_or_array);
			
		}
		
		
		protected static function loadProductData(&$order_items) {
			if(!$order_items) return;
			
			$mapping = array();
			foreach($order_items as $item) {
				$item->thumbnail = imagePkgHelperLibrary::getThumbnailUrl(0, 50, 50, 'crop', 'png');
				$item->catalog_link = null;
				if (!$item->entity_id) continue;
				if (!$item->entity_name) continue;
				
				if (!isset($mapping[$item->entity_name])) $mapping[$item->entity_name] = array();
				if (!isset($mapping[$item->entity_name][$item->entity_id])) $mapping[$item->entity_name][$item->entity_id] = array();
				$mapping[$item->entity_name][$item->entity_id][] = $item;
			}
			
			if (!$mapping) return;
			
			foreach ($mapping as $entity_name => &$entities) {
			
				$ids = implode(',', array_keys($entities));
				$entity = Application::getEntityInstance($entity_name);
				$entity_table = $entity->getTableName();
				
				$entity_params['where'][] = "$entity_table.id IN($ids)";
				if ($entity_name == 'product') {
					$entity_params['where'][] = "$product_table.active=1";
				} 

				
				$list = $entity->load_list($entity_params);
				imagePkgHelperLibrary::loadImages($list, 'images');
				
				foreach($list as $p) {
					$image_id = isset($p->images[0]) ? $p->images[0]->id : 0;
					$thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 50, 50, 'crop', $image_id ? 'jpeg' : 'png');
					$catalog_link = $entity_name == 'product' ? Application::getSeoUrl("/catalog/$p->product_category_id/$p->id") : '';
					foreach($entities[$p->id] as $item) {
						$item->thumbnail = $thumbnail;
						$item->catalog_link = $catalog_link;
					}
					
				}
			}
			
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	