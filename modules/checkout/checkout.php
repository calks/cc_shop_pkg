<?php

	class shopPkgCheckoutModule extends coreBaseModule {

		protected $task;
		
		protected $status;
		protected $content;
		protected $user_logged;		
		
		protected $payment_response;
		protected $order;
		
		protected function isAjaxRequest() {
			return isset($_POST['ajax']) && $_POST['ajax'] = 1;
		}
		
		public function run($params=array()) {

			$user_session = Application::getUserSession();
			$this->user_logged = $user_session->getUserAccount();

			$this->task = @array_shift($params);
			if (!$this->task) $this->task = 'review';
			
			$back_url = Application::getSeoUrl("/{$this->getName()}/$this->task");
			$login_redirect_url = Application::getSeoUrl("/login?back=" . rawurlencode($back_url));
			
			$authorization_needed = $this->task != 'result_listener'; 
			
			if ($authorization_needed && !$this->user_logged) {
				Application::stackWarning("Для оформления заказа вы должны войти на сайт или зарегистрироваться");
				Redirector::redirect($login_redirect_url);
			}
			
			if ($authorization_needed && !in_array(USER_ROLE_CONSUMER, $this->user_logged->roles)) {
				Application::stackWarning("Для оформления заказа необходимо зайти на сайт как покупатель");
				$user_session->logout();				
				Redirector::redirect($login_redirect_url);
			}
			
			
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
			
			
			$this->order = $this->createNewOrder($cart);
			if ($this->order->save()) {
				$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
				$payment_url = $payment_connector->getPaymentUrl($this->order, 'Заказ в интернет-магазине #' . $this->order->id);
				$payment_connector->writeLog($this->order->id, "Пользователь отправлен на оплату \n $payment_url");
				Redirector::redirect($payment_url);				
			}
			else {
				Application::stackError("Не удалось сохранить заказ");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}"));
			}
		}
		
		protected function processPaymentReturnUrl($url_type) {
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			
			switch ($url_type) {
				case 'success':
					$this->payment_response = $payment_connector->parseSuccessParams();
					break;
				case 'fail':
					$this->payment_response = $payment_connector->parseFailParams();
					break;
				default:
					Application::stackError("Внутренняя ошибка");
					Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
			}
			

			if (!$this->payment_response->is_valid) {
				Application::stackError("Недостоверные данные");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
			}
			
			$order_id = $this->payment_response->order_id;
			$this->order = Application::getEntityInstance('order');
			$this->order = $this->order->load($order_id);
			
			if (!$this->order) {
				Application::stackError("Заказ не найден");
				Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));				
			}
		}
		
		protected function taskSuccess($params=array()) {
			$this->processPaymentReturnUrl('success');
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			
			if ($this->successChangeOrder()) {
				$payment_connector->writeLog($this->order->id, "Статус заказа изменен на {$this->order->status}.");
				return $this->successShowResult();	
			}
			else {
				$payment_connector->writeLog($this->order->id, "Не удалось изменить статус заказа на {$this->order->status}.");
				return $this->successShowError();
			}
		}
		
		protected function successChangeOrder() {
			$new_order_status = $this->order->status; 
			
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			$payment_status = $payment_connector->queryPaymentStatus($this->order);
			// если у нас получилось запросить статус заказа асинхронно...
			if ($payment_status->result == 'ok') {
				if ($payment_status->order_status) {
					$new_order_status = $payment_status->order_status;					
				}	
			}
			// если не получилось, считаем, что заказ оплачен 
			// (к этому моменту мы уже знаем, что на suceess url пришли достоверные данные)
			else {
				$new_order_status = 'payed';
			}
			
			// если статус заказа изменился, сохраняем
			if ($new_order_status != $this->order->status) {
				$this->order->status = $new_order_status;
				return $this->order->save();					
			}
			// если нет, то ничего не делаем
			else {
				$payment_connector->writeLog($this->order->id, "Заказ в статусе $new_order_status. Не меняем.");
				return $this->order->id;
			}
		}
		
		
		protected function successShowResult() {
			$cart = shopPkgHelperLibrary::getCartInstance();
			$cart->clear();
			
			$new_status = $this->order->status;			
			Application::stackMessage($this->getUserMessage($new_status));
			Redirector::redirect(Application::getSeoUrl("/profile/orders"));
		}
		
		
		protected function getUserMessage($order_status) {
			$order_statuses = $this->order->getStatusOptions();
			$order_status_str = isset($order_statuses[$order_status]) ? $order_statuses[$order_status] : $order_status;
			
			switch ($order_status) {
				case 'payed':
					return "Ваш заказ переведен в статус &laquo;$order_status_str&raquo;. Спасибо за покупку.";
				case 'processing':
					return "Ваш заказ переведен в статус &laquo;$order_status_str&raquo;. Он будет доступен, как только мы получим подтверждение оплаты.";
				default:
					return "Ваш заказ переведен в статус &laquo;$order_status_str&raquo;.";
			}
		}
		
		protected function successShowError() {
			$new_status = $this->order->status;
			$order_statuses = $order->getStatusOptions();
			$new_status_str = isset($order_statuses[$new_status]) ? $order_statuses[$new_status] : $new_status;
						
			Application::stackError("Не удалось перевести заказ в статус &laquo;$new_status_str&raquo;");				
			Redirector::redirect(Application::getSeoUrl("/{$this->getName()}/error"));
		}
		
		
		protected function taskFail($params=array()) {
			
			$this->processPaymentReturnUrl('fail');
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			
			if ($this->failChangeOrder()) {
				$payment_connector->writeLog($this->order->id, "Статус заказа изменен на {$this->order->status}.");
				return $this->failShowResult();	
			}
			else {
				$payment_connector->writeLog($this->order->id, "Не удалось изменить статус заказа на {$this->order->status}.");
				return $this->failShowError();
			}
		}
		
		protected function taskError($params=array()) {
			
		}
		
		protected function failChangeOrder() {
			$new_order_status = $this->order->status; 
			
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			$payment_status = $payment_connector->queryPaymentStatus($this->order);
			// если у нас получилось запросить статус заказа асинхронно...
			if ($payment_status->result == 'ok') {
				if ($payment_status->order_status) {
					$new_order_status = $payment_status->order_status;					
				}	
			}
			// если не получилось, считаем, что оплата заказа провалена  
			// (к этому моменту мы уже знаем, что на fail url пришли достоверные данные)
			else {
				$new_order_status = 'canceled';
			}
			
			// если статус заказа изменился, сохраняем
			if ($new_order_status != $this->order->status) {
				$this->order->status = $new_order_status;
				return $this->order->save();	
			}
			// если нет, то ничего не делаем
			else {
				$payment_connector->writeLog($this->order->id, "Заказ в статусе $new_order_status. Не меняем.");
				return $this->order->id;
			}
		}
		
		
		protected function failShowResult() {
			$new_status = $this->order->status;			
			Application::stackMessage($this->getUserMessage($new_status));
			Redirector::redirect(Application::getSeoUrl("/profile/orders"));
		}
		
		
		protected function failShowError() {
			return $this->successShowError();
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
		
		
		protected function taskResultListener($params=array()) {
			if (!Request::isPostMethod()) die();
			$payment_connector = shopPkgHelperLibrary::getPaymentInterfaceConnector();
			$this->payment_response = $payment_connector->parseResultParams();

			if (!$this->payment_response->is_valid) die('signature does not match');
			
			$order_id = $this->payment_response->order_id;
			$this->order = Application::getEntityInstance('order');
			$this->order = $this->order->load($order_id);
			
			if (!$this->order) die('order not found');
			
			if ($this->successChangeOrder()) {
				$payment_connector->writeLog($this->order->id, "Статус заказа изменен на {$this->order->status}.");
			}
			
			die("OK$order_id\n");
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	