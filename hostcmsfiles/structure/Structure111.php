<h1>Задать вопрос</h1>
<!-- validate -->
<script type="text/javascript" src="/hostcmsfiles/jquery/jquery.validate.js"></script>
<script type="text/javascript" src="/hostcmsfiles/jquery/jquery.form.js"></script>
<!-- maskedinput -->
<script src="/hostcmsfiles/jquery/jquery.maskedinput.js" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {
		$("#phone_f").mask("+7 (999) 999-99-99");
		$("#form").validate({
			focusInvalid: true,
			errorClass: "input_error",
			submitHandler: function(form) {
				$.ajax({
					type: 'POST',
					url: '/ajax/send_mail.php',
					data: $(form).serialize(),
					success: function(result){
						if(result == "true"){
							$(form).trigger( 'reset' );
							$("#message_f").show();
							$(form).hide();
						}
						else {
							$("#error_f").show();
						}
					}
				});
				return false; 
			  }
		})
	});
</script>

		<div id="message_f">Сообщение успешно отправлено! Мы ответим вам в кротчайшие сроки.</div>
		<div id="error_f">Сообщение не отправлено!</div>
		<form name="form" id="form" class="validate" action="" method="post" enctype="multipart/form-data">
            <span>Введите ваше имя:</span>
			<input name="fio" value="" class="required" minlength="1" title="Введите ваше имя" placeholder="Иванов Иван" type="text">
            <span>Введите ваш e-mail:</span>
			<input name="mail" value="" class="required" minlength="1" title="Введите e-mail" placeholder="name@mail.ru" type="email">
<span>Введите ваш телефон:</span>
			<input name="phone" id="phone_f" value=""  title="Введите ваш телефон" placeholder="+7 (999) 999-99-99" type="text">
            <span>Текст сообщение:</span>
			<textarea name="comment"  ols="50" rows="5" wrap="off" placeholder="Текст сообщение"></textarea>
			<input value="Отправить" class="button" type="submit" style="margin-top:10px; margin-right:25px">
		</form>
			<br><a href="https://metchelservice74.ru/upload/information_system_8/5/6/2/item_562/information_items_property_547.doc" target="_blank">Закон о персональных данных</a><br>
			<a href="https://metchelservice74.ru/upload/information_system_8/5/6/3/item_563/information_items_property_548.docx"  target="_blank">Положение о хранении и использовании персональных данных</a><br>
			