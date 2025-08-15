<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Relationship_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Relationship_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array(),
		'siteuser_recipient' => array('foreign_key' => 'recipient_siteuser_id', 'model' => 'siteuser'),
		'siteuser_relationship_type' => array(),
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'read' => 0,
	);

	/**
	 * Show related siteusers in XML
	 * @var boolean
	 */
	protected $_showXmlSiteusers = FALSE;

	/**
	 * Show related siteusers in XML
	 * @param boolean $showXmlSiteusers
	 * @return self
	 */
	public function showXmlSiteusers($showXmlSiteusers = TRUE)
	{
		$this->_showXmlSiteusers = $showXmlSiteusers;
		return $this;
	}

	/**
	 * Show related recipient siteusers in XML
	 * @var boolean
	 */
	protected $_showXmlRecipientSiteusers = FALSE;

	/**
	 * Show related recipient siteusers in XML
	 * @param boolean $showXmlRecipientSiteusers
	 * @return self
	 */
	public function showXmlRecipientSiteusers($showXmlRecipientSiteusers = TRUE)
	{
		$this->_showXmlRecipientSiteusers = $showXmlRecipientSiteusers;
		return $this;
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event siteuser_relationship.onBeforeRedeclaredGetXml
	 */
	public function getXml()
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetXml', $this);

		$this->_prepareData();

		return parent::getXml();
	}

	/**
	 * Get stdObject for entity and children entities
	 * @return stdObject
	 * @hostcms-event siteuser_relationship.onBeforeRedeclaredGetStdObject
	 */
	public function getStdObject($attributePrefix = '_')
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetStdObject', $this);

		$this->_prepareData();

		return parent::getStdObject($attributePrefix);
	}

	/**
	 * Prepare entity and children entities
	 * @return self
	 */
	protected function _prepareData()
	{
		$this->clearXmlTags();

		$this->_showXmlSiteusers && $this->addEntity(
			$this->Siteuser->showXmlProperties(TRUE)
		);

		$this->_showXmlRecipientSiteusers && $this->addEntity(
			$this->Siteuser_Recipient->showXmlProperties(TRUE)
		);

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_relationship.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_relationship.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Siteuser->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}