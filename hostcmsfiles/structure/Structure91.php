<h1>Раскрытие информации</h1>
<p><strong>Стандарт раскрытия информации об&nbsp;управляющей организации в&nbsp;соответствии с&nbsp;Постановлением Правительства РФ&nbsp;от&nbsp;23 сентября 2010 г. №731 &laquo;Об утверждении стандарта раскрытия информации организациями, осуществляющими деятельность в&nbsp;сфере управления многоквартирными домами&raquo;</strong></p>

<h3>Общая информация</h3>
<p class="red button" onclick="$('#info_8').toggle()">Подпункт &laquo;а&raquo; пункт 3</p>
<table id="info_8" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
    <p><strong>Фирменное наименование управляющей организации:</strong></p>
    <p>ООО «МЕТЧЕЛСЕРВИС»;</p>
    <p><strong>Фамилия, имя&nbsp;и&nbsp;отчество руководителя:</strong></p>
    <p>Катаева Надежда Александровна, директор.</p>
    <p>Окончила &laquo;Московский психолого-социальный Университет по&nbsp;специальности государственное и&nbsp;муниципальное управление&raquo;, факультет &laquo;Экономика и&nbsp;Управление&raquo;, опыт работы сфере ЖКХ&nbsp;&mdash; c 2001 года.</p>
    <p><strong>Свидетельство о&nbsp;государственной регистрации и&nbsp;реквизиты:</strong></p>
    <p><em>Реквизиты</em></p>
    <table border="1" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
    <td>Полное наименование</td>
    <td>ООО «МЕТЧЕЛСЕРВИС»;</td>
    </tr>
    <tr>
    <td>Форма собственности</td>
    <td>Общество с&nbsp;ограниченной ответственностью</td>
    </tr>
    <tr>
    <td>Юр.адрес</td>
    <td>454017, г. Челябинск, ул. Мира, д. 63А, пом.1</td>
    </tr>
    <tr>
    <td>№ тел</td>
    <td>8 912 804 95 57</td>
    </tr>
    <tr>
    <td>Должность и&nbsp;ФИО&nbsp;руководителя</td>
    <td>Катаева Надежда Александровна</td>
    </tr>
    <tr>
    <td>Документ основание деятельности</td>
    <td>Устав</td>
    </tr>
    <tr>
    <td>Режим налогообложения</td>
    <td>Упрощенная система</td>
    </tr>
    <tr>
    <td>ИНН</td>
    <td>7451361524</td>
    </tr>
    <tr>
    <td>КПП</td>
    <td>7460013481</td>
    </tr>
    <tr>
    <td>ОКПО</td>
    <td>49143509</td>
    </tr>
    </tbody>
    </table>
    <p><em>Сведения о&nbsp;гос.регистрации:</em></p>
    <table border="1" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
    <td>Наименование регистрирующего органа</td>
    <td>Межрайонная ИФНС России №22 по Челябинской области</td>
    </tr>
    <tr>
    <td>Регистрационный номер</td>
    <td>1147460000467</td>
    </tr>
    <tr>
    <td>Дата регистрации</td>
    <td>04.02.2014 г.</td>
    </tr>
    </tbody>
    </table>
    <p><em>Банковские реквизиты:</em></p>
    <table border="1" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
    <td>Расчетный счет</td>
    <td>40702810972000004561</td>
    </tr>
    <tr>
    <td>Банк</td>
    <td>ОТДЕЛЕНИЕ №8597 СБЕРБАНКА РОССИИ 454048;</td>
    </tr>
    <tr>
    <td>Город</td>
    <td>Челябинск</td>
    </tr>
    <tr>
    <td>БИК</td>
    <td>047501602</td>
    </tr>
    <tr>
    <td>Корр.счет</td>
    <td>30101810700000000602</td>
    </tr>
    </tbody>
    </table>
    <p><strong>Почтовый адрес, адрес фактического местонахождения органов управления управляющей организации, контактные телефоны, а&nbsp;также официальный сайт в&nbsp;сети Интернет и&nbsp;адрес электронной почты</strong></p>
    <p><a href="/contacts/" target="_blank" rel="noopener noreferrer">Контакты</a></p>
    <p><strong>Режим работы управляющей организации, в&nbsp;том&nbsp;числе часы личного приема граждан сотрудниками управляющей организации и&nbsp;работы диспетчерских служб</strong></p>
    <p><a href="/contacts/" target="_blank" rel="noopener noreferrer">Режим работы и&nbsp;часы личного приема граждан</a></p>
    <p><strong>Перечень многоквартирных домов, находящихся в&nbsp;управлении управляющей организации</strong></p>
    <?php
    // Новости
    if (Core::moduleIsActive('informationsystem'))
    {
        $Informationsystem_Controller_Show = new Informationsystem_Controller_Show(
            Core_Entity::factory('Informationsystem', 18)
        );
        $Informationsystem_Controller_Show
            ->xsl(
                Core_Entity::factory('Xsl')->getByName('СписокДомов1')
            )
            //->groupsMode('none')
            ->itemsProperties(FALSE)
            ->itemsForbiddenTags(array('text'))
            ->group(FALSE)
            ->limit(50)
            ->show();
    }
    ?>
    <p><strong>Основные показатели финансово-хозяйственной деятельности управляющей организации</strong></p>
			<?php
				// Новости
				if (Core::moduleIsActive('informationsystem'))
				{
						$Informationsystem_Controller_Show = new Informationsystem_Controller_Show(
								Core_Entity::factory('Informationsystem', 27)
						);
						$Informationsystem_Controller_Show
								->xsl(
										Core_Entity::factory('Xsl')->getByName('СписокФайлов2')
								)
								//->groupsMode('none')
								->itemsProperties(TRUE)
								->itemsForbiddenTags(array('text'))
								->group(FALSE)
								->limit(50)
								->show();
				}
		?>

