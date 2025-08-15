<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Shortlink
 *
 * @package HostCMS
 * @subpackage Shortlink
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Shortlink_Controller
{
	/**
	 * Chars
	 * @var string
	 */
	static protected $_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	/**
	* Converts a number to an alpha-numeric string.
	*
	* @param int $id Number to convert
	* @return string
	*/
	static public function encode($id)
	{
		$iCharsLen = strlen(self::$_chars);
		$mod = $id % $iCharsLen;

		if ($id - $mod == 0)
		{
			return substr(self::$_chars, $id, 1);
		}

		$return = '';
		while ($mod > 0 || $id > 0)
		{
			$return = substr(self::$_chars, $mod, 1) . $return;
			$id = ($id - $mod) / $iCharsLen;
			$mod = $id % $iCharsLen;
		}

		return $return;
	}

	/**
	 * Converts an alpha numeric string to a number
	 *
	 * @param string $shortlink String to decode
	 * @return int Decoded number
	 */
	static public function decode($shortlink)
	{
		$iCharsLen = strlen(self::$_chars);
		$len = strlen($shortlink);

		$id = 0;
		for ($i = 0; $i < $len; $i++)
		{
			$id += strpos(self::$_chars, substr($shortlink, $i, 1)) * pow($iCharsLen, $len - $i - 1);
		}

		return $id;
	}
}