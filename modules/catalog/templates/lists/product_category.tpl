		

	{if !$items}
			<div class="details alinment-center">
		
				<div class="box-t1">
					<div class="box-t2">
						<div class="box-t3"></div>
					</div>
				</div>
	
				<div class="box-1">
					<div class="description">						
						<h2>Раздел пуст</h2>
					</div>
				</div>
			
				<div class="box-b1">
					<div class="box-b2">
						<div class="box-b3"></div>
					</div>
				</div>
			
			</div>	

	{else}
		
		{assign var=rows value=$items|@array_chunk:4}
		
		<div class="categories has-box-title">
			<h1 class="box-title"><span><span>Категории товаров</span></span></h1>
	
			<div class="box-t1">
				<div class="box-t2">
					<div class="box-t3"></div>
				</div>
			</div>
	
			<div class="box-1">
				{foreach item=row from=$rows name=row_loop}
					<div class="row{if $smarty.foreach.row_loop.first} first-row{/if}">
						{foreach item=item from=$row name=cell_loop}
							<div class="width25{if $smarty.foreach.row_loop.first} first-cell{/if}">
								<div class="category">
									<h2 class="title">
										<a title="{$item->title}" href="{$item->link}">{$item->title}</a>
										<span>({$item->product_count})</span>				
									</h2>
									
									<a title="{$item->title}" href="{$item->link}" class="teaser-image">
										<img width="165" height="100" alt="{$item->title}" title="{$item->title}" src="{$item->thumbnail}">
									</a>
									
									<div class="description">{$item->description_short}</div>	
								</div>
							</div>
						
						{/foreach}
					</div>
				{/foreach}	

				{if $pagenav_block}
					{$pagenav_block->render()}
				{/if}	
			
			</div>

			<div class="box-b1">
				<div class="box-b2">
					<div class="box-b3"></div>
				</div>
			</div>										

		</div>
	{/if}	