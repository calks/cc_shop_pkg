<?php

	class shopPkgOrderItemEntity extends coreBaseEntity {
		
		public $order_id;
		public $product_id;
		public $product_title;
		public $quantity;
		public $price;
		public $cost;
		
		
		public function getTableName() {
			return 'order_item';
		}
		

		public function load_list($params=array()) {
			$table = $this->getTableName();
			$product = Application::getEntityInstance('product');
			$product_table = $product->getTableName();
			$product_alias = 't_' . md5(uniqid());
			
			$params['from'][] = "
				LEFT JOIN $product_table $product_alias ON $product_alias.id = $table.product_id
			";
			$params['fields'][] = "IF($product_alias.title IS NOT NULL, $product_alias.title, $table.product_title) AS product_title";
			
			return parent::load_list($params);
		}
		
		
	}