<?php

require_once('../bootstrap.php');

if (Core::moduleIsActive('counter'))
{
	$oSite = Core_Entity::factory('Site')->find(Core_Array::getGet('id', 0, 'int'));

	if ($oSite->id)
	{
		!defined('CURRENT_SITE') && define('CURRENT_SITE', $oSite->id);

		Core::initConstants($oSite);

		Counter_Controller::instance()
			->site($oSite)
			->referrer(urldecode(Core_Array::getGet('refer', '', 'trim')))
			->page(urldecode(Core_Array::getGet('current_page', '', 'trim')))
			->display(Core_Array::getGet('screen', '', 'trim'))
			->ip(Core::getClientIp())
			->userAgent(Core_Array::get($_SERVER, 'HTTP_USER_AGENT', '', 'str'))
			->counterId(Core_Array::getGet('counter', 0, 'int'))
			->updateCounter(Core_Array::getGet('update_counter', 1, 'int'))
			->updateData()
			->buildCounter();
	}
}