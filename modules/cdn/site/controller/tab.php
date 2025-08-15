<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cdn_Site_Controller_Tab
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cdn_Site_Controller_Tab extends Core_Servant_Properties
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'cdn_id',
		'site_id',
	);

	/**
	 * Form controller
	 * @var Admin_Form_Controller
	 */
	protected $_Admin_Form_Controller = NULL;

	/**
	 * Constructor.
	 * @param Admin_Form_Controller $Admin_Form_Controller controller
	 */
	public function __construct(Admin_Form_Controller $Admin_Form_Controller)
	{
		parent::__construct();

		$this->_Admin_Form_Controller = $Admin_Form_Controller;
	}

	/**
	 * Executes the business logic.
	 */
	public function execute()
	{
		$oCdn_Site = $this->_getCdnOptions();

		$oOptionDiv = Admin_Form_Entity::factory('Div');

		if (is_null($oCdn_Site))
		{
			$oCdn_Site = Core_Entity::factory('Cdn_Site');
		}

		$oOptionDiv
			->add(
				Admin_Form_Entity::factory('Div')->class('row')
					->add(Admin_Form_Entity::factory('Checkbox')
						->value(1)
						->checked($oCdn_Site->active)
						->name("cdn_site_active")
						->divAttr(array('class' => 'form-group col-xs-12'))
						->caption(Core::_("Cdn_Site.active"))
					)
					->add(
						Admin_Form_Entity::factory('Checkbox')
							->value(1)
							->checked($oCdn_Site->default)
							->name("cdn_site_default")
							->divAttr(array('class' => 'form-group col-xs-12'))
							->caption(Core::_("Cdn_Site.default"))
					)
			)
			->add(
				Admin_Form_Entity::factory('Div')->class('row')
					->add(
						Admin_Form_Entity::factory('Checkbox')
							->value(1)
							->checked($oCdn_Site->css)
							->name("cdn_site_css")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 margin-top-21'))
							->caption(Core::_("Cdn_Site.css"))
					)
					->add(
						Admin_Form_Entity::factory('Input')
							->value($oCdn_Site->css_domain)
							->name("cdn_site_css_domain")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
							->caption(Core::_("Cdn_Site.css_domain"))
					)
			)
			->add(
				Admin_Form_Entity::factory('Div')->class('row')
					->add(
						Admin_Form_Entity::factory('Checkbox')
							->value(1)
							->checked($oCdn_Site->js)
							->name("cdn_site_js")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 margin-top-21'))
							->caption(Core::_("Cdn_Site.js"))
					)
					->add(
						Admin_Form_Entity::factory('Input')
							->value($oCdn_Site->js_domain)
							->name("cdn_site_js_domain")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
							->caption(Core::_("Cdn_Site.js_domain"))
					)
			)
			->add(
				Admin_Form_Entity::factory('Div')->class('row')
					->add(
						Admin_Form_Entity::factory('Checkbox')
							->value(1)
							->checked($oCdn_Site->informationsystem)
							->name("cdn_site_is")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 margin-top-21'))
							->caption(Core::_("Cdn_Site.informationsystem"))
					)
					->add(
						Admin_Form_Entity::factory('Input')
							->value($oCdn_Site->informationsystem_domain)
							->name("cdn_site_informationsystem_domain")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
							->caption(Core::_("Cdn_Site.informationsystem_domain"))
					)
			)
			->add(
				Admin_Form_Entity::factory('Div')->class('row')
					->add(
						Admin_Form_Entity::factory('Checkbox')
							->value(1)
							->checked($oCdn_Site->shop)
							->name("cdn_site_shop")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 margin-top-21'))
							->caption(Core::_("Cdn_Site.shop"))
					)
					->add(
						Admin_Form_Entity::factory('Input')
							->value($oCdn_Site->shop_domain)
							->name("cdn_site_shop_domain")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
							->caption(Core::_("Cdn_Site.shop_domain"))
					)
			)
			->add(
				Admin_Form_Entity::factory('Div')->class('row')
					->add(
						Admin_Form_Entity::factory('Checkbox')
							->value(1)
							->checked($oCdn_Site->structure)
							->name("cdn_site_structure")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 margin-top-21'))
							->caption(Core::_("Cdn_Site.structure"))
					)
					->add(
						Admin_Form_Entity::factory('Input')
							->value($oCdn_Site->structure_domain)
							->name("cdn_site_structure_domain")
							->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
							->caption(Core::_("Cdn_Site.structure_domain"))
					)
			);

		return $oOptionDiv;
	}

	/**
	 * Get cdn options
	 * @return array
	 */
	protected function _getCdnOptions()
	{
		if ($this->site_id && $this->cdn_id !== 0)
		{
			$oCdn_Sites = Core_Entity::factory('Cdn_Site');
			$oCdn_Sites->queryBuilder()
				->where('cdn_sites.site_id', '=', $this->site_id)
				->where('cdn_sites.cdn_id', '=', $this->cdn_id)
				->limit(1);

			$aCdn_Sites = $oCdn_Sites->findAll(FALSE);

			$oCdn_Site = isset($aCdn_Sites[0]) ? $aCdn_Sites[0] : NULL;

			return $oCdn_Site;
		}
	}

	/**
	 * Apply object property
	 */
	public function applyObjectProperty()
	{
		$oCdn_Site = $this->_getCdnOptions();

		if (is_null($oCdn_Site))
		{
			$oCdn_Site = Core_Entity::factory('Cdn_Site');
			$oCdn_Site->cdn_id = $this->cdn_id;
			$oCdn_Site->site_id = $this->site_id;
		}

		$oCdn_Site
			->active(intval(Core_Array::getPost("cdn_site_active", 0)))
			->default(intval(Core_Array::getPost("cdn_site_default", 0)))
			->css(intval(Core_Array::getPost("cdn_site_css", 0)))
			->js(intval(Core_Array::getPost("cdn_site_js", 0)))
			->informationsystem(intval(Core_Array::getPost("cdn_site_is", 0)))
			->shop(intval(Core_Array::getPost("cdn_site_shop", 0)))
			->structure(intval(Core_Array::getPost("cdn_site_structure", 0)))
			->css_domain(trim(Core_Array::getPost("cdn_site_css_domain")))
			->js_domain(trim(Core_Array::getPost("cdn_site_js_domain")))
			->informationsystem_domain(trim(Core_Array::getPost("cdn_site_informationsystem_domain")))
			->shop_domain(trim(Core_Array::getPost("cdn_site_shop_domain")))
			->structure_domain(trim(Core_Array::getPost("cdn_site_structure_domain")))
			->save();
	}
}