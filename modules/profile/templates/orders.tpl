
	
	{if $order}
	
		<h2>Заказ №{$order->id} ({$order->status_str}). Добавлен {$order->created_str}.</h2>
	
		<table class="order-list zebra">
		
			<tr>
				<th>Название</th>
				<th>Цена</th>				
				{if $order->status=='payed'}<th>&nbsp;</th>{/if}	
			</tr>
		
			{foreach item=item from=$order->items}
				<tr>
					<td>
						{if $item->catalog_link}<a href="{$item->catalog_link}">{/if}
							<img src="{$item->thumbnail}" alt="{$item->product_title}" />
							<span>{$item->product_title}</span>
						{if $item->catalog_link}</a>{/if}					
					</td>
					<td>{$item->price_str}</td>
					{if $order->status=='payed'}
						<th>
							{if $item->download_link}<a href="{$item->download_link}">скачать</a>{/if}
						</th>
					{/if}	
				</tr>
			{/foreach}
		
		</table>
		
		
		<br />
		<a class="button-more" href="{$back_link}">Вернуться к списку</a>
		{if $order->pay_link}
			<a class="button-more" href="{$order->pay_link}">Оплатить</a>
		{/if}
	
	
		{*<pre>
			{$order|@print_r:1}
		</pre>*}	
	
	
	{else}
	
	
		{if !$order_list}
			<p>У вас пока нет заказов.</p>
		{else}
		
			<table class="order-list zebra">
			
				<tr>
					<th>Номер заказа</th>
					<th>Добавлен</th>
					<th>Сумма</th>
					<th>Статус</th>
					<th>Состав</th>
				</tr>
			
				{foreach item=item from=$order_list}
					<tr>
						<td>{$item->id}</td>
						<td>{$item->created_str}</td>
						<td>{$item->amount_str}</td>
						<td>
							{$item->status_str}
							{if $item->pay_link}
								(<a href="{$item->pay_link}">оплатить</a>)
							{/if}
						</td>
						<td><a href="{$item->link}">смотреть</a></td>
					</tr>
				{/foreach}
			
			</table>
			
		{/if}	
	
	
		{*<pre>
			{$order_list|@print_r:1}
		</pre>*}	
	{/if}