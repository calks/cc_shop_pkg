

	{if $subtitle}
		<h3>{$subtitle}</h3>
	{/if}


    <br clear="all">
	    <form action="" method="POST">
		    <table class="filter_table">
			    <tr>
			        <th>Статус</th>        
			        <td>{$filter->render("search_status")}</td>			        
			    </tr>
			    <tr>
			        <th>ФИО/Email покупателя</th>        
			        <td>{$filter->render("search_keyword")}</td>
			    </tr>
			    {*<tr>
			        <th>Показывать по</th>        
			        <td>{$filter->render("search_limit")}</td>
			    </tr>*}

			    <tr>
			        <th></th>
			        <td align="left" valign="bottom" class="buttom_form"><input type="submit" value="Показать"></td>
			    </tr>
			    
			    {$filter->render("search_order_field")}
			    {$filter->render("search_order_direction")}


		    </table>
	    </form>
	<br clear="all">

    
    <table class="list" id="hover" summary="">
	    <tr>
	    	<th>Номер</th>
	    	<th>Пользователь</th>
	        <th>Дата и время</th>
	        <th>Сумма</th>
	        <th>Статус</th>
	        <th>Просмотр</th>	        
	    </tr>
    
		{foreach key=key item=object from=$objects name=objectlist}
		    <tr class="{cycle values='odd,even'}">
		    	<td class="delete">
		    		{$object->id}
		    	</td>
		    	<td>
		    		{if $object->user_link}
		    			<a href="{$object->user_link}" target="_blank">{$object->user_name} {$object->user_family_name}</a>
		    		{else}
		    			{$object->user_name} {$object->user_family_name}
		    		{/if}
		    	</td>
		    	<td class="delete">
		    		{$object->created_str}
		    	</td>
	        	<td class="delete">
	        		{$object->amount_str}
	        	</td>	  
		        <td class="delete">
		        	{$object->status_str}
		        </td>
		        <td class="delete">
		        	<a href="{$object->edit_link}">
		        		<img src="{$app_img_dir}/edit.gif" width="15" height="15" alt="Просмотр">
		        	</a>
		        </td>
	    	</tr>
		{/foreach}
	</table>
	
	{if $pagenav}<br><br>{$pagenav->render()}{/if}
