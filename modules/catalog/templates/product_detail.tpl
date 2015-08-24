

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

		
		
		
		
		
		
		
		<section class="ct-shopSection ct-u-paddingBottom80">
			<div class="container">

				<div class="product" itemtype="http://schema.org/Product">

				<div class="images">

					<div class="flexslider ct-flexslider--arrowType1 ct-flexslider--whiteControls woo_flexslider ct-js-flexslider">
						<ul class="slides">
							{foreach item=gallery_item from=$product->gallery}
								<li>								
									<img width="350" height="350" alt="" src="{$gallery_item->slide}">
								</li>
							{/foreach}	
						</ul>
					</div>
	
					<div class="flexslider woo_flexslider_thumbs">
						{if $product->gallery|@count > 1}					
							<ul class="slides">
								{foreach item=gallery_item from=$product->gallery}
									<li>
										<img width="90" height="90"  src="{$gallery_item->slide_thumb}">
									</li>
								{/foreach}	
							</ul>
						{/if}	
					</div>
		
				</div>

	
				<div class="summary entry-summary">

					<h1 class="product_title entry-title" itemprop="name">{$product->title}</h1>

					{*<div class="ct-productPagination">
						<a class="ct-productPagination-left" href="http://macaroon.themeplayers.net/product/minty-duo/">
							<i class="fa fa-arrow-left"></i>
						</a>
						<a class="ct-productPagination-right" href="http://macaroon.themeplayers.net/product/strawberry-kisses/">
							<i class="fa fa-arrow-right"></i>
						</a>
					</div>*}
					
					<div itemtype="http://schema.org/Offer" itemscope="" itemprop="offers">

						<p class="price"><span class="amount">{$product->price_str}</span></p>

						<meta content="{$product->price}" itemprop="price">
						<meta content="RUR" itemprop="priceCurrency">
						
						<link href="http://schema.org/InStock" itemprop="availability">

					</div>

					<div class="description" itemprop="description">
						{$product->description}
					</div>

	
					<form action="{$product->buy_link}" enctype="multipart/form-data" method="post" class="cart">
	 	
						<div class="quantity">
							<input type="number" size="4" class="input-text qty text" title="Qty" value="1" name="quantity" min="1" step="1">
						</div>

						<input type="hidden" value="796" name="add-to-cart">

						<button class="single_add_to_cart_button button alt" type="submit">Добавить в корзину</button>

					</form>

	
					
					<div class="product_meta">
						{*<span class="sku_wrapper">SKU: <span itemprop="sku" class="sku">100241194</span>.</span>*}
						{if $product->categories}
							<span class="posted_in">
								Раздел каталога: 
								{foreach item=cat from=$product->categories name=cat_loop}
									<a rel="tag" href="{$cat->link}">{$cat->title}</a>
									{if !$smarty.foreach.cat_loop.last}/{/if}
								{/foreach}
							</span>
						{/if}	
						{*<span class="tagged_as">Tags: <a rel="tag" href="http://macaroon.themeplayers.net/product-tag/finetti/">Finetti</a>, <a rel="tag" href="http://macaroon.themeplayers.net/product-tag/macaroon/">Macaroon</a>.</span>*}	
					</div>

				</div>
			</div>
    	</div>
	</section>
	
	
	
			