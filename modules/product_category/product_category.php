<?php


	class shopPkgProductCategoryModule extends coreCrudBaseModule {
		
		protected function getObjectName() {
			return 'product_category';
		}		
		
		protected function beforeListLoad(&$load_params) {
			parent::beforeListLoad($load_params);
			
			$entity_name = $this->getObjectName();
			$entity = Application::getEntityInstance($entity_name);
			$entity_table = $entity->getTableName();
			$load_params['where'][] = "($entity_table.parent_id IS NULL OR $entity_table.parent_id = 0)";
		}		
		
		
		protected function loadChildren(&$list, $level=0) {
			if (!$list) return array();
			$mapping = array();
			foreach ($list as $item) {
				$item->level = $level;
				$item->children = array();
				$mapping[$item->id] = $item; 
			}
			
			$entity_name = $this->getObjectName();
			$entity = Application::getEntityInstance($entity_name);
			$entity_table = $entity->getTableName();
			
			$item_ids = implode(',', array_keys($mapping));
			$children_params['where'][] = "$entity_table.parent_id IN($item_ids)";
			$children = $entity->load_list($children_params);
			$this->loadChildren($children, $level+1);
			
			foreach ($children as $child) $mapping[$child->parent_id]->children[] = $child;
		}
		
		protected function getFlatList($tree) {
			$out = array();
			
			$last_idx = count($tree)-1; 
			
			foreach ($tree as $idx=>$item) {
				$item->is_first = $idx==0;
				$item->is_last = $idx==$last_idx;
								
				$out[] = $item;
				if ($item->children) {
					foreach ($this->getFlatList($item->children) as $child) {
						$out[] = $child;
					}
				}
			}			
			return $out;
		}
		
		protected function afterListLoad(&$list) {
			$this->loadChildren($list);
			$list = $this->getFlatList($list);
			
			imagePkgHelperLibrary::loadImages($list, 'image');
			
			foreach($list as $item) {
				$image_id = isset($item->image_list[0]) ? $item->image_list[0]->id : 0;
				$item->image_thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 60, 60, 'crop', $image_id ? 'jpeg' : 'png');
				$item->products_link = Application::getSeoUrl("/product?search_product_category=$item->id");
			}
		}
		
		
		protected function neighbourExtraCondition() {			
			$entity = isset($this->objects[0]) ? $this->objects[0] : null;
			
			if (!$entity) return '';
			$table = $entity->getTableName();
			
			return $entity->parent_id ? "$table.parent_id=$entity->parent_id" : "($table.parent_id IS NULL OR $table.parent_id=0)";
		}
		
				
		
	}