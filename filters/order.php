<?php

    class shopPkgOrderFilter extends coreBaseFilter {
	
        function add_fields() {
        	$order = Application::getEntityInstance('order');
        	
        	$this->addField(coreFormElementsLibrary::get('checkbox_collection', 'search_status', array(
        		'options' => $order->getStatusOptions()
        	)));
        	
        	$this->addField(new TEditField('search_keyword', ''));
        	$limit_options = array(        		
        		20 => '20',
        		50 => '50',
        		100 => '100',
        		'all' => 'все'
        	);
        	$this->addField(new TSelectField('search_limit', '', $limit_options));
        	
        }
        

        function set_params(&$params) {
        	parent::set_params($params);
        	
            $db = Application::getDb();

            $order = Application::getEntityInstance('order');
            $table = $order->getTableName();
                        
            $keyword = trim($this->getValue('search_keyword'));
            if($keyword) {
            	$skeyword = addslashes($keyword);
            	$params['where'][] = "(user_name LIKE '%$keyword%' OR user_family_name LIKE '%$keyword%' OR user_email LIKE '%$keyword%')";
            }
                        
            $status = $this->getValue('search_status');
            if ($status) {
            	$allowed_statuses = array_keys($order->getStatusOptions());
            	if (!array_is_subset($status, $allowed_statuses)) {
            		$params['where'][] = '0';
            	}
            	else {
	            	$slist = array();
            		foreach ($status as $s) $slist[] = "'" . addslashes($s) . "'";
	            	$slist = implode(',', $slist);
	            	$params['where'][] = "$table.status IN($slist)";
            	}
            }
            
        }
        
		
	}
	
	
	
	
	
	
	
	