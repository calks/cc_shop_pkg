<?php

	class shopPkgProductCategoryEntity extends coreBaseEntity {
		
		public $parent_id;
		public $title;
		public $description;
		public $seq;
		public $active;
		
		protected static $_tree;
		protected static $_parent_mapping;
		protected static $_child_mapping;
				
		public function getTableName() {
			return "product_category";
		}
		
        public function mandatory_fields() {
            return array(
            	"title" => "Название"
            );
        }
				
        public function order_by() {
            return "seq";
        }

        
        public function getAllParents($item_id) {        	
        	$parent_maping = $this->getParentMapping();
        	$parent = isset($parent_maping[$item_id]) ? $parent_maping[$item_id] : null;        	
        	if (!$parent) return array();
        	$out = array($parent);
        	$grand_parents = $this->getAllParents($parent->id);
        	if ($grand_parents) $out = array_merge($grand_parents, $out); 
        	return $out;
        }
        
        
        public function getAllChildren($item_id) {        	
        	$child_maping = $this->getChildMapping();
        	$children = isset($child_maping[$item_id]) ? $child_maping[$item_id] : array();
        	        	        	
        	if (!$children) return array();
        	$out = $children;
        	foreach ($children as $child) {
        		$grand_children = $this->getAllChildren($child->id);
        		if ($grand_children) $out = array_merge($out, $grand_children); 
        	}
        	
        	return $out;
		}
        
        
        protected function invalidateTreeData() {
        	self::$_tree = null;
        	self::$_parent_mapping = null;
        	self::$_child_mapping = null;
        }
        
        public function getTree() {
        	$this->loadTreeData();
        	return self::$_tree;
        }
        
        public function getParentMapping() {
        	$this->loadTreeData();
        	return self::$_parent_mapping;
        }
        
        public function getChildMapping() {
        	$this->loadTreeData();
        	return self::$_child_mapping;
        }
        
        
        
        protected function loadTreeData() {
        	if (!is_null(self::$_tree)) return;
        	
        	$db = Application::getDb();
        	$category_table = $this->getTableName();
        	$ordering = $this->order_by();
        	$product = Application::getEntityInstance('product');
        	$product_table = $product->getTableName();
        	
        	$sql = "
        		SELECT 
        			$category_table.id AS id,
        			IF($category_table.parent_id IS NULL, 0, $category_table.parent_id) AS parent_id,
        			$category_table.title AS title,
        			COUNT($product_table.id) AS product_count
        		FROM
        			$category_table 
        			LEFT JOIN $product_table ON $product_table.product_category_id=$category_table.id
        		GROUP BY $category_table.id
        		ORDER BY $category_table.parent_id, $category_table.$ordering
        	";
        			
        	$data = $db->executeSelectAllObjects($sql);
        	
        	$out = array();
        	
        	$mapping = array();        	
        	$mapping[0] = &$out; 
        	
        	$token = null;
        	$current_parent_id = -1;
        	        	
        	$reindexed = array();

        	self::$_child_mapping = array();
        	
        	foreach ($data as $d) {
        		$reindexed[$d->id] = $d;        		
        		
        		$d->children = array();
        		
        		if($d->parent_id != $current_parent_id) {
        			$token = &$mapping[$d->parent_id];
        			$current_parent_id = $d->parent_id;
        		}
        		$mapping[$d->id] = &$d->children;        		
        		$token[] = $d;
        		
        		self::$_child_mapping[$d->id] = &$d->children;
        	}
        	
        	
        	self::$_parent_mapping = array();
        	foreach ($data as $d) {
        		self::$_parent_mapping[$d->id] = $d->parent_id ? $reindexed[$d->parent_id] : null;
        	}
        	
        	self::$_tree = $out;
        }
        
        
        public function getCategoryParentSelect($root_item_name, $excluded_id = null) {
        	return $this->getParentSelect(null, $root_item_name, $excluded_id, 'yes', 'no', null, 1);
        }
        
        public function getProductParentSelect($null_item = null) {
        	return $this->getParentSelect($null_item, null, null, 'no', 'yes', null, 0);
        }
        
        
        protected function getParentSelect($null_item, $root_item, $excluded_id=null, $subcategories_allowed=null, $subproducts_allowed=null, $branch=null, $level=1) {
        	$is_first_pass = is_null($branch);
        	
        	
        	if ($is_first_pass) {
        		$out = get_empty_select($null_item);
        		$branch = $this->getTree();
        		if ($root_item) $out[0] = $root_item;
        	}
        	else {
        		$out = array();
        	}
        	
        	foreach ($branch as $item) {        		
        		if (!is_null($excluded_id) && $item->id == $excluded_id) continue;
        		if ($subproducts_allowed=='no' && $item->product_count != 0) continue;
        		
        		if ($subcategories_allowed=='no' && $item->children) $item->title .= '[disabled]';
        		
        		$out[$item->id] = str_repeat('[space]', $level*2) . $item->title;
        		$children = $this->getParentSelect(
        			null, 
        			null, 
        			$excluded_id, 
        			$subcategories_allowed, 
        			$subproducts_allowed, 
        			$item->children, 
        			$level+1
        		);
        		foreach ($children as $k=>$v) {
        			$out[$k] = $v;
        		}
        	}        	
        	return $out;
        }
        
        
        public function make_form(&$form) {        	
            $form->addField(coreFormElementsLibrary::get('hidden', 'id'));
            $form->addField(coreFormElementsLibrary::get('hidden', 'seq'));            
                        
            $form->addField(coreFormElementsLibrary::get('parent_select', 'parent_id')->setOptions($this->getCategoryParentSelect('Верхний уровень', $this->id)));
            
            $form->addField(coreFormElementsLibrary::get('text', 'title'));
            $form->addField(coreFormElementsLibrary::get('rich_editor', 'description'));
            $form->addField(coreFormElementsLibrary::get('checkbox', 'active', array(
            	'value' => 1
            )));
            
            $image_field = coreFormElementsLibrary::get('image', 'image');
            $image_field->setEntityName($this->getName());
            $image_field->setEntityId($this->id);
            $image_field->setMaxFiles(1);
            $form->addField($image_field);
            
            return $form;
        }
		
        
        public function delete() {
        	$this->loadTreeData();
        	$children_deleted = $this->delete_children();
        	$this->invalidateTreeData(); 
        	
        	return $children_deleted ? parent::delete() : false;
        }
        
        
        protected function delete_children() {
        	if (!$this->children) return true;
        	foreach ($this->children as $child) {
        		if (!$child->delete_children()) return false;
        		return $child->delete();
        	}
        }
        
        public function save() {
        	$this->invalidateTreeData();
        	$id = parent::save();        	
			if ($id) {				
				imagePkgHelperLibrary::commitUploadedFiles(Request::get('image'), $id);				
			}        	
        	return $id;
        }
        
        
        public function load_list($params=array()) {        
			$product = Application::getEntityInstance('product');
			$product_table = $product->getTableName();
			$entity_table = $this->getTableName();
			
			$params['from'][] = "
				LEFT JOIN $product_table ON $product_table.product_category_id=$entity_table.id  
			";
			$params['fields'][] = "COUNT($product_table.id) AS product_count";
			$params['group_by'][] = "$entity_table.id";

			return parent::load_list($params);
        }
        
        
        
        
	}