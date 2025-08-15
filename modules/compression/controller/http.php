<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Compression.
 *
 * @package HostCMS
 * @subpackage Compression
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Compression_Controller_Http extends Compression_Controller
{
	/**
	 * List of supported compression algorithms
	 * @var array
	 */
	static protected $_allowEncoding = array('x-gzip', 'gzip', 'deflate');

	/**
	 * Get accepted encoding methods
	 * @return string
	 */
	public function getAcceptEncoding()
	{
		$HTTP_ACCEPT_ENCODING = Core_Array::get($_SERVER, 'HTTP_ACCEPT_ENCODING');

		if ($HTTP_ACCEPT_ENCODING)
		{
			foreach (self::$_allowEncoding as $encoding)
			{
				if (strpos($HTTP_ACCEPT_ENCODING, $encoding) !== FALSE)
				{
					return $encoding;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Checks if compression was allowed
	 * @return boolean
	 */
	public function compressionAllowed()
	{
		return !headers_sent()
			&& !connection_aborted()
			&& function_exists('gzcompress')
			&& !ini_get('zlib.output_compression');
	}

	/**
	 * Compress data
	 * @param string $content
	 * @return string
	 */
	public function compress($content, $encoding = NULL)
	{
		is_null($encoding)
			&& $encoding = $this->getAcceptEncoding();

		if (Core_Page::instance()->response)
		{
			Core_Page::instance()
				->response
				->header('Content-Encoding', $encoding);
		}
		else
		{
			header('Content-Encoding: ' . $encoding);
		}

		$level = 6;

		return gzencode((string) $content, $level, ($encoding == 'deflate') ? FORCE_DEFLATE : FORCE_GZIP);
	}
}