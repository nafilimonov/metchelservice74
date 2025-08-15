<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Restapi_Controller.
 *
 * @package HostCMS
 * @subpackage Restapi
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2018 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Restapi_Controller extends Core_Servant_Properties
{
	/**
	 * Generate token
	 * @return string
	 */
	static public function createToken($bytes = 16)
	{
		$token = function_exists('openssl_random_pseudo_bytes')
			? openssl_random_pseudo_bytes($bytes)
			: random_bytes($bytes);

		return bin2hex($token);
	}
}