</td>
</tr>
</tbody>
</table>


<h3>Общая информация о&nbsp;многоквартирных домах</h3>
<p class="red button" onclick="$('#info_10').toggle()">Подпункт &laquo;б&raquo;&nbsp;&mdash; &laquo;в&raquo; пункт 3</p>
<table id="info_10" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
    <p><strong>Количество домов находящихся в&nbsp;управлении</strong></p>
    	
    <?php
    // Новости
    if (Core::moduleIsActive('informationsystem'))
    {
        $Informationsystem_Controller_Show = new Informationsystem_Controller_Show(
            Core_Entity::factory('Informationsystem', 18)
        );
        $Informationsystem_Controller_Show
            ->xsl(
                Core_Entity::factory('Xsl')->getByName('СписокДомов1')
            )
            //->groupsMode('none')
            ->itemsProperties(TRUE)
            ->itemsForbiddenTags(array('text'))
            ->group(FALSE)
            ->limit(50)
            ->show();
    }
    ?>	
</td>
</tr>
</tbody>
</table>

<h3>Информация о&nbsp;выполняемых работах (оказываемых услугах) по&nbsp;содержанию и&nbsp;ремонту общего имущества в&nbsp;многоквартирном доме</h3>

<p class="red button" onclick="$('#info_14').toggle()">Подпункт &laquo;г&raquo;&nbsp;&mdash; &laquo;д&raquo; пункт 3</p>
<table id="info_14" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
	<p><a href="https://metchelservice74.ru/files/plan2021.pdf" target="_blank" rel="noopener noreferrer">План текущего ремонта на 2021г.</a></p>
  <p><a href="https://metchelservice74.ru/upload/information_system_8/5/6/1/item_561/information_items_property_546.xls" target="_blank" rel="noopener noreferrer">План текущего ремонта на 2020г.</a></p>
	<p><a href="https://metchelservice74.ru/upload/information_system_8/5/6/0/item_560/information_items_property_545.doc" target="_blank" rel="noopener noreferrer">Перечень работ по содержанию МКД</a></p>
	<p><a href="https://metchelservice74.ru/upload/information_system_8/5/5/9/item_559/information_items_property_544.doc" target="_blank" rel="noopener noreferrer">Услуги, связанные с достижением целей управления МКД, которые оказываются управляющей организацией.</a></p>
	<p><a href="https://metchelservice74.ru/upload/information_system_8/5/5/8/item_558/information_items_property_543.docx" target="_blank" rel="noopener noreferrer">Минимальный перечень услуг по МКД</a></p>
	<p><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_157/information_items_property_31.rtf" target="_blank" rel="noopener noreferrer">Постановление Правительства РФ от 03 апреля 2013 г. № 290</a></p>
	<p>Стоимость работ 2020 г.</p>
	<ul>
		<li><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_158/len1.xlsx" target="_blank" rel="noopener noreferrer">Стоимость работ Ленина, 1 (Рощино)</a></li>
		<li><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_158/len20.xlsx" target="_blank" rel="noopener noreferrer">Стоимость работ Ленина, 20 (Рощино)</a></li>
		<li><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_158/len22.xlsx" target="_blank" rel="noopener noreferrer">Стоимость работ Ленина, 22 (Рощино)</a></li>
		<li><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_158/mol10.xlsx" target="_blank" rel="noopener noreferrer">Стоимость работ Молодёжная, 10 (Рощино)</a></li>
		<li><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_158/mira63.xlsx" target="_blank" rel="noopener noreferrer">Стоимость работ Мира, 63</a></li>
		<li><a href="https://metchelservice74.ru/upload/information_system_8/1/5/7/item_158/mira63a.xlsx" target="_blank" rel="noopener noreferrer">Стоимость работ Мира, 63 А</a></li>
	</ul>

</td>
</tr>
</tbody>
</table>
<p class="red button" onclick="$('#info_12').toggle()">Подпункт &laquo;е&raquo; пункт 3</p>
<table id="info_12" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
</td>
</tr>
</tbody>
</table>
<p class="red button" onclick="$('#info_13').toggle()">Подпункт &laquo;з&raquo; пункт 3</p>
<table id="info_13" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
</td>
</tr>
</tbody>
</table>
<h3>Информация о&nbsp;капитальном ремонте общего имущества в&nbsp;многоквартирном доме</h3>
<p class="red button" onclick="$('#info_15').toggle()">Подпункт &laquo;ж&raquo; пункт 3</p>
<table id="info_15" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
<p><a href="https://metchelservice74.ru/upload/information_system_8/5/6/4/item_564/information_items_property_549.docx">Информация о капитальном ремонте общего имущества в многоквартирном доме</a></p>
</td>
</tr>
</tbody>
</table>
<h3>Информация о&nbsp;случаях привлечения к&nbsp;административной ответственности</h3>
<p class="red button" onclick="$('#info_16').toggle()">Подпункт &laquo;к&raquo; пункт 3</p>
<table id="info_16" border="0" cellspacing="0" cellpadding="0" style="width: 100%;">
<tbody>
<tr>
<td>
<p>Случаев привлечения управляющей организации в&nbsp;предыдущем календарном году к&nbsp;административной ответственности за&nbsp;нарушения в&nbsp;сфере управления многоквартирными домами не&nbsp;было.</p>
</td>
</tr>
</tbody>
</table>