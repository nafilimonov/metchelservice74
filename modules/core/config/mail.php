<?php

return array (
	'default' => array (
		'driver' => 'smtp',
	),
	'sendmail' => array (
		'driver' => 'sendmail',
	),
	'smtp' => array (
		'driver' => 'smtp',
		'username' => 'info@metchelservice74.ru',
		'port' => '465', // для SSL порт 465
		'host' => 'ssl://mail.metchelservice74.ru', // для SSL используйте ssl://smtp.gmail.com
		'password' => 'bM0wB0aO7i',
        //'log' => TRUE,
//		'tls' => TRUE,
//        'timeout' => 10,
        'options' => array(
            'ssl' => array(
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            )
        )
	)
);