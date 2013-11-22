<?php

	class shopPkgCheckoutModule extends coreBaseModule {

		protected $task;
		
		protected $status;
		protected $content;
		protected $user_logged;
		
		protected function isAjaxRequest() {
			return isset($_POST['ajax']) && $_POST['ajax'] = 1;
		}
		
		public function run($params=array()) {

			$user_session = Application::getUserSession();
			$this->user_logged = $user_session->getUserAccount();
			
			if (!$this->user_logged) {
				Application::stackWarning("Для оформления заказа вы должны войти на сайт или зарегистрироваться");
				Redirector::redirect(Application::getSeoUrl("/login"));
			}
			
			if (!in_array(USER_ROLE_CONSUMER, $this->user_logged->roles)) {
				Application::stackWarning("Для оформления заказа необходимо зайти на сайт как покупатель");
				$user_session->logout();
				Redirector::redirect(Application::getSeoUrl("/login"));
			}
			
			
			$this->task = @array_shift($params);
			if (!$this->task) $this->task = 'review';
			
			$method_name = coreNameUtilsLibrary::underscoredToCamel('task_' . $this->task);
			if (method_exists($this, $method_name)) {
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
		
		
		protected function taskReview($params=array()) {
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->updateCalculatedData();
			
			$page_heading = "Ваш заказ";
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
			
			$smarty->assign('confirm_link', Application::getSeoUrl("/{$this->getName()}/confirm"));
			$smarty->assign('back_link', Application::getSeoUrl("/cart"));
			
			$template_path = $this->getTemplatePath($this->task);
			
			$this->content = $smarty->fetch($template_path);
		}
		
		
		protected function taskConfirm($params=array()) {
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->updateCalculatedData();
			
			if ($cart->isEmpty()) {
				Application::stackWarning("Корзина пуста");
				Redirector::redirect(Application::getSeoUrl("/cart"));
			}
			
			
			$order = $this->createNewOrder($cart);
			if ($order->save()) {
				$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
				$redirect_url = $payment_connector->getPaymentUrl($order, 'Заказ в интернет-магазине');
				Redirector::redirect($redirect_url);
			}
			else {
				Application::stackError("Не удалось сохранить заказ");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}"));
			}
		}
		
		
		protected function taskSuccess($params=array()) {
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			$response = $payment_connector->parseSuccessParams();

			if (!$response->is_valid) {
				Application::stackError("Недостоверные данные");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
			}
			
			$order_id = $response->order_id;
			$order = Application::getEntityInstance('order');
			$order = $order->load($order_id);
			
			if (!$order) {
				Application::stackError("Заказ не найден");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));				
			}
			
			$new_status = 'payed';
			$order_statuses = $order->getStatusOptions();
			$new_status_str = isset($order_statuses[$new_status]) ? $order_statuses[$new_status] : $new_status;
			
			$order->status = $new_status;
			if (!$order->save()) {
				Application::stackError("Не удалось перевести заказ в статус &laquo;$new_status_str&raquo;");
				die();
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
			}
			
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->clear();
			Application::stackMessage("Ваш заказ переведен в статус &laquo;$new_status_str&raquo;. Спасибо за покупку.");
			Redirector::redirect(Application::getSeoUrl("/profile/orders"));
		}
		
				
		protected function taskFail($params=array()) {
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			$response = $payment_connector->parseSuccessParams();

			if (!$response->is_valid) {
				Application::stackError("Недостоверные данные");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
			}
			
			$order_id = $response->order_id;
			$order = Application::getEntityInstance('order');
			$order = $order->load($order_id);
			
			if (!$order) {
				Application::stackError("Заказ не найден");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));				
			}
			
			$new_status = 'failed';
			$order_statuses = $order->getStatusOptions();
			$new_status_str = isset($order_statuses[$new_status]) ? $order_statuses[$new_status] : $new_status;
			
			$order->status = $new_status;
			if (!$order->save()) {
				Application::stackError("Не удалось перевести заказ в статус &laquo;$new_status_str&raquo;");
				die();
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
			}
			
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->clear();
			Application::stackMessage("Заказ не был оплачен и переведен в статус &laquo;$new_status_str&raquo;.");
			Redirector::redirect(Application::getSeoUrl("/profile/orders"));
		}

		
		protected function taskError($params=array()) {
			
		}
		
		
		protected function createNewOrder($cart) {
			$order = Application::getEntityInstance('order');
			
			$order->user_id = $this->user_logged->id;
			$order->status = 'new';
			$order->amount = $cart->getTotal();
			$order->user_name = $this->user_logged->name;
			$order->user_family_name = $this->user_logged->family_name;
			$order->user_email = $this->user_logged->email;
			
			foreach ($cart->getContent() as $cart_item) {
				$order->items[] = $this->createOrderItem($cart_item);
			}
			
			return $order;
		}
		
		protected function createOrderItem($cart_item) {
			$order_item = Application::getEntityInstance('order_item');
			
			$order_item->product_id = $cart_item->id;
			$order_item->product_title = $cart_item->title;
			$order_item->quantity = $cart_item->quantity;
			$order_item->price = $cart_item->price;
			$order_item->cost = $cart_item->cost;
			
			return $order_item;
		}
		
		
		
		
	}
	
	
	
	
	
	
	
	
	
	