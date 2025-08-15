<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Counter_Entity extends Core_Empty_Entity
{
	/**
	 * Backend property
	 * @var string
	 */
	public $adminCode = NULL;

	public $id;

	public $name;

	/**
	 * Backend callback method
	 * @return string
	 */
	public function adminCounter()
	{
		ob_start();
		/*Core_Html_Entity::factory('Img')
			->src('/counter/counter.php?id='.CURRENT_SITE.'&counter='.$this->id)
			->execute();*/

		$oCurrentAlias = Core_Entity::factory('Site', CURRENT_SITE)->getCurrentAlias();
		if (!is_null($oCurrentAlias))
		{
			Counter_Controller::instance()->showCounterCode($this->id, $oCurrentAlias->name);
		}

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function adminCode()
	{
		ob_start();
		$oCurrentAlias = Core_Entity::factory('Site', CURRENT_SITE)->getCurrentAlias();
		if (!is_null($oCurrentAlias))
		{
			Counter_Controller::instance()->showCounterCode($this->id, $oCurrentAlias->name);
		}
		$sCode = ob_get_clean();

		ob_start();
		Core_Html_Entity::factory('Textarea')
			->value($sCode)
			->style('margin: 7px; width: 443px; height: 216px;')
			->onclick('$(this).select()')
			->execute();
		$content = ob_get_clean();

		ob_start();
		Core_Html_Entity::factory('I')
			->class('fa fa-file-code-o pointer')
			->onclick("$.showWindow('counterCode{$this->id}', '" . Core_Str::escapeJavascriptVariable($content) .
			"', {width: 500, height: 250, title: '" . Core::_('Counter.code_title', $this->name) .
			"', Maximize: false, resizable: false})/*.HostCMSWindow('moveToTop')*/")
			->execute();
		return ob_get_clean();
	}
}