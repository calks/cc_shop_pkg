<?php

    class shopPkgProductFilter extends coreBaseFilter {

        function add_fields() {
        	$category = Application::getEntityInstance('product_category');        	
        	
        	$category_select = coreFormElementsLibrary::get('parent_select', 'search_product_category');
        	$category_select->setOptions($category->getProductParentSelect('-- Любой раздел --'));
        	$this->addField($category_select); 
        }


        function set_params(&$params) {
        	parent::set_params($params);
            $db = Application::getDb();
            
            $category_id = (int)$this->getValue('search_product_category');
            
            if($category_id) {
            	$product = Application::getEntityInstance('product');            	
            	$table = $product->getTableName();
            	$params['where'][] = "$table.product_category_id=$category_id";
            }
        }
    }

