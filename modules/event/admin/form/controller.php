<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Admin forms.
 *
 * @package HostCMS
 * @subpackage Skin
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2018 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
abstract class Event_Admin_Form_Controller extends Admin_Form_Controller
{
	/**
	 * Create new form controller
	 * @param Admin_Form_Model $oAdmin_Form
	 * @return object
	 */
	static public function create(Admin_Form_Model $oAdmin_Form = NULL)
	{
		$className = 'Skin_' . ucfirst(Core_Skin::instance()->getSkinName()) . '_' . __CLASS__;

		if (!class_exists($className))
		{
			throw new Core_Exception("Class '%className' does not exist",
					array('%className' => $className));
		}

		return new $className($oAdmin_Form);
	}
}