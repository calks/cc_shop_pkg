

	<section id="cart_contents_block">
		{if $cart_items_count}
			В <a href="{$cart_link}">корзине</a> 
			<span class="items_count">{$cart_items_count}</span> {$cart_items_count_noun}
			на сумму <span class="total">{$cart_total}</span>
		{else}
			<span class="empty">Корзина пуста</span>
		{/if}
	
	</section>