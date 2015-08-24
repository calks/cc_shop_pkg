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
		
		
		public static function getPaymentInterfaceConnector($connector_name='cash') {
			if (!$connector_name) {
				throw new Exception("No $connector_name supplied", 999);
			}

			$connector_class = coreResourceLibrary::getEffectiveClass('payment_connector', $connector_name);
						
			if (!$connector_class) {
				throw new Exception("No $connector_name interface connector", 999);
				return null;
			}

			$connector = new $connector_class();
			$connector->loadSettings();
			
			return $connector;
		}
		
		
		public static function prepareOrder(&$order_or_array) {
			if (!$order_or_array) return;
			
			$array_given = is_array($order_or_array);
			if (!$array_given) $order_or_array = array($order_or_array);
			
			foreach($order_or_array as $o) {
				$o->created_str = coreFormattingLibrary::formatDate($o->created);
				$o->amount_str = coreFormattingLibrary::formatCurrency($o->amount);
				if (!$o->payment_method) continue;
				$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector($o->payment_method);
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
				$d->price_str = coreFormattingLibrary::formatCurrency($d->price);
				$d->cost_str = coreFormattingLibrary::formatCurrency($d->cost);
				$mapping[$d->order_id]->items[] = $d;
			}
			
			if (!$array_given) $order_or_array = array_shift($order_or_array);
			
		}
		
		
		protected static function loadProductData(&$order_items) {
			if(!$order_items) return;
			
			$mapping = array();
			foreach($order_items as $item) {
				$item->thumbnail = imagePkgHelperLibrary::getThumbnailUrl(0, 90, 90, 'crop', 'png');
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
					$entity_params['where'][] = "$entity_table.active=1";
				} 

				
				$list = $entity->load_list($entity_params);
				imagePkgHelperLibrary::loadImages($list, 'image');
				
				foreach($list as $p) {
					$image_id = isset($p->image_list[0]) ? $p->image_list[0]->id : 0;
					$thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 90, 90, 'crop', $image_id ? 'jpeg' : 'png');
					
					$catalog_link = $entity_name == 'product' ? self::getProductCatalogLink($p) : '';
					//$catalog_link = $entity_name == 'product' ? Application::getSeoUrl("/catalog/$p->product_category_id/$p->id") : '';
					
					
					foreach($entities[$p->id] as $item) {
						$item->thumbnail = $thumbnail;
						$item->catalog_link = $catalog_link;
					}
					
				}
			}
			
		}
		
		
		public static function getProductCatalogLink($product) {
			/*$category = Application::getEntityInstance('product_category');
			$parents = $category->getAllParents($product->product_category_id);
			$out[] = 'catalog';
			foreach ($parents as $p) $out[] = "$p->id";
			$out[] = $product->product_category_id;
			$out[] = $product->id;
			
			return Application::getSeoUrl('/' . implode('/', $out));*/
			
			return Application::getSeoUrl("/catalog/product/$product->id");
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	