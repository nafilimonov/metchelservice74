<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Model
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Lead_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var mixed
	 */
	public $contact = NULL;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'lead_directory_email' => array('foreign_key' => 'lead_id'),
		'directory_email' => array('through' => 'lead_directory_email', 'through_table_name' => 'lead_directory_emails', 'foreign_key' => 'lead_id'),

		'lead_directory_phone' => array('foreign_key' => 'lead_id'),
		'directory_phone' => array('through' => 'lead_directory_phone', 'through_table_name' => 'lead_directory_phones', 'foreign_key' =>
		'lead_id'),

		'lead_directory_website' => array('foreign_key' => 'lead_id'),
		'directory_website' => array('through' => 'lead_directory_website', 'through_table_name' => 'lead_directory_websites', 'foreign_key' => 'lead_id'),

		'lead_directory_address' => array('foreign_key' => 'lead_id'),
		'directory_address' => array('through' => 'lead_directory_address', 'through_table_name' => 'lead_directory_addresses', 'foreign_key' => 'lead_id'),

		// 'lead_note' => array(),
		'lead_shop_item' => array(),
		'lead_step' => array(),
		'lead_event' => array(),
		'event' => array('through' => 'lead_event'),
		'lead_history' => array(),
		'lead_crm_note' => array(),
		'crm_note' => array('through' => 'lead_crm_note'),
		'dms_document' => array('through' => 'lead_dms_document'),
		'lead_dms_document' => array(),
		'tag' => array('through' => 'tag_lead'),
		'tag_lead' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'user' => array(),
		'site' => array(),
		'lead_status' => array(),
		'lead_need' => array(),
		'lead_maturity' => array(),
		'crm_source' => array(),
		'siteuser' => array(),
		'shop' => array(),
		'shop_order' => array(),
		'shop_currency' => array(),
		'deal' => array(),
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
			$this->_preloadValues['last_contacted'] = '0000-00-00 00:00:00';
		}
	}

	/**
	 * Get full name of lead
	 * @return string
	 */
	public function getFullName()
	{
		$aPartsFullName = array();

		!empty($this->surname) && $aPartsFullName[] = $this->surname;
		!empty($this->name) && $aPartsFullName[] = $this->name;
		!empty($this->patronymic) && $aPartsFullName[] = $this->patronymic;

		return implode(' ', $aPartsFullName);
	}

	/**
	 * Show source badge
	 * @return string
	 */
	public function showSource()
	{
		$oCrm_Source = $this->Crm_Source;
		?><span class="badge badge-square" style="color: <?php echo htmlspecialchars((string) $oCrm_Source->color)?>; background-color:<?php echo Core_Str::hex2lighter((string) $oCrm_Source->color, 0.88)?>"><i class="<?php echo htmlspecialchars((string) $oCrm_Source->icon)?>"></i> <span class="hidden-xxs hidden-xs"><?php echo htmlspecialchars((string) $oCrm_Source->name)?></span></span><?php
	}

	/**
	 * Show phones badge
	 * @return string
	 */
	public function showPhones()
	{
		$aLead_Directory_Phones = $this->Lead_Directory_Phones->findAll(FALSE);
		foreach ($aLead_Directory_Phones as $oLead_Directory_Phone)
		{
			$oDirectory_Phone = $oLead_Directory_Phone->Directory_Phone;
			?><span class="badge badge-square badge-max-width lead-phone"><?php echo htmlspecialchars($oDirectory_Phone->value)?></span><?php
		}
	}

	/**
	 * Show e-mail badge
	 * @return string
	 */
	public function showEmails()
	{
		$aLead_Directory_Emails = $this->Lead_Directory_Emails->findAll(FALSE);
		foreach ($aLead_Directory_Emails as $oLead_Directory_Email)
		{
			$oDirectory_Email = $oLead_Directory_Email->Directory_Email;
			?><span class="badge badge-square badge-max-width lead-email"><a href="mailto:<?php echo htmlspecialchars($oDirectory_Email->value)?>"><?php echo htmlspecialchars($oDirectory_Email->value)?></a></span><?php
		}
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function amountBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if (floatval($this->amount))
		{
			?><span><?php echo htmlspecialchars(Core_Entity::factory('Shop_Currency', $this->shop_currency_id)->formatWithCurrency($this->amount))?></span><?php
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 * @see contactBackend()
	 * @see Lead_Controller_Kanban::_showContent()
	 */
	public function commentBackend()
	{
		if (strlen((string) $this->comment))
		{
			?><div class="crm-description">
				<span><?php echo nl2br(htmlspecialchars(Core_Str::cut($this->comment, 255)))?></span>
			</div><?php
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 * @hostcms-event lead.onAfterContactCompany
	 * @hostcms-event lead.onAfterContactEmails
	 * @hostcms-event lead.onAfterContact
	 */
	public function contactBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		?><div class="semi-bold small"><?php echo htmlspecialchars($this->getFullName())?></div><?php

		?><div class="crm-company small"><?php
		if ($this->company != '')
		{
			?><span class="semi-bold"><?php echo htmlspecialchars($this->company)?></span><?php
		}
		if ($this->post != '')
		{
			echo ', ', htmlspecialchars($this->post);
		}
		?></div><?php

		Core_Event::notify($this->_modelName . '.onAfterContactCompany', $this, array($oAdmin_Form_Field, $oAdmin_Form_Controller));

		$oUser = Core_Auth::getCurrentUser();

		$aAvailableFields = $oUser
			? $oAdmin_Form_Controller->getAdminForm()->getAvailableFieldsForUser($oUser->id)
			: array();

		!isset($aAvailableFields[2282]) && $this->commentBackend();

		?><div class="crm-contacts"><?php
		$this->showPhones();
		$this->showEmails();
		?>
		</div>

		<?php
		Core_Event::notify($this->_modelName . '.onAfterContactEmails', $this, array($oAdmin_Form_Field, $oAdmin_Form_Controller));
		if (Core::moduleIsActive('tag'))
		{
			$aTags = $this->Tags->findAll(FALSE);

			foreach ($aTags as $oTag)
			{
				Core_Html_Entity::factory('Code')
					->value('<span class="badge badge-square badge-tag badge-max-width badge-lightgray margin-right-5" title="' . htmlspecialchars($oTag->name) . '"><i class="fa fa-tag"></i> ' . htmlspecialchars($oTag->name) . '</span>')
					->execute();
			}
		}
		?>

		<div class="row">
			<div class="col-xs-12 col-sm-6 overflow-hidden">
				<?php
				if ($this->crm_source_id && !isset($aAvailableFields[1569]))
				{
					echo $this->showSource();
				}
				?>
			</div>
			<div class="col-xs-12 col-sm-6 text-align-right kanban-list-deals">
				<?php
				if (Core::moduleIsActive('event'))
				{
					echo Event_Controller::showRelatedEvents($this);
				}
				?>
			</div>
		</div><?php

		Core_Event::notify($this->_modelName . '.onAfterContact', $this, array($oAdmin_Form_Field, $oAdmin_Form_Controller));

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function datetimeBackend()
	{
		return '<span class="small2">' . Core_Date::timestamp2string(Core_Date::sql2timestamp($this->datetime)) . '</span>';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function last_contactedBackend()
	{
		return $this->last_contacted != '0000-00-00 00:00:00'
			? '<span class="small2">' . Core_Date::timestamp2string(Core_Date::sql2timestamp($this->last_contacted)) . '</span>'
			: '';
	}

	/**
	 * Get lead status bar
	 * @return string
	 */
	public function getStatusBar($oAdmin_Form_Controller)
	{
		$aLead_Statuses = Core_Entity::factory('Lead_Status')->getAllBySite_id(CURRENT_SITE);

		ob_start();

		if (count($aLead_Statuses))
		{
			$css = '<style>';

			$oCurrentLeadStatus = $this->Lead_Status;

			if ($oCurrentLeadStatus)
			{
				?>
				<div class="lead-status-name lead-status-name-<?php echo $this->id?>" style="color: <?php echo htmlspecialchars((string) $oCurrentLeadStatus->color)?>"><?php echo htmlspecialchars((string) $oCurrentLeadStatus->name)?></div>
				<?php
			}
			?>
			<div class="lead-stage-wrapper lead-stage-wrapper-<?php echo $this->id?>">
				<?php
				foreach ($aLead_Statuses as $oLead_Status)
				{
					$class = $oLead_Status->id == $this->lead_status_id
						? 'active'
						: '';

					switch ($oLead_Status->type)
					{
						case 1:
							$statusClass = 'finish';
						break;
						case 2:
							$statusClass = 'failed';
						break;
						default:
							$statusClass = '';
					}

					$darkerColor = Core_Str::hex2darker($oLead_Status->color, 0.1);

					$css .= '.lead-stage-wrapper #lead-stage-' . $oLead_Status->id . '.active { background-color: ' . htmlspecialchars($oLead_Status->color) . '; border-color: ' . $darkerColor . ' !important; }';
					?>
					<div id="lead-stage-<?php echo $oLead_Status->id?>" data-id="<?php echo $oLead_Status->id?>" class="lead-stage <?php echo $class?> <?php echo $statusClass?>" data-color="<?php echo htmlspecialchars($oLead_Status->color)?>" data-dark="<?php echo $darkerColor?>" data-name="<?php echo htmlspecialchars($oLead_Status->name)?>"></div>
					<?php
				}
				?>
			</div>

			<?php
			$css .= '</style>';

			$windowId = $oAdmin_Form_Controller->getWindowId();
			?>

			<script>
				$(function() {
					$.leadStatusBar(<?php echo $this->id?>, '<?php echo $oAdmin_Form_Controller->getWindowId()?>');

					$('#<?php echo $windowId?> .lead-stage-wrapper-<?php echo $this->id?> .lead-stage').on('mouseover', function(e){
						var jParent = $(this).parents('.lead-stage-wrapper'),
							jNameDiv = jParent.prev();

						if ($(this).css('cursor') == 'pointer')
						{
							jNameDiv
								.text($(this).data('name'))
								.css('color', $(this).data('color'));
						}
					});

					$('#<?php echo $windowId?> .lead-stage-wrapper-<?php echo $this->id?> .lead-stage').on('mouseout', function(){
						var jParent = $(this).parents('.lead-stage-wrapper'),
							jNameDiv = jParent.prev();

						jNameDiv
							.text(jParent.find('.lead-stage.active').data('name'))
							.css('color', jParent.find('.lead-stage.active').data('color'));
					});
				});
			</script>
			<?php
			echo $css;
		}

		return ob_get_clean();
	}

	/**
	 * Update kanban
	 * @param Lead_Status_Model $oLead_Status
	 * @return array
	 */
	public function	updateKanban(Lead_Status_Model $oLead_Status)
	{
		$aReturn = array();

		$count = $this->Site->Leads->getCountBylead_status_id($oLead_Status->id, FALSE);

		$aReturn['data'] = $count == 0
			? ''
			: array('count' => $count);

		return $aReturn;
	}

	/**
	 * Morph lead into another entity
	 * @param int $type type of entity
	 * @param int $siteuser_id siteuser id
	 * @return string
	 */
	public function morph($type, $siteuser_id = 0, $deal_template_id = 0)
	{
		if ($type == 0 || $type > 4)
		{
			return 'unknownMorphType';
		}

		$oSite = $this->Site;

		switch($type)
		{
			// Новый клиент
			case 1:
				if (Core::moduleIsActive('siteuser'))
				{
					$aLead_Directory_Emails = $this->Lead_Directory_Emails->findAll();

					$oSiteuser = isset($aLead_Directory_Emails[0])
						? $oSite->Siteusers->getByEmail($aLead_Directory_Emails[0]->Directory_Email->value)
						: NULL;

					$oSiteuser = $this->_convertToSiteuser($oSiteuser);

					$this->siteuser_id = $oSiteuser->id;
					$this->save();

					return 'success';
				}
			break;
			// Существующий клиент
			case 2:
				if (Core::moduleIsActive('siteuser') && $siteuser_id)
				{
					$oSiteuser = $oSite->Siteusers->getById($siteuser_id);

					if (!is_null($oSiteuser))
					{
						$oSiteuser = $this->_convertToSiteuser($oSiteuser);

						$this->siteuser_id = $oSiteuser->id;
						$this->save();

						return 'success';
					}
				}
			break;
			// Заказ
			case 3:
				if (Core::moduleIsActive('shop'))
				{
					$oShop_Order = $this->_convertToShopOrder();

					$this->shop_order_id = $oShop_Order->id;
					$this->save();

					return 'success';
				}
			break;
			// Сделка
			case 4:
				if (Core::moduleIsActive('deal') && $deal_template_id)
				{
					$oDeal_Template = Core_Entity::factory('Deal_Template')->getById($deal_template_id);

					if (!is_null($oDeal_Template))
					{
						$oDeal = $this->_convertToDeal($oDeal_Template);

						$this->deal_id = $oDeal->id;
						$this->save();

						return 'success';
					}
				}
			break;
		}
	}

	/*
	 * Convert lead to shop order
	 * @param object $oDeal_Template Deal_Template_Model
	 * @return Dea_Model object
	 */
	protected function _convertToDeal(Deal_Template_Model $oDeal_Template)
	{
		$oShop_Currency = Core_Entity::factory('Shop_Currency')->getDefault();

		$oDeal = Core_Entity::factory('Deal');
		$oDeal->shop_id = $this->shop_id;
		$oDeal->creator_id = $this->user_id;
		$oDeal->user_id = $this->user_id;
		$oDeal->shop_currency_id = $oShop_Currency->id;
		$oDeal->name = Core::_('Lead.morph_deal_name', $this->getFullName());
		$oDeal->description = Core::_('Lead.morph_description', $this->getFullName());
		$oDeal->start_datetime = Core_Date::timestamp2sql(time());

		$oDeal->deal_template_id = $oDeal_Template->id;

		$aDeal_Template_Steps = $oDeal_Template->Deal_Template_Steps->findAll();
		$oDeal->deal_template_step_id = isset($aDeal_Template_Steps[0])
			? $aDeal_Template_Steps[0]->id
			: 0;

		$oCompany_Department = $this->User->Company_Departments->getFirst();

		$oDeal->company_id = $oCompany_Department ? $oCompany_Department->company_id : 0;

		if (Core::moduleIsActive('siteuser') && $this->siteuser_id)
		{
			$oDeal->siteuser_id = $this->siteuser_id;

			$oSiteuser = $this->Siteuser;

			$aSiteuser_Companies = $oSiteuser->Siteuser_Companies->findAll();
			foreach ($aSiteuser_Companies as $oSiteuser_Company)
			{
				$oDeal_Siteuser = Core_Entity::factory('Deal_Siteuser');
				$oDeal_Siteuser->siteuser_company_id = $oSiteuser_Company->id;

				$oDeal->add($oDeal_Siteuser);
			}

			$aSiteuser_People = $oSiteuser->Siteuser_People->findAll();
			foreach ($aSiteuser_People as $oSiteuser_Person)
			{
				$oDeal_Siteuser = Core_Entity::factory('Deal_Siteuser');
				$oDeal_Siteuser->siteuser_person_id = $oSiteuser_Person->id;

				$oDeal->add($oDeal_Siteuser);
			}
		}

		// Товары
		$aLead_Shop_Items = $this->Lead_Shop_Items->findAll();
		foreach ($aLead_Shop_Items as $oLead_Shop_Item)
		{
			$oDeal_Shop_Item = Core_Entity::factory('Deal_Shop_Item');
			$oDeal_Shop_Item->shop_item_id = $oLead_Shop_Item->shop_item_id;
			$oDeal_Shop_Item->name = $oLead_Shop_Item->name;
			$oDeal_Shop_Item->quantity = $oLead_Shop_Item->quantity;
			$oDeal_Shop_Item->price = $oLead_Shop_Item->price;
			$oDeal_Shop_Item->marking = $oLead_Shop_Item->marking;
			$oDeal_Shop_Item->rate = $oLead_Shop_Item->rate;
			$oDeal_Shop_Item->user_id = $oLead_Shop_Item->user_id;
			$oDeal_Shop_Item->type = $oLead_Shop_Item->type;
			$oDeal_Shop_Item->shop_warehouse_id = $oLead_Shop_Item->shop_warehouse_id;

			$oDeal->add($oDeal_Shop_Item);
		}

		$oDeal->amount = $this->amount;
		$oDeal->save();

		return $oDeal;
	}

	/*
	 * Convert lead to shop order
	 * @return Shop_Order_Model object
	 */
	protected function _convertToShopOrder()
	{
		$oShop = $this->Shop;

		$oShop_Order = Core_Entity::factory('Shop_Order');
		$oShop_Order->name = strval($this->name);
		$oShop_Order->surname = strval($this->surname);
		$oShop_Order->patronymic = strval($this->patronymic);
		$oShop_Order->company = strval($this->company);

		$oShop_Order->shop_currency_id = $oShop->shop_currency_id;
		$oShop_Order->shop_order_status_id = $oShop->shop_order_status_id;

		// Адреса
		$aLead_Directory_Addresses = $this->Lead_Directory_Addresses->findAll();
		if (isset($aLead_Directory_Addresses[0]))
		{
			$oDirectory_Address = $aLead_Directory_Addresses[0]->Directory_Address;

			$oShop_Order->postcode = strval($oDirectory_Address->postcode);
			$oShop_Order->address = strval($oDirectory_Address->value);

			$oShop_Country = Core_Entity::factory('Shop_Country')->getByName(trim($oDirectory_Address->country));

			if (!is_null($oShop_Country))
			{
				$oShop_Order->shop_country_id = $oShop_Country->id;

				$oShop_Country_Location_Cities = Core_Entity::factory('Shop_Country_Location_City');
				$oShop_Country_Location_Cities->queryBuilder()
					->select('shop_country_location_cities.*')
					->join('shop_country_locations', 'shop_country_locations.id', '=', 'shop_country_location_cities.shop_country_location_id')
					->where('shop_country_locations.shop_country_id', '=', $oShop_Country->id)
					->where('shop_country_location_cities.name', 'LIKE', trim($oDirectory_Address->city))
					->limit(1);

				$aShop_Country_Location_Cities = $oShop_Country_Location_Cities->findAll();

				if (isset($aShop_Country_Location_Cities[0]))
				{
					$oShop_Order->shop_country_location_city_id = $aShop_Country_Location_Cities[0]->id;
					$oShop_Order->shop_country_location_id = $aShop_Country_Location_Cities[0]->shop_country_location_id;
				}
			}
		}

		// Телефоны
		$aLead_Directory_Phones = $this->Lead_Directory_Phones->findAll();
		$oShop_Order->phone = isset($aLead_Directory_Phones[0])
			? strval($aLead_Directory_Phones[0]->Directory_Phone->value())
			: '';

		// E-mails
		$aLead_Directory_Emails = $this->Lead_Directory_Emails->findAll();
		$oShop_Order->email = isset($aLead_Directory_Emails[0])
			? strval($aLead_Directory_Emails[0]->Directory_Email->value())
			: '';

		$oShop_Order->description = Core::_('Lead.morph_description', $this->getFullName());

		Core::moduleIsActive('siteuser')
			&& $this->siteuser_id
			&& $oShop_Order->siteuser_id = $this->siteuser_id;

		$oShop->add($oShop_Order);

		$oShop_Order->createInvoice();
		$oShop_Order->save();

		// Товары
		$aLead_Shop_Items = $this->Lead_Shop_Items->findAll();
		foreach ($aLead_Shop_Items as $oLead_Shop_Item)
		{
			$oShop_Order_Item = Core_Entity::factory('Shop_Order_Item');
			$oShop_Order_Item->shop_item_id = $oLead_Shop_Item->shop_item_id;
			$oShop_Order_Item->name = strval($oLead_Shop_Item->name);
			$oShop_Order_Item->quantity = $oLead_Shop_Item->quantity;
			$oShop_Order_Item->price = $oLead_Shop_Item->price;
			$oShop_Order_Item->marking = $oLead_Shop_Item->marking;
			$oShop_Order_Item->rate = $oLead_Shop_Item->rate;
			$oShop_Order_Item->user_id = $oLead_Shop_Item->user_id;
			$oShop_Order_Item->type = $oLead_Shop_Item->type;
			$oShop_Order_Item->shop_warehouse_id = $oLead_Shop_Item->shop_warehouse_id;

			$oShop_Order->add($oShop_Order_Item);
		}

		return $oShop_Order;
	}

	/*
	 * Convert lead to siteuser
	 * @param Siteuser_Model|NULL $oSiteuser
	 * @return Siteuser_Model object
	 */
	protected function _convertToSiteuser($oSiteuser)
	{
		$oSite = $this->Site;

		$oUser = Core_Auth::getCurrentUser();
		$user_id = !is_null($oUser) ? $oUser->id : 0;

		$aLead_Directory_Emails = $this->Lead_Directory_Emails->findAll();

		if (is_null($oSiteuser))
		{
			$email = isset($aLead_Directory_Emails[0])
				? trim($aLead_Directory_Emails[0]->Directory_Email->value)
				: NULL;

			$oSiteuser = Core_Entity::factory('Siteuser');
			$oSiteuser->site_id = $oSite->id;
			$oSiteuser->crm_source_id = $this->crm_source_id;
			$oSiteuser->login = !is_null($email) ? $email : '';
			$oSiteuser->password = Core_Password::get();
			$oSiteuser->email = $email;
			$oSiteuser->datetime = Core_Date::timestamp2sql(time());
			$oSiteuser->guid = Core_Guid::get();
			$oSiteuser->active = 1;
			$oSiteuser->user_id = $user_id;
			$oSiteuser->last_activity = Core_Date::timestamp2sql(time());
			$oSiteuser->save();

			$oSiteuser->login == ''
				&& $oSiteuser->login('id' . $oSiteuser->id)->save();
		}

		$oSiteuser_Person = $oSiteuser->Siteuser_People->getByNameAndSurname($this->name, $this->surname);

		if (is_null($oSiteuser_Person))
		{
			$oSiteuser_Person = Core_Entity::factory('Siteuser_Person');
			$oSiteuser_Person->siteuser_id = $oSiteuser->id;
			$oSiteuser_Person->name = $this->name;
			$oSiteuser_Person->surname = $this->surname;
			$oSiteuser_Person->patronymic = $this->patronymic;
			$oSiteuser_Person->post = $this->post;
			$oSiteuser_Person->save();
		}

		!strlen((string) $oSiteuser_Person->birthday)
			&& $oSiteuser_Person->birthday = $this->birthday;

		$oSiteuser_Person->user_id = $user_id;
		$oSiteuser_Person->save();

		// Адреса
		/*$aLead_Directory_Addresses = $this->Lead_Directory_Addresses->findAll();
		if (isset($aLead_Directory_Addresses[0]) && !strlen($oSiteuser_Person->address))
		{
			$oDirectory_Address = $aLead_Directory_Addresses[0]->Directory_Address;

			$oSiteuser_Person->country = $oDirectory_Address->country;
			$oSiteuser_Person->postcode = $oDirectory_Address->postcode;
			$oSiteuser_Person->city = $oDirectory_Address->city;
			$oSiteuser_Person->address = $oDirectory_Address->value;
		}*/

		$aLead_Directory_Addresses = $this->Lead_Directory_Addresses->findAll();
		if (isset($aLead_Directory_Addresses[0]))
		{
			$oDirectory_Address = $aLead_Directory_Addresses[0]->Directory_Address;

			$oPerson_Directory_Address = $oSiteuser_Person->Directory_Phones->getByValue($oDirectory_Address->value);

			if (is_null($oPerson_Directory_Address))
			{
				$oClone_Directory_Address = clone $oDirectory_Address;
				$oClone_Directory_Address->save();

				$oSiteuser_Person_Directory_Address = Core_Entity::factory('Siteuser_Person_Directory_Address');
				$oSiteuser_Person_Directory_Address->directory_address_id = $oClone_Directory_Address->id;
				$oSiteuser_Person->add($oSiteuser_Person_Directory_Address);
			}
		}

		// Телефоны
		$aLead_Directory_Phones = $this->Lead_Directory_Phones->findAll();
		foreach ($aLead_Directory_Phones as $oLead_Directory_Phone)
		{
			$oDirectory_Phone = $oLead_Directory_Phone->Directory_Phone;

			$oPerson_Directory_Phone = $oSiteuser_Person->Directory_Phones->getByValue($oDirectory_Phone->value);

			if (is_null($oPerson_Directory_Phone))
			{
				$oClone_Directory_Phone = clone $oDirectory_Phone;
				$oClone_Directory_Phone->save();

				$oSiteuser_Person_Directory_Phone = Core_Entity::factory('Siteuser_Person_Directory_Phone');
				$oSiteuser_Person_Directory_Phone->directory_phone_id = $oClone_Directory_Phone->id;
				$oSiteuser_Person->add($oSiteuser_Person_Directory_Phone);
			}
		}

		// E-mails
		foreach ($aLead_Directory_Emails as $oLead_Directory_Email)
		{
			$oDirectory_Email = $oLead_Directory_Email->Directory_Email;

			$oPerson_Directory_Email = $oSiteuser_Person->Directory_Emails->getByValue($oDirectory_Email->value);

			if (is_null($oPerson_Directory_Email))
			{
				$oClone_Directory_Email = clone $oDirectory_Email;
				$oClone_Directory_Email->save();

				$oSiteuser_Person_Directory_Email = Core_Entity::factory('Siteuser_Person_Directory_Email');
				$oSiteuser_Person_Directory_Email->directory_email_id = $oClone_Directory_Email->id;
				$oSiteuser_Person->add($oSiteuser_Person_Directory_Email);
			}
		}

		// Сайты
		$aLead_Directory_Websites = $this->Lead_Directory_Websites->findAll();
		foreach ($aLead_Directory_Websites as $oLead_Directory_Website)
		{
			$oDirectory_Website = $oLead_Directory_Website->Directory_Website;

			$oPerson_Directory_Website = $oSiteuser_Person->Directory_Websites->getByValue($oDirectory_Website->value);

			if (is_null($oPerson_Directory_Website))
			{
				$oClone_Directory_Website = clone $oDirectory_Website;
				$oClone_Directory_Website->save();

				$oSiteuser_Person_Directory_Website = Core_Entity::factory('Siteuser_Person_Directory_Website');
				$oSiteuser_Person_Directory_Website->directory_website_id = $oClone_Directory_Website->id;
				$oSiteuser_Person->add($oSiteuser_Person_Directory_Website);
			}
		}

		if (strlen(trim($this->company)))
		{
			$oSiteuser_Company = $oSiteuser->Siteuser_Companies->getByName($this->company);

			if (is_null($oSiteuser_Company))
			{
				$oSiteuser_Company = Core_Entity::factory('Siteuser_Company');
				$oSiteuser_Company->name = $this->company;
				$oSiteuser_Company->user_id = $user_id;

				$oSiteuser->add($oSiteuser_Company);
			}
		}

		return $oSiteuser;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function lead_need_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		$aMasLeadNeeds = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aLead_Needs = Core_Entity::factory('Lead_Need')->getAllBySite_id(CURRENT_SITE);

		foreach ($aLead_Needs as $oLead_Need)
		{
			$aMasLeadNeeds[$oLead_Need->id] = array(
				'value' => $oLead_Need->name,
				'color' => $oLead_Need->color
			);
		}

		$oCore_Html_Entity_Dropdownlist = new Core_Html_Entity_Dropdownlist();

		$oCore_Html_Entity_Dropdownlist
			->value($this->lead_need_id)
			->options($aMasLeadNeeds)
			->data('change-context', 'true')
			->onchange("$.adminLoad({path: '{$oAdmin_Form_Controller->getPath()}', additionalParams: 'hostcms[checked][0][{$this->id}]=0&leadNeedId=' + $(this).find('li[selected]').prop('id'), action: 'changeNeed', windowId: '{$oAdmin_Form_Controller->getWindowId()}'});")
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function lead_maturity_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		$aMasLeadMaturities = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aLead_Maturities = Core_Entity::factory('Lead_Maturity')->getAllBySite_id(CURRENT_SITE);

		foreach ($aLead_Maturities as $oLead_Maturity)
		{
			$aMasLeadMaturities[$oLead_Maturity->id] = array(
				'value' => $oLead_Maturity->name,
				'color' => $oLead_Maturity->color
			);
		}

		$oCore_Html_Entity_Dropdownlist = new Core_Html_Entity_Dropdownlist();

		$oCore_Html_Entity_Dropdownlist
			->value($this->lead_maturity_id)
			->options($aMasLeadMaturities)
			->data('change-context', 'true')
			->onchange("$.adminLoad({path: '{$oAdmin_Form_Controller->getPath()}', additionalParams: 'hostcms[checked][0][{$this->id}]=0&leadMaturityId=' + $(this).find('li[selected]').prop('id'), action: 'changeMaturity', windowId: '{$oAdmin_Form_Controller->getWindowId()}'});")
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function crm_source_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		$aMasCrmSources = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
		foreach ($aCrm_Sources as $oCrm_Source)
		{
			$aMasCrmSources[$oCrm_Source->id] = array(
				'value' => $oCrm_Source->name,
				'color' => $oCrm_Source->color,
				'icon' => $oCrm_Source->icon
			);
		}

		$oCore_Html_Entity_Dropdownlist = new Core_Html_Entity_Dropdownlist();

		$oCore_Html_Entity_Dropdownlist
			->value($this->crm_source_id)
			->options($aMasCrmSources)
			->data('change-context', 'true')
			->onchange("$.adminLoad({path: '{$oAdmin_Form_Controller->getPath()}', additionalParams: 'hostcms[checked][0][{$this->id}]=0&crmSourceId=' + $(this).find('li[selected]').prop('id'), action: 'changeCrmSource', windowId: '{$oAdmin_Form_Controller->getWindowId()}'});")
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function lead_status_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		return $this->getStatusBar($oAdmin_Form_Controller);
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function entityBackend()
	{
		ob_start();

		if (Core::moduleIsActive('siteuser') && $this->siteuser_id)
		{
			?><a href="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/index.php')?>?hostcms[action]=edit&hostcms[checked][0][<?php echo $this->siteuser_id?>]=1" onclick="$.modalLoad({path: hostcmsBackend + '/siteuser/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $this->siteuser_id?>]=1', view: 'list', windowId: 'id_content'}); return false"><i class="fa fa-user-o fa-fw margin-right-5 info" title="<?php echo htmlspecialchars($this->Siteuser->login)?>"></i></a><?php
		}

		if (Core::moduleIsActive('shop') && $this->shop_order_id)
		{
			?><a href="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/shop/order/index.php')?>?hostcms[action]=edit&hostcms[checked][0][<?php echo $this->shop_order_id?>]=1&shop_id=<?php echo $this->shop_id?>" onclick="$.modalLoad({path: hostcmsBackend + '/shop/order/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $this->shop_order_id?>]=1&shop_id=<?php echo $this->shop_id?>', view: 'list', windowId: 'id_content'}); return false"><i class="fa fa-shopping-basket fa-fw margin-right-5 darkorange" title="<?php echo htmlspecialchars($this->Shop_Order->invoice)?>"></i></a><?php
		}

		if (Core::moduleIsActive('deal') && $this->deal_id)
		{
			?><a href="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/deal/index.php')?>?hostcms[action]=edit&hostcms[checked][0][<?php echo $this->deal_id?>]=1" onclick="$.modalLoad({path: hostcmsBackend + '/deal/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $this->deal_id?>]=1', view: 'list', windowId: 'id_content'}); return false"><i class="fa fa-handshake-o fa-fw margin-right-5 yellow"title="<?php echo htmlspecialchars($this->Deal->name)?>"></i></a><?php
		}

		return ob_get_clean();
	}

	/**
	 * Notify Bots
	 * @return self
	 */
	public function notifyBotsChangeStatus()
	{
		if (Core::moduleIsActive('bot'))
		{
			$oModule = Core::$modulesList['lead'];
			Bot_Controller::notify($oModule->id, 0, $this->lead_status_id, $this);
		}

		return $this;
	}

	/**
	 * Push history
	 * @return self
	 */
	public function pushHistory($text, $color = '#333333')
	{
		$oLead_History = Core_Entity::factory('Lead_History');
		$oLead_History->lead_id = $this->id;
		$oLead_History->text = $text;
		$oLead_History->color = $color;

		$oLead_History->save();

		return $this;
	}

	/**
	 * Get responsible users
	 * @return array
	 */
	public function getResponsibleUsers()
	{
		return $this->user_id
			? array($this->User)
			: array();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event lead.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Directory_Emails->deleteAll(FALSE);
		$this->Directory_Phones->deleteAll(FALSE);
		$this->Directory_Websites->deleteAll(FALSE);
		$this->Directory_Addresses->deleteAll(FALSE);
		$this->Lead_Directory_Emails->deleteAll(FALSE);
		$this->Lead_Directory_Phones->deleteAll(FALSE);
		$this->Lead_Directory_Addresses->deleteAll(FALSE);
		$this->Lead_Directory_Websites->deleteAll(FALSE);
		// $this->Lead_Notes->deleteAll(FALSE);
		$this->Lead_Shop_Items->deleteAll(FALSE);
		$this->Lead_Steps->deleteAll(FALSE);
		$this->Lead_Events->deleteAll(FALSE);
		$this->Lead_Histories->deleteAll(FALSE);
		$this->Crm_Notes->deleteAll(FALSE);
		$this->Lead_Crm_Notes->deleteAll(FALSE);

		$this->Lead_Dms_Documents->deleteAll(FALSE);

		if (Core::moduleIsActive('tag'))
		{
			// Удаляем метки
			$this->Tag_Leads->deleteAll(FALSE);
		}

		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Get message files href
	 * @return string
	 */
	public function getHref()
	{
		 return $this->Site->uploaddir . 'private/leads/' . Core_File::getNestingDirPath($this->id, 3) . '/lead_' . $this->id . '/';
	}

	/**
	 * Get path for files
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Create message files directory
	 * @return self
	 */
	public function createDir()
	{
		if (!Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::mkdir($this->getPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Delete message files directory
	 * @return self
	 */
	public function deleteDir()
	{
		if (Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::deleteDir($this->getPath());
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Apply tags for item
	 * @param string $sTags string of tags, separated by comma
	 * @return self
	 */
	public function applyTags($sTags)
	{
		$aTags = explode(',', $sTags);

		return $this->applyTagsArray($aTags);
	}

	/**
	 * Apply array tags for item
	 * @param array $aTags array of tags
	 * @return self
	 */
	public function applyTagsArray(array $aTags)
	{
		// Удаляем связь метками
		$this->Tag_Leads->deleteAll(FALSE);

		foreach ($aTags as $tag_name)
		{
			$tag_name = trim($tag_name);

			if ($tag_name != '')
			{
				$oTag = Core_Entity::factory('Tag')->getByName($tag_name, FALSE);

				if (is_null($oTag))
				{
					$oTag = Core_Entity::factory('Tag');
					$oTag->name = $oTag->path = $tag_name;
					$oTag->save();
				}

				$this->add($oTag);
			}
		}

		return $this;
	}

	/**
	 * Merge Leads
	 * @param Lead_Model $oLead
	 * @return self
	 */
	public function merge(Lead_Model $oLead)
	{
		// ФИО нет или фамилия совпала
		if ($this->surname == '' && $this->name == '' && $this->patronymic == '' || $this->surname == $oLead->surname)
		{
			$this->surname == ''
				&& $oLead->surname != ''
				&& $this->surname = $oLead->surname;

			$this->name == ''
				&& $oLead->name != ''
				&& $this->name = $oLead->name;

			$this->patronymic == ''
				&& $oLead->patronymic != ''
				&& $this->patronymic = $oLead->patronymic;
		}

		$this->lead_need_id == 0
			&& $oLead->lead_need_id > 0
			&& $this->lead_need_id = $oLead->lead_need_id;

		$this->lead_maturity_id == 0
			&& $oLead->lead_maturity_id > 0
			&& $this->lead_maturity_id = $oLead->lead_maturity_id;

		$this->crm_source_id == 0
			&& $oLead->crm_source_id > 0
			&& $this->crm_source_id = $oLead->crm_source_id;

		$this->lead_status_id == 0
			&& $oLead->lead_status_id > 0
			&& $this->lead_status_id = $oLead->lead_status_id;

		$this->siteuser_id == 0
			&& $oLead->siteuser_id > 0
			&& $this->siteuser_id = $oLead->siteuser_id;

		$this->shop_id == 0
			&& $oLead->shop_id > 0
			&& $this->shop_id = $oLead->shop_id;

		$this->shop_order_id == 0
			&& $oLead->shop_order_id > 0
			&& $this->shop_order_id = $oLead->shop_order_id;

		$this->deal_id == 0
			&& $oLead->deal_id > 0
			&& $this->deal_id = $oLead->deal_id;

		$this->birthday == '0000-00-00'
			&& $oLead->birthday != '0000-00-00'
			&& $this->birthday = $oLead->birthday;

		$this->post == ''
			&& $oLead->post != ''
			&& $this->post = $oLead->post;

		$this->company == ''
			&& $oLead->company != ''
			&& $this->company = $oLead->company;

		$this->amount == 0
			&& $oLead->amount > 0
			&& $this->amount = $oLead->amount;

		$this->shop_currency_id == 0
			&& $oLead->shop_currency_id > 0
			&& $this->shop_currency_id = $oLead->shop_currency_id;

		$oLead->comment != ''
			&& $this->comment = trim($this->comment . "\n" . $oLead->comment);

		Core_QueryBuilder::update('lead_shop_items')
			->set('lead_id', $this->id)
			->where('lead_id', '=', $oLead->id)
			->execute();

		Core_QueryBuilder::update('lead_crm_notes')
			->set('lead_id', $this->id)
			->where('lead_id', '=', $oLead->id)
			->execute();

		Core_QueryBuilder::update('lead_dms_documents')
			->set('lead_id', $this->id)
			->where('lead_id', '=', $oLead->id)
			->execute();

		Core_QueryBuilder::update('lead_events')
			->set('lead_id', $this->id)
			->where('lead_id', '=', $oLead->id)
			->execute();

		Core_QueryBuilder::update('lead_histories')
			->set('lead_id', $this->id)
			->where('lead_id', '=', $oLead->id)
			->execute();

		// Directory_Addresses
		$aTmpAddresses = array();

		$aTmp_Directory_Addresses = $this->Directory_Addresses->findAll(FALSE);
		foreach ($aTmp_Directory_Addresses as $oDirectory_Address)
		{
			$aTmpAddresses[] = trim($oDirectory_Address->value);
		}

		$aDirectory_Addresses = $oLead->Directory_Addresses->findAll(FALSE);
		foreach ($aDirectory_Addresses as $oDirectory_Address)
		{
			strlen(trim($oDirectory_Address->value)) && !in_array($oDirectory_Address->value, $aTmpAddresses)
				&& $this->add(clone $oDirectory_Address);
		}

		// Directory_Phones
		$aTmpPhones = array();

		$aTmp_Directory_Phones = $this->Directory_Phones->findAll(FALSE);
		foreach ($aTmp_Directory_Phones as $oDirectory_Phone)
		{
			$aTmpPhones[] = Core_Str::sanitizePhoneNumber($oDirectory_Phone->value);
		}

		$aDirectory_Phones = $oLead->Directory_Phones->findAll(FALSE);
		foreach ($aDirectory_Phones as $oDirectory_Phone)
		{
			!in_array(Core_Str::sanitizePhoneNumber($oDirectory_Phone->value), $aTmpPhones)
				&& $this->add(clone $oDirectory_Phone);
		}

		// Directory_Emails
		$aTmpEmails = array();

		$aTmp_Directory_Emails = $this->Directory_Emails->findAll(FALSE);
		foreach ($aTmp_Directory_Emails as $oDirectory_Email)
		{
			$aTmpEmails[] = trim($oDirectory_Email->value);
		}

		$aDirectory_Emails = $oLead->Directory_Emails->findAll(FALSE);
		foreach ($aDirectory_Emails as $oDirectory_Email)
		{
			strlen(trim($oDirectory_Email->value)) && !in_array($oDirectory_Email->value, $aTmpEmails)
				&& $this->add(clone $oDirectory_Email);
		}

		// Directory_Websites
		$aTmpWebsites = array();

		$aTmp_Directory_Websites = $this->Directory_Websites->findAll(FALSE);
		foreach ($aTmp_Directory_Websites as $oDirectory_Website)
		{
			$aTmpWebsites[] = trim($oDirectory_Website->value);
		}

		$aDirectory_Websites = $oLead->Directory_Websites->findAll(FALSE);
		foreach ($aDirectory_Websites as $oDirectory_Website)
		{
			strlen(trim($oDirectory_Website->value)) && !in_array($oDirectory_Website->value, $aTmpWebsites)
				&& $this->add(clone $oDirectory_Website);
		}

		// Tags
		$aTmpTags = array();

		$aTmp_Tag_Leads = $this->Tag_Leads->findAll(FALSE);
		foreach ($aTmp_Tag_Leads as $oTag_Lead)
		{
			$aTmpTags[] = $oTag_Lead->tag_id;
		}

		$aTag_Leads = $oLead->Tag_Leads->findAll(FALSE);
		foreach ($aTag_Leads as $oTag_Lead)
		{
			$oTag_Lead->tag_id && !in_array($oTag_Lead->tag_id, $aTmpTags)
				&& $this->add(clone $oTag_Lead);
		}

		$this->save();

		$oLead->markDeleted();

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event lead.onBeforeGetRelatedSite
	 * @hostcms-event lead.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}