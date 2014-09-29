<?php

	class shopPkgCartEntity extends coreBaseEntity {
		
		protected $_products;

		protected $content;
		protected $total;
		protected $subtotal;
		protected $items_count;
		
		
		public function __construct() {
			$this->_products = array();			
		}		

		protected function getEmptyProductRecord() {
			return array(
				'quantity' => 0
			);
		}
		
		public function add($product_id, $quantity=1) {
			$quantity = (int)$quantity;
			if ($quantity <= 0) return;
			if (!$this->isInCart($product_id)) {
				$this->_products[$product_id] = $this->getEmptyProductRecord();
			}
			$this->_products[$product_id]['quantity'] += $quantity;
			$this->save();
		}
		
		public function isEmpty() {
			return count($this->_products) == 0;
		}
		
		public function clear() {
			$this->_products = array();
			$this->save();
		}
		
		public function remove($product_id) {
			if (!$this->isInCart($product_id)) return;			
			unset($this->_products[$product_id]);
			$this->save();
		}
		
		public function setProductQuantity($product_id, $quantity) {
			$quantity = (int)$quantity;
			if ($quantity <= 0) return $this->remove($product_id);
			
			if (!$this->isInCart($product_id)) {
				$this->_products[$product_id] = $this->getEmptyProductRecord();
			}
			$this->_products[$product_id]['quantity'] = $quantity;
			$this->save();
		}
		
		public function save() {
			if (!$this->id) return false;
			$_SESSION[$this->id] = serialize($this->_products);			
			return $this->id;
		}
		
		public function load($id, $params=array()) {
			if (!isset($_SESSION[$id])) return null;
			$classname = get_class($this);
			$out = new $classname();
			$out->id = $id;			
			$out->_products = unserialize($_SESSION[$id]);
			return $out;
		}
		
		
		public function load_list($params=array()) {
			return array();
		}
		
		public function isInCart($product_id) {
			return isset($this->_products[$product_id]);
		}
		
		
		public function reloadContent() {
			$this->content = array();
			
			if (!$this->_products) return;
			
			$product = Application::getEntityInstance('product');
			$product_ids = array_keys($this->_products);
			$product_ids = implode(',', $product_ids);
			$product_table = $product->getTableName();			
			
			$params['where'][] = "$product_table.active=1";
			$params['where'][] = "$product_table.id IN($product_ids)";
			
			$product_list = $product->load_list($params);
			foreach($product_list as $p) {
				$this->content[$p->id] = $p;
				$this->content[$p->id]->quantity = $this->_products[$p->id]['quantity'];
			}
			
			$updated = false;
			foreach($this->_products as $pid => $p) {
				if (!array_key_exists($pid, $this->content)) {
					$this->remove($pid);
					$updated = true;					
				}
			}
			if ($updated) $this->save();
		}
		
		
		public function updateCalculatedData() {
			$this->subtotal = 0;
			$this->total = 0;
			$this->items_count = 0;
			
			$this->reloadContent();
			
			foreach ($this->content as $pid => $product) {
				$product->cost = $product->price * $product->quantity;
				$this->subtotal += $product->cost;
				$this->items_count += $product->quantity;
			}
			
			$this->total = $this->subtotal;
			
		}
		
		public function getContent() {
			$cart_content = $this->content;
			
			imagePkgHelperLibrary::loadImages($cart_content, 'image');
			
			foreach($cart_content as $item) {
				$item->cost_str = coreFormattingLibrary::formatCurrency($item->cost);
				$image_id = isset($item->image_list[0]) ? $item->image_list[0]->id : null;
				$item->thumbnail = imagePkgHelperLibrary::getThumbnailUrl($image_id, 50, 50, 'crop', $image_id ? 'jpeg' : 'png'); 	
				$item->link = shopPkgHelperLibrary::getProductCatalogLink($item);
				
				$item->remove_link = Application::getSeoUrl("/{$this->getName()}/remove/$item->id");
			}
			
			
			return $cart_content;
			
		}
		
		public function getSubtotal() {
			return $this->subtotal;
		}
		
		public function getTotal() {
			return $this->total;
		}
		
		public function getItemsCount() {
			return $this->items_count;
		}
		
		public function getProductsCount() {
			return count($this->_products);
		}
		
		
		
	}