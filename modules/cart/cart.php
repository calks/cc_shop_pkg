<?php

	class shopPkgCartModule extends coreBaseModule {

		protected $task;
		
		protected $status;
		protected $content;
		
		
		protected function isAjaxRequest() {
			return isset($_POST['ajax']) && $_POST['ajax'] = 1;
		}
		
		public function run($params=array()) {
			
			shopPkgHelperLibrary::getPaymentInterfaceConnector('robokassa');
			
			$this->task = @array_shift($params);
			if (!$this->task) $this->task = 'list_content';
			
			$method_name = coreNameUtilsLibrary::underscoredToCamel('task_' . $this->task);
			if (method_exists($this, $method_name)) {
				$this->setContinueLink();
				call_user_func(array($this, $method_name), $params);
			} 
			else {
				return $this->terminate();
			}
			
			if ($this->isAjaxRequest()) {				
				die(json_encode($this->getAjaxResponse()));
			}
			else {				
				$smarty = Application::getSmarty();
				$smarty->assign('content', $this->content);
				$smarty->assign('message_stack_block', Application::getBlock('message_stack'));
				
				$template_path = $this->getTemplatePath();				
				return $smarty->fetch($template_path);
			}
		}
		
		
		protected function getAjaxResponse() {
			$status = 'ok';
			if ($this->warnings) $status = 'warning';
			if ($this->errors) $status = 'error';
			
			$message_stack = Application::getMessageStack();
			
			$out = array(
				'status' => $status,
				'errors' => $message_stack->getTexts('error'),
				'warnings' => $message_stack->getTexts('warning'),
				'messages' => $message_stack->getTexts('message')
			);
			
			$message_stack->clear();
			
			if ($this->content) $out['content'] = $this->content;			
		}
		
		
		protected function terminate() {
			if ($this->isAjaxRequest()) {
				Application::stackError('Ошибка в запросе');
				die(json_encode($this->getAjaxResponse()));
			}
			else {
				return parent::terminate();
			}
		}
		
		
		
		protected function taskListContent($params=array()) {
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->updateCalculatedData();
			
			$page_heading = "Корзина";
			$page_content = "";
			
			$document = corePagePropertiesHelperLibrary::getDocument();			
			if ($document) {
				$page_heading = $document->title;
				$page_content = $document->content;
			}
			
			$smarty = Application::getSmarty();
			
			$smarty->assign('page_heading', $page_heading);
			$smarty->assign('page_content', $page_content);
			
			
			$smarty->assign('cart_content', $cart->getContent());
			$subtotal_str = coreFormattingLibrary::formatCurrency($cart->getSubtotal(), ' руб.');
			$smarty->assign('cart_subtotal_str', $subtotal_str);
			
			$smarty->assign('checkout_link', Application::getSeoUrl("/checkout"));
			$smarty->assign('continue_link', $this->getContinueLink());
			
			$template_path = $this->getTemplatePath($this->task);
			$this->content = $smarty->fetch($template_path);
		}
		
		
		protected function taskAdd($params=array()) {
			$product_id = (int)array_shift($params);
			$product = Application::getEntityInstance('product');
			$product = $product->load($product_id);
			
			if (!$product) {
				Application::stackError("Товар не найден");				
			}
			else {			
				$cart = shopPkgHelperLibrary::getCartInstance();
				$cart->add($product_id);
				Application::stackMessage("Товар &laquo;$product->title&raquo; добавлен в корзину");
			}
						
			if (!$this->isAjaxRequest()) {
				Redirector::redirect(Application::getSeoUrl("/" . $this->getName()));
			};			
		}
		
		
		
		protected function taskRemove($params=array()) {
			$product_id = (int)array_shift($params);
			$product = Application::getEntityInstance('product');
			$product = $product->load($product_id);
			
			if (!$product) {
				Application::stackError("Товар не найден");				
			}			
			else {			
				$cart = shopPkgHelperLibrary::getCartInstance();
				if ($cart->isInCart($product_id)) {
					$cart->remove($product_id);
					Application::stackMessage("Товар &laquo;$product->title&raquo; убран из корзины");
				}
				else {
					Application::stackWarning("Товара &laquo;$product->title&raquo; и так не было в корзине");
				}
			}
						
			if (!$this->isAjaxRequest()) {
				Redirector::redirect(Application::getSeoUrl("/" . $this->getName()));
			};			
			
		}
		
		
		protected function taskUpdate($params=array()) {
			
		}
		

		protected function getContinueLinkSessionKey() {
			return 'continue_' . md5(__FILE__);
		}
		
		
		protected function setContinueLink() {
			
			$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			if (!$referer) return;
			
			if (!preg_match('/^http(s)?:\/\/(?<domain>[a-z0-9\.\-_]+)\/(?<path>.*)/is', $referer, $matches)) return;
			
			$domain = isset($matches['domain']) ? $matches['domain'] : '';
			$path = isset($matches['path']) ? $matches['path'] : '';
			
			if ($domain != $_SERVER['HTTP_HOST']) return;
			
			if (!$path) {
				$continue_link = '/';
			}
			else {
				$module_name = array_shift(explode('/', $path));				
				if (!in_array($module_name, array('cart', 'checkout'))) {
					$continue_link = $referer;
				}
				else {
					$continue_link = null;
				}
			}
			
			if ($continue_link) {
				$session_key = $this->getContinueLinkSessionKey();
				$_SESSION[$session_key] = $continue_link;
			}

		}
			
		protected function getContinueLink() {
			$session_key = $this->getContinueLinkSessionKey();
			return isset($_SESSION[$session_key]) ? $_SESSION[$session_key] : '';
		}
		
	}
	
	
	
	
	
	
	
	
	
	