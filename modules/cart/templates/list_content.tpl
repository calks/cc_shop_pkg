

	{*$cart_content|@print_r:1*}
	
	
	{if !$cart_content}
		<p>Корзина пуста.</p>
	{else}
	
		<table class="cart-content zebra">
			{foreach item=item from=$cart_content}
				<tr>
					<td class="thumb">
						<a href="{$item->link}">
							<img src="{$item->thumbnail}" alt="{$item->title}"/>
						</a>
					</td>
					<td class="title">
						<a href="{$item->link}">{$item->title}</a>
					</td>
					<td class="price">
						{$item->price_str}
					</td>
					<td class="remove">
						<a href="{$item->remove_link}">убрать</a>
					</td>
				</tr>
			{/foreach}
			
			<tr>
				<td class="title" colspan="2">
					Итого
				</td>
				<td class="price">
					<b>{$cart_subtotal_str}</b>
				</td>
				<td class="remove">
					
				</td>
			</tr>

		
		</table>
		
		<br />
		<a class="button-more" href="{$checkout_link}">Оформить заказ</a>
		{if $continue_link}
			<a class="button-more" href="{$continue_link}">Вернуться к покупкам</a>
		{/if}	
	
	{/if}