<?php

	define('CATALOG_MODULE_ITEMS_PER_PAGE', 12);

    class shopPkgCatalogModule extends coreBaseModule {
    	
    	protected $action;
    	protected $product_category_id;
    	protected $product_category;
    	
    	protected $product_id;
    	protected $product;
    	
    	protected $listed_entity_name;
    	protected $base_url;
    	
    	protected $page_heading;
    	protected $page_content;

        public function run($params=array()) {
			$page = Application::getPage();
			
			$this->base_url = '/' . $this->getName();
			
			$this->action = 'list';
			$this->listed_entity_name = 'product_category';
			
			$this->page_heading = "Каталог";
			
			$breadcrumbs = Application::getBreadcrumbs();
			$breadcrumbs->addNode($this->base_url, $this->page_heading);
			
			$document = corePagePropertiesHelperLibrary::getDocument();			
			if ($document) {
				$this->page_heading = $document->title;
				$this->page_content = $document->content;
			}
			
			$this->product_category_id = @(int)array_shift($params);
			if ($this->product_category_id) {
				$this->product_category = Application::getEntityInstance('product_category');
				$this->product_category = $this->product_category->load($this->product_category_id);
				if (!$this->product_category) return $this->terminate();
				if (!$this->product_category->active) return $this->terminate();
				$this->base_url .= "/$this->product_category_id";				
				$this->listed_entity_name = 'product';
				$this->page_heading = $this->product_category->title;
				$this->page_content = $this->product_category->description;
				$breadcrumbs->addNode($this->base_url, $this->page_heading);
				
				$this->product_id = @(int)array_shift($params);
				if ($this->product_id) {
					$this->product = Application::getEntityInstance('product');
					$this->product = $this->product->load($this->product_id);
					if (!$this->product) return $this->terminate();
					if (!$this->product->active) return $this->terminate();
					
					$this->base_url .= "/$this->product_category_id/$this->product_id";				
					$this->action = 'detail';
					$this->page_heading = $this->product->title;
					$this->page_content = $this->product->description;
					$breadcrumbs->addNode($this->base_url, $this->page_heading);
				}
			}
			
        	
			$method_name = 'task' . ucfirst($this->action);
			if (!method_exists($this, $method_name)) return $this->terminate();
			
			$page = corePagePropertiesHelperLibrary::getDocument();
			$smarty = Application::getSmarty();
			$smarty->assign('page_heading', $this->page_heading);
			$smarty->assign('page_content', $this->page_content);
			$smarty->assign('breadcrumbs_block', $this->product_category_id ? Application::getBlock('breadcrumbs') : null);
			
			return call_user_func(array($this, $method_name), $params);	
        }
        
        
        protected function taskList($params=array()) {
        	
        	$current_page = (int)Request::get('page', 1);
        	if ($current_page < 1) return $this->terminate();
        	
        	$entity = Application::getEntityInstance($this->listed_entity_name);
        	$table = $entity->getTableName();
        	$load_params = array();
        	if ($this->listed_entity_name=='product_category') {
        		$load_params['where'][] = "$table.active=1";
        	}
        	else {
        		$load_params['where'][] = "$table.product_category_id=$this->product_category_id";
        		$load_params['where'][] = "$table.active=1";
        	}
        	$total_items = $entity->count_list($load_params);

        	$pagenav_block = null;
        	if ($total_items > CATALOG_MODULE_ITEMS_PER_PAGE) {
        		$pagenav_block = Application::getBlock('pagenav');        		
        		$pagenav_block->setPageLinkTemplate("$this->base_url?page=%page%");
        		$pagenav_block->setItemsTotal($total_items);
        		$pagenav_block->setItemsPerPage(CATALOG_MODULE_ITEMS_PER_PAGE);	
        		$pagenav_block->setCurrentPage($current_page);
        	}
        	
        	$total_pages = ceil($total_items/CATALOG_MODULE_ITEMS_PER_PAGE);
        	
        	if ($total_pages && $current_page > $total_pages) {
        		return $this->terminate();
        	}
        	
        	$load_params['limit'] = CATALOG_MODULE_ITEMS_PER_PAGE;
        	$load_params['offset'] = CATALOG_MODULE_ITEMS_PER_PAGE * ($current_page-1);
        	
        	$items = $entity->load_list($load_params);
        	
        	if ($this->listed_entity_name=='product_category') {
        		/*$items = array_merge($items, $items, $items);
        		$items = array_merge($items, $items, $items);*/
        		$this->prepareCategory($items);
        	}
        	else {
        		$this->prepareProduct($items);
        	}       	
        	
			$smarty = Application::getSmarty();
			$smarty->assign('items', $items);
			$smarty->assign('pagenav_block', $pagenav_block);
			
			$list_template_path = Application::getSitePath() . $this->getTemplatePath("lists/$this->listed_entity_name");
			$smarty->assign('list_template_path', $list_template_path);
			
			$template_path = $this->getTemplatePath($this->action);						
			return $smarty->fetch($template_path);   
        	
        }
        
        
        protected function prepareCategory(&$category_or_array) {
        	if (!$category_or_array) return;
        	$array_given = is_array($category_or_array);
        	if (!$array_given) $category_or_array = array($category_or_array);
        	
        	$this->loadProductCount($category_or_array);
        	
        	imagePkgHelperLibrary::loadImages($category_or_array, 'images');
        	foreach($category_or_array as $item) {				
        		$item->title = coreFormattingLibrary::plaintext($item->title);
				$image_id = isset($item->images[0]) ? $item->images[0]->id : 0;				
				$item->thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 156, 100, 'crop', $image_id ? 'jpeg' : 'png');
				$item->link = Application::getSeoUrl("$this->base_url/$item->id");
				$item->description_short = coreFormattingLibrary::truncate($item->description, 100);				
        	} 
        	
        	if (!$array_given) $category_or_array = array_shift($category_or_array);
        }
        
        
        protected function loadProductCount(&$categories) {
        	if (!$categories) return;
        	$mapping = array();
        	foreach($categories as $c) {
        		$c->product_count = 0;
        		$mapping[$c->id] = $c;
        	}
        	
        	$ids = implode(',', array_keys($mapping));
        	$db = Application::getDb();
        	$product = Application::getEntityInstance('product');
        	$product_table = $product->getTableName();
        	$data = $db->executeSelectAllObjects("
        		SELECT 
        			product_category_id,
        			COUNT(*) AS product_count
        		FROM 
        			$product_table
        		WHERE 	
        			product_category_id IN($ids) AND
        			active=1
        		GROUP BY product_category_id  
        	");
        			
        	foreach($data as $d) {
        		$mapping[$d->product_category_id]->product_count = $d->product_count;
        	}
        	
        }
        
        protected function prepareProduct(&$product_or_array) {
        	if (!$product_or_array) return;
        	$array_given = is_array($product_or_array);
        	if (!$array_given) $product_or_array = array($product_or_array);
        	
        	imagePkgHelperLibrary::loadImages($product_or_array, 'images');
        	foreach($product_or_array as $item) {				
        		$item->title = coreFormattingLibrary::plaintext($item->title);
				$image_id = isset($item->images[0]) ? $item->images[0]->id : 0;				
				$item->thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 150, 117, 'crop', $image_id ? 'jpeg' : 'png');
				$item->image = imagePkgHelperLibrary::getThumbnailUrl($image_id, 320, 250, 'crop', $image_id ? 'jpeg' : 'png');
				$item->link = Application::getSeoUrl("$this->base_url/$item->id");
				$item->description_short = coreFormattingLibrary::truncate($item->description, 150);
				$item->buy_link = Application::getSeoUrl("/cart/add/$item->id");
        	} 
        	
        	if (!$array_given) $product_or_array = array_shift($product_or_array);
        }
        
        
        protected function taskDetail($params=array()) {
        	$smarty = Application::getSmarty();
        	$this->prepareProduct($this->product);
        	$smarty->assign('item', $this->product);
			$template_path = $this->getTemplatePath($this->action);						
			return $smarty->fetch($template_path);
        }
        

    }
