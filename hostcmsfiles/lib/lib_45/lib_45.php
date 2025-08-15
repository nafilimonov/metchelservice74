<h1>Показания счетчиков</h1>

<?
$captcha_id = 27102017;

$acc_code = Core_Array::getPost('acc_code');
$house_num = Core_Array::getPost('house_num');
$flat_num = Core_Array::getPost('flat_num');
$hvs = Core_Array::getPost('hvs');
$gvs = Core_Array::getPost('gvs');
$hvs_2 = Core_Array::getPost('hvs_2');
$gvs_2 = Core_Array::getPost('gvs_2');

if (Core_Array::getPost('submit_question'))
{
	if ( Core_Captcha::valid(Core_Array::getPost('captcha_id'), Core_Array::getPost('captcha')) ) {

		$message = "Показания счетчиков:\n";
		$message .= "Лицевой счет: " . $acc_code . "\n";
		$message .= "Номер дома: " . $house_num . "\n";
		$message .= "Номер квартиры: " . $flat_num . "\n";
		$message .= "ХВС: " . $hvs . "\n";
		$message .= "ГВС: " . $gvs . "\n";
		$message .= "ХВС-2: " . $hvs_2 . "\n";
		$message .= "ГВС-2: " . $gvs_2 . "\n";

		$oCore_Mail_Driver = Core_Mail::instance()
			->to(SEND_MAIL)
			->from(SEND_MAIL)
			->subject('Показания счетчиков')
			->message($message)
			->contentType('text/plain')
			->send();

		if ($oCore_Mail_Driver->getStatus()) {
			echo '<div id="message">Благодарим Вас! Показания счетчиков приняты.</div>';
		}
		else {
			echo '<div id="error">Произошла ошибка. Попробуйте позже!</div>';
		}
	}
	else {
		echo '<div id="error">Введен неверный код подтверждения!</div>';
	}
}
?>

<form action="./" method="post">
	<div class="row"> 
		<div class="caption">Лицевой счет</div>
		<div class="field">
			<input type="text" name="acc_code" placeholder="3140000000" size="50" value="<?=$acc_code?>">
		</div>
	</div>
	<div class="row">
		<div class="caption">Номер дома</div>
		<div class="field">
			<input type="text" name="house_num" placeholder="1А"  size="50" value="<?=$house_num?>">
		</div>
	</div>
	<div class="row">
		<div class="caption">Номер квартиры</div>
		<div class="field">
			<input type="text" name="flat_num" placeholder="0" size="50" value="<?=$flat_num?>">
		</div>
	</div>
	<div class="row">
		<div class="caption">ХВС</div>
		<div class="field">
			<input type="text" name="hvs" placeholder="0" size="50" value="<?=$hvs?>">
		</div>
	</div>
	<div class="row">
		<div class="caption">ГВС</div>
		<div class="field">
			<input type="text" name="gvs" placeholder="0" size="50" value="<?=$gvs?>">
		</div>
	</div>
	<div class="row">
		<div class="caption">ХВС (2-ой счетчик)</div>
		<div class="field">
			<input type="text" name="hvs_2" placeholder="0" size="50" value="<?=$hvs_2?>">
		</div>
	</div>
	<div class="row">
		<div class="caption">ГВС (2-ой счетчик)</div>
		<div class="field">
			<input type="text" name="gvs_2" placeholder="0" size="50" value="<?=$gvs_2?>">
		</div>
	</div>
	<div class="row">
		<div class="caption"></div>
		<div class="field">
			<img id="guestBookForm" class="captcha" src="/captcha.php?id=<?=$captcha_id?>&height=30&width=100" title="Контрольное число" name="captcha">
			<div class="captcha">
				<img src="/images/refresh.png">
				<span onclick="$('#guestBookForm').updateCaptcha('<?=$captcha_id?>', 30); return false">Показать другое число</span>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="caption">
			Контрольное число<sup><font color="red">*</font></sup></div>
			<div class="field">
				<input type="hidden" name="captcha_id" value="<?=$captcha_id?>">
				<input type="text" name="captcha" size="15">
			</div>
		</div>
	<div class="row">
		<div class="caption"></div>
		<div class="field">
			<input type="submit" name="submit_question" value="Отправить показания" class="button button1">
		</div>
	</div>
</form>