<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Bot_Create
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Bot_Create extends Bot_Controller
{
	/**
	 * Bot module color
	 * @var string
	 */
	protected $_color = '#f4b400';

	/**
	 * Get bot module fields
	 * @return array
	 */
	public function getFields()
	{
		$aStatusOptions = $aNeedOptions = $aMaturityOptions = $aCrmSourceOptions = array();

		$aLead_Statuses = Core_Entity::factory('Lead_Status')->getAllBySite_id(CURRENT_SITE);
		foreach ($aLead_Statuses as $oLead_Status)
		{
			$aStatusOptions[$oLead_Status->id] = array(
				'value' => $oLead_Status->name,
				'color' => $oLead_Status->color
			);
		}

		$aLead_Needs = Core_Entity::factory('Lead_Need')->getAllBySite_id(CURRENT_SITE);
		foreach ($aLead_Needs as $oLead_Need)
		{
			$aNeedOptions[$oLead_Need->id] = array(
				'value' => $oLead_Need->name,
				'color' => $oLead_Need->color
			);
		}

		$aLead_Maturities = Core_Entity::factory('Lead_Maturity')->getAllBySite_id(CURRENT_SITE);
		foreach ($aLead_Maturities as $oLead_Maturity)
		{
			$aMaturityOptions[$oLead_Maturity->id] = array(
				'value' => $oLead_Maturity->name,
				'color' => $oLead_Maturity->color
			);
		}

		$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
		foreach ($aCrm_Sources as $oCrm_Source)
		{
			$aCrmSourceOptions[$oCrm_Source->id] = array(
				'value' => $oCrm_Source->name,
				'color' => $oCrm_Source->color,
				'icon' => $oCrm_Source->icon
			);
		}

		$this->_fields = array(
			'status' => array(
				'caption' => Core::_('Lead.status'),
				'type' => 'dropdown',
				'options' => $aStatusOptions,
				'value' => FALSE,
				'obligatory' => TRUE,
				'divAttr' => 'form-group col-xs-12 col-sm-6'
			),
			'need' => array(
				'caption' => Core::_('Lead.need'),
				'type' => 'dropdown',
				'options' => $aNeedOptions,
				'value' => FALSE,
				'obligatory' => TRUE,
				'divAttr' => 'form-group col-xs-12 col-sm-6'
			),
			'maturity' => array(
				'caption' => Core::_('Lead.maturity'),
				'type' => 'dropdown',
				'options' => $aMaturityOptions,
				'value' => FALSE,
				'obligatory' => TRUE,
				'divAttr' => 'form-group col-xs-12 col-sm-6'
			),
			'source' => array(
				'caption' => Core::_('Lead.source'),
				'type' => 'dropdown',
				'options' => $aCrmSourceOptions,
				'value' => FALSE,
				'obligatory' => TRUE,
				'divAttr' => 'form-group col-xs-12 col-sm-6'
			),
			'comment' => array(
				'caption' => Core::_('Lead.comment'),
				'type' => 'textarea',
				'value' => FALSE,
				'obligatory' => TRUE
			),
		);

		return parent::getFields();
	}

	/**
	 * Check available
	 */
	public function available()
	{
		return Core::moduleIsActive('lead') && Core::moduleIsActive('shop');
	}

	/**
	 * Execute business logic
	 */
	public function execute()
	{
		$aSettings = json_decode($this->_oBot_Module->json, TRUE);

		$oSiteuser = Core::moduleIsActive('siteuser') && $this->_oObject->siteuser_id
			? $this->_oObject->Siteuser
			: NULL;

		if (is_null($oSiteuser) && get_class($this->_oObject) == 'Shop_Order_Model')
		{
			if (method_exists($this->_oObject, 'getResponsibleUsers'))
			{
				$aResponsibleUsers = isset($aSettings['responsible']) && $aSettings['responsible']
					? $this->_oObject->getResponsibleUsers()
					: array();
			}
			else
			{
				Core_Log::instance()->clear()
					->status(Core_Log::$ERROR)
					->write("Lead_Bot_Create: method getResponsibleUsers() doesn`t exist in model");
			}

			if (count($aResponsibleUsers))
			{
				$oUser = $aResponsibleUsers[0];

				$oCore_Meta = new Core_Meta();
				$oCore_Meta
					->addObject('user', $oUser)
					->addObject('object', $this->_oObject)
					->addObject('settings', $aSettings);

				$shop_id = !is_null($this->_oObject) && $this->_oObject->shop_id
					? $this->_oObject->shop_id
					: 0;

				$oLead = Core_Entity::factory('Lead');
				$oLead->site_id = $this->_oObject->Shop->site_id;
				$oLead->lead_status_id = isset($aSettings['status']) ? $aSettings['status'] : 0;
				$oLead->lead_need_id = isset($aSettings['need']) ? $aSettings['need'] : 0;
				$oLead->lead_maturity_id = isset($aSettings['maturity']) ? $aSettings['maturity'] : 0;
				$oLead->crm_source_id = isset($aSettings['source']) ? $aSettings['source'] : 0;
				$oLead->siteuser_id = 0;
				$oLead->shop_id = $shop_id;
				$oLead->shop_order_id = $this->_oObject->id;
				$oLead->deal_id = 0;
				$oLead->datetime = Core_Date::timestamp2sql(time());
				$oLead->name = $this->_oObject->name;
				$oLead->surname = $this->_oObject->surname;
				$oLead->patronymic = $this->_oObject->patronymic;
				$oLead->last_contacted = Core_Date::timestamp2sql(time());
				$oLead->company = $this->_oObject->company;
				$oLead->comment = isset($aSettings['comment']) && strlen(trim($aSettings['comment'])) ? $oCore_Meta->apply($aSettings['comment']) : '';
				$oLead->user_id = $oUser->id;
				$oLead->amount = $this->_oObject->getAmount();
				$oLead->save();

				if (strlen(trim($this->_oObject->phone)))
				{
					$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type')->getFirst();
					$directory_phone_type_id = $oDirectory_Phone_Type
						? $oDirectory_Phone_Type->id
						: 0;

					$oDirectory_Phone = Core_Entity::factory('Directory_Phone')
						->directory_phone_type_id($directory_phone_type_id)
						->public(0)
						->value($this->_oObject->phone)
						->save();

					$oLead->add($oDirectory_Phone);
				}

				if (strlen(trim($this->_oObject->email)))
				{
					$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->getFirst();
					$directory_email_type_id = $oDirectory_Email_Type
						? $oDirectory_Email_Type->id
						: 0;

					$oDirectory_Email = Core_Entity::factory('Directory_Email')
						->directory_email_type_id($directory_email_type_id)
						->public(0)
						->value($this->_oObject->email)
						->save();

					$oLead->add($oDirectory_Email);
				}

				if (strlen(trim($this->_oObject->address)))
				{
					$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->getFirst();
					$directory_address_type_id = $oDirectory_Address_Type
						? $oDirectory_Address_Type->id
						: 0;

					$oDirectory_Address = Core_Entity::factory('Directory_Address')
						->directory_address_type_id($directory_address_type_id)
						->country($this->_oObject->shop_country_id
							? $this->_oObject->Shop_Country->name
							: ''
						)
						->postcode(trim($this->_oObject->postcode))
						->city($this->_oObject->shop_country_location_city_id
							? $this->_oObject->Shop_Country_Location_City->name
							: ''
						)
						->public(0)
						->value($this->_oObject->address)
						->save();

					$oLead->add($oDirectory_Address);
				}

				if ($oLead->lead_status_id)
				{
					$oLead_Step = Core_Entity::factory('Lead_Step');
					$oLead_Step->user_id = $oUser->id;
					$oLead_Step->lead_status_id = $oLead->lead_status_id;
					$oLead_Step->datetime = Core_Date::timestamp2sql(time());
					$oLead->add($oLead_Step);
				}

				$aShop_Order_Items = $this->_oObject->Shop_Order_Items->findAll();
				foreach ($aShop_Order_Items as $oShop_Order_Item)
				{
					$oLead_Shop_Item = Core_Entity::factory('Lead_Shop_Item');
					$oLead_Shop_Item->shop_item_id = $oShop_Order_Item->shop_item_id;
					$oLead_Shop_Item->shop_currency_id = $oShop_Order_Item->Shop_Item->shop_currency_id;
					$oLead_Shop_Item->name = $oShop_Order_Item->name;
					$oLead_Shop_Item->quantity = $oShop_Order_Item->quantity;
					$oLead_Shop_Item->price = $oShop_Order_Item->price;
					$oLead_Shop_Item->marking = $oShop_Order_Item->marking;
					$oLead_Shop_Item->rate = $oShop_Order_Item->rate;
					$oLead_Shop_Item->user_id = $oUser->id;
					$oLead_Shop_Item->type = $oShop_Order_Item->type;
					$oLead_Shop_Item->shop_warehouse_id = $oShop_Order_Item->shop_warehouse_id;
					$oLead->add($oLead_Shop_Item);
				}
			}
		}
	}
}