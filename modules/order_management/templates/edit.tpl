
        <div class="top_comment">
            Просмотр заказа
        </div>
        
        
        <table summary="" align="center">
	        <tr>
	        	<td align="right" class="buttom_form">
		            <input type="button" onclick="javascript:window.location.href='{$back_link}'" name="back" value="&lt;&lt;Назад к списку">
		        </td>
		    </tr>
	        <tr>
	        	<td>
		            <table summary="" class="edit">
			            <tr><th>Создан :</th><td>{$object->created_str}</td></tr>
			            <tr><th>Сумма :</th><td>{$object->amount_str}</td></tr>
			            <tr><th>Статус :</th><td>{$object->status_str}</td></tr>
			            <tr>
			            	<th>Товары:</th>
			            	<td>
							    <table class="list" id="hover" summary="">
								    <tr>
								    	<th></th>
								    	<th>Наименование</th>
								    	<th>Цена</th>
								        <th>Количество</th>
								        <th>Стоимость</th>
								    </tr>
									{foreach item=item from=$object->items}
									    <tr>
									    	<td class="delete">
									    		<img src="{$item->thumbnail}" alt="">
									    	</td>
									    	<td>
									    		{if $item->product_link}
									    			<a href="{$item->product_link}" target="_blank">{$item->product_title}</a>
									    		{else}
									    			{$item->product_title}
									    		{/if}	
									    	</td>
									    	<td>{$item->price_str}</td>
									        <td>{$item->quantity}</td>
									        <td>{$item->cost_str}</td>
									    </tr>
									{/foreach}			            	

								</table>
			            	</td>
			            </tr>
		            </table>	
	            	<br>
	            	
	        	</td>
	        </tr>
	        <tr>
	        	<td align="right" class="buttom_form">
		            <input type="button" onclick="javascript:window.location.href='{$back_link}'" name="back" value="&lt;&lt;Назад к списку">
		        </td>
		    </tr>
        </table>
        
        <br>
