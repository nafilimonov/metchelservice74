<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Representative_Controller_Merge.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Representative_Controller_Merge extends Admin_Form_Action_Controller
{
	/**
	 * Global properties support
	 * @var Core_Registry
	 */
	protected $_Core_Registry = NULL;

	/**
	 * Key name for saving in Core_Registry
	 * @var string
	 */
	protected $_keyNameCompany = 'siteuser-company-merge';

	/**
	 * Key name for saving in Core_Registry
	 * @var string
	 */
	protected $_keyNamePerson = 'siteuser-person-merge';

	/**
	 * Constructor.
	 * @param Admin_Form_Action_Model $oAdmin_Form_Action action
	 */
	public function __construct(Admin_Form_Action_Model $oAdmin_Form_Action)
	{
		parent::__construct($oAdmin_Form_Action);

		$this->_Core_Registry = Core_Registry::instance();

		// Skip prev value
		$this->_Core_Registry->set($this->_keyNameCompany, NULL);
		$this->_Core_Registry->set($this->_keyNamePerson, NULL);
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		switch (get_class($this->_object))
		{
			case 'Siteuser_Person_Model':
				$keyName = $this->_keyNamePerson;
			break;
			case 'Siteuser_Company_Model':
				$keyName = $this->_keyNameCompany;
			break;
			default:
				throw new Core_Exception('Siteuser_Representative_Controller_Merge: Wrong object');
		}

		$keyValue = $this->_Core_Registry->get($keyName);

		if (is_null($keyValue))
		{
			$this->_Core_Registry->set($keyName, $this->_object->getPrimaryKey());
			return NULL;
		}
		else
		{
			// Предыдущий объект
			$className = get_class($this->_object);
			$prevObject = new $className($keyValue);

			$prevObject->merge($this->_object);
		}

		return $this;
	}
}