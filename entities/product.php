<?php

	class shopPkgProductEntity extends coreBaseEntity {		
		
		public $product_category_id;
		public $title;
		public $price;
		public $description;
		public $seq;
		public $active;
		
		public function getTableName() {
			return "product";
		}		
				
        public function order_by() {
            return " seq ";
        }
        
        public function mandatory_fields() {
            return array(
            	'title' => 'Название'
            );
        }
        

        public function make_form(&$form) {
        	$form->addField(new THiddenField("id"));
        	
        	$form->addField(coreFormElementsLibrary::get('text', 'price'));
            $form->addField(coreFormElementsLibrary::get('text', 'title'));
            $form->addField(coreFormElementsLibrary::get('rich_editor', 'description'));
            $form->addField(coreFormElementsLibrary::get('checkbox', 'active', array(
            	'value' => 1
            )));
            
            $category = Application::getEntityInstance('product_category');
            $form->addField(coreFormElementsLibrary::get('parent_select', 'product_category_id')->setOptions($category->getProductParentSelect('-- Не выбран --')));
            
            $form->addField(new THiddenField("seq"));

            $image_field = coreFormElementsLibrary::get('image', 'image');
            $image_field->setEntityName($this->getName());
            $image_field->setEntityId($this->id);
            $image_field->setMaxFiles(1);
            $form->addField($image_field);
            
            
            
            return $form;
        }		

        public function validate() {
        	$errors = parent::validate();
        	
        	if (!$this->product_category_id) {
        		$errors[] = "Нужно выбрать раздел каталога";
        	}
        	
        	$price = (floatval($this->price));
        	if ($price <= 0) {
        		$errors[] = "Неправильное значение цены";
        	}
        	
        	return $errors;
        }
        
        
        public function save() {
        	$this->price = (float)$this->price;
        	
        	$id = parent::save();
			if ($id) {				
				imagePkgHelperLibrary::commitUploadedFiles(Request::get('image'), $id);				
			}
        	
        	return $id;
        }
        
        
        public function load_list($params=array()) {
        	$list = parent::load_list($params);
        	
        	foreach ($list as $item) {
        		$item->price_str = coreFormattingLibrary::formatCurrency($item->price);
        	}
        	
        	return $list;
        }
        
	}