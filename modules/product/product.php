<?php

	
	class shopPkgProductModule extends coreCrudBaseModule {
		
		protected function getObjectName() {
			return 'product';
		}		
		

		protected function beforeListLoad(&$load_params) {
			$filter = Application::getFilter($this->getObjectName());
			$search_category = (int)Request::get('search_product_category');
			if ($search_category) {
				$filter->setValue('search_product_category', $search_category);
				$filter->saveToSession(Application::getApplicationName());
				$this->url_addition .= 'search_product_category=' .$search_category;
			}
			$filter->set_params($load_params);
			$smarty = Application::getSmarty();
			$smarty->assign('allow_sorting', $search_category != 0);
			$smarty->assign('filter', $filter);
			
			$category = Application::getEntityInstance('product_category');
			$category_table = $category->getTableName();
						
			$object = Application::getEntityInstance($this->getObjectName());
			$table = $object->get_table_name();
			 
			
			$load_params['fields'][] = "$category_table.title AS product_category_title";
			$load_params['from'][] = "
				LEFT JOIN $category_table
				ON $category_table.id = $table.product_category_id
			";			
		}
		
		protected function taskEdit() {
			if ($this->action == 'add' && !Request::isPostMethod()) {
				$filter = Application::getFilter($this->getObjectName());
				$search_category = (int)$filter->getValue('search_product_category');
				if ($search_category) $this->objects[0]->product_category_id = $search_category;
			}
			
			parent::taskEdit();
		}
		
		protected function neighbourExtraCondition() {
			$object = $this->objects[0];			
			return "product_category_id=$object->product_category_id";
		}
		
		
		protected function afterListLoad(&$list) {
			imagePkgHelperLibrary::loadImages($list, 'images');
			
			foreach($list as $item) {
				$image_id = isset($item->images[0]) ? $item->images[0]->id : 0;
				$item->image_thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 60, 60, 'crop', $image_id ? 'jpeg' : 'png');
			}
		}
				
		
		/*protected function beforeObjectSave($object) {
			parent::beforeObjectSave($object);
			
			$image_field_hash = $this->form->getValue('image');
			$image_uploaded = imagePkgHelperLibrary::getFilesCount($image_field_hash) > 0;
			
			if (!$image_uploaded) {
				$this->errors[] = "Нужно загрузить картинку";
			}
		}*/
		
	}