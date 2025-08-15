<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this
			->addSkipColumn('guid')
			->addSkipColumn('last_activity');

		return parent::setObject($object);
	}

	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title($this->_object->id
			? Core::_('Siteuser.su_edit_users_data_title', $this->_object->login, FALSE)
			: Core::_('Siteuser.su_add_users_data_title')
		);

		$this->_Admin_Form_Entity_Form->autocomplete('off');

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab
			->add(Admin_Form_Entity::factory('Div')->class('row')
				->add($oDivLeft = Admin_Form_Entity::factory('Div')->class('col-xs-12 col-md-6 col-lg-7 left-block'))
				->add($oDivRight = Admin_Form_Entity::factory('Div')->class('col-xs-12 col-md-6 col-lg-5 right-block'))
			);

		$oMainTab
		->add(Admin_Form_Entity::factory('Script')
			->value('
				$(function(){
					var bodyWidth = parseInt($("body").width()),
						timer = setInterval(function(){
							if (bodyWidth >= 992)
							{
								var heightLeft = $("#' . $windowId . ' .left-block").height();
								if (heightLeft)
								{
									clearInterval(timer);

									$("#' . $windowId . ' .right-block").find("#' . $windowId . '-siteuser-timeline, #' . $windowId . '-siteuser-notes, #' . $windowId . '-shop-orders, #' . $windowId . '-siteuser-emails, #' . $windowId . '-related-events, #' . $windowId . '-related-deals").slimscroll({
										height: heightLeft < 500 ? heightLeft + 150 : heightLeft - 75,
										color: "rgba(0, 0, 0, 0.3)",
										size: "5px"
									});
								}
							}
					}, 500);
				});
			'));

		$oSite = $this->_object->Site;

		// Shop orders
		/*$oShopOrderTab = Admin_Form_Entity::factory('Tab')
			->caption(Core::_('Siteuser.tabShopOrders'))
			->name('Shop_Order');

		$this->addTabAfter($oShopOrderTab, $oMainTab);

		$oShopOrderTab
			->add($oShopOrderTabRow1 = Admin_Form_Entity::factory('Div')->class('row'));

		if (Core::moduleIsActive('shop') && Core_Entity::factory('Shop')->getCountBySite_id(CURRENT_SITE))
		{
			if ($this->_object->id)
			{
				$sShopOrdersContainerId = 'shop-orders';

				$oDivShopOrders = Admin_Form_Entity::factory('Div')
					->id($sShopOrdersContainerId)
					->class('col-xs-12')
					->add(
						Admin_Form_Entity::factory('Script')
							->value("$(function (){
								$.adminLoad({ path: hostcmsBackend + '/shop/order/index.php', additionalParams: 'siteuser_id=" . $this->_object->id . "&hideMenu=1&_module=0', windowId: '{$sShopOrdersContainerId}' });
							});")
					);

				$oShopOrderTabRow1->add($oDivShopOrders);

				$countOrders = $this->_object->Shop_Orders->getCount(FALSE);

				$countOrders&& $oShopOrderTab
					->badge($countOrders)
					->badgeColor('palegreen');
			}
			else
			{
				$oShopOrderTabRow1->add(Admin_Form_Entity::factory('Code')->html(
					Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
				));
			}
		}*/

		$oUser = Core_Auth::getCurrentUser();

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oPropertyTab = Admin_Form_Entity::factory('Tab')
			->caption(Core::_('Admin_Form.tabProperties'))
			->name('Property');

		$this->addTabAfter($oPropertyTab, $oMainTab);

		if ($this->_object->id)
		{
			$isOnline = $this->_object->isOnline();

			$sStatus = $isOnline ? 'online' : 'offline';

			$lng = $isOnline ? 'siteuser_active' : 'siteuser_last_activity';

			$sStatusTitle = !is_null($this->_object->last_activity)
				? Core::_('Siteuser.' . $lng, Core_Date::sql2datetime($this->_object->last_activity))
				: '';

			$this->addEntity(
				Admin_Form_Entity::factory('Code')
					->html('
						<script>
							$("#' . $windowId . ' h5.row-title").append("<span title=\"' . htmlspecialchars($sStatusTitle) . '\" class=\"' . htmlspecialchars($sStatus) . ' margin-left-5\"></span>");
						</script>
				')
			);
		}

		$aSiteuser_Groups = $oSite->Siteuser_Groups->findAll(FALSE);
		if (count($aSiteuser_Groups))
		{
			$oSiteuserGroupsTab = Admin_Form_Entity::factory('Tab')
				->caption(Core::_('Siteuser.siteuser_groups'))
				->name('SiteuserGroups');

			$this->addTabAfter($oSiteuserGroupsTab, $oPropertyTab);

			$sTableBody = '';

			$aTmp = array();

			if ($this->_object->id)
			{
				$aGroupsForSiteuser = $this->_object->Siteuser_Groups->findAll(FALSE);
				foreach ($aGroupsForSiteuser as $oSiteuser_Group)
				{
					$aTmp[] = $oSiteuser_Group->id;
				}
			}
			else
			{
				$oSiteuser_Group = $oSite->Siteuser_Groups->getDefault();

				!is_null($oSiteuser_Group)
					&& $aTmp[] = $oSiteuser_Group->id;
			}

			$countGroups = count($aTmp);

			$countGroups && $oSiteuserGroupsTab
				->badge($countGroups)
				->badgeColor('sky');

			foreach ($aSiteuser_Groups as $oSiteuser_Group)
			{
				$checked = in_array($oSiteuser_Group->id, $aTmp)
					? 'checked="checked"'
					: '';

				$sTableBody .= '<tr>
					<td class="text-align-center">
						<label>
							<input type="checkbox" ' . $checked . ' name="siteuser_group_' . $oSiteuser_Group->id . '" value="1"/>
							<span class="text"></span>
						</label>
					</td>
					<td>
						<a href="' . Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/group/list/index.php') . '?siteuser_group_id=' . $oSiteuser_Group->id .'" onclick="$.modalLoad({path: hostcmsBackend + \'/siteuser/group/list/index.php\', additionalParams: \'siteuser_group_id=' . $oSiteuser_Group->id . '\', windowId: \'' . $windowId . '\'}); return false">'
							. htmlspecialchars($oSiteuser_Group->name)
							. '</a>
					</td>
					<td>' . htmlspecialchars((string) $oSiteuser_Group->description) . '</td>
					<td>' . ($oSiteuser_Group->default ? '<i class="fa fa-lightbulb-o"></i>' : '') . '</td>
					</tr>';
			}

			ob_start();

			Admin_Form_Entity::factory('code')
				->html($sTableBody)
				->execute();

			$sHtmlTableBody = ob_get_clean();

			$oSiteuserGroupRow = Admin_Form_Entity::factory('Div')
				->class('row')
				->add(
					 Admin_Form_Entity::factory('Div')
						->class("form-group col-lg-12")
						->add(
							Admin_Form_Entity::factory('code')
								->html('
									<table class="table">
										<thead>
											<tr>
												<th></th>
												<th>' . Core::_('Siteuser.siteuser_group') . '</th>
												<th>' . Core::_('Siteuser.siteuser_group_description') . '</th>
												<th width="120px">' . Core::_('Siteuser.siteuser_group_default') . '</th>
											</tr>
											<tbody>' . $sHtmlTableBody . '</tbody>
										</thead>
									</table>
								')
						)

				);

			$oSiteuserGroupsTab->add($oSiteuserGroupRow);
		}

		$oDivLeft
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'));


		$oDivRight
			->add($oMainRowRight1 = Admin_Form_Entity::factory('Div')->class('row'));

		$oAdditionalTab
			->add($oAdditionalRow1 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab
			->move($this->getField('login')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))->format(FALSE), $oMainRow1)
			->move($this->getField('email')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))->class('form-control input-lg semi-bold black'), $oMainRow1);

		$oMainTab->delete($this->getField('password'));

		$aConfig = Siteuser_Controller::getConfig($this->_object->site_id);

		$aPasswordFormat = array(
			'minlen' => array('value' => 5),
			'maxlen' => array('value' => 255),
			'fieldEquality' => array(
				'value' => 'password_second',
				'message' => Core::_('Siteuser.passwords_not_valid')
			)
		);

		$oPasswordFirst = Admin_Form_Entity::factory('Password');
		$oPasswordFirst
			->caption(Core::_('Siteuser.password'))
			->id('password_first')
			->name('password_first')
			->divAttr(array('class' => 'form-group col-xs-6 col-sm-4'))
			->generatePassword(TRUE)
			->generatePasswordLength($aConfig['generatePasswordLength']);

		$oPasswordSecond = Admin_Form_Entity::factory('Password');

		$aPasswordFormatSecond = array(
			'fieldEquality' => array(
				'value' => 'password_first',
				'message' => Core::_('Siteuser.passwords_not_valid')
			)
		);

		if (!$this->_object->id)
		{
			$password = Core_Password::get($aConfig['generatePasswordLength']);

			$oPasswordFirst->format($aPasswordFormat)->value($password)->type('text');
			$aPasswordFormatSecond += $aPasswordFormat;
			$oPasswordSecond->value($password)->type('text');
		}

		$oPasswordSecond
			->caption(Core::_('Siteuser.password_retry'))
			->id('password_second')
			->name('password_second')
			->divAttr(array('class' => 'form-group col-xs-6 col-sm-4'))
			->format($aPasswordFormatSecond);

		$oMainRow2
			->add($oPasswordFirst)
			->add($oPasswordSecond);

		$oMainTab
			->move($this->getField('active')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4 margin-top-21')), $oMainRow2);

		$oAdditionalTab
			->delete($this->getField('site_id'))
			->delete($this->getField('siteuser_type_id'))
			->delete($this->getField('siteuser_status_id'))
			->delete($this->getField('crm_source_id'));

		$aMasSiteuserTypes = array(array('value' => Core::_('Siteuser.not'), 'color' => '#aebec4'));

		$aSiteuser_Types = Core_Entity::factory('Siteuser_Type', 0)->findAll();
		foreach ($aSiteuser_Types as $oSiteuser_Type)
		{
			$aMasSiteuserTypes[$oSiteuser_Type->id] = array('value' => $oSiteuser_Type->name, 'color' => $oSiteuser_Type->color);
		}

		$oDropdownlistSiteuserTypes = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasSiteuserTypes)
			->name('siteuser_type_id')
			->value($this->_object->siteuser_type_id)
			->caption(Core::_('Siteuser.siteuser_type_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

		$oMainRow3->add($oDropdownlistSiteuserTypes);

		$aMasSiteuserStatuses = array(array('value' => Core::_('Siteuser.not'), 'color' => '#aebec4'));

		$aSiteuser_Statuses = Core_Entity::factory('Siteuser_Status', 0)->findAll();
		foreach ($aSiteuser_Statuses as $oSiteuser_Status)
		{
			$aMasSiteuserStatuses[$oSiteuser_Status->id] = array('value' => $oSiteuser_Status->name, 'color' => $oSiteuser_Status->color);
		}

		$oDropdownlistSiteuserStatuses = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasSiteuserStatuses)
			->name('siteuser_status_id')
			->value($this->_object->siteuser_status_id)
			->caption(Core::_('Siteuser.siteuser_status_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

		$oMainRow3->add($oDropdownlistSiteuserStatuses);

		$aMasCrmSources = array(array('value' => Core::_('Siteuser.not'), 'color' => '#aebec4'));

		$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
		foreach ($aCrm_Sources as $oCrm_Source)
		{
			$aMasCrmSources[$oCrm_Source->id] = array(
				'value' => $oCrm_Source->name,
				'color' => $oCrm_Source->color,
				'icon' => $oCrm_Source->icon
			);
		}

		$oDropdownlistCrmSources = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasCrmSources)
			->name('crm_source_id')
			->value($this->_object->crm_source_id)
			->caption(Core::_('Siteuser.crm_source_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

		$oMainRow3
			->add($oDropdownlistCrmSources);

		// Ответственные сотрудники
		$aResponsibleEmployees = array();

		$showBar = FALSE;

		if (!$this->_object->id)
		{
			$oSiteuser_Company = Core_Entity::factory('Siteuser_Company');

			$oSiteuser_Person = Core_Entity::factory('Siteuser_Person');
		}
		else
		{
			$aSiteuser_Companies = $this->_object->Siteuser_Companies->findAll(FALSE);

			$oSiteuser_Company = count($aSiteuser_Companies) == 1
				? $aSiteuser_Companies[0]
				: NULL;

			$aSiteuser_People = $this->_object->Siteuser_People->findAll(FALSE);

			$oSiteuser_Person = count($aSiteuser_People) == 1
				? $aSiteuser_People[0]
				: NULL;

			$aSiteuser_Users = $this->_object->Siteuser_Users->findAll(FALSE);
			foreach ($aSiteuser_Users as $oSiteuserUser)
			{
				$aResponsibleEmployees[] = $oSiteuserUser->user_id;
			}

			!is_null($oSiteuser_Company) || !is_null($oSiteuser_Person)
				&& $showBar = TRUE;
		}

		if (is_null($oSiteuser_Company) && is_null($oSiteuser_Person) && $showBar)
		{
			$oDivLeft
				->add($oRepresentativeTitle = Admin_Form_Entity::factory('Div')->class('row profile-container'));

			$oRepresentativeTitle->add(
				Admin_Form_Entity::factory('Code')
					->html('<div class="col-xs-12"><h6 class="row-title before-palegreen">' . Core::_('Siteuser.representatives') . '</h6>'
						. $this->_object->counterpartyBackend(NULL, $this->_Admin_Form_Controller)
						. '</div>'
					)
			);
		}
		else
		{
			$oDivLeft
				->add($oPersonTitle = Admin_Form_Entity::factory('Div')->class('row profile-container'))
				->add($oPersonBlock = Admin_Form_Entity::factory('Div')->class('well-person-block'))
				->add($oCompanyTitle = Admin_Form_Entity::factory('Div')->class('row profile-container'))
				->add($oCompanyBlock = Admin_Form_Entity::factory('Div')->class('well-company-block'));

			if ($oSiteuser_Person)
			{
				$oPersonTitle->add(
					Admin_Form_Entity::factory('Code')
						->html('<div class="col-xs-12"><h6 class="row-title before-darkorange">' . Core::_('Siteuser.person_header') . '</h6></div>')
				);

				// Представитель
				$oPersonBlock
					/*->add(Admin_Form_Entity::factory('Div')
						->class('header bordered-darkorange')
						->value(Core::_('Siteuser.person_header'))
					)*/
					->add($oPersonRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oPersonRow2 = Admin_Form_Entity::factory('Div')->class('row hidden-field hidden'))
					->add($oPersonRow3 = Admin_Form_Entity::factory('Div')->class('row hidden-field hidden'))
					->add($oPersonRow4 = Admin_Form_Entity::factory('Div')->class('row hidden-field hidden'))
					->add($oPersonRowShow = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oPersonRow5 = Admin_Form_Entity::factory('Div')->class(''));

				$oPersonRow1->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Person.surname'))
						->class('form-control input-lg semi-bold black')
						->name('person_surname')
						->value($oSiteuser_Person->surname)
				)->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Person.name'))
						->class('form-control input-lg semi-bold black')
						->name('person_name')
						->value($oSiteuser_Person->name)
				)->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Person.patronymic'))
						->class('form-control input-lg')
						->name('person_patronymic')
						->value($oSiteuser_Person->patronymic)
				);

				$oPersonRow2->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_('Siteuser_Person.post'))
						->class('form-control')
						->name('person_post')
						->value($oSiteuser_Person->post)
				);

				/*$oPersonRow3->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Person.postcode'))
						->class('form-control')
						->name('person_postcode')
						->value($oSiteuser_Person->postcode)
				)->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Person.country'))
						->class('form-control')
						->name('person_country')
						->value($oSiteuser_Person->country)
				)->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Person.city'))
						->class('form-control')
						->name('person_city')
						->value($oSiteuser_Person->city)
				);

				$oPersonRow4->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_('Siteuser_Person.address'))
						->class('form-control')
						->name('person_address')
						->value($oSiteuser_Person->address)
				);*/

				$oPersonRowShow->add(Admin_Form_Entity::factory('Span')
					->divAttr(array('class' => 'form-group col-xs-12'))
					->add(Admin_Form_Entity::factory('A')
						->value(Core::_("Siteuser.show_fields"))
						->class('representative-show-link darkgray')
						->onclick('$.toggleRepresentativeFields(".well-person-block")')
					)
				);

				// Адреса
				$oSiteuserPersonAddressesRow = Directory_Controller_Tab::instance('address')
					->title(Core::_('Directory_Address.address'))
					->relation($oSiteuser_Person->Siteuser_Person_Directory_Addresses)
					->showPublicityControlElement(TRUE)
					->prefix('person_')
					->execute();

				// Телефоны
				$oSiteuserPersonPhonesRow = Directory_Controller_Tab::instance('phone')
					->title(Core::_('Directory_Phone.phones'))
					->relation($oSiteuser_Person->Siteuser_Person_Directory_Phones)
					->showPublicityControlElement(TRUE)
					->prefix('person_')
					->execute();

				// Email'ы
				$oSiteuserPersonEmailsRow = Directory_Controller_Tab::instance('email')
					->title(Core::_('Directory_Email.emails'))
					->relation($oSiteuser_Person->Siteuser_Person_Directory_Emails)
					->showPublicityControlElement(TRUE)
					->prefix('person_')
					->execute();

				// Социальные сети
				$oSiteuserPersonSocialsRow = Directory_Controller_Tab::instance('social')
					->title(Core::_('Directory_Social.socials'))
					->relation($oSiteuser_Person->Siteuser_Person_Directory_Socials)
					->showPublicityControlElement(TRUE)
					->prefix('person_')
					->execute();

				// Мессенджеры
				$oSiteuserPersonMessengersRow = Directory_Controller_Tab::instance('messenger')
					->title(Core::_('Directory_Messenger.messengers'))
					->relation($oSiteuser_Person->Siteuser_Person_Directory_Messengers)
					->showPublicityControlElement(TRUE)
					->prefix('person_')
					->execute();

				// Сайты
				$oSiteuserPersonWebsitesRow = Directory_Controller_Tab::instance('website')
					->title(Core::_('Directory_Website.sites'))
					->relation($oSiteuser_Person->Siteuser_Person_Directory_Websites)
					->showPublicityControlElement(TRUE)
					->prefix('person_')
					->execute();

				$oPersonRow5
					->add($oSiteuserPersonAddressesRow)
					->add($oSiteuserPersonPhonesRow)
					->add($oSiteuserPersonEmailsRow)
					->add($oSiteuserPersonSocialsRow)
					->add($oSiteuserPersonMessengersRow)
					->add($oSiteuserPersonWebsitesRow);
			}

			if ($oSiteuser_Company)
			{
				$oCompanyTitle->add(
					Admin_Form_Entity::factory('Code')
						->html('<div class="col-xs-12"><h6 class="row-title before-palegreen">' . Core::_('Siteuser.company_header') . '</h6></div>')
				);

				// Компания
				$oCompanyBlock
					/*->add(Admin_Form_Entity::factory('Div')
							->class('header bordered-palegreen')
							->value(Core::_('Siteuser.company_header'))
					)*/
					->add($oCompanyRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oCompanyRow2 = Admin_Form_Entity::factory('Div')->class('row hidden-field hidden'))
					->add($oCompanyRowShow = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oCompanyRow3 = Admin_Form_Entity::factory('Div')->class(''))
					->add($oCompanyRow4 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oCompanyRow5 = Admin_Form_Entity::factory('Div')->class('row'));

				$oCompanyRow1->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_('Siteuser_Company.name'))
						->class('form-control input-lg semi-bold black')
						->name('company_name')
						->value($oSiteuser_Company->name)
				);

				$oCompanyRow2->add(
					Admin_Form_Entity::factory('Textarea')
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_('Siteuser_Company.description'))
						->class('form-control')
						->name('company_description')
						->value($oSiteuser_Company->description)
				)->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Company.business_area'))
						->class('form-control')
						->name('company_business_area')
						->value($oSiteuser_Company->business_area)
				)->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Company.headcount'))
						->class('form-control')
						->name('company_headcount')
						->value(intval($oSiteuser_Company->headcount))
				)
				->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))
						->caption(Core::_('Siteuser_Company.annual_turnover'))
						->class('form-control')
						->name('company_annual_turnover')
						->value(intval($oSiteuser_Company->annual_turnover))
				);

				$oCompanyRowShow->add(Admin_Form_Entity::factory('Span')
					->divAttr(array('class' => 'form-group col-xs-12'))
					->add(Admin_Form_Entity::factory('A')
						->value(Core::_("Siteuser.show_fields"))
						->class('representative-show-link darkgray')
						->onclick('$.toggleRepresentativeFields(".well-company-block")')
					)
				);

				// Адреса
				$oSiteuserCompanyAddressesRow = Directory_Controller_Tab::instance('address')
					->title(Core::_('Directory_Address.address'))
					->relation($oSiteuser_Company->Siteuser_Company_Directory_Addresses)
					->showPublicityControlElement(TRUE)
					->prefix('company_')
					->execute();

				// Телефоны
				$oSiteuserCompanyPhonesRow = Directory_Controller_Tab::instance('phone')
					->title(Core::_('Directory_Phone.phones'))
					->relation($oSiteuser_Company->Siteuser_Company_Directory_Phones)
					->showPublicityControlElement(TRUE)
					->prefix('company_')
					->execute();

				// Email'ы
				$oSiteuserCompanyEmailsRow = Directory_Controller_Tab::instance('email')
					->title(Core::_('Directory_Email.emails'))
					->relation($oSiteuser_Company->Siteuser_Company_Directory_Emails)
					->showPublicityControlElement(TRUE)
					->prefix('company_')
					->execute();

				// Социальные сети
				$oSiteuserCompanySocialsRow = Directory_Controller_Tab::instance('social')
					->title(Core::_('Directory_Social.socials'))
					->relation($oSiteuser_Company->Siteuser_Company_Directory_Socials)
					->showPublicityControlElement(TRUE)
					->prefix('company_')
					->execute();

				// Мессенджеры
				$oSiteuserCompanyMessengersRow = Directory_Controller_Tab::instance('messenger')
					->title(Core::_('Directory_Messenger.messengers'))
					->relation($oSiteuser_Company->Siteuser_Company_Directory_Messengers)
					->showPublicityControlElement(TRUE)
					->prefix('company_')
					->execute();

				// Сайты
				$oSiteuserCompanyWebsitesRow = Directory_Controller_Tab::instance('website')
					->title(Core::_('Directory_Website.sites'))
					->relation($oSiteuser_Company->Siteuser_Company_Directory_Websites)
					->showPublicityControlElement(TRUE)
					->prefix('company_')
					->execute();

				$oCompanyRow3
					->add($oSiteuserCompanyPhonesRow)
					->add($oSiteuserCompanyEmailsRow)
					->add($oSiteuserCompanyAddressesRow)
					->add($oSiteuserCompanySocialsRow)
					->add($oSiteuserCompanyMessengersRow)
					->add($oSiteuserCompanyWebsitesRow);

				$oCompanyRow4->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_('Siteuser_Company.tin'))
						->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
						->class('form-control')
						->name('company_tin')
						->value($oSiteuser_Company->tin)
				);

				$oCompanyRow5->add(
					Admin_Form_Entity::factory('Textarea')
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_('Siteuser_Company.bank_account'))
						->class('form-control')
						->name('company_bank_account')
						->value($oSiteuser_Company->bank_account)
				);
			}
		}

		$aSelectedResponsibleEmployees = $oSite->Companies->getUsersOptions();

		$oSelectResponsibleEmployees = Admin_Form_Entity::factory('Select');
		$oSelectResponsibleEmployees
			->id('siteuser_user_id')
			->multiple('multiple')
			->options($aSelectedResponsibleEmployees)
			->name('siteuser_user_id[]')
			->value($aResponsibleEmployees)
			// ->caption(Core::_('Siteuser.siteuser_user_id'))
			->divAttr(array('class' => 'form-group col-xs-12'));

		$oScriptResponsibleEmployees = Admin_Form_Entity::factory('Script')
			->value('
				$("#' . $windowId . ' #siteuser_user_id").select2({
					placeholder: "",
					allowClear: true,
					//multiple: true,
					templateResult: $.templateResultItemResponsibleEmployees,
					escapeMarkup: function(m) { return m; },
					templateSelection: $.templateSelectionItemResponsibleEmployees,
					language: "' . Core_I18n::instance()->getLng() . '",
					width: "100%"
				});'
			);

		$oDivLeft
			->add($oResponsibleTitle = Admin_Form_Entity::factory('Div')->class('row profile-container'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'));

		$oResponsibleTitle->add(
			Admin_Form_Entity::factory('Code')
				->html('<div class="col-xs-12"><h6 class="row-title before-darkorange">' . Core::_('Siteuser.siteuser_user_id') . '</h6></div>')
		);

		$oMainRow4
			->add($oSelectResponsibleEmployees)
			->add($oScriptResponsibleEmployees);

		if (!$this->_object->active)
		{
			$oSend_Mail_Confirm = Admin_Form_Entity::factory('Checkbox')
				->name('send_mail_confirm')
				->caption(Core::_('Siteuser.send_mail_confirm'))
				->value(1);

			$oDivLeft->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'));
			$oMainRow5->add($oSend_Mail_Confirm);
		}

		// Properties
		Core::moduleIsActive('property') && Property_Controller_Tab::factory($this->_Admin_Form_Controller)
			->setObject($this->_object)
			->setDatasetId($this->getDatasetId())
			->linkedObject(Core_Entity::factory('Siteuser_Property_List', CURRENT_SITE))
			->setTab($oPropertyTab)
			->fillTab();

		// Siteuser's Affiliate
		$oSiteuser_Affiliate = Core_Entity::factory('Siteuser_Affiliate')->getByReferral_siteuser_id($this->_object->id);

		if (!is_null($oSiteuser_Affiliate) && $oSiteuser_Affiliate->Siteuser->id)
		{
			$oSiteuserLink = Admin_Form_Entity::factory('Link');
			$oSiteuserLink
				->divAttr(array('class' => 'form-group col-lg-3 col-md-3 col-sm-12 col-xs-12'))
				->caption(Core::_('Siteuser.affiliate'))
				->a
					->class('btn btn-labeled btn-sky')
					->href($this->_Admin_Form_Controller->getAdminActionLoadHref('/{admin}/siteuser/index.php', 'edit', NULL, 0, $oSiteuser_Affiliate->Siteuser->id))
					->onclick("$.openWindowAddTaskbar({path: hostcmsBackend + '/siteuser/index.php', additionalParams: 'hostcms[checked][0][{$oSiteuser_Affiliate->Siteuser->id}]=1&hostcms[action]=edit', shortcutImg: '" . '/modules/skin/' . Core_Skin::instance()->getSkinName() . '/images/module/siteuser.png' . "', shortcutTitle: 'undefined', Minimize: true}); return false")
					->value($oSiteuser_Affiliate->Siteuser->login)
					->target('_blank');
			$oSiteuserLink
				->icon
					->class('btn-label fa fa-user');

			$oAdditionalRow1->add($oSiteuserLink);
		}

		$oUser_Controller_Edit = new User_Controller_Edit($this->_Admin_Form_Action);

		$oMainTab->move($this->getField('ip')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow1);

		// Список сайтов
		$oSelect_Sites = Admin_Form_Entity::factory('Select');
		$oSelect_Sites
			->options($oUser_Controller_Edit->fillSites())
			->name('site_id')
			->value($this->_object->site_id)
			->caption(Core::_('Siteuser.site_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-3'));

		$oAdditionalRow1->add($oSelect_Sites);

		$oMainTab->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow1);

		if (Core::moduleIsActive('forum'))
		{
			$countMessages = 0;

			if ($this->_object->Forum_Siteuser_Counts->getCount())
			{
				$aForum_Siteuser_Counts = $this->_object->Forum_Siteuser_Counts->findAll(FALSE);
				foreach ($aForum_Siteuser_Counts as $oForum_Siteuser_Count)
				{
					$countMessages += $oForum_Siteuser_Count->count;
				}
			}

			$oAdditionalRow1->add(
				Admin_Form_Entity::factory('Input')
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-3'))
					->caption(Core::_('Siteuser.count_messages'))
					->class('form-control')
					->name('count_messages')
					->disabled('disabled')
					->value($countMessages)
			);
		}

		ob_start();
		?>
		<div class="tabbable">
			<ul class="nav nav-tabs tabs-flat" id="dealTabs">
				<li class="active" data-type="timeline">
					<a data-toggle="tab" href="#<?php echo $windowId?>_timeline" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/timeline/index.php')?>" data-window-id="<?php echo $windowId?>-siteuser-timeline" data-additional="siteuser_id=<?php echo $this->_object->id?>">
						<i class="fa fa-bars"></i>
					</a>
				</li>
				<li data-type="note">
					<a data-toggle="tab" href="#<?php echo $windowId?>_notes" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/note/index.php')?>" data-window-id="<?php echo $windowId?>-siteuser-notes" data-additional="siteuser_id=<?php echo $this->_object->id?>">
						<?php echo Core::_("Siteuser.tabNotes")?> <?php echo ($count = $this->_object->Crm_Notes->getCount())
							? '<span class="badge badge-yellow">' . $count . '</span>'
							: ''?>
					</a>
				</li>
				<?php
				if (Core::moduleIsActive('shop') && Core_Entity::factory('Shop')->getCountBySite_id(CURRENT_SITE))
				{
					if ($this->_object->id)
					{
						?>
						<li data-type="shop_order">
							<a data-toggle="tab" href="#<?php echo $windowId?>_shop_orders" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/shop/order/index.php')?>" data-window-id="<?php echo $windowId?>-shop-orders" data-additional="show_subs=1&hideMenu=1&siteuser_id=<?php echo $this->_object->id?>&parent_id=<?php echo $this->_object->id?>">
								<?php echo Core::_("Siteuser.tabShopOrders")?> <?php echo ($countOrders = $this->_object->Shop_Orders->getCount(FALSE))
									? '<span class="badge badge-palegreen">' . $countOrders . '</span>'
									: ''?>
							</a>
						</li>
						<?php
					}
				}

				if ($this->_object->id)
				{
					?>
					<li data-type="email">
						<a data-toggle="tab" href="#<?php echo $windowId?>_emails" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/event/index.php')?>" data-window-id="<?php echo $windowId?>-siteuser-emails" data-additional="show_subs=1&hideMenu=1&siteuser_id=<?php echo $this->_object->id?>&parent_id=<?php echo $this->_object->id?>">
							<?php echo Core::_("Siteuser.tabEmails")?> <?php echo ($countEmails = $this->_object->Siteuser_Emails->getCount(FALSE))
								? '<span class="badge badge-azure">' . $countEmails . '</span>'
								: ''?>
						</a>
					</li>
					<?php
				}

				if (Core::moduleIsActive('event'))
				{
					if ($this->_object->id)
					{
						$oEventsQb = Core_Entity::factory('Event');
						$oEventsQb
							->queryBuilder()
							->join('event_siteusers', 'events.id', '=', 'event_siteusers.event_id')
							->join('event_users', 'events.id', '=', 'event_users.event_id')
							->leftJoin('siteuser_companies', 'event_siteusers.siteuser_company_id', '=', 'siteuser_companies.id')
							->leftJoin('siteuser_people', 'event_siteusers.siteuser_person_id', '=', 'siteuser_people.id')
							->where('event_users.user_id', '=', $oUser->id)
							->open()
								->where('siteuser_companies.siteuser_id', '=', $this->_object->id)
								->setOr()
								->where('siteuser_people.siteuser_id', '=', $this->_object->id)
							->close()
							;

						$countEvents = $oEventsQb->getCount(FALSE, 'events.id', TRUE);
					}
					else
					{
						$countEvents = 0;
					}
					?>
					<li data-type="event">
						<a data-toggle="tab" href="#<?php echo $windowId?>_events" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/event/index.php')?>" data-window-id="<?php echo $windowId?>-related-events" data-additional="show_subs=1&hideMenu=1&siteuser_id=<?php echo $this->_object->id?>&parent_id=<?php echo $this->_object->id?>">
							<?php echo Core::_("Siteuser.tabEvents")?> <?php echo $countEvents
								? '<span class="badge badge-orange">' . $countEvents . '</span>'
								: ''?>
						</a>
					</li>
					<?php
				}

				if (Core::moduleIsActive('deal'))
				{
					if ($this->_object->id)
					{
						$oDealsQb = Core_Entity::factory('Deal');
						$oDealsQb
							->queryBuilder()
							->join('deal_siteusers', 'deals.id', '=', 'deal_siteusers.deal_id')
							->leftJoin('siteuser_companies', 'deal_siteusers.siteuser_company_id', '=', 'siteuser_companies.id')
							->leftJoin('siteuser_people', 'deal_siteusers.siteuser_person_id', '=', 'siteuser_people.id')
							->open()
								->where('siteuser_companies.siteuser_id', '=', $this->_object->id)
								->setOr()
								->where('siteuser_people.siteuser_id', '=', $this->_object->id)
							->close();

						$countDeals = $oDealsQb->getCount(FALSE, 'deals.id', TRUE);
					}
					else
					{
						$countDeals = 0;
					}
					?>
					<li data-type="deal">
						<a data-toggle="tab" href="#<?php echo $windowId?>_deals" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/deal/index.php')?>" data-window-id="<?php echo $windowId?>-related-deals" data-additional="show_subs=1&hideMenu=1&siteuser_id=<?php echo $this->_object->id?>&parent_id=<?php echo $this->_object->id?>">
							<?php echo Core::_("Siteuser.tabDeals")?> <?php echo $countDeals
								? '<span class="badge badge-purple">' . $countDeals . '</span>'
								: ''?>
						</a>
					</li>
					<?php
				}
				?>
			</ul>
			<div class="tab-content tabs-flat">
				<div id="<?php echo $windowId?>_timeline" class="tab-pane in active">
					<?php
						Admin_Form_Entity::factory('Div')
							->controller($this->_Admin_Form_Controller)
							->id("{$windowId}-siteuser-timeline")
							->add(
								$this->_object->id
									? $this->_addSiteuserTimeline()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
									)
							)
							->execute();
					?>
				</div>
				<div id="<?php echo $windowId?>_notes" class="tab-pane">
				<?php
				Admin_Form_Entity::factory('Div')
					->controller($this->_Admin_Form_Controller)
					->id("{$windowId}-siteuser-notes")
					->add(
						$this->_object->id
							? $this->_addSiteuserNotes()
							: Admin_Form_Entity::factory('Code')->html(
								Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
							)
					)
					->execute();
				?>
				</div>
				<?php
				if (Core::moduleIsActive('shop') && Core_Entity::factory('Shop')->getCountBySite_id(CURRENT_SITE))
				{
					if ($this->_object->id)
					{
						?>
						<div id="<?php echo $windowId?>_shop_orders" class="tab-pane">
						<?php
						Admin_Form_Entity::factory('Div')
							->id("{$windowId}-shop-orders")
							->add(
								$this->_object->id
									? $this->_addShopOrders()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
									)
							)
							->execute();
						?>
						</div>
						<?php
					}
				}

				if ($this->_object->id)
				{
					?>
					<div id="<?php echo $windowId?>_emails" class="tab-pane">
					<?php
					Admin_Form_Entity::factory('Div')
						->id("{$windowId}-siteuser-emails")
						->add(
							$this->_object->id
								? $this->_addSiteuserEmails()
								: Admin_Form_Entity::factory('Code')->html(
									Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
								)
						)
						->execute();
					?>
					</div>
					<?php
				}

				if (Core::moduleIsActive('event'))
				{
				?>
					<div id="<?php echo $windowId?>_events" class="tab-pane">
					<?php
						Admin_Form_Entity::factory('Div')
							->id("{$windowId}-related-events")
							// ->class('related-events related-events-inner')
							->add(
								$this->_object->id
									? $this->_addSiteuserEvents()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
									)
							)
							->execute();
					?>
					</div>
				<?php
				}

				if (Core::moduleIsActive('deal'))
				{
				?>
					<div id="<?php echo $windowId?>_deals" class="tab-pane">
					<?php
						Admin_Form_Entity::factory('Div')
							->id("{$windowId}-related-deals")
							// ->class('related-events related-events-inner')
							->add(
								$this->_object->id
									? $this->_addSiteuserDeals()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Siteuser.enable_after_save'), 'warning')
									)
							)
							->execute();
					?>
					</div>
				<?php
				}
				?>
			</div>
		</div>
		<?php
		$oMainRowRight1->add(Admin_Form_Entity::factory('Div')
			->class('form-group col-xs-12')
			->add(
				Admin_Form_Entity::factory('Code')
					->html(ob_get_clean())
			)
		);

		return $this;
	}

	/**
	 * Add timeline
	 * @return Admin_Form_Entity
	 */
	protected function _addSiteuserTimeline()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function() {
				$.adminLoad({ path: hostcmsBackend + '/siteuser/timeline/index.php', additionalParams: 'siteuser_id={$this->_object->id}&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-siteuser-timeline' });
			});");
	}

	/**
	 * Add notes
	 * @return Admin_Form_Entity
	 */
	protected function _addSiteuserNotes()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/siteuser/note/index.php', additionalParams: 'siteuser_id=" . $this->_object->id . "', windowId: '{$windowId}-siteuser-notes' });
			});");
	}

	/*
	 * Add siteuser events
	 * @return Admin_Form_Entity
	 */
	protected function _addSiteuserEvents()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/siteuser/event/index.php', additionalParams: 'siteuser_id={$this->_object->id}&show_subs=1&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-related-events' });
			});");
	}

	/*
	 * Add siteuser deals
	 * @return Admin_Form_Entity
	 */
	protected function _addSiteuserDeals()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/siteuser/deal/index.php', additionalParams: 'siteuser_id={$this->_object->id}&show_subs=1&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-related-deals' });
			});");
	}

	/*
	 * Add siteuser emails
	 * @return Admin_Form_Entity
	 */
	protected function _addSiteuserEmails()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/siteuser/email/index.php', additionalParams: 'siteuser_id={$this->_object->id}&show_subs=1&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-siteuser-emails' });
			});");
	}

	/*
	 * Add shop orders
	 * @return Admin_Form_Entity
	 */
	protected function _addShopOrders()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/shop/order/index.php', additionalParams: 'siteuser_id={$this->_object->id}&show_subs=1&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-shop-orders' });
			});");
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Siteuser_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$this
			->addSkipColumn('password')
			->addSkipColumn('last_activity');

		$password_first = Core_Array::getPost('password_first', '', 'str');
		$password_second = Core_Array::getPost('password_second', '', 'str');

		$bNewSiteuser = is_null($this->_object->id);

		if ($password_first !== '' && $password_first === $password_second || $bNewSiteuser)
		{
			$this->_object->password = Core_Hash::instance()->hash($password_first);
		}

		parent::_applyObjectProperty();

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		if ($bNewSiteuser)
		{
			ob_start();
			$this->_addSiteuserTimeline()->execute();
			$this->_addSiteuserNotes()->execute();
			Core::moduleIsActive('event') && $this->_addSiteuserEvents()->execute();
			Core::moduleIsActive('deal') && $this->_addSiteuserDeals()->execute();
			?><script>
				$(function(){
					$("#<?php echo $windowId?> a[data-additional='siteuser_id=']").data('additional', 'siteuser_id=<?php echo $this->_object->id?>');
				});
			</script><?php
			$this->_Admin_Form_Controller->addMessage(ob_get_clean());
		}

		if ($this->_object->login == '')
		{
			$this->_object->login = 'id' . $this->_object->id;
			$this->_object->save();
		}

		$oSiteuser = $this->_object;

		// Ответственные сотрудники
		$aSiteuser_Users = $oSiteuser->Siteuser_Users->findAll(FALSE);

		$aSiteuserUserId = Core_Array::getPost('siteuser_user_id');
		!is_array($aSiteuserUserId) && $aSiteuserUserId = array();

		$aAlreadyExists = array();
		foreach ($aSiteuser_Users as $oSiteuser_User)
		{
			$iSearchIndex = array_search($oSiteuser_User->user_id, $aSiteuserUserId);

			$iSearchIndex === FALSE
				? $oSiteuser_User->delete()
				: $aAlreadyExists[] = $oSiteuser_User->user_id;
		}

		foreach ($aSiteuserUserId as $iSiteuserUserId)
		{
			if (!in_array($iSiteuserUserId, $aAlreadyExists))
			{
				$oSiteuser_User = Core_Entity::factory('Siteuser_User')
					->user_id($iSiteuserUserId);

				$oSiteuser->add($oSiteuser_User);
			}
		}

		$aSiteuser_Group_Lists = $oSiteuser->Siteuser_Group_Lists->findAll(FALSE);
		$aTmp = array();
		foreach ($aSiteuser_Group_Lists as $oSiteuser_Group_List)
		{
			$aTmp[$oSiteuser_Group_List->siteuser_group_id] = $oSiteuser_Group_List;
		}

		$aSiteuser_Groups = $oSiteuser->Site->Siteuser_Groups->findAll(FALSE);
		foreach ($aSiteuser_Groups as $oSiteuser_Group)
		{
			if (Core_Array::getPost('siteuser_group_' . $oSiteuser_Group->id))
			{
				if (!isset($aTmp[$oSiteuser_Group->id]))
				{
					$oSiteuser_Group_List = Core_Entity::factory('Siteuser_Group_List');
					$oSiteuser_Group_List->siteuser_group_id = $oSiteuser_Group->id;
					$oSiteuser_Group_List->siteuser_id = $oSiteuser->id;
					$oSiteuser_Group_List->save();
				}
			}
			elseif (isset($aTmp[$oSiteuser_Group->id]))
			{
				$aTmp[$oSiteuser_Group->id]->delete();
			}
		}

		// Дополнительные свойства
		Core::moduleIsActive('property') && Property_Controller_Tab::factory($this->_Admin_Form_Controller)
			->setObject($this->_object)
			->linkedObject(Core_Entity::factory('Siteuser_Property_List', CURRENT_SITE))
			->applyObjectProperty();

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		if ($bNewSiteuser)
		{
			$oSiteuser_Company = Core_Entity::factory('Siteuser_Company');
			$oSiteuser_Person = Core_Entity::factory('Siteuser_Person');
		}
		else
		{
			$aSiteuser_Companies = $this->_object->Siteuser_Companies->findAll(FALSE);

			$oSiteuser_Company = count($aSiteuser_Companies) == 1
				? $aSiteuser_Companies[0]
				: NULL;

			$aSiteuser_People = $this->_object->Siteuser_People->findAll(FALSE);

			$oSiteuser_Person = count($aSiteuser_People) == 1
				? $aSiteuser_People[0]
				: NULL;
		}

		// Компания
		if ($oSiteuser_Company && strlen(Core_Array::getPost('company_name')))
		{
			$oSiteuser_Company->name = Core_Array::getPost('company_name', '', 'trim');
			$oSiteuser_Company->description = Core_Array::getPost('company_description', '', 'trim');
			$oSiteuser_Company->tin = Core_Array::getPost('company_tin', 0, 'int');
			$oSiteuser_Company->bank_account = Core_Array::getPost('company_bank_account', '', 'trim');
			$oSiteuser_Company->headcount = Core_Array::getPost('company_headcount', 0, 'int');
			$oSiteuser_Company->annual_turnover = Core_Array::getPost('company_annual_turnover', 0, 'int');
			$oSiteuser_Company->business_area = Core_Array::getPost('company_business_area', '', 'trim');

			$bNewSiteuser
				? $oSiteuser->add($oSiteuser_Company)
				: $oSiteuser_Company->save();

			Directory_Controller_Tab::instance('address')->prefix('company_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Company);
			Directory_Controller_Tab::instance('phone')->prefix('company_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Company);
			Directory_Controller_Tab::instance('email')->prefix('company_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Company);
			Directory_Controller_Tab::instance('social')->prefix('company_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Company);
			Directory_Controller_Tab::instance('website')->prefix('company_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Company);
			Directory_Controller_Tab::instance('messenger')->prefix('company_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Company);
		}

		// Представитель
		if ($oSiteuser_Person
			&& (strlen(Core_Array::getPost('person_surname')) || strlen(Core_Array::getPost('person_name')) || strlen(Core_Array::getPost('person_patronymic')))
		)
		{
			$oSiteuser_Person->name = Core_Array::getPost('person_name', '', 'trim');
			$oSiteuser_Person->surname = Core_Array::getPost('person_surname', '', 'trim');
			$oSiteuser_Person->patronymic = Core_Array::getPost('person_patronymic', '', 'trim');
			$oSiteuser_Person->post = Core_Array::getPost('person_post', '', 'trim');
			// $oSiteuser_Person->country = strval(Core_Array::getPost('person_country'));
			// $oSiteuser_Person->postcode = strval(Core_Array::getPost('person_postcode'));
			// $oSiteuser_Person->city = strval(Core_Array::getPost('person_city'));
			// $oSiteuser_Person->address = strval(Core_Array::getPost('person_address'));

			if ($bNewSiteuser)
			{
				$oSiteuser->add($oSiteuser_Person);
			}
			else
			{
				$oSiteuser_Person->save();
			}

			Directory_Controller_Tab::instance('address')->prefix('person_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Person);
			Directory_Controller_Tab::instance('phone')->prefix('person_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Person);
			Directory_Controller_Tab::instance('email')->prefix('person_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Person);
			Directory_Controller_Tab::instance('social')->prefix('person_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Person);
			Directory_Controller_Tab::instance('website')->prefix('person_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Person);
			Directory_Controller_Tab::instance('messenger')->prefix('person_')->applyObjectProperty($this->_Admin_Form_Controller, $oSiteuser_Person);
		}

		// Отправка письма с подтверждением регистрации
		if (!is_null(Core_Array::getPost('send_mail_confirm')))
		{
			$Siteuser_Controller_Show = new Siteuser_Controller_Show($oSiteuser);

			$oSite_Alias = $oSiteuser->Site->getCurrentAlias();

			$aConfig = Siteuser_Controller::getConfig($oSiteuser->site_id) + array(
				'confirmationMailSubject' => Core::_('Siteuser.confirm_subject', !is_null($oSite_Alias) ? $oSite_Alias->alias_name_without_mask : '')
			);

			$oXsl = Core_Entity::factory('Xsl')->getByName($aConfig['confirmationMailXsl']);

			if (!is_null($oXsl))
			{
				$Siteuser_Controller_Show
					->addEntity(
						Core::factory('Core_Xml_Entity')
							->name('password')
							->value($password_first)
					)
					->subject($aConfig['confirmationMailSubject'])
					->contentType($aConfig['confirmationMailContentType'])
					->sendConfirmationMail($oXsl);
			}
		}

		// if (Core::moduleIsActive('deal') && $bNewSiteuser)
		if ($bNewSiteuser && !is_null(Core_Array::getGet('targetSelect')))
		{
			$targetWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('targetWindowId', '', 'str'));
			$sOperationName = $this->_Admin_Form_Controller->getOperation();

			$targetSelect = Core_Array::getGet('targetSelect', '', 'trim');
			$targetType = Core_Array::getGet('targetType', 'siteuser', 'trim');

			if (in_array($sOperationName, array('saveModal', 'applyModal')) && strlen($targetWindowId) && strlen($targetSelect))
			{
				switch ($targetType)
				{
					case 'person':
						$load = 'loadSiteuserPersonSelect2';

						$oSiteuser_Person = $this->_object->Siteuser_People->getFirst(FALSE);
						$object_id = !is_null($oSiteuser_Person)
							? $oSiteuser_Person->id
							: 0;
					break;
					case 'company':
						$load = 'loadSiteuserCompanySelect2';

						$oSiteuser_Company = $this->_object->Siteuser_Companies->getFirst(FALSE);
						$object_id = !is_null($oSiteuser_Company)
							? $oSiteuser_Company->id
							: 0;
					break;
					default:
						$load = 'loadSiteuserSelect2';
						$object_id = $this->_object->id;
				}

				if ($object_id)
				{
					ob_start();
					Core_Html_Entity::factory('Script')
						->value('
							var objectSiteuserSelect = $("#' . $targetWindowId . ' select[name = ' . Core_Str::escapeJavascriptVariable($targetSelect) . ']");

							$.ajax({
								type: "GET",
								dataType: "json",
								url: hostcmsBackend + "/siteuser/index.php?' . $load . '=' . $object_id . '"
							}).then(function (data) {
								if (data)
								{
									// create the option and append to Select2
									var option = new Option(data.text, data.id, true, true);
									objectSiteuserSelect.append(option).trigger("change");

									// manually trigger the `select2:select` event
									objectSiteuserSelect.trigger({
										type: "select2:select",
										params: {
											data: data
										}
									});
								}
							});
						')
						->execute();

					$content = ob_get_clean();

					$sOperationName == 'saveModal' && $this->_Admin_Form_Controller->addMessage($content);
					$sOperationName == 'applyModal' && $this->_Admin_Form_Controller->addContent($content);
				}
			}
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		if (!is_null($operation) && $operation != '')
		{
			$id = Core_Array::getRequest('id');
			$login = Core_Array::getRequest('login');
			$site_id = Core_Array::getRequest('site_id');

			$oSameSiteuser = Core_Entity::factory('Site', $site_id)->Siteusers->getByLogin($login);

			// The same login
			if (!is_null($oSameSiteuser) && $oSameSiteuser->id != $id)
			{
				$this->addMessage(
					Core_Message::get(Core::_('Siteuser.login_error'), 'error')
				);
				return TRUE;
			}

			$email = Core_Array::getRequest('email');
			if ($email != '')
			{
				$oSameSiteuser = Core_Entity::factory('Site', $site_id)->Siteusers->getByEmail($email);

				// The same e-mail
				if (!is_null($oSameSiteuser) && $oSameSiteuser->id != $id)
				{
					$this->addMessage(
						Core_Message::get(Core::_('Siteuser.email_error'), 'error')
					);
					return TRUE;
				}
			}
		}

		return parent::execute($operation);
	}

	/**
	 * Fill user groups
	 * @param int $iSiteId site ID
	 * @return array
	 */
	public function fillSiteuserGroups($iSiteId)
	{
		$aReturn = array();
		$aChildren = Core_Entity::factory('Siteuser_Group')->getBySiteId($iSiteId);

		foreach ($aChildren as $oSiteuser_Group)
		{
			$aReturn[$oSiteuser_Group->id] = $oSiteuser_Group->name;
		}

		return $aReturn;
	}

	/*
	 * Show siteuser select2 with button
	 */
	static public function addSiteuserSelect2($oSiteuserSelect, $oSiteuser, $oAdmin_Form_Controller)
	{
		$windowId = $oAdmin_Form_Controller->getWindowId();
		$siteuser_id = $oSiteuser ? $oSiteuser->id : 0;

		$oUser = Core_Auth::getCurrentUser();

		$oSiteuserLink = Core_Html_Entity::factory('Span');
		$oSiteuserLink
			->class('input-group-addon siteuser-link' . (!$siteuser_id ? ' hidden' : ''));

		if ($oSiteuser && $oUser->checkModuleAccess(array('siteuser'), $oSiteuser->Site))
		{
			$oSiteuserLink
				->add(
					Core_Html_Entity::factory('A')
						->target('_blank')
						->href(
							Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/index.php?hostcms[action]=edit&hostcms[checked][0][' . $siteuser_id . ']=1')
						)
						->onclick(
							'mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + \'/siteuser/index.php\', action: \'edit\', operation: \'modal\', additionalParams: \'hostcms[checked][0][' . $siteuser_id . ']=1\', windowId: \'' . $windowId . '\'}); return false'
						)
						->class('fa fa-user btn-default show-user-info')
				);
		}

		$oSiteuserSelect
			->add($oSiteuserLink)
			->add(
				Core_Html_Entity::factory('Span')
					->class('input-group-addon')
					->onclick('mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + \'/siteuser/index.php\', action: \'edit\', operation: \'modal\', additionalParams: \'hostcms[checked][0][0]=1&targetSelect=' . Core_Str::escapeJavascriptVariable($oSiteuserSelect->name) . '&targetWindowId=' . $windowId . '\', windowId: \'' . $windowId . '\'}); return false')
					->add(
						Core_Html_Entity::factory('Span')
							->class('fa fa-plus')
					)
			);

		$placeholder = Core::_('Siteuser.select_siteuser');
		$language = Core_I18n::instance()->getLng();

		$oCore_Html_Entity_Script = Core_Html_Entity::factory('Script')
			->value("setTimeout(function(){
			var windowId = $('#{$windowId}'),
				select = windowId.find('select[name = {$oSiteuserSelect->name}]'),
				dropdownParent = windowId.closest('.modal').length ? windowId.closest('.modal') : windowId;

			select
				.selectSiteuser({language: '{$language}', placeholder: '{$placeholder}', dropdownParent: dropdownParent})
				.val('{$siteuser_id}')
				.trigger('change.select2')
				.parent()
				.addClass('col-xs-12');

			select.on('select2:unselect', function (){
				$(this)
					.nextAll('.input-group-addon.siteuser-link')
					.toggleClass('hidden');

				$('#{$windowId} .siteuser-representative-list').empty();
			});

			var siteuserLink = $('#{$windowId} .siteuser-link');

			if (siteuserLink.length)
			{
				select.on('select2:select', function (e) {
					var data = e.params.data, siteuser_id, oCompany, oPerson;

					switch(data.type)
					{
						case 'company':
							oCompany = data;
							siteuser_id = data.siteuser_id;
						break;
						case 'person':
							oPerson = data;
							siteuser_id = data.siteuser_id;
						break;
						case 'siteuser':
							if (data.companies.length)
							{
								oCompany = data.companies[0];
							}
							if (data.people.length)
							{
								oPerson = data.people[0];
							}
							siteuser_id = data.id;
						break;
					}

					$(this).find(':selected').val(siteuser_id);

					siteuserLink.removeClass('hidden');

					siteuserLink.find('a.show-user-info')
						.attr('href', hostcmsBackend + '/siteuser/index.php?hostcms[action]=edit&hostcms[checked][0][' + siteuser_id + ']=1')
						.attr('onclick', 'mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + \'/siteuser/index.php\',action: \'edit\', operation: \'modal\',additionalParams: \'hostcms[checked][0][' + siteuser_id + ']=1\',view: \'list\', windowId: \'id_content\'}); return false');

					// Замена аватарок представителей
					$.ajax({
						url: hostcmsBackend + '/siteuser/index.php',
						data: { 'loadSelect2Avatars': 1, 'siteuser_id': siteuser_id },
						dataType: 'json',
						type: 'POST',
						success: function(answer){
							$('#{$windowId} .siteuser-representative-list').empty();

							if (answer.result == 'success')
							{
								$('#{$windowId} .siteuser-representative-list').append(answer.html);
							}
						}
					});

					// Подстановка значений в форму
					if ($('#{$windowId} select[name = shop_country_id]').length)
					{
						var shop_country_id = $('#{$windowId} select[name = shop_country_id]'),
							shop_country_location_id = $('#{$windowId} select[name = shop_country_location_id]'),
							shop_country_location_city_id = $('#{$windowId} select[name = shop_country_location_city_id]'),
							phone = $('#{$windowId} input[name = phone]'),
							email = $('#{$windowId} input[name = email]'),
							postcode = $('#{$windowId} input[name = postcode]'),
							address = $('#{$windowId} input[name = address]'),
							house = $('#{$windowId} input[name = house]'),
							flat = $('#{$windowId} input[name = flat]'),
							surname = $('#{$windowId} input[name = surname]'),
							name = $('#{$windowId} input[name = name]'),
							patronymic = $('#{$windowId} input[name = patronymic]'),
							company = $('#{$windowId} input[name = company]'),
							tin = $('#{$windowId} input[name = tin]');

						if (oCompany)
						{
							if (oCompany.addresses.length)
							{
								oCompany.postcode = oCompany.addresses[0].postcode;
								oCompany.country = oCompany.addresses[0].country;
								oCompany.location = oCompany.addresses[0].location;
								oCompany.city = oCompany.addresses[0].city;
								oCompany.address = oCompany.addresses[0].address;
							}
						}

						var mainData = oCompany ? oCompany : oPerson;

						if (typeof mainData != 'undefined') {
							if (shop_country_id.val() == 0
								&& shop_country_location_id.val() == 0
								&& shop_country_location_city_id.val() == 0
								&& postcode.val() == ''
								&& address.val() == ''
								&& house.val() == ''
								&& flat.val() == ''
							)
							{
								shop_country_location_id.data('setOptionId', mainData.location);
								shop_country_location_city_id.data('setOptionId', mainData.city);

								shop_country_id.val(mainData.country).change();

								postcode.val(mainData.postcode);
								address.val(mainData.address);
							}

							phone.val() == '' && phone.val(mainData.phone);
							email.val() == '' && email.val(mainData.email);
						}

						if (oCompany && company.val() == '')
						{
							company.val(oCompany.name);
							tin.val(oCompany.tin);
						}

						if (oPerson
							&& surname.val() == '' && name.val() == '' && patronymic.val() == '')
						{
							surname.val(oPerson.surname);
							name.val(oPerson.name);
							patronymic.val(oPerson.patronymic);
						}
					}
				});
			}
			}, 100);");

		$oSiteuserSelect->add($oCore_Html_Entity_Script);
	}

	/*
	 * Show siteuser select2 with button
	 */
	static public function addRepresentativeSelect2($oSelect, $oEntity, $oAdmin_Form_Controller)
	{
		$windowId = $oAdmin_Form_Controller->getWindowId();

		$entity_id = !is_null($oEntity)
			? $oEntity->id
			: 0;

		$oUser = Core_Auth::getCurrentUser();

		// Тип выбора клиента: siteuser, person, company
		$targetType = !is_null($oSelect->data('targetType'))
			? $oSelect->data('targetType')
			: 'undefined';

		$ico = $targetType == 'company'
			? 'fa-building'
			: 'fa-user';

		$oSiteuserLink = Core_Html_Entity::factory('Span');
		$oSiteuserLink
			->class('input-group-addon siteuser-link' . (!$entity_id ? ' hidden' : ''))
			->add(
				$oA = Core_Html_Entity::factory('A')
					->target('_blank')
					->class('fa ' . $ico . ' btn-default show-user-info')
			);

		if (!is_null($oEntity) && $oEntity->siteuser_id && $oUser->checkModuleAccess(array('siteuser'), $oEntity->Siteuser->Site))
		{
			$oA
				->href(
					Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php') . '?hostcms[action]=edit&hostcms[checked][0][' . $entity_id . ']=1&show=' . $targetType
				)
				->onclick(
					'mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + \'/siteuser/representative/index.php\', action: \'edit\', operation: \'modal\', additionalParams: \'hostcms[checked][0][' . $entity_id . ']=1&show=' . $targetType . '\', windowId: \'' . $windowId . '\'}); return false'
				);
		}

		$oSelect
			->add($oSiteuserLink)
			->add(
				Core_Html_Entity::factory('Span')
					->class('input-group-addon')
					->onclick('mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + \'/siteuser/representative/index.php\', action: \'edit\', operation: \'modal\', additionalParams: \'hostcms[checked][0][0]=1&siteuser_id=0&targetSelect=' . Core_Str::escapeJavascriptVariable($oSelect->name) . '&targetType=' . Core_Str::escapeJavascriptVariable($targetType) . '&show=' . Core_Str::escapeJavascriptVariable($targetType) . '&targetWindowId=' . $windowId . '\', windowId: \'' . $windowId . '\'}); return false')
					->add(
						Core_Html_Entity::factory('Span')
							->class('fa fa-plus')
					)
			);

		$placeholder = Core::_('Siteuser.select_siteuser');
		$language = Core_I18n::instance()->getLng();

		$siteuser_id = !is_null($oEntity)
			? $oEntity->siteuser_id
			: 0;

		$oCore_Html_Entity_Script = Core_Html_Entity::factory('Script')
			->value("$('#{$windowId} select[name = {$oSelect->name}]')
				.selectSiteuser({language: '{$language}', placeholder: '{$placeholder}', dropdownParent: $('#{$windowId}')})
				.val('{$siteuser_id}')
				.trigger('change.select2')
				.parent()
				.addClass('col-xs-12');

			$('#{$windowId} select[name = {$oSelect->name}]').on('select2:unselect', function (){
				$(this)
					.nextAll('.input-group-addon.siteuser-link')
					.toggleClass('hidden');

				$('#{$windowId} .siteuser-representative-list').empty();
			});

			var siteuserLink = $('#{$windowId} select[name = {$oSelect->name}]').parents('.input-group').find('.siteuser-link');

			if (siteuserLink.length)
			{
				$('#{$windowId} select[name = {$oSelect->name}]').on('select2:select', function (e) {
					var data = e.params.data, siteuser_id, id, type;

					var siteuserLink = $('#{$windowId} select[name = {$oSelect->name}]').parents('.input-group').find('.siteuser-link');

					type = data.id.split('_', 2)[0];
					id = data.id.split('_', 2)[1];

					siteuserLink.removeClass('hidden');

					siteuserLink.find('a.show-user-info')
						.attr('href', hostcmsBackend + '/siteuser/representative/index.php?hostcms[action]=edit&hostcms[checked][0][' + id + ']=1&show=' + type)
						.attr('onclick', 'mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + \'/siteuser/representative/index.php\',action: \'edit\', operation: \'modal\',additionalParams: \'hostcms[checked][0][' + id + ']=1&show=' + type + '\',view: \'list\', windowId: \'id_content\'}); return false');
				});
			}");

		$oSelect->add($oCore_Html_Entity_Script);
	}

	static public function addSiteuserRepresentativeAvatars($oSiteuser)
	{
		$aObjects = array();

		$aSiteuser_Companies = $oSiteuser->Siteuser_Companies->findAll(FALSE);
		foreach ($aSiteuser_Companies as $oSiteuser_Company)
		{
			$aObjects[] = array(
				'id' => $oSiteuser_Company->id,
				'name' => htmlspecialchars($oSiteuser_Company->name),
				'src' => $oSiteuser_Company->getAvatar(),
				'type' => 'company'
			);
		}

		$aSiteuser_People = $oSiteuser->Siteuser_People->findAll(FALSE);
		foreach ($aSiteuser_People as $oSiteuser_Person)
		{
			$fullName = $oSiteuser_Person->getFullName();

			$aObjects[] = array(
				'id' => $oSiteuser_Person->id,
				'name' => htmlspecialchars($fullName),
				'src' => $oSiteuser_Person->getAvatar(),
				'type' => 'person'
			);
		}

		$icons = '<div class="siteuser-representative-wrapper">';

		$oAdmin_Form = Core_Entity::factory('Admin_Form', 230); // Форма "Компании и представители"
		$oAdminUser = Core_Auth::getCurrentUser();

		foreach ($aObjects as $aObject)
		{
			/*$dataset = $aObject['type'] == 'company'
				? 0
				: 1;*/

			$dataset = 0;

			$show = $aObject['type'] == 'company'
				? 'show=company'
				: 'show=person';

			$image = $oAdmin_Form->Admin_Form_Actions->checkAllowedActionForUser($oAdminUser, 'view')
				? '<a href="' . Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php') . '?hostcms[action]=view&hostcms[checked][' . $dataset . '][' . $aObject['id'] . ']=1&' . $show . '" onclick="$.modalLoad({path: hostcmsBackend + \'/siteuser/representative/index.php\', action: \'view\', operation: \'modal\', additionalParams: \'hostcms[checked][' . $dataset . '][' . $aObject['id'] . ']=1&' . $show . '\', windowId: \'id_content\'}); return false"><img width="30" height="30" class="img-circle" title="' . $aObject['name'] . '" src="' . $aObject['src'] . '"/></a>'
				: '<img width="30" height="30" title="' . $aObject['name'] . '" class="img-circle" src="' . $aObject['src'] . '"/>';

			$icons .= '<div>' . $image . '</div>';
		}

		$icons .= '</div>';

		return $icons;
	}
}