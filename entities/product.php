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
        	
        	$form->addField(coreFormElementsLibrary::get('edit', 'price'));
            $form->addField(coreFormElementsLibrary::get('edit', 'title'));
            $form->addField(coreFormElementsLibrary::get('text', 'description'));
            $form->addField(coreFormElementsLibrary::get('checkbox', 'active', array(
            	'value' => 1
            )));
            
            $category = Application::getEntityInstance('product_category');
            $form->addField(coreFormElementsLibrary::get('select', 'product_category_id', array(
            	'options' => $category->getSelect('-- Не выбран --')
            )));
            
            $form->addField(new THiddenField("seq"));            
            
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
        	return parent::save();
        }
        
        
        public function load_list($params=array()) {
        	$list = parent::load_list($params);
        	
        	foreach ($list as $item) {
        		$item->price_str = coreFormattingLibrary::formatCurrency($item->price, ' руб.');
        	}
        	
        	return $list;
        }
        
	}