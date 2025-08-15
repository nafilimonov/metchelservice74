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
class Compression_Controller_Js extends Compression_Controller
{
	/**
	 * Js array
	 * @var array
	 */
	protected $_aJs = array();

	/**
	 * Add Js
	 * @param string $path
	 * @return self
	 */
	public function addJs($path)
	{
		$this->_aJs[] = $path;
		return $this;
	}

	/**
	 * Get path of the cache dir for the JS with CMS_FOLDER
	 * @return string
	 */
	public function getJsDirPath()
	{
		return CMS_FOLDER . Core::$mainConfig['compressionJsDirectory'];
	}

	/**
	 * Get href of the cache dir for the JS
	 * @return string
	 */
	public function getJsDirHref()
	{
		return '/' . Core::$mainConfig['compressionJsDirectory'];
	}

	/**
	 * Get path
	 * @return string
	 */
	public function getPath()
	{
		$sFileName = md5(implode(',', $this->_aJs)) . '.js';

		$sJsDir = $this->getJsDirPath();

		clearstatcache();

		if (!Core_File::isFile($sJsDir . $sFileName))
		{
			$aFilePaths = array();
			$sContent = '';
			foreach ($this->_aJs as $js)
			{
				$sPath = ltrim($js, '/\\');
				$aFilePaths[] = $sPath;

				$sFullPath = Core_File::pathCorrection(CMS_FOLDER . $sPath);

				if (Core_File::isFile($sFullPath))
				{
					$sContent .= $this->compress(
						Core_File::read($sFullPath), $js
					)
					. ';' . PHP_EOL;
				}
			}

			clearstatcache();

			if (!Core_File::isDir($sJsDir))
			{
				Core_File::mkdir($sJsDir);
			}

			// sourceMappingURL
			$sMapFileName = $sFileName . '.map';
			$sContent .= "//# sourceMappingURL={$sMapFileName}";

			// Source Map Revision 3 Proposal: https://sourcemaps.info/spec.html
			$aMapContent = new stdClass();
			$aMapContent->version = 3;
			$aMapContent->file = $sFileName;
			$aMapContent->sources = $aFilePaths;
			$aMapContent->sourcesContent = array_fill(0, count($aFilePaths), NULL);

			// Save .js
			Core_File::write($sJsDir . $sFileName, $sContent);
			// Save .js.map
			Core_File::write($sJsDir . $sMapFileName, json_encode($aMapContent));
		}

		return $this->getJsDirHref() . $sFileName;
	}

	/**
	 * Compress data
	 * @return string
	 */
	public function compress($str, $fileName)
	{
		$str = Core_Str::removeBOM($str);

		// Remove sourceMappingURL
		$str = preg_replace('~^//[#@]\s(source(?:Mapping)?URL)=\s*(\S+)~m', '', $str);

		return strpos($fileName, '.min.') === FALSE
			? Compression_Controller_JSMin::minify($str)
			: $str;
	}

	/**
	 * Clear controller
	 * @return self
	 */
	public function clear()
	{
		$this->_aJs = array();
		return $this;
	}

	/**
	 * Delete all cached files
	 * @return self
	 */
	public function deleteAllJs()
	{
		Core_File::deleteDir(
			$this->getJsDirPath()
		);

		clearstatcache();

		return TRUE;
	}
}