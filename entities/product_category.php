<?php

	class shopPkgProductCategoryEntity extends coreBaseEntity {
		public $title;		
		public $description;
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
            $form->addField(coreFormElementsLibrary::get('rich_editor', 'description'));
            $form->addField(coreFormElementsLibrary::get('checkbox', 'active', array(
            	'value' => 1
            )));
            
			$form->addField(imagePkgHelperLibrary::getField('image', $this->getName(), $this->id, array(			
				'width' => 600,
				'height' => 100,
				'max_files' => 1
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
        
        
        public function save() {
        	$id = parent::save();
			if ($id) {				
				imagePkgHelperLibrary::commitUploadedFiles(Request::get('image'), $id);				
			}        	
        	return $id;
        }
        
        
	}