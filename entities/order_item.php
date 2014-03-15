<?php

	class shopPkgOrderItemEntity extends coreBaseEntity {
		
		public $order_id;
		public $entity_name;
		public $entity_id;
		public $entity_data;		
		public $quantity;
		public $price;
		public $cost;
		
		
		public function getTableName() {
			return 'order_item';
		}
		
		
		public function save() {
			$entity_data = $this->entity_data;
			$this->entity_data = serialize($this->entity_data);
			$id = parent::save();
			$this->entity_data = $entity_data;
			return $id;
		}
		

		public function load_list($params=array()) {
			
			$list = parent::load_list($params);
			foreach($list as $item) {
				$item->entity_data = unserialize($item->entity_data);
			}
			return $list;
		}
		
		
	}