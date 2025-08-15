<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bootstrap.php');

$sendto = SEND_MAIL;

$name = nl2br($_POST['fio']);
$mail = nl2br($_POST['mail']);
$phone = nl2br($_POST['phone']);
$comment = nl2br($_POST['comment']);

// Формирование заголовка письма
$subject  = "Вопрос с сайта";

// Формирование тела письма
$msg  = "<html><body style='font-family:Arial,sans-serif;'>";
$msg .= "<h2 style='font-weight:bold;border-bottom:1px dotted #ccc;'>Вопрос с сайта</h2>\r\n";
$msg .= "<p><strong>От кого:</strong> ".$name."</p>\r\n";
$msg .= "<p><strong>E-mail:</strong> ".$mail."</p>\r\n";
$msg .= "<p><strong>Номер телефона:</strong> ".$phone."</p>\r\n";
$msg .= "<p><strong>Текст:</strong> ".$comment."</p>\r\n";
$msg .= "</body></html>";


$oCore_Mail_Driver = Core_Mail::instance()
	->to(SEND_MAIL)
	->from(SEND_MAIL)
	->subject($subject)
	->message($msg)
	->contentType('text/html')
	->send();

// отправка сообщения
if ($oCore_Mail_Driver->getStatus()) {
	echo "true";
} else {
	echo "false";
}