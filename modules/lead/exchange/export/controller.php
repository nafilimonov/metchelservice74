<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Lead_Exchange_Export_Controller
{
	/**
	 * Site object
	 * @var Site_Model
	 */
	private $_oSite = NULL;

	/**
	 * CSV data
	 * @var array
	 */
	private $_aCurrentData;

	/**
	 * Data
	 * @var array
	 */
	protected $_aData = array();

	/**
	 * Additional fields of leads
	 * @var array
	 */
	private $_aLead_Fields = array();

	/**
	 * Lead fields count
	 * Требуется хранить количество полей отдельно, т.к. количество полей файла CSV для полей не равно количеству полей (из-за файлов)
	 * @var int
	 */
	// private $_iLead_Fields_Count;

	/**
	 * Кэш значений полей
	 * @var array
	 */
	protected $_cacheFieldValues = array();

	/**
	 * Get Item Titles
	 * @return array
	 * @hostcms-event Lead_Exchange_Export_Controller.onGetItemTitles
	 */
	public function getItemTitles()
	{
		// 18
		$return = array(
			Core::_('Lead_Exchange.id'),
			Core::_('Lead_Exchange.surname'),
			Core::_('Lead_Exchange.name'),
			Core::_('Lead_Exchange.patronymic'),
			Core::_('Lead_Exchange.company'),
			Core::_('Lead_Exchange.post'),
			Core::_('Lead_Exchange.amount'),
			Core::_('Lead_Exchange.birthday'),
			Core::_('Lead_Exchange.need'),
			Core::_('Lead_Exchange.maturity'),
			Core::_('Lead_Exchange.source'),
			Core::_('Lead_Exchange.shop'),
			Core::_('Lead_Exchange.status'),
			Core::_('Lead_Exchange.comment'),
			Core::_('Lead_Exchange.last_contacted'),
			Core::_('Lead_Exchange.address'),
			Core::_('Lead_Exchange.phone'),
			Core::_('Lead_Exchange.email'),
			Core::_('Lead_Exchange.website'),
		);

		Core_Event::notify(get_class($this) . '.onGetItemTitles', $this, array($return));

		return !is_null(Core_Event::getLastReturn())
			? Core_Event::getLastReturn()
			: $return;
	}

	/**
	 * Constructor.
	 */
	public function __construct(Site_Model $oSite)
	{
		$this->_oSite = $oSite;

		$iCurrentDataPosition = 0;

		// $this->_iLead_Fields_Count = 0;

		$aItemTitles = array_map(array($this, 'prepareCell'), $this->getItemTitles());

		$this->_aCurrentData[$iCurrentDataPosition] = $aItemTitles;

		// Fields
		if (Core::moduleIsActive('field'))
		{
			$this->_aLead_Fields = Field_Controller::getFields('lead', $oSite->id);
			foreach ($this->_aLead_Fields as $oField)
			{
				$this->_aCurrentData[$iCurrentDataPosition][] = $this->prepareCell($oField->name);
				// $this->_iLead_Fields_Count++;

				if ($oField->type == 2)
				{
					$this->_aCurrentData[$iCurrentDataPosition][] = $this->prepareCell(Core::_('Lead_Exchange.import_file_description', $oField->name));
					// $this->_iLead_Fields_Count++;

					$this->_aCurrentData[$iCurrentDataPosition][] = $this->prepareCell(Core::_('Lead_Exchange.import_small_images', $oField->name));
					// $this->_iLead_Fields_Count++;
				}
			}
		}
	}

	/**
	 * Executes the business logic.
	 */
	public function execute()
	{
		$oUser = Core_Auth::getCurrentUser();
		if (!$oUser->superuser && $oUser->only_access_my_own)
		{
			return FALSE;
		}

		header("Pragma: public");
		header("Content-Description: File Transfer");
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename = " . 'leads_' . date("Y_m_d_H_i_s") . '.csv' . ";");
		header("Content-Transfer-Encoding: binary");

		if (!defined('DENY_INI_SET') || !DENY_INI_SET)
		{
			@set_time_limit(1200);
			ini_set('max_execution_time', '1200');
		}

		foreach ($this->_aCurrentData as $aData)
		{
			$this->_printRow($aData);
		}

		$offset = 0;
		$limit = 100;

		do {
			$oLeads = $this->_oSite->Leads;
			$oLeads->queryBuilder()
				->clearOrderBy()
				->orderBy('leads.id')
				->offset($offset)
				->limit($limit);

			$aLeads = $oLeads->findAll(FALSE);

			foreach ($aLeads as $oLead)
			{
				$this->_aData = array(
					sprintf('"%s"', $this->_prepareString($oLead->id)),
					sprintf('"%s"', $this->_prepareString($oLead->surname)),
					sprintf('"%s"', $this->_prepareString($oLead->name)),
					sprintf('"%s"', $this->_prepareString($oLead->patronymic)),
					sprintf('"%s"', $this->_prepareString($oLead->company)),
					sprintf('"%s"', $this->_prepareString($oLead->post)),
					sprintf('"%s"', $this->_prepareString($oLead->amount)),
					sprintf('"%s"', $this->_prepareString($oLead->birthday != '0000-00-00' ? Core_Date::sql2date($oLead->birthday) : '')),
					sprintf('"%s"', $this->_prepareString($oLead->Lead_Need->name)),
					sprintf('"%s"', $this->_prepareString($oLead->Lead_Maturity->name)),
					sprintf('"%s"', $this->_prepareString($oLead->Crm_Source->name)),
					sprintf('"%s"', $this->_prepareString($oLead->Shop->name)),
					sprintf('"%s"', $this->_prepareString($oLead->Lead_Status->name)),
					sprintf('"%s"', $this->_prepareString($oLead->comment)),
					sprintf('"%s"', $this->_prepareString($oLead->last_contacted != '0000-00-00 00:00:00' ? Core_Date::sql2datetime($oLead->last_contacted) : '')),
				);

				$aDataAddresses = $aDataPhones = $aDataEmails = $aDataWebsites = array();

				// Directory_Addresses
				$aDirectory_Addresses = $oLead->Directory_Addresses->findAll();
				foreach ($aDirectory_Addresses as $oDirectory_Address)
				{
					$aDataAddresses[] = $oDirectory_Address->value;
				}
				$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode('| ', $aDataAddresses)));

				// Directory_Phones
				$aDirectory_Phones = $oLead->Directory_Phones->findAll();
				foreach ($aDirectory_Phones as $oDirectory_Phone)
				{
					$aDataPhones[] = $oDirectory_Phone->value;
				}
				$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataPhones)));

				// Directory_Emails
				$aDirectory_Emails = $oLead->Directory_Emails->findAll();
				foreach ($aDirectory_Emails as $oDirectory_Email)
				{
					$aDataEmails[] = $oDirectory_Email->value;
				}
				$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataEmails)));

				// Directory_Websites
				$aDirectory_Websites = $oLead->Directory_Websites->findAll();
				foreach ($aDirectory_Websites as $oDirectory_Website)
				{
					$aDataWebsites[] = $oDirectory_Website->value;
				}
				$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataWebsites)));

				// Fields
				if (Core::moduleIsActive('field'))
				{
					// Кэш всех значений полей товара
					$this->_cacheFieldValues[$oLead->id] = array();
					foreach ($this->_aLead_Fields as $oField)
					{
						$this->_cacheFieldValues[$oLead->id][$oField->id] = $oField->getValues($oLead->id, FALSE);
					}

					$this->_aData = array_merge($this->_aData, $this->getFieldsData($this->_aLead_Fields, $oLead));
				}

				$this->_printRow($this->_aData);

				// Оставшиеся множественные значения полей
				if (count($this->_cacheFieldValues[$oLead->id]))
				{
					$aBaseLeadData = array_fill(0, count($this->getItemTitles()), '""');
					$aBaseLeadData[0] = $this->_aData[0]; //id
					$aBaseLeadData[16] = $this->_aData[16]; //phone
					$aBaseLeadData[17] = $this->_aData[17]; //email

					// Оставшиеся множественные значения полей
					while (count($this->_cacheFieldValues[$oLead->id]))
					{
						$aCurrentFieldLine = array_merge($aBaseLeadData, $this->getFieldsData($this->_aLead_Fields, $oLead));
						$this->_printRow($aCurrentFieldLine);
					}
					unset($this->_cacheFieldValues[$oLead->id]);
				}
			}

			$offset += $limit;
		}
		while (count($aLeads));

		exit();
	}

	/**
	 * Get block of lead fields values
	 * @param array $aFields
	 * @param object $object
	 * @return array
	 */
	public function getFieldsData(array $aFields, $object)
	{
		$aRow = array();

		foreach ($aFields as $oField)
		{
			$oField_Value = isset($this->_cacheFieldValues[$object->id][$oField->id]) && is_array($this->_cacheFieldValues[$object->id][$oField->id])
				? array_shift($this->_cacheFieldValues[$object->id][$oField->id])
				: NULL;

			$aRow[] = $this->prepareCell(
				$oField_Value
					? $this->_getFieldValue($oField, $oField_Value, $object)
					: ''
			);

			if ($oField->type == 2)
			{
				$aRow[] = $oField_Value
					? $this->prepareCell($oField_Value->file_description)
					: '';

				$aRow[] = $oField_Value
					? ($oField_Value->file_small == ''
						? ''
						: $this->prepareCell($oField_Value->getSmallFileHref())
					)
					: '';
			}

			$oField_Value && $oField_Value->clear();

			// Удаляем пустой массив для свойств, чтобы определить, что значения закончились
			if (isset($this->_cacheFieldValues[$object->id][$oField->id]) && !count($this->_cacheFieldValues[$object->id][$oField->id]))
			{
				unset($this->_cacheFieldValues[$object->id][$oField->id]);
			}
		}

		return $aRow;
	}

	/**
	 * Cache list values
	 * @var array
	 */
	protected $_cacheGetListValue = array();

	/**
	 * Get list value
	 * @param int $list_item_id
	 * @return string
	 */
	protected function _getListValue($list_item_id)
	{
		return $list_item_id && Core::moduleIsActive('list')
			? (isset($this->_cacheGetListValue[$list_item_id])
				? $this->_cacheGetListValue[$list_item_id]
				: $this->_cacheGetListValue[$list_item_id] = Core_Entity::factory('List_Item', $list_item_id)->value
			)
			: '';
	}

	/**
	 * Get value of Property_Value
	 * @param Field_Model $oField
	 * @param mixed $oField_Value
	 * @param mixed $object
	 * @return string
	 * @hostcms-event List_Exchange_Export_Controller.onGetFieldValueDefault
	 */
	protected function _getFieldValue($oField, $oField_Value, $object)
	{
		switch ($oField->type)
		{
			case 0: // Int
			case 1: // String
			case 4: // Textarea
			case 6: // Wysiwyg
			case 7: // Checkbox
			case 10: // Hidden field
			case 11: // Float
				$result = $oField_Value->value;
			break;
			case 2: // File
				$href = method_exists($object, 'getItemHref')
					? $object->getItemHref()
					: $object->getGroupHref();

				$result = $oField_Value->file == ''
					? ''
					: $oField_Value
						->setHref($href)
						->getLargeFileHref();
			break;
			case 3: // List
				$result = $this->_getListValue($oField_Value->value);
			break;
			case 5: // Informationsystem
				$result = $oField_Value->value
					? $oField_Value->Informationsystem_Item->name
					: '';
			break;
			case 8: // Date
				$result = Core_Date::sql2date($oField_Value->value);
			break;
			case 9: // Datetime
				$result = Core_Date::sql2datetime($oField_Value->value);
			break;
			case 12: // Shop
				$result = $oField_Value->value
					? $oField_Value->Shop_Item->name
					: '';
			break;
			default:
				$result = $oField_Value->value;

				Core_Event::notify(get_class($this) . '.onGetFieldValueDefault', $this, array($oField, $oField_Value, $object));

				if (!is_null(Core_Event::getLastReturn()))
				{
					$result = Core_Event::getLastReturn();
				}
		}

		return $result;
	}

	/**
	 * Prepare string
	 * @param string $string
	 * @return string
	 */
	protected function _prepareString($string)
	{
		return str_replace('"', '""', trim((string) $string));
	}

	/**
	 * Prepare cell
	 * @param string $string
	 * @return string
	 */
	public function prepareCell($string)
	{
		return sprintf('"%s"', $this->_prepareString($string));
	}

	/**
	 * Print array
	 * @param array $aData
	 * @return self
	 */
	protected function _printRow($aData)
	{
		echo Core_Str::iconv('UTF-8', 'Windows-1251', implode(';', $aData) . "\n");
		return $this;
	}
}