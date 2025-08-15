<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Controller_Morph
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Controller_Morph extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'title',
		'Shop',
		'buttonName',
		'skipColumns'
	);

	/**
	 * Constructor.
	 * @param Admin_Form_Action_Model $oAdmin_Form_Action action
	 */
	public function __construct(Admin_Form_Action_Model $oAdmin_Form_Action)
	{
		parent::__construct($oAdmin_Form_Action);

		$this->buttonName(Core::_('Admin_Form.apply'));
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		$mode = Core_Array::getPost('mode', 'list');

		$oLead = $this->_object;

		$previousObject = clone $oLead;

		// if (is_null($operation))
		if ($operation == 'finish')
		{
			// Original windowId
			$windowId = $this->_Admin_Form_Controller->getWindowId();

			$newWindowId = 'Morph_Lead_' . time();

			$oCore_Html_Entity_Form = Core_Html_Entity::factory('Form');

			$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
				->id($newWindowId)
				->add($oCore_Html_Entity_Form);

			$oCore_Html_Entity_Form
				->action($this->_Admin_Form_Controller->getPath())
				->class('row')
				->method('post');

			$window_Admin_Form_Controller = clone $this->_Admin_Form_Controller;

			// Select на всплывающем окне должен быть найден через ID нового окна, а не id_content
			$window_Admin_Form_Controller->window($newWindowId);

			$aTypes = array();

			if (Core::moduleIsActive('siteuser'))
			{
				$aTypes[1] = Core::_('Lead.new_client');
				$aTypes[2] = Core::_('Lead.exist_client');
			}

			if (Core::moduleIsActive('shop'))
			{
				$aTypes[3] = Core::_('Lead.order');
			}

			if (Core::moduleIsActive('deal'))
			{
				$aTypes[4] = Core::_('Lead.deal');
			}

			$oAdmin_Form_Entity_Select_Type = Admin_Form_Entity::factory('Select')
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
				->name('type')
				->options($aTypes)
				->onchange('$.morphLeadChangeType(this)')
				->controller($window_Admin_Form_Controller);

			if (Core::moduleIsActive('siteuser'))
			{
				$oSiteuser = $oLead->Siteuser;

				$options = !is_null($oSiteuser->id)
					? array($oSiteuser->id => $oSiteuser->login . ' [' . $oSiteuser->id . ']')
					: array(0);

				$oSiteuserSelect = Admin_Form_Entity::factory('Select')
					->options($options)
					->name('siteuser_id_' . $oLead->id)
					->class('siteuser-tag hidden')
					->style('width: 100%')
					->divAttr(array('class' => 'col-xs-12'))
					->controller($window_Admin_Form_Controller);

				// Show button
				Siteuser_Controller_Edit::addSiteuserSelect2($oSiteuserSelect, $oSiteuser, $window_Admin_Form_Controller);
			}

			if (Core::moduleIsActive('deal'))
			{
				$aOptions = array();

				$aDeal_Templates = Core_Entity::factory('Deal_Template')->findAll();
				foreach($aDeal_Templates as $oDeal_Template)
				{
					$aOptions[$oDeal_Template->id] = $oDeal_Template->name;
				}

				$oDealTemplateSelect = Admin_Form_Entity::factory('Select')
					->id('deal_template_id')
					->options($aOptions)
					->name('deal_template_id' . $oLead->id)
					->style('width: 100%')
					->divAttr(array('class' => 'col-xs-12'));
			}

			$oAdmin_Form_Entity_Button = Admin_Form_Entity::factory('Button')
				->name('apply')
				->type('submit')
				->class('applyButton btn btn-success')
				->value($this->buttonName)
				->onclick(
					'bootbox.hideAll(); '
					. $this->_Admin_Form_Controller->getAdminSendForm(array('operation' => 'apply'))
				)
				->controller($this->_Admin_Form_Controller);

			$lead_status_id = Core_Array::getPost('lead_status_id', 0);

			$oCore_Html_Entity_Form
				->add($oAdmin_Form_Entity_Select_Type);

			if (Core::moduleIsActive('siteuser'))
			{
				$oCore_Html_Entity_Form
					->add(
						Admin_Form_Entity::factory('Div')
							->class('col-xs-12 col-sm-6 siteuser-select2 lead-exist-client hidden')
							->add($oSiteuserSelect)
					);
			}

			if (Core::moduleIsActive('deal'))
			{
				$oCore_Html_Entity_Form
					->add(
						Admin_Form_Entity::factory('Div')
							->class('col-xs-12 col-sm-6 lead-deal-template hidden')
							->add($oDealTemplateSelect)
					);
			}

			$oCore_Html_Entity_Form
				->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'hidden'))
						->type('hidden')
						->name('lead_status_id')
						->value($lead_status_id)
						->controller($window_Admin_Form_Controller)
				)
				->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'hidden'))
						->type('hidden')
						->name('mode')
						->value($mode)
						->controller($window_Admin_Form_Controller)
				)
				->add(
					Admin_Form_Entity::factory('Input')
						->divAttr(array('class' => 'hidden'))
						->type('hidden')
						->name('last_step')
						->value(1)
						->controller($window_Admin_Form_Controller)
				)
				->add(
					Admin_Form_Entity::factory('Div')
						->class('form-group col-xs-12')
						->add($oAdmin_Form_Entity_Button)
				);

			$oCore_Html_Entity_Div->execute();

			ob_start();

			Core_Html_Entity::factory('Script')
				->value("$(function() {
					$('#{$newWindowId}').HostCMSWindow({ autoOpen: true, destroyOnClose: false, title: '" . Core_Str::escapeJavascriptVariable($this->title) . "', AppendTo: '#{$windowId}', width: 550, height: 100, addContentPadding: true, modal: false, Maximize: false, Minimize: false }); });")
				->execute();

			$this->addMessage(ob_get_clean());

			return TRUE;
		}
		else
		{
			$bSave = FALSE;

			if (Core_Array::getPost('last_step'))
			{
				$type = intval(Core_Array::getPost('type'));
				$siteuser_id = $deal_template_id = 0;

				if ($type == 2)
				{
					$siteuser_id = intval(Core_Array::getPost('siteuser_id_' . $oLead->id));
				}
				elseif ($type == 4)
				{
					$deal_template_id = intval(Core_Array::getPost('deal_template_id' . $oLead->id));
				}

				$sResult = $oLead->morph($type, $siteuser_id, $deal_template_id);

				if ($sResult == 'success')
				{
					$bSave = TRUE;
				}
			}
			else
			{
				$bSave = TRUE;
			}

			if ($bSave)
			{
				$lead_status_id = Core_Array::getPost('lead_status_id', 0);

				$oLead->lead_status_id = $lead_status_id;
				$oLead->save();

				if ($previousObject->lead_status_id != $oLead->lead_status_id)
				{
					$oLead->notifyBotsChangeStatus();
				}

				$sNewLeadStepDatetime = Core_Date::timestamp2sql(time());

				$oCurrentUser = Core_Auth::getCurrentUser();

				Core_Entity::factory('Lead_Step')
					->lead_id($oLead->id)
					->lead_status_id($oLead->lead_status_id)
					->user_id($oCurrentUser->id)
					->datetime($sNewLeadStepDatetime)
					->save();
			}
		}

		//return $this;
		return $mode == 'edit'
			? TRUE
			: NULL;
	}
}