<?php

	class shopPkgProductCategoryEntity extends coreBaseEntity {
		public $title;		
		public $seq;
		public $active;
		
		
		public function getTableName() {
			return "product_category";
		}
		
        function mandatory_fields() {
            return array(
            	"title" => "Название"
            );
        }
				
        function order_by() {
            return " seq ";
        }

        function make_form(&$form) {
            $form->addField(new THiddenField("id"));
            $form->addField(new THiddenField("seq"));
            $form->addField(coreFormElementsLibrary::get('edit', 'title'));
            $form->addField(coreFormElementsLibrary::get('checkbox', 'active', array(
            	'value' => 1
            )));
            return $form;
        }
		
        function getSelect($add_null_item) {
        	$db = Application::getDb();
        	$table = $this->get_table_name();

        	$sql = "
        		SELECT id, title 
        		FROM $table
        	";        	
        	$data = $db->executeSelectAllObjects($sql);
        	
        	$out = get_empty_select($add_null_item);
        	foreach($data as $item) $out[$item->id] = $item->title;
        	return $out;
        }
        
	}