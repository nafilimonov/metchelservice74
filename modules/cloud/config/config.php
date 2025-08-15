<?php

return array (
	'drivers' => array(
		'google' => array(
			'name' => 'Google Drive',
			'driver' => 'google',
			'chunk' => 1048576,
		),
		'yandex' => array(
			'name' => 'Яндекс.Диск',
			'driver' => 'yandex',
			'chunk' => 1048576,
		),
		'dropbox' => array(
			'name' => 'Dropbox',
			'driver' => 'dropbox',
			'chunk' => 1048576,
		),
		'onedrive' => array(
			'name' => 'OneDrive',
			'driver' => 'onedrive',
			'chunk' => 327680 * 3, // https://docs.microsoft.com/ru-ru/onedrive/developer/rest-api/api/driveitem_createuploadsession?view=odsp-graph-online
		)
	)
);