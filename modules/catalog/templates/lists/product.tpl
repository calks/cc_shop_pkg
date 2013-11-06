

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
		
		{assign var=rows value=$items|@array_chunk:2}
		
		
		<div class="items has-box-title">
			<h1 class="box-title"><span><span>Товары</span></span></h1>
	
			<div class="box-t1">
				<div class="box-t2">
					<div class="box-t3"></div>
				</div>
			</div>
	
			<div class="box-1">
				{foreach item=row from=$rows name=row_loop}
					<div class="row{if $smarty.foreach.row_loop.first} first-row{/if}">
						{foreach item=item from=$row name=cell_loop}
						
							<div class="width50{if $smarty.foreach.row_loop.first} first-item{/if}">
								<div class="teaser-item">
									<div class="pos-media media-left">
										<a title="{$item->title}" href="{$item->link}">
											<img width="150" height="117" alt="{$item->title}" title="{$item->title}" src="{$item->thumbnail}">
										</a>
									</div>

									<h2 class="pos-title">
										<a href="{$item->link}" title="{$item->title}">{$item->title}</a>
									</h2>

									<div class="pos-description">
										<div class="element element-textarea  last">
											<div>
												<p>
													{$item->description_short}
												</p>
											</div>
										</div>
									</div>

									<p class="pos-links">
										<span class="element element-itemlink  first last">
											<a href="{$item->link}">Перейти</a>
										</span>
									</p>
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


