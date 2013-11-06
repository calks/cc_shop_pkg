
        <div class="top_comment">
            {if $action == 'add'}
                Добавление товара
            {else}
                Редактирование товара
            {/if}
        </div>
        
        <form action="{$form_action}" method="POST" enctype="multipart/form-data">
        <table summary="" align="center">
	        <tr>
	        	<td align="right" class="buttom_form">
		            <input type="button" onclick="javascript:window.location.href='{$back_link}'" name="back" value="&lt;&lt;Назад к списку">
		            <input type="submit" name="save" value="Сохранить">
		            <input type="reset" name="reset" value="Сбросить">
		        </td>
		    </tr>
	        <tr>
	        	<td>
		            <table summary="" class="edit">
		            	<tr><th>Активен :</th><td>{$form->render('active')}</td></tr>
		            	<tr><th>Раздел *:</th><td>{$form->render('product_category_id')}</td></tr>
			            <tr><th>Название *:</th><td>{$form->render('title')}</td></tr>
			            <tr><th>Цена *:</th><td>{$form->render('price')}</td></tr>
			            <tr><th>Описание :</th><td>{$form->render('description')}</td></tr>			            
			            <tr>
			            	<th>Картинка :</th>
			            	<td>{$form->render('image')}</td>
			            </tr>
			            <tr>
			            	<th>Аудиофайл *:</th>
			            	<td>{$form->render('audio')}</td>
			            </tr>
		            </table>	
	            	<br>
	            	* - обязательное поле
	        	</td>
	        </tr>
	        <tr>
	        	<td align="right" class="buttom_form">
		            <input type="button" onclick="javascript:window.location.href='{$back_link}'" name="back" value="&lt;&lt;Назад к списку">
		            <input type="submit" name="save" value="Сохранить">
		            <input type="reset" name="reset" value="Сбросить">
					<input type="hidden" name="action" value="{$action}">
		            {$form->render('id')}
		            {$form->render('seq')}
		        </td>
		    </tr>
        </table>
        </form>
        <br>
