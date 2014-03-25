<?php

	class shopPkgCartContentsBlock extends coreBaseBlock {
	
		public function render() {
			
			$current_module = Router::getModuleName();
			if ($current_module == 'cart') return '';
			
			$smarty = Application::getSmarty();
			
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->updateCalculatedData();
			
			$items_count = $cart->getItemsCount();
			$items_count_noun = coreFormattingLibrary::getNounForNumber($items_count, 'товар', 'товара', 'товаров');
			$smarty->assign('cart_items_count', $items_count);
			$smarty->assign('cart_items_count_noun', $items_count_noun);
			
			$products_count = $cart->getItemsCount();
			$products_count_noun = coreFormattingLibrary::getNounForNumber($products_count, 'товар', 'товара', 'товаров');
			$smarty->assign('cart_products_count', $products_count);
			$smarty->assign('cart_products_count_noun', $products_count_noun);
			
			
			$smarty->assign('cart_subtotal', coreFormattingLibrary::formatCurrency($cart->getSubtotal(), CURRENCY_LABEL));
			$smarty->assign('cart_total', coreFormattingLibrary::formatCurrency($cart->getTotal(), CURRENCY_LABEL));
			$smarty->assign('cart_link', Application::getSeoUrl("/cart"));
			
			$template_path = $this->getTemplatePath();
			return $smarty->fetch($template_path);
		
		}
	
	}