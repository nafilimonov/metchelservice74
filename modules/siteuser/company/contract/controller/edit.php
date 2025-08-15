<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Company_Contract_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
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

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$iSiteuserCompanyId = intval(Core_Array::getGet('siteuser_company_id'));

		if ($iSiteuserCompanyId)
		{
			$oSiteuser_Company = Core_Entity::factory('Siteuser_Company', $iSiteuserCompanyId);

			$oSiteuser = $oSiteuser_Company->Siteuser;
		}

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$this->title($this->_object->id
			? Core::_('Siteuser_Company_Contract.edit_form_title', $this->_object->number, FALSE)
			: Core::_('Siteuser_Company_Contract.add_form_title')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$oMainTab
			->add($oMainRow0 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow7 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow8 = Admin_Form_Entity::factory('Div')->class('row'))
			->add(
				Admin_Form_Entity::factory('Code')
					->html("<script>radiogroupOnChange('{$windowId}', " . intval($this->_object->type) . ", [0, 1, 2, 3])</script>")
			);
;

		$oAdditionalTab->delete($this->getField('company_id'));

		$aCompanies = $oSite->Companies->findAll();

		$aTmp = [];

		foreach($aCompanies as $oCompany)
		{
			$aTmp[$oCompany->id] = $oCompany->name;
		}

		$oSelect_Companies = Admin_Form_Entity::factory('Select')
			->options($aTmp)
			->name('company_id')
			->value($this->_object->company_id)
			->caption(Core::_('Siteuser_Company_Contract.company_id'))
			->divAttr(array('class'=>'form-group col-xs-12 col-md-6'));

		$oMainRow1->add($oSelect_Companies);

		$oAdditionalTab->delete($this->getField('siteuser_company_id'));

		$aTmp = [];

		if ($iSiteuserCompanyId)
		{
			$aSiteuser_Companies = $oSiteuser->Siteuser_Companies->findAll();

			foreach($aSiteuser_Companies as $oSiteuser_Company)
			{
				$aTmp[$oSiteuser_Company->id] = $oSiteuser_Company->name;
			}
		}
		else
		{
			$oSiteuser_Company = Core_Entity::factory('Siteuser_Company')->getById($this->_object->siteuser_company_id);
			$oSiteuser = !is_null($oSiteuser_Company)
				? $oSiteuser_Company->Siteuser
				: NULL;

			if ($oSiteuser)
			{
				$oOptgroupSiteuser = new stdClass();
				$oOptgroupSiteuser->attributes = array('label' => $oSiteuser->login, 'class' => 'siteuser');

				if ($oSiteuser_Company)
				{
					$tin = !empty($oSiteuser_Company->tin)
						? ' [' . $oSiteuser_Company->tin . ']'
						: '';

					$oOptgroupSiteuser->children['company_' . $oSiteuser_Company->id] = array(
						'value' => $oSiteuser_Company->name . $tin . '%%%' . $oSiteuser_Company->getAvatar(),
						'attr' => array('class' => 'siteuser-company')
					);
				}

				//$aMasSiteusers[$oSiteuser->id] = $oOptgroupSiteuser;
				$aTmp[$oSiteuser->id] = $oOptgroupSiteuser;
			}

			$oScriptSiteusers = Admin_Form_Entity::factory('Script')
				->value('
					$("#' . $windowId . ' #siteuser_company_id").select2({
						dropdownParent: $("#' . $windowId . '"),
						minimumInputLength: 1,
						placeholder: "",
						allowClear: true,
						// multiple: true,
						ajax: {
							url: hostcmsBackend + "/siteuser/index.php?loadSiteusers&types[]=company",
							dataType: "json",
							type: "GET",
							processResults: function (data) {
								var aResults = [];
								$.each(data, function (index, item) {
									aResults.push(item);
								});
								return {
									results: aResults
								};
							}
						},
						templateResult: $.templateResultItemSiteusers,
						escapeMarkup: function(m) { return m; },
						templateSelection: $.templateSelectionItemSiteusers,
						language: "' . Core_I18n::instance()->getLng() . '",
						width: "100%"
					})
					.val("company_' . $this->_object->siteuser_company_id . '")
					.trigger("change.select2");
				');
		}

		//var_dump($this->_object->siteuser_company_id);
		//var_dump($iSiteuserCompanyId);

		$oSelect_Siteuser_Companies = Admin_Form_Entity::factory('Select')
			->id('siteuser_company_id')
			->options($aTmp)
			->name('siteuser_company_id')
			->value($this->_object->siteuser_company_id ? $this->_object->siteuser_company_id : $iSiteuserCompanyId)
			->caption(Core::_('Siteuser_Company_Contract.siteuser_company_id'))
			->divAttr(array('class'=>'form-group col-xs-12 col-md-6'));

		$oMainRow2->add($oSelect_Siteuser_Companies);
		!$iSiteuserCompanyId && $oMainRow2->add($oScriptSiteusers);

		$oMainTab->delete($this->getField('type'));

		$oSelect_Contract_Type = Admin_Form_Entity::factory('Select')
			->options(array(
					// 0 => Core::_('Siteuser_Company_Contract.type_0'),
					1 => Core::_('Siteuser_Company_Contract.type_1'),
					// 2 => Core::_('Siteuser_Company_Contract.type_2'),
					3 => Core::_('Siteuser_Company_Contract.type_3')
				)
			)
			->name('type')
			->value($this->_object->type)
			->caption(Core::_('Siteuser_Company_Contract.type'))
			->divAttr(array('class'=>'form-group col-xs-12 col-md-6'))
			->onchange("radiogroupOnChange('{$windowId}', $(this).val(), [0, 1, 2, 3])");

		$oMainRow3->add($oSelect_Contract_Type);


		$oMainTab->delete($this->getField('comission_method'));

		$oSelect_Comission_Method = Admin_Form_Entity::factory('Select')
			->options(array(Core::_('Siteuser_Company_Contract.comission_method_0'), Core::_('Siteuser_Company_Contract.comission_method_1')))
			->name('comission_method')
			->value($this->_object->comission_method)
			->caption(Core::_('Siteuser_Company_Contract.comission_method'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-3 shown-2'));

		$oMainRow3->add($oSelect_Comission_Method);

		$this->getField('comission_percent')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-3 shown-2'));

		$oMainTab->move($this->getField('comission_percent'), $oMainRow3);

		$this->getField('number')
			->id('number')
			->class('form-control input-lg')
			->divAttr(array('class' => 'form-group col-xs-6 col-sm-3 no-padding-right'))
			->add(
				Core_Html_Entity::factory('Span')
					->class('input-group-addon dimension_patch')
					->value(Core::_('Siteuser_Company_Contract.number_date_preposition'))
			);

		$this->getField('date')
			->id('date')
			->class('form-control input-lg')
			->divAttr(array('class' => 'form-group col-xs-6 col-sm-3 no-padding-left'))
			->format(array('minlen' => array('value' => 1)));

		$oMainTab
			->move($this->getField('number'), $oMainRow0)
			->move($this->getField('date'), $oMainRow0);

		$oMainTab
			->move(
				$this->getField('name')
					->id('name')
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')),
				$oMainRow4);

		$oAdditionalTab->delete($this->getField('shop_currency_id'));

		$aShop_Currencies = Core_Entity::factory('Shop_Currency')->findAll();

		$aTmp = [];
		$iDefaultKey = 0;

		foreach($aShop_Currencies as $oShop_Currency)
		{
			$aTmp[$oShop_Currency->id] = $oShop_Currency->sign;

			$oShop_Currency->default && $iDefaultKey = $oShop_Currency->id;
		}

		$oSelect_Currency = Admin_Form_Entity::factory('Select')
			->options($aTmp)
			->name('shop_currency_id')
			->value($this->_object->id ? $this->_object->shop_currency_id : $iDefaultKey)
			->caption(Core::_('Siteuser_Company_Contract.shop_currency_id'))
			->divAttr(array('class' => ''));

			//->divAttr(array('class'=>'form-group col-xs-12 col-md-6'));

		// Amount
		$oDiv_Amount = Admin_Form_Entity::factory('Div')
			->class('form-group col-xs-12 col-sm-6 col-md-3 amount-currency');

		$oMainTab
			->move($this->getField('amount')->divAttr(array('class' => '')), $oDiv_Amount);

		$oDiv_Amount->add($oSelect_Currency);

		$oMainRow6->add($oDiv_Amount);

		$oMainTab
			->move($this->getField('payment_term')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3')), $oMainRow7);

		$oMainTab
			->move($this->getField('expiration')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3')), $oMainRow8);


		$oScript = Admin_Form_Entity::factory('Script')
			->value('
				$("#' . $windowId . ' #number, #' . $windowId . ' #date").on("blur", function(e){

					var $this = $(this), oContractName = $("#' . $windowId . ' #name"),
						oContractNumber, oContractDate, sNumberValueTemplate,
						sContractNumberValue, sContractDateValue;

					// Название договора отсутствует
					if (!oContractName.val().trim())
					{
						if ($this.attr("id") == "number")
						{
							oContractNumber = $this;
							oContractDate = $("#' . $windowId . ' #date");
						}
						else
						{
							oContractNumber = $("#' . $windowId . ' #number");
							oContractDate = $this;
						}

						// Указаны номер и дата договора
						if ((sContractNumberValue = oContractNumber.val().trim()) && (sContractDateValue = oContractDate.val().trim()))
						{
							sNumberValueTemplate = "' . Core::_('Siteuser_Company_Contract.number_value_template') . '";

							var mapObj = {
								"%contract_number%": sContractNumberValue,
								"%contract_date%": sContractDateValue
							};

							sNumberValue = sNumberValueTemplate.replace(/%contract_number%|%contract_date%/gi, function(matched){
								return mapObj[matched];
							});

							oContractName.val(sNumberValue);
						}
					}
				});
			');
		$oMainTab->add($oScript);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Shop_Warehouse_Incoming_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$sSiteuserCompany = Core_Array::getPost('siteuser_company_id', 0, 'strval');
		$aExplodeCompany = explode('_', $sSiteuserCompany);
		$siteuser_company_id = intval($aExplodeCompany[0] == 'company' && isset($aExplodeCompany[1]) ? $aExplodeCompany[1] : $aExplodeCompany[0]);
		$this->_formValues['siteuser_company_id'] = $siteuser_company_id;

		parent::_applyObjectProperty();
	}
}