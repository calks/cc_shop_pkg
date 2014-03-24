<?php


	class shopPkgProductCategoryModule extends coreCrudBaseModule {
		
		protected function getObjectName() {
			return 'product_category';
		}		
		
		protected function beforeListLoad(&$load_params) {
			parent::beforeListLoad($load_params);
			$product = Application::getEntityInstance('product');
			$product_table = $product->getTableName();
			$entity_name = $this->getObjectName();
			$entity = Application::getEntityInstance($entity_name);
			$entity_table = $entity->getTableName();
			
			$load_params['from'][] = "
				LEFT JOIN $product_table ON $product_table.product_category_id=$entity_table.id  
			";
			$load_params['fields'][] = "COUNT($product_table.id) AS product_count";
			$load_params['group_by'][] = "$entity_table.id";
		}
		
		protected function afterListLoad(&$list) {
			imagePkgHelperLibrary::loadImages($list, 'image');
			
			foreach($list as $item) {
				$image_id = isset($item->image_list[0]) ? $item->image_list[0]->id : 0;
				$item->image_thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 60, 60, 'crop', $image_id ? 'jpeg' : 'png');
				$item->products_link = Application::getSeoUrl("/product?search_product_category=$item->id");
			}
		}
		
				
		
	}