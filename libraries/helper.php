<?php

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
		
		
		public static function getPaymentInterfaceConnector() {
			
			$connector_name='robokassa';
			
			$addon_name = $connector_name . '_connector';
			$addons_available = coreResourceLibrary::getAvailableFiles(APP_RESOURCE_TYPE_ADDON, 'payment_interface', "/$addon_name.php");
			
			if (!$addons_available) {
				die("No $connector_name interface connector");
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
			
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			
			foreach($order_or_array as $o) {
				$o->created_str = coreFormattingLibrary::formatDate($o->created);
				$o->amount_str = coreFormattingLibrary::formatCurrency($o->amount, ' руб.');
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
				$d->price_str = coreFormattingLibrary::formatCurrency($d->price, ' руб.');
				$d->cost_str = coreFormattingLibrary::formatCurrency($d->cost, ' руб.');
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
				if (!$item->product_id) continue;
				
				if (!isset($mapping[$item->product_id])) $mapping[$item->product_id] = array();
				$mapping[$item->product_id][] = $item;
			}
			
			if (!$mapping) return;
			
			$product_ids = implode(',', array_keys($mapping));
			$product = Application::getEntityInstance('product');
			$product_table = $product->getTableName();
			
			$product_params['where'][] = "$product_table.id IN($product_ids)"; 
			$product_params['where'][] = "$product_table.active=1";
			
			$product_list = $product->load_list($product_params);
			imagePkgHelperLibrary::loadImages($product_list, 'images');
			
			foreach($product_list as $p) {
				$image_id = isset($p->images[0]) ? $p->images[0]->id : 0;
				$thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 50, 50, 'crop', $image_id ? 'jpeg' : 'png');
				$catalog_link = Application::getSeoUrl("/catalog/$p->product_category_id/$p->id");
				foreach($mapping[$p->id] as $item) {
					$item->thumbnail = $thumbnail;
					$item->catalog_link = $catalog_link;
				}
				
			}
			
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	