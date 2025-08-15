<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2018 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Shop_Cart_Controller_Add extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'siteuser_id',
		'shop_item_id',
		'shop_item_name',
	);

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 */
	public function execute($operation = NULL)
	{
		if (strlen($this->shop_item_name))
		{
			$oShop_Cart_Controller = Shop_Cart_Controller::instance();

			$oShop_Cart_Controller
				->clear()
				->shop_item_id($this->shop_item_id)
				->siteuser_id($this->siteuser_id)
				->quantity(1)
				->add();
		}

		return NULL;
	}
}