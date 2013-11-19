
	
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
				</tr>
			{/foreach}
			
			<tr>
				<td class="title" colspan="2">
					Итого
				</td>
				<td class="price">
					<b>{$cart_subtotal_str}</b>
				</td>
			</tr>

		
		</table>
		
		<a class="button-more" href="{$confirm_link}">Оформить</a>
		<a class="button-more" href="{$back_link}">Назад</a>
	
	{/if}