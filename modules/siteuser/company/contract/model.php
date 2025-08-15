<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Company_Contract_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Company_Contract_Model extends Core_Entity
{
	protected $_tableName = 'siteuser_company_contracts';

	public $siteuserCompanyTin = NULL;
	public $siteuserCompanyName = NULL;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'shop_warehouse_purchaseorder' => array(),
		'shop_warehouse_invoice' => array(),
		'shop_warehouse_supply' => array(),
		'shop_warehouse_purchasereturn' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'company' => array(),
		'siteuser_company' => array(),
		'shop_currency' => array(),
		'user' => array(),
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

			//$this->_preloadValues['date'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$color = Core_Str::createColor($this->type);

		echo ' <span class="badge badge-round badge-max-width" style="border-color: ' . $color . '; color: ' . Core_Str::hex2darker($color, 0.2) . '; background-color: ' . Core_Str::hex2lighter($color, 0.88) . '">' . Core::_('Siteuser_Company_Contract.type_' . $this->type) . '</span>';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function company_idBackend()
	{
		return $this->company_id
			? '<div class="profile-container tickets-container counterparty-block"><ul class="tickets-list">' . $this->Company->getProfileBlock() . '</ul></div>'
			: '';
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function siteuserCompanyNameBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		return $this->siteuser_company_id
			? '<div class="profile-container tickets-container counterparty-block"><ul class="tickets-list">' . $this->Siteuser_Company->getProfileBlock() . '</ul></div>'
			: '';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function amountBackend()
	{
		return '<div class="small">' . htmlspecialchars($this->Shop_Currency->formatWithCurrency($this->amount)) . '</div>';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function paidBackend()
	{
		// Кассы и расчетные счета
		switch ($this->type)
		{
			case 0:
			case 2:
			default:
				$paid = 0;
			break;
			case 1: // С покупателем
				$paid = Chartaccount_Entry_Controller::getEntriesAmount(array('dchartaccount' => '50.1', 'subcount' => array(7 => $this->id)))
					+ Chartaccount_Entry_Controller::getEntriesAmount(array('dchartaccount' => '51', 'subcount' => array(7 => $this->id)))
					- Chartaccount_Entry_Controller::getEntriesAmount(array('cchartaccount' => '50.1', 'subcount' => array(7 => $this->id)))
					- Chartaccount_Entry_Controller::getEntriesAmount(array('cchartaccount' => '51', 'subcount' => array(7 => $this->id)));
			break;
			case 3: // С поставщиком
				$paid = Chartaccount_Entry_Controller::getEntriesAmount(array('cchartaccount' => '50.1', 'subcount' => array(7 => $this->id)))
					+ Chartaccount_Entry_Controller::getEntriesAmount(array('cchartaccount' => '51', 'subcount' => array(7 => $this->id)))
					- Chartaccount_Entry_Controller::getEntriesAmount(array('dchartaccount' => '50.1', 'subcount' => array(7 => $this->id)))
					- Chartaccount_Entry_Controller::getEntriesAmount(array('dchartaccount' => '51', 'subcount' => array(7 => $this->id)));
			break;
		}

		$class = $paid < 0 ? 'darkorange' : '';

		$dot = $paid >= $this->amount
			? '<span class="online online-small"></span> '
			: '';

		return $paid
			? '<div class="small">' . $dot . '<span class="' . $class . '">' . $this->Shop_Currency->formatWithCurrency($paid) . '</span></div>'
			: '';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function shippedBackend()
	{
		$shipped = Chartaccount_Entry_Controller::getEntriesAmount(array('cchartaccount' => '60.1', 'subcount' => array(7 => $this->id)))
			- Chartaccount_Entry_Controller::getEntriesAmount(array('dchartaccount' => '76.2', 'subcount' => array(7 => $this->id)));

		$class = $shipped < 0 ? 'darkorange' : '';

		$dot = $shipped >= $this->amount
			? '<span class="online online-small"></span> '
			: '';

		return $shipped
			? '<div class="small">' . $dot . '<span class="' . $class . '">' . $this->Shop_Currency->formatWithCurrency($shipped) . '</span></div>'
			: '';
	}

	/**
	 * Get contracts by company and counterparty
	 * @param string $login login
	 * @param string $password password
	 * @return User_Model|NULL
	 */
	public function getByCompanyAndSiteuserCompany($iCompanyId, $iSiteuserCompanyId)
	{
		$this->queryBuilder()
			->clear()
			->where('company_id', '=', $iCompanyId)
			->where('siteuser_company_id', '=', $iSiteuserCompanyId);

		return $this->findAll(FALSE);
	}

	/**
	 * Get active user by login and password
	 * @param string $login login
	 * @param string $password password
	 * @return User_Model|NULL
	 */
	/*public function getByLoginAndPassword($login, $password)
	{
		$this->queryBuilder()
			->clear()
			->where('users.login', '=', $login)
			->where('users.password', '=', Core_Hash::instance()->hash($password))
			->where('users.active', '=', 1)
			->where('users.dismissed', '=', 0)
			->limit(1);

		$aUsers = $this->findAll(FALSE);

		return isset($aUsers[0]) ? $aUsers[0] : NULL;
	}*/
}