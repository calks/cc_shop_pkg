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
		
		
		
		
		
		
		
		
		
	}
	
	