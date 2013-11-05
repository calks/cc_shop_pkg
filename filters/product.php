<?php

    class shopPkgProductFilter extends coreBaseFilter {

        function add_fields() {
        	$category = Application::getEntityInstance('product_category');        	
        	$this->addField(new TSelectField('search_product_category', '', $category->getSelect('-- Любой --'))); 
        }


        function set_params(&$params) {
            $db = Application::getDb();
            
            $category_id = (int)$this->getValue('search_product_category');
            
            if($category_id) {
            	$product = Application::getEntityInstance('product');            	
            	$table = $product->getTableName();
            	$params['where'][] = "$table.product_category_id=$category_id";
            }
        }
    }

