<?php

	class shopPkgOrderManagementModule extends coreCrudBaseModule {
		
		protected $for_user;
				
		protected function getObjectName() {
			return 'order';
		}		
		
		
		public function run($params = array()) {
			
			$for_user_id = (int)Request::get('user_id');
			if ($for_user_id) {
				$user = Application::getEntityInstance('user');
				$user = $user->load($for_user_id);
				if ($user) {
					$this->for_user = $user;
					$this->url_addition .= strpos($this->url_addition, '?') === false ? '?' : '&';
					$this->url_addition .= "user_id=$for_user_id";
				}
			}
			
			return parent::run($params);
		}
		
		
		protected function beforeListLoad(&$load_params) {
			$smarty = Application::getSmarty();
			
			$filter = Application::getFilter($this->getObjectName());
			$smarty->assign('filter', $filter);
			$filter->set_params($load_params);
			
			if ($this->for_user) {			
				$entity = Application::getEntityInstance($this->getObjectName());
				$table = $entity->getTableName();
				$load_params['where'][] = "$table.user_id={$this->for_user->id}";				
				$smarty->assign('subtitle', "Заказы пользователя {$this->for_user->name} {$this->for_user->family_name}");
			}
			
			
		}
		
		protected function afterListLoad(&$list) {			
			shopPkgHelperLibrary::prepareOrder($list);
			
			foreach ($list as $item) {
				$item->user_link = $item->user_id ? Application::getSeoUrl("/user?action=edit&ids[]=$item->user_id") : '';
			}
		}
		
		
		protected function taskAdd() {
			return $this->terminate();
		}
		
		protected function taskDelete() {
			return $this->terminate();
		}
		
		
		protected function taskEdit() {
			if (!isset($this->objects[0])) return $this->terminate();			
			$object = $this->objects[0];
			shopPkgHelperLibrary::loadOrderItems($object);
			
			foreach($object->items as $i) {
				$i->product_link = $i->product_id ? Application::getSeoUrl("/product?action=edit&ids[]=$i->product_id") : '';
			}
			
			$smarty = Application::getSmarty();
			
			$url_addition = $this->url_addition;
			if ($this->page > 1) $url_addition .= $url_addition ? "&page=$this->page" : "page=$this->page"; 
			$url_addition = $url_addition ? '&amp;' . str_replace('&', '&amp;', $url_addition) : '';
			
			$back_link = "/{$this->getName()}?action=list" . $url_addition;
			$back_link = Application::getSeoUrl($back_link);						
			$smarty->assign('back_link', $back_link);
			
			$this->links['back'] = $back_link;

			$smarty->assign('object', $object);
		}
		
	}