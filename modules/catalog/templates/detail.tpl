		
	{if $breadcrumbs_block}
		{$breadcrumbs_block->render()}
	{/if}	

	<div class="yoo-zoo product-default product-default-frontpage" id="yoo-zoo">
	
		<h1 class="title">{$page_heading}</h1>

		<div class="item">
				
			<div class="floatbox">
		
				<div class="box-t1">
					<div class="box-t2">
						<div class="box-t3"></div>
					</div>
				</div>
			
				<div class="box-1">
		
					<div class="pos-media media-right">
						<div class="element element-image  first last">
							<img width="320" height="250" alt="{$item->title}" src="{$item->image}">
						</div>		
					</div>
				
					<div class="pos-description">
						<div class="element element-textarea  first last">
							{$item->description}
						</div>		
					</div>
					
					<div class="pos-bottom">
						<div class="element element-itemtag  first">
							<h3>{$item->price_str}</h3>
							<a class="buy" href="{$item->buy_link}">купить</a>
						</div>
					</div>
						
				</div>
				
				<div class="box-b1">
					<div class="box-b2">
						<div class="box-b3"></div>
					</div>
				</div>
				
			</div>				
						
						
						
		</div>
			
	</div>		