<?php

	define('CATALOG_MODULE_ITEMS_PER_PAGE', 12);

    class shopPkgCatalogModule extends coreBaseModule {
    	
    	/*protected $action;
    	protected $product_category_id;
    	protected $product_category;
    	
    	protected $page_num;
    	
    	protected $product_id;
    	protected $product;
    	
    	/*protected $listed_entity_name;
    	protected $base_url;*/
    	
    	/*protected $page_heading;
    	protected $page_content;*/

        public function run($params=array()) {        	
			$page = Application::getPage();
			
			$params_parsed = array();
			while($params) {
				$param_name = @array_shift($params);
				$param_value = @(int)array_shift($params);
				if (!$param_name) continue;
				$params_parsed[$param_name] = $param_value;				
			}
			
			if (isset($params_parsed['product'])) {
				$this->task = 'product_detail';
			}
			else {
				$this->task = 'product_list';
			}
						
			
			$this->runTask($this->task, $params_parsed);				
			return $this->returnResponse();			
			
        }
        
        
        protected function taskProductList($params=array()) {
        	
        	$product = Application::getEntityInstance('product');
        	$product_load_params = array();
        	
        	$category_id = isset($params['category']) ? (int)$params['category'] : null;
        	if ($category_id) {
        		
        		$category = Application::getEntityInstance('product_category');
        		$category = $category->load($category_id);
        		if (!$category) return $this->terminate();
        		
        		$category_ids = array($category_id);
        		foreach ($category->getAllChildren($category_id) as $child) {
        			$category_ids[] = $child->id;
        		}
        		        		
        		$product_table = $product->getTableName();
        		$category_ids = implode(',', $category_ids);
        		
        		$product_load_params['where'][] = "$product_table.product_category_id IN($category_ids)";

        	}
        	
        	$total_products = $product->count_list($product_load_params);
        	$current_page = isset($params['page']) ? (int)$params['page'] : 1;
        	if ($current_page < 1) $current_page = 1;


        	$pagenav_block = null;
        	if ($total_products > CATALOG_MODULE_ITEMS_PER_PAGE) {
        		$pagenav_block = Application::getBlock('pagenav');        		
        		$pagenav_block->setPageLinkTemplate("{$this->getName()}/category/$category_id/page/%page%");
        		$pagenav_block->setItemsTotal($total_products);
        		$pagenav_block->setItemsPerPage(CATALOG_MODULE_ITEMS_PER_PAGE);	
        		$pagenav_block->setCurrentPage($current_page);
        	}
        	
        	$total_pages = ceil($total_products/CATALOG_MODULE_ITEMS_PER_PAGE);
        	
        	if ($total_pages && $current_page > $total_pages) {
        		return $this->terminate();
        	}
        	
        	$product_load_params['limit'] = CATALOG_MODULE_ITEMS_PER_PAGE;
        	$product_load_params['offset'] = CATALOG_MODULE_ITEMS_PER_PAGE * ($current_page-1);
        	
        	$products = $product->load_list($product_load_params);
        	$this->prepareProduct($products);
        	
			$smarty = Application::getSmarty();
			$smarty->assign('products', $products);
			$smarty->assign('pagenav_block', $pagenav_block);
			
			
			switch($total_products) {
				case 0:
					$count_string = 'В этом разделе нет товаров';
					break;
				case 1:
					$count_string = '';
					break;
				default:
					$first = CATALOG_MODULE_ITEMS_PER_PAGE * ($current_page-1) + 1;
					$last = $first + $total_products - 1;
					$count_string = "Показаны товары $first-$last из $total_products";			
			}
			
			
			$smarty->assign('count_string', $count_string);
			
			$template_path = $this->getTemplatePath($this->task);			
			$this->html = $smarty->fetch($template_path);   
       	
        }
        
        
		protected function taskProductDetail($params=array()) {
        	
        	$product = Application::getEntityInstance('product');
        	$product_load_params = array();

        	$product_id = isset($params['product']) ? (int)$params['product'] : null;
        	if (!$product_id) return $this->terminate();
        		
       		$product = Application::getEntityInstance('product');
       		$product = $product->load($product_id);
        	if (!$product) return $this->terminate();
        		
        	$this->prepareProduct($product);
        	
			$smarty = Application::getSmarty();
			$smarty->assign('product', $product);
			
			$template_path = $this->getTemplatePath($this->task);		
			$this->html = $smarty->fetch($template_path);   
       	
        }
        
        
        
        protected function prepareCategory(&$category_or_array) {
        	if (!$category_or_array) return;
        	$array_given = is_array($category_or_array);
        	if (!$array_given) $category_or_array = array($category_or_array);
        	
        	$this->loadProductCount($category_or_array);
        	
        	imagePkgHelperLibrary::loadImages($category_or_array, 'image');
        	foreach($category_or_array as $item) {				
				$item->link = Application::getSeoUrl("{$this->getName()}/category/$item->id");
				$this->adjustCategoryAppearance($item);			
        	} 
        	
        	if (!$array_given) $category_or_array = array_shift($category_or_array);
        }
        
        
        protected function adjustCategoryAppearance($category) {        	
        	//$category->title = coreFormattingLibrary::plaintext($category->title);
			$image_id = isset($category->image_list[0]) ? $category->image_list[0]->id : 0;				
			$category->thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 150, 150, 'crop', $image_id ? 'jpeg' : 'png');
			$category->description_short = coreFormattingLibrary::truncate($category->description, 100);        	
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
        	
        	imagePkgHelperLibrary::loadImages($product_or_array, 'image');
        	foreach($product_or_array as $item) {
				$item->link = Application::getSeoUrl("/{$this->getName()}/product/$item->id");
				$item->buy_link = Application::getSeoUrl("/cart/add/$item->id");				        		
        		$this->adjustProductAppearance($item);
        	}
        	
        	if (!$array_given) $product_or_array = array_shift($product_or_array);
        }
        
        
        protected function adjustProductAppearance($product) {
        	$product->title = coreFormattingLibrary::plaintext($product->title);
			$image_id = isset($product->image_list[0]) ? $product->image_list[0]->id : 0;				
			$product->thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 350, 240, 'crop', $image_id ? 'jpeg' : 'png');
			$product->description_short = coreFormattingLibrary::truncate($product->description, 50);
			
			$product->gallery = array();
			if (!$product->image_list) {
				$gallery_item = new stdClass();
				$gallery_item->slide = imagePkgHelperLibrary::getThumbnailUrl(0, 547, 547, 'crop', 'png');
				$gallery_item->slide_thumb = imagePkgHelperLibrary::getThumbnailUrl(0, 80, 80, 'crop', 'png');
				$product->gallery[] = $gallery_item;
			}
			else {
				foreach($product->image_list as $img) {
					$gallery_item = new stdClass();
					$gallery_item->slide = imagePkgHelperLibrary::getThumbnailUrl($img->id, 547, 547, 'crop', 'jpeg');
					$gallery_item->slide_thumb = imagePkgHelperLibrary::getThumbnailUrl($img->id, 90, 90, 'crop', 'jpeg');
				$product->gallery[] = $gallery_item;
				}
			}
			
			
			
        }
        


    }
