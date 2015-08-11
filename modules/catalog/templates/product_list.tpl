
	{if $breadcrumbs_block}
		{$breadcrumbs_block->render()}
	{/if}	

		
		
		<section class="ct-u-paddingTop40 ct-u-paddingBottom30  ct-u-backgroundGray">
        	<div class="container">
        		<div class="text-center">
        			<h4 class="ct-u-sectionHeader ct-u-sectionHeader--primary text-uppercase">{$page_heading}</h4>
        		</div>
        	</div>
		
			{if $page_content}
				<div class="container textpage-content ct-u-paddingTop40">		
					{$page_content}				
				</div>
			{/if}
		
		</section>
		
		

		<a name="products"></a>
		<section class="ct-shopSection ct-u-paddingBottom80 ct-u-backgroundGray ">
			<div class="container">
				<div class="row">
                    <div class="col-md-9 col-md-push-3">
                    
                    	{if $count_string}
							<p class="woocommerce-result-count">
								{$count_string}
							</p>
						{/if}	
							
						{*<form method="get" class="woocommerce-ordering">
							<select class="orderby" name="orderby">
								<option selected="selected" value="menu_order">Default sorting</option>
								<option value="popularity">Sort by popularity</option>
								<option value="rating">Sort by average rating</option>
								<option value="date">Sort by newness</option>
								<option value="price">Sort by price: low to high</option>
								<option value="price-desc">Sort by price: high to low</option>
							</select>
						</form>*}


						{if $products}

							<ul class="products woocolumns-3">
								{foreach $products as $product}
							
							
									<li class="product product-{$product->tile_color}">
			
										<a href="{$product->link}">
		
											<img width="350" height="240" alt="Macaroons" src="{$product->thumbnail}">
											<h3>{$product->title}</h3>
				
											<div class="excerpt">
												<p>{$product->description_short}</p>
											</div>
											<span class="price">
												<span class="amount">{$product->price_str}</span>
											</span>
										</a>
		
										<a class="button add_to_cart_button product_type_simple" rel="nofollow" href="{$product->buy_link}"></a>
									</li>
								
								{/foreach}
							</ul>
							
						{/if}	
						
						
						{if $pagenav_block}
							{$pagenav_block->render()}
						{/if}
						

					</div>
					
					
                    <div class="col-md-3 col-md-pull-9 ct-js-sidebar">
                        <div class="row">
                        	{$catalog_tree_block->render()}
	                    </div>
	                </div>
	
				</div>
			</div>
		</section>		
		














