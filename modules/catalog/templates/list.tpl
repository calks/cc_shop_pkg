
	{if $breadcrumbs_block}
		{$breadcrumbs_block->render()}
	{/if}	

	<div class="yoo-zoo product-default product-default-frontpage" id="yoo-zoo">
	
		<h1 class="title">{$page_heading}</h1>
		
		{if $page_content}
			<div class="description">						
				{$page_content}
				<br />
			</div>
		{/if}
	
		
		{include file=$list_template_path}
		

	</div>












