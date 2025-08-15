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
class Compression_Controller_Css extends Compression_Controller
{
	/**
	 * Array of paths
	 * @var array
	 */
	protected $_aCss = array();

	/**
	 * Colors for replace
	 * @var array
	 */
	protected static $_aColors = array(
		'#808080' => 'gray',
		'#008000' => 'green',
		'#800000' => 'maroon',
		'#000080' => 'navy',
		'#808000' => 'olive',
		'#800080' => 'purple',
		'#c0c0c0' => 'silver',
		'#008080' => 'teal'
	);

	/**
	 * Add path of CSS
	 * @param string $path
	 * @return self
	 */
	public function addCss($path)
	{
		$this->_aCss[] = $path;
		return $this;
	}

	/**
	 * Get path of the cache dir for the CSS with CMS_FOLDER
	 * @return string
	 */
	public function getCssDirPath()
	{
		return CMS_FOLDER . Core::$mainConfig['compressionCssDirectory'];
	}

	/**
	 * Get href of the cache dir for the CSS
	 * @return string
	 */
	public function getCssDirHref()
	{
		return '/' . Core::$mainConfig['compressionCssDirectory'];
	}

	/**
	 * Get filename. Depends on $aCss
	 * @param array $aCss Array of paths
	 * @return string
	 */
	public function getFilename($aCss)
	{
		return md5(implode(',', $aCss)) . '.css';
	}

	/**
	 * Build CSS and return path to compressed file
	 * @return string
	 */
	public function getPath()
	{
		$sFileName = $this->getFilename($this->_aCss);

		$this->buildCss($sFileName);

		return $this->getCssDirHref() . $sFileName;
	}

	/**
	 * Build CSS and return compressed content
	 * @return string
	 */
	public function getContent()
	{
		return $this->_getCssContent($this->_aCss);
	}

	/**
	 * Build CSS
	 * @param string $sFileName CSS filename
	 * @param boolean $bReplace Replace if exists, default FALSE
	 * @return self
	 */
	public function buildCss($sFileName, $bReplace = FALSE)
	{
		$sCssDir = $this->getCssDirPath();

		if ($bReplace || !Core_File::isFile($sCssDir . $sFileName))
		{
			// Delete all items for $sFileName
			$oCompression_Css = Core_Entity::factory('Compression_Css');
			$oCompression_Css
				->queryBuilder()
				->where('filename', '=', $sFileName);
			$oCompression_Css->deleteAll(FALSE);

			foreach ($this->_aCss as $sorting => $sCss)
			{
				$oCompression_Css = Core_Entity::factory('Compression_Css');
				$oCompression_Css->filename = $sFileName;
				$oCompression_Css->sorting = $sorting;
				$oCompression_Css->path = $sCss;
				$oCompression_Css->save();
			}

			!Core_File::isDir($sCssDir)
				&& Core_File::mkdir($sCssDir);

			Core_File::write($sCssDir . $sFileName, $this->getContent());
		}

		return $this;
	}

	/**
	 * Get content of CSS for array of paths
	 * @param array $aCss Array of paths
	 * @return string
	 */
	protected function _getCssContent($aCss)
	{
		$sContent = '';
		foreach ($aCss as $css)
		{
			$sPath = Core_File::pathCorrection(CMS_FOLDER . ltrim($css, '/\\'));

			if (Core_File::isFile($sPath))
			{
				$sContent .= $this->compress(
					Core_File::read($sPath), $css
				)
				. PHP_EOL;

				// Замена относительных путей
				// url('fonts/OpenSans-CondensedItalic.woff')
				$dirname = dirname($css) . '/';

				$sContent = preg_replace(
					// skip url(data:image/png;base64 ...)
					'/(url\()\s*(["\']?)(?![a-z\-]+:)([^\/"\'])/i',
					'${1}${2}' . $dirname . '${3}',
					$sContent
				);
			}
		}

		return $sContent;
	}

	/**
	 * Compress data
	 * @return string
	 */
	public function compress($str, $fileName)
	{
		$str = Core_Str::removeBOM($str);

		// Комментарии, удаляются даже без _compress, т.к. необходимо удалить sourceMappingURL= в .min
		$str = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $str);

		return strpos($fileName, '.min.') === FALSE
			? $this->_compress($str)
			: $str;
	}

	/**
	 * Compress data
	 * @return string
	 */
	protected function _compress($str)
	{
		// White space may appear between a combinator and the simple selectors around it.
		// Only the characters "space" (U+0020), "tab" (U+0009), "line feed" (U+000A),
		// "carriage return" (U+000D), and "form feed" (U+000C) can occur in whitespace.
		$str = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $str);

		// Повторяющиеся пробелы
		while (mb_strpos($str, '  ') !== FALSE)
		{
			$str = str_replace('  ', ' ', $str);
		}

		// Пробелы до и после { }, пробелы и последняя точка с запятой
		$str = preg_replace('/\s*{\s*/', '{', $str);
		$str = preg_replace('/;?\s*}\s*/', '}', $str);

		// Пробелы вокруг ;
		$str = preg_replace('/\s*;\s*/', ';', $str);

		// Несколько ;
		$str = preg_replace('/;;+/', ';', $str);

		// #3399FF => 39F
		$str = preg_replace('/([^=])#([a-f\d])\\2([a-f\d])\\3([a-f\d])\\4([\s;\}])/i', '${1}#${2}${3}${4}${5}', $str);

		// #808080 => gray
		$str = str_replace(array_keys(self::$_aColors), array_values(self::$_aColors), $str);

		// : 0.0px, .0px, 0px => :0
		$str = preg_replace('/([^0-9])(?:0?\.)?0(?:em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $str);
		// The colon is required to NOT change '@keyframes phase { 0% { opacity: 1; }' to '@keyframes phase { 0 { opacity: 1; }'
		$str = preg_replace('/\:([^0-9])(?:0?\.)?0%/i', ':${1}0', $str);

		// border-top: none; => border-top: 0;
		$str = preg_replace('/(border\-?(?:top|bottom|left|right|)|outline)\:\s?none(;|\}|\s?\!)/i', '${1}:0${2}', $str);

		return $str;
	}

	/**
	 * Clear controller
	 * @return self
	 */
	public function clear()
	{
		$this->_aCss = array();

		return $this;
	}

	/**
	 * Delete all cached files
	 * @return self
	 */
	public function deleteAllCss()
	{
		Core_File::deleteDir(
			$this->getCssDirPath()
		);

		return $this;
	}
}