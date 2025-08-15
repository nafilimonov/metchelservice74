<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead import CSV controller
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Lead_Exchange_Import_Controller extends Core_Servant_Properties
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		// Кодировка импорта
		'encoding',
		// Файл импорта
		'file',
		// Позиция в файле импорта
		'seek',
		// Ограничение импорта по времени
		'time',
		// Ограничение импорта по количеству
		'step',
		// Настройка CSV: разделитель
		'separator',
		// Настройка CSV: ограничитель
		'limiter',
		// Настройка CSV: первая строка - название полей
		'firstlineheader',
		// Настройка CSV: массив соответствий полей CSV сущностям системы HostCMS
		'csv_fields',
		// Путь к картинкам
		'imagesPath',
		// Действие с существующими лидами:
		// 1 - обновить существующие элементы
		// 2 - не обновлять существующие элементы
		'importAction',
		'deleteFieldValues'
	);

	/**
	 * Current site
	 * @var Site_Model
	 */
	protected $_oSite;

	/**
	 * Current lead
	 * @var Lead_Model
	 */
	protected $_oLead;

	/**
	* Set $this->_oLead
	* @param Lead_Model $oLead
	* @return self
	*/
	public function setLead(Lead_Model $oLead)
	{
		$this->_oLead = $oLead;
		return $this;
	}

	/**
	 * List of external fields
	 * @var array
	 */
	protected $_aExternalFields = array();

	/**
	 * List of small parts of external fields
	 * @var array
	 */
	protected $_aExternalFieldsSmall = array();

	/**
	 * List of descriptions of external fields
	 * @var array
	 */
	protected $_aExternalFieldsDesc = array();

	/**
	 * Array of ID's and GUIDs of cleared item's properties
	 * @var array
	 */
	protected $_aClearedItemsFieldValues = array();

	/**
	 * Initialization
	 * @return self
	 */
	protected function init()
	{
		// Инициализация текущего элемента
		$this->_oLead = Core_Entity::factory('Lead');
		$this->_oLead->site_id = intval($this->_oSite->id);

		return $this;
	}

	/**
	 * CSV config
	 * @var array
	 */
	protected $_aConfig = NULL;

	/**
	 * Count of inserted leads
	 * @var int
	 */
	protected $_InsertedLeadsCount;

	/**
	 * Count of updated leads
	 * @var int
	 */
	protected $_UpdatedLeadsCount;

	/**
	 * Get inserted leads count
	 * @return int
	 */
	public function getInsertedLeadsCount()
	{
		return $this->_InsertedLeadsCount;
	}

	/**
	 * Get updated leads count
	 * @return int
	 */
	public function getUpdatedLeadsCount()
	{
		return $this->_UpdatedLeadsCount;
	}

	/**
	 * Array of inserted leads
	 * @var array
	 */
	protected $_aInsertedLeadIDs = array();

	/**
	 * Array of updated items
	 * @var array
	 */
	protected $_aUpdatedLeadIDs = array();

	/**
	 * Increment inserted leads
	 * @param int $iLeadId lead ID
	 * @return self
	 */
	protected function _incInsertedLeads($iLeadId)
	{
		if (!in_array($iLeadId, $this->_aInsertedLeadIDs))
		{
			$this->_aInsertedLeadIDs[] = $iLeadId;
			$this->_InsertedLeadsCount++;
		}

		return $this;
	}

	/**
	 * Increment updated leads
	 * @param int $iLeadId item ID
	 * @return self
	 */
	protected function _incUpdatedLeads($iLeadId)
	{
		if (!in_array($iLeadId, $this->_aUpdatedLeadIDs))
		{
			$this->_aUpdatedLeadIDs[] = $iLeadId;
			$this->_UpdatedLeadsCount++;
		}
		return $this;
	}

	/**
	 * List of phones
	 * @var array
	 */
	//protected $_aLeadPhones = array();

	/**
	 * Constructor
	 * @param Site_Model $oSite
	 */
	public function __construct(Site_Model $oSite)
	{
		parent::__construct();

		$this->_aConfig = Core_Config::instance()->get('lead_exchange_csv', array()) + array(
			'maxTime' => 20,
			'maxCount' => 100
		);

		$this->_oSite = $oSite;

		$this->time = $this->_aConfig['maxTime'];
		$this->step = $this->_aConfig['maxCount'];

		$this->init();

		$this->deleteFieldValues = TRUE;

		// Единожды в конструкторе, чтобы после __wakeup() не обнулялось
		$this->_InsertedLeadsCount = 0;
		$this->_UpdatedLeadsCount = 0;
	}

	/**
	* Импорт CSV
	* @hostcms-event Lead_Exchange_Import_Controller.onBeforeImport
	* @hostcms-event Lead_Exchange_Import_Controller.onAfterImport
	*/
	public function import()
	{
		Core_Event::notify('Lead_Exchange_Import_Controller.onBeforeImport', $this, array($this->_oSite));

		$fInputFile = fopen($this->file, 'rb');

		if ($fInputFile === FALSE)
		{
			throw new Core_Exception("");
		}

		// Remove first BOM
		if ($this->seek == 0)
		{
			$BOM = fgets($fInputFile, 4); // length - 1 байт

			if ($BOM === "\xEF\xBB\xBF")
			{
				$this->seek = 3;
			}
			else
			{
				fseek($fInputFile, 0);
			}
		}
		else
		{
			fseek($fInputFile, $this->seek);
		}

		$iCounter = 0;

		$timeout = Core::getmicrotime();

		$aCsvLine = array();

		while ((Core::getmicrotime() - $timeout + 3 < $this->time)
			&& $iCounter < $this->step
			&& ($aCsvLine = $this->getCSVLine($fInputFile)))
		{
			if (count($aCsvLine) == 1
			&& (is_null($aCsvLine[0]) || $aCsvLine[0] == ''))
			{
				continue;
			}

			$aData = array();

			foreach ($aCsvLine as $iKey => $sData)
			{
				if (!isset($this->csv_fields[$iKey]))
				{
					continue;
				}

				if ($sData != '')
				{
					switch ($this->csv_fields[$iKey])
					{
						case 'lead_id':
							$aData['id'] = $sData;
						break;
						case 'lead_surname':
							$aData['surname'] = $sData;
						break;
						case 'lead_name':
							$aData['name'] = $sData;
						break;
						case 'lead_patronymic':
							$aData['patronymic'] = $sData;
						break;
						case 'lead_company':
							$aData['company'] = $sData;
						break;
						case 'lead_post':
							$aData['post'] = $sData;
						break;
						case 'lead_amount':
							$aData['amount'] = Shop_Controller::instance()->convertPrice($sData);
						break;
						case 'lead_birthday':
							$aData['birthday'] = Core_Date::date2sql($sData);
						break;
						case 'lead_need':
							$oLead_Need = $this->_oSite->Lead_Needs->getByName($sData);

							!is_null($oLead_Need)
								&& $aData['lead_need_id'] = $oLead_Need->id;
						break;
						case 'lead_maturity':
							$oLead_Maturity = $this->_oSite->Lead_Maturities->getByName($sData);

							!is_null($oLead_Maturity)
								&& $aData['lead_maturity_id'] = $oLead_Maturity->id;
						break;
						case 'lead_source':
							$oCrm_Source = Core_Entity::factory('Crm_Source')->getByName($sData);

							!is_null($oCrm_Source)
								&& $aData['crm_source_id'] = $oCrm_Source->id;
						break;
						case 'lead_shop':
							$oShop = $this->_oSite->Shops->getByName($sData);

							!is_null($oShop)
								&& $aData['shop_id'] = $oShop->id;
						break;
						case 'lead_status':
							$oLead_Status = $this->_oSite->Lead_Statuses->getByName($sData);

							!is_null($oLead_Status)
								&& $aData['lead_status_id'] = $oLead_Status->id;
						break;
						case 'lead_comment':
							$aData['comment'] = $sData;
						break;
						case 'lead_last_contacted':
							$aData['last_contacted'] = Core_Date::datetime2sql($sData);
						break;
						case 'lead_address':
							$aData['addresses'] = explode('|', $sData);
							$aData['addresses'] = array_map('trim', $aData['addresses']);
						break;
						case 'lead_phone':
							$aData['phones'] = explode(',', $sData);
							$aData['phones'] = array_map('trim', $aData['phones']);
						break;
						case 'lead_email':
							$aData['emails'] = explode(',', $sData);
							$aData['emails'] = array_map('trim', $aData['emails']);
						break;
						case 'lead_website':
							$aData['websites'] = explode(',', $sData);
							$aData['websites'] = array_map('trim', $aData['websites']);
						break;
						default:
							$sFieldName = $this->csv_fields[$iKey];

							if (strpos($sFieldName, "fieldsmall-") === 0)
							{
								// Дополнительный файл пользовательских полей/Малое изображение картинки пользовательских полей
								$aFieldSmallInfo = explode("-", $sFieldName);

								$this->_aExternalFieldsSmall[$aFieldSmallInfo[1]][] = $sData;
							}

							if (strpos($sFieldName, "fielddesc-") === 0)
							{
								// Описание пользовательских полей
								$aTmpExplode = explode('-', $sFieldName);
								$this->_aExternalFieldsDesc[$aTmpExplode[1]][] = $sData;
							}

							if (strpos($sFieldName, "field-") === 0)
							{
								// Основной файл пользовательских полей/Большое изображение картинки пользовательских полей
								$aFieldInfo = explode("-", $sFieldName);

								$this->_aExternalFields[$aFieldInfo[1]][] = $sData;
							}
						break;
					}
				}
			}

			$this->_oLead = NULL;

			// By ID
			if (isset($aData['id']))
			{
				$oTmpObject = Core_Entity::factory('Lead')->getById($aData['id']);
				if (!is_null($oTmpObject))
				{
					$this->_oLead = $oTmpObject;

					$this->_incUpdatedLeads($this->_oLead->id);
				}
			}

			// By Phone
			if (is_null($this->_oLead) && isset($aData['phones']) && count($aData['phones']))
			{
				$oTmp = Core_Entity::factory('Lead');
				$oTmp->queryBuilder()
					->select('leads.*')
					->join('lead_directory_phones', 'leads.id', '=', 'lead_directory_phones.lead_id')
					->join('directory_phones', 'lead_directory_phones.directory_phone_id', '=', 'directory_phones.id')
					->where('leads.site_id', '=', $this->_oSite->id)
					->where('directory_phones.value', 'IN', $aData['phones'])
					->limit(1);

				$aLeads = $oTmp->findAll(FALSE);

				if (isset($aLeads[0]))
				{
					$this->_oLead = $aLeads[0];
					$this->_incUpdatedLeads($this->_oLead->id);
				}
			}

			// By Email
			if (is_null($this->_oLead) && isset($aData['emails']) && count($aData['emails']))
			{
				$oTmp = Core_Entity::factory('Lead');
				$oTmp->queryBuilder()
					->select('leads.*')
					->join('lead_directory_emails', 'leads.id', '=', 'lead_directory_emails.lead_id')
					->join('directory_emails', 'lead_directory_emails.directory_email_id', '=', 'directory_emails.id')
					->where('leads.site_id', '=', $this->_oSite->id)
					->where('directory_emails.value', 'IN', $aData['emails'])
					->limit(1);

				$aLeads = $oTmp->findAll(FALSE);

				if (isset($aLeads[0]))
				{
					$this->_oLead = $aLeads[0];
					$this->_incUpdatedLeads($this->_oLead->id);
				}
			}

			if (is_null($this->_oLead))
			{
				$this->_oLead = Core_Entity::factory('Lead');
				$this->_oLead->site_id = $this->_oSite->id;
				$this->_oLead->save();

				$this->_incInsertedLeads($this->_oLead->id);
			}
			elseif ($this->importAction == 2)
			{
				// если сказано - оставить без изменений
				continue;
			}

			foreach ($aData as $key => $value)
			{
				if (!is_array($value) && $key != 'id')
				{
					$this->_oLead->$key = $value;
				}
			}

			// Статус не был передан
			if ($this->_oLead->lead_status_id == 0)
			{
				$oLead_Status = Core_Entity::factory('Lead_Status')->getFirst();
				if ($oLead_Status->id)
				{
					$this->_oLead->lead_status_id = $oLead_Status->id;
				}
			}

			$this->_oLead->save();

			if (isset($aData['phones']))
			{
				$aPhones = array();
				$aLead_Directory_Phones = $this->_oLead->Lead_Directory_Phones->findAll(FALSE);
				foreach ($aLead_Directory_Phones as $oLead_Directory_Phone)
				{
					$oDirectory_Phone = $oLead_Directory_Phone->Directory_Phone;

					$aPhones[] = trim($oDirectory_Phone->value);
				}

				foreach ($aData['phones'] as $sPhone)
				{
					if (!in_array($sPhone, $aPhones))
					{
						$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type')->getFirst();

						$oDirectory_Phone = Core_Entity::factory('Directory_Phone')
							->directory_phone_type_id($oDirectory_Phone_Type->id)
							->public(0)
							->value(Core_Str::sanitizePhoneNumber($sPhone))
							->save();

						$this->_oLead->add($oDirectory_Phone);
					}
				}
			}

			if (isset($aData['emails']))
			{
				$aEmails = array();
				$aLead_Directory_Emails = $this->_oLead->Lead_Directory_Emails->findAll(FALSE);
				foreach ($aLead_Directory_Emails as $oLead_Directory_Email)
				{
					$oDirectory_Email = $oLead_Directory_Email->Directory_Email;

					$aEmails[] = trim($oDirectory_Email->value);
				}

				foreach ($aData['emails'] as $sEmail)
				{
					if (!in_array($sEmail, $aEmails))
					{
						$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->getFirst();

						$oDirectory_Email = Core_Entity::factory('Directory_Email')
							->directory_email_type_id($oDirectory_Email_Type->id)
							->public(0)
							->value($sEmail)
							->save();

						$this->_oLead->add($oDirectory_Email);
					}
				}
			}

			if (isset($aData['websites']))
			{
				$aWebsites = array();
				$aLead_Directory_Websites = $this->_oLead->Lead_Directory_Websites->findAll(FALSE);
				foreach ($aLead_Directory_Websites as $oLead_Directory_Website)
				{
					$oDirectory_Website = $oLead_Directory_Website->Directory_Website;

					$aWebsites[] = trim($oDirectory_Website->value);
				}

				foreach ($aData['websites'] as $sWebsite)
				{
					if (!in_array($sWebsite, $aWebsites))
					{
						$aUrl = @parse_url($sWebsite);

						// Если не был указан протокол, или
						// указанный протокол некорректен для url
						!array_key_exists('scheme', $aUrl)
							&& $sWebsite = 'http://' . $sWebsite;

						$oDirectory_Website = Core_Entity::factory('Directory_Website')
							->public(0)
							->value($sWebsite);

						$this->_oLead->add($oDirectory_Website);
					}
				}
			}

			if (isset($aData['addresses']))
			{
				$aAddresses = array();
				$aLead_Directory_Addresses = $this->_oLead->Lead_Directory_Addresses->findAll(FALSE);
				foreach ($aLead_Directory_Addresses as $oLead_Directory_Address)
				{
					$oDirectory_Adress = $oLead_Directory_Address->Directory_Address;

					$aAddresses[] = trim($oDirectory_Adress->value);
				}

				foreach ($aData['addresses'] as $sAddress)
				{
					$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->getFirst();

					$oDirectory_Address = Core_Entity::factory('Directory_Address')
						->directory_address_type_id($oDirectory_Address_Type->id)
						->public(0)
						->value($sAddress)
						->save();

					$this->_oLead->add($oDirectory_Address);
				}
			}

			// Импорт пользовательских полей с передачей вызова в метод _addItemFieldValue
			foreach ($this->_aExternalFields as $iFieldID => $aFieldValue)
			{
				$oField = Core_Entity::factory('Field')->find($iFieldID);

				foreach ($aFieldValue as $key => $sFieldValue)
				{
					Core_Event::notify('Lead_Exchange_Import_Controller.onBeforeImportItemField', $this, array($this->_oLead, $oField, $sFieldValue));
					$this->_addItemFieldValue($this->_oLead, $oField, $sFieldValue, $key);
				}
			}

			// Отдельный импорт малых изображений, когда большие не были проимпортированы
			foreach ($this->_aExternalFieldsSmall as $iFieldID => $aFieldValue)
			{
				$oField = Core_Entity::factory('Field')->find($iFieldID);

				// Разрешаем свойство для группы
				// $this->_allowFieldForGroup($oField, $this->_oCurrentGroup->id);

				foreach ($aFieldValue as $sFieldValue)
				{
					/*$aFieldValues = $oField->getValues($this->_oLead->id, FALSE);

					$oField_Value = isset($aFieldValues[0])
						? $aFieldValues[0]
						: $oField->createNewValue($this->_oLead->id);*/

					// При отдельном импорте малых изображений, всегда создаются новые значения,
					// при совместном импорте с большими, малые изображения обрабатываются в _addItemFieldValue()
					$oField_Value = $oField->createNewValue($this->_oLead->id);

					// Папка назначения
					$sDestinationFolder = $this->_oLead->getPath();

					// Файл-источник
					$sTmpFilePath = $this->imagesPath . $sFieldValue;

					$sSourceFileBaseName = basename($sTmpFilePath, '');

					$bHttp = strpos(strtolower($sTmpFilePath), "http://") === 0 || strpos(strtolower($sTmpFilePath), "https://") === 0;

					if (Core_File::isValidExtension($sTmpFilePath, Core::$mainConfig['availableExtension']) || $bHttp)
					{
						// Создаем папку назначения
						$this->_oLead->createDir();

						if ($bHttp)
						{
							try {
								$sSourceFile = $this->_downloadHttpFile($sTmpFilePath);
							}
							catch (Exception $e)
							{
								Core_Message::show($e->getMessage(), 'error');
								$sSourceFile = NULL;
							}
						}
						else
						{
							$sSourceFile = CMS_FOLDER . $sTmpFilePath;
						}

						if (!$oField->change_filename)
						{
							$sTargetFileName = "small_{$sSourceFileBaseName}";
						}
						else
						{
							$sTargetFileExtension = Core_File::getExtension($sSourceFileBaseName);
							$sTargetFileExtension = $sTargetFileExtension == '' || strlen($sTargetFileExtension) > 5
								? '.jpg'
								: ".{$sTargetFileExtension}";

							$oField_Value->save();
							$sTargetFileName = "small_lead_field_file_{$this->_oLead->id}_{$oField_Value->id}{$sTargetFileExtension}";
						}

						$aPicturesParam = array();
						$aPicturesParam['small_image_source'] = $sSourceFile;
						$aPicturesParam['small_image_name'] = $sSourceFileBaseName;
						$aPicturesParam['small_image_target'] = $sDestinationFolder . $sTargetFileName;
						$aPicturesParam['create_small_image_from_large'] = FALSE;
						$aPicturesParam['small_image_max_width'] = $oField->image_small_max_width;
						$aPicturesParam['small_image_max_height'] = $oField->image_small_max_height;
						$aPicturesParam['small_image_preserve_aspect_ratio'] = $oField->preserve_aspect_ratio_small;

						// Удаляем старое малое изображение
						if ($oField_Value->file_small != '')
						{
							try
							{
								Core_File::delete($sDestinationFolder . $oField_Value->file_small);
							} catch (Exception $e) {}
						}

						try {
							Core_Event::notify('Lead_Exchange_Import_Controller.onBeforeAdminUpload', $this, array($aPicturesParam));
							$aTmpReturn = Core_Event::getLastReturn();
							is_array($aTmpReturn) && $aPicturesParam = $aTmpReturn;

							$aResult = Core_File::adminUpload($aPicturesParam);
						}
						catch (Exception $e)
						{
							Core_Message::show(strtoupper($this->encoding) == 'UTF-8'
								? $e->getMessage()
								: @iconv($this->encoding, "UTF-8//IGNORE//TRANSLIT", $e->getMessage())
							, 'error');

							$aResult = array('large_image' => FALSE, 'small_image' => FALSE);
						}

						if ($aResult['small_image'])
						{
							$oField_Value->file_small = $sTargetFileName;
							$oField_Value->file_small_name = '';
						}

						if (!is_null($sSourceFile) && strpos(basename($sSourceFile), "CMS") === 0)
						{
							// Файл временный, подлежит удалению
							Core_File::delete($sSourceFile);
						}
					}

					$oField_Value->save();
				}
			}

			$iCounter++;

			// Очищаем временные массивы
			$this->_aExternalFieldsSmall =
				$this->_aExternalFields =
				$this->_aExternalFieldsDesc = array();
		} // end line

		$iCurrentSeekPosition = !$aCsvLine ? $aCsvLine : ftell($fInputFile);

		fclose($fInputFile);

		Core_Event::notify('Lead_Exchange_Import_Controller.onAfterImport', $this, array($this->_oSite, $iCurrentSeekPosition));

		return $iCurrentSeekPosition;
	}

	/**
	 * Add field to item
	 * @param Lead_Model $oLead
	 * @param Field_Model $oField
	 * @param string $sFieldValue field value
	 * @hostcms-event Lead_Exchange_Import_Controller.onAddItemFieldValueDefault
	 */
	protected function _addItemFieldValue(Lead_Model $oLead, Field_Model $oField, $sFieldValue, $position = 0)
	{
		$aFieldValues = $oField->getValues($oLead->id, FALSE);

		// Удалять ранее загруженные свойства или свойство в массиве у удалению перед загрузкой
		if ($this->deleteFieldValues === TRUE
			|| is_array($this->deleteFieldValues) && in_array($oField->id, $this->deleteFieldValues))
		{
			// Свойство для данного товара не было очищено
			if (!isset($this->_aClearedItemsFieldValues[$oLead->id])
				|| !in_array($oField->id, $this->_aClearedItemsFieldValues[$oLead->id]))
			{
				foreach ($aFieldValues as $oFieldValue)
				{
					$oField->type == 2
						&& $oFieldValue->setDir($oLead->getPath());
					$oFieldValue->delete();
				}

				$aFieldValues = array();

				$this->_aClearedItemsFieldValues[$oLead->id][] = $oField->id;
			}
		}

		switch ($oField->type)
		{
			case 0: // Int
				$changedValue = Shop_Controller::convertDecimal($sFieldValue);
			break;
			case 2: // Файл
				$changedValue = $sFieldValue;
			break;
			case 3: // Список
				if (Core::moduleIsActive('list'))
				{
					$oList_Item = $oField->List->List_Items->getByValue($sFieldValue, FALSE);

					if ($oList_Item)
					{
						$changedValue = $oList_Item->id;
					}
					else
					{
						$oList_Item = Core_Entity::factory('List_Item')
							->list_id($oField->list_id)
							->value($sFieldValue);

						// Apache %2F (/) is forbidden
						strpos($sFieldValue, '/') !== FALSE
							&& $oList_Item->path = trim(str_replace('/', ' ', $sFieldValue));

						$changedValue = $oList_Item->save()
							->id;
					}
				}
				else
				{
					$changedValue = NULL;
				}
			break;
			case 5: // Informationsystem
				$oInformationsystem_Item = $oField->Informationsystem->Informationsystem_Items->getByName($sFieldValue);
				if ($oInformationsystem_Item)
				{
					$changedValue = $oInformationsystem_Item->id;
				}
				elseif (is_numeric($sFieldValue))
				{
					$oInformationsystem_Item = $oField->Informationsystem->Informationsystem_Items->getById($sFieldValue);

					$changedValue = $oInformationsystem_Item
						? $oInformationsystem_Item->id
						: NULL;
				}
				else
				{
					$changedValue = NULL;
				}
			break;
			case 7: // Checkbox
				$changedValue = $this->_correctCheckbox($sFieldValue);
			break;
			case 8:
				$changedValue = preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/", $sFieldValue)
					? $sFieldValue
					: Core_Date::datetime2sql($sFieldValue);
			break;
			case 9:
				$changedValue = preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", $sFieldValue)
					? $sFieldValue
					: Core_Date::datetime2sql($sFieldValue);
			break;
			case 11: // Float
				$changedValue = Shop_Controller::convertDecimal($sFieldValue);
			break;
			case 12: // Shop
				// by Name
				$oShop_Item = $oField->Shop->Shop_Items->getByName($sFieldValue);
				if ($oShop_Item)
				{
					$changedValue = $oShop_Item->id;
				}
				else
				{
					// by Marking
					$oShop_Item = $oField->Shop->Shop_Items->getByMarking($sFieldValue);
					if ($oShop_Item)
					{
						$changedValue = $oShop_Item->id;
					}
					// by ID
					elseif (is_numeric($sFieldValue))
					{
						$oShop_Item = $oField->Shop->Shop_Items->getById($sFieldValue);

						$changedValue = $oShop_Item
							? $oShop_Item->id
							: NULL;
					}
					else
					{
						$changedValue = NULL;
					}
				}
			break;
			default:
				Core_Event::notify(get_class($this) . '.onAddItemFieldValueDefault', $this, array($oLead, $oField, $sFieldValue));

				$changedValue = is_null(Core_Event::getLastReturn())
					? $sFieldValue
					: Core_Event::getLastReturn();
		}

		if (!is_null($changedValue))
		{
			if ($oField->multiple)
			{
				$bHttp = strpos(strtolower($changedValue), "http://") === 0 || strpos(strtolower($changedValue), "https://") === 0;

				foreach ($aFieldValues as $oField_Value)
				{
					if ($oField->type == 2 && basename($oField_Value->file_name) == basename($changedValue)
						|| $oField->type != 2 && $oField_Value->value == $changedValue)
					{
						return $oField_Value;
					}
				}

				$oField_Value = $oField->createNewValue($oLead->id);
			}
			else
			{
				$oField_Value = isset($aFieldValues[0])
					? $aFieldValues[0]
					: $oField->createNewValue($oLead->id);
			}

			// File
			if ($oField->type == 2)
			{
				// Папка назначения
				$sDestinationFolder = $oLead->getPath();

				// Файл-источник
				$sTmpFilePath = $this->imagesPath . (
					/*strtoupper($this->encoding) == 'UTF-8'
						? $sFieldValue
						: Core_File::convertfileNameFromLocalEncoding($sFieldValue)*/
					$sFieldValue
				);

				$sSourceFileBaseName = basename($sTmpFilePath, '');

				$bHttp = strpos(strtolower($sTmpFilePath), "http://") === 0 || strpos(strtolower($sTmpFilePath), "https://") === 0;

				$sSourceFileName = $bHttp
					? basename($sFieldValue)
					: $sSourceFileBaseName;

				if (Core_File::isValidExtension($sTmpFilePath, Core::$mainConfig['availableExtension']) || $bHttp)
				{
					// Создаем папку назначения
					$oLead->createDir();

					if ($bHttp)
					{
						try {
							$sSourceFile = $this->_downloadHttpFile($sTmpFilePath);
						}
						catch (Exception $e)
						{
							Core_Message::show($e->getMessage(), 'error');
							$sSourceFile = NULL;
						}
					}
					else
					{
						$sSourceFile = CMS_FOLDER . ltrim($sTmpFilePath, '/\\');
					}

					if (!$oField->change_filename)
					{
						$sTargetFileName = $sSourceFileBaseName;
					}
					else
					{
						$sTargetFileExtension = Core_File::getExtension($sSourceFileBaseName);
						$sTargetFileExtension = $sTargetFileExtension == '' || strlen($sTargetFileExtension) > 5
							? '.jpg'
							: ".{$sTargetFileExtension}";

						$oField_Value->save();
						$sTargetFileName = "lead_field_file_{$oLead->id}_{$oField_Value->id}{$sTargetFileExtension}";
					}

					// Создаем массив параметров для загрузки картинок элементу
					$aPicturesParam = array();
					$aPicturesParam['large_image_source'] = $sSourceFile;
					$aPicturesParam['large_image_name'] = $sSourceFileBaseName;
					$aPicturesParam['large_image_target'] = $sDestinationFolder . $sTargetFileName;
					$aPicturesParam['large_image_preserve_aspect_ratio'] = $oField->preserve_aspect_ratio;
					$aPicturesParam['large_image_max_width'] = $oField->image_large_max_width;
					$aPicturesParam['large_image_max_height'] = $oField->image_large_max_height;

					if (isset($this->_aExternalFieldsSmall[$oField->id][$position]))
					{
						// Малое изображение передано
						$aPicturesParam['create_small_image_from_large'] = FALSE;

						// Файл-источник
						$sTmpFilePath = $this->imagesPath . $this->_aExternalFieldsSmall[$oField->id][$position];

						$sSourceFileBaseNameSmall = basename($sTmpFilePath, '');

						$bHttp = strpos(strtolower($sTmpFilePath), "http://") === 0 || strpos(strtolower($sTmpFilePath), "https://") === 0;

						if (Core_File::isValidExtension($sTmpFilePath, Core::$mainConfig['availableExtension']) || $bHttp)
						{
							// Создаем папку назначения
							$oLead->createDir();

							if ($bHttp)
							{
								try {
									$sSourceFileSmall = $this->_downloadHttpFile($sTmpFilePath);
								}
								catch (Exception $e)
								{
									Core_Message::show($e->getMessage(), 'error');
									$sSourceFileSmall = NULL;
								}
							}
							else
							{
								$sSourceFileSmall = CMS_FOLDER . $sTmpFilePath;
							}

							if (!$oField->change_filename)
							{
								$sTargetFileNameSmall = "small_{$sSourceFileBaseNameSmall}";
							}
							else
							{
								$sTargetFileExtension = Core_File::getExtension($sSourceFileBaseNameSmall);
								$sTargetFileExtension = $sTargetFileExtension == '' || strlen($sTargetFileExtension) > 5
									? '.jpg'
									: ".{$sTargetFileExtension}";

								$oField_Value->save();
								$sTargetFileNameSmall = "small_lead_field_file_{$oLead->id}_{$oField_Value->id}{$sTargetFileExtension}";
							}

							$aPicturesParam['small_image_source'] = $sSourceFileSmall;
							$aPicturesParam['small_image_name'] = $sSourceFileBaseNameSmall;
							$aPicturesParam['small_image_target'] = $sDestinationFolder . $sTargetFileNameSmall;

							// Удаляем старое малое изображение
							/*if ($oField_Value->file_small != '')
							{
								try
								{
									Core_File::delete($sDestinationFolder . $oField_Value->file_small);
								} catch (Exception $e) {}
							}*/
						}

						// ------------------------------------------
						// Исключаем из отдельного импорта малых изображений
						unset($this->_aExternalFieldsSmall[$oField->id][$position]);
					}
					else
					{
						// Малое изображение не передано
						$aPicturesParam['create_small_image_from_large'] = TRUE;
						$aPicturesParam['small_image_source'] = $aPicturesParam['large_image_source'];
						$aPicturesParam['small_image_name'] = $aPicturesParam['large_image_name'];
						$aPicturesParam['small_image_target'] = $sDestinationFolder . "small_{$sTargetFileName}";

						$sSourceFileSmall = NULL;
						$sTargetFileNameSmall = "small_{$sTargetFileName}";
					}

					$aPicturesParam['small_image_max_width'] = $oField->image_small_max_width;
					$aPicturesParam['small_image_max_height'] = $oField->image_small_max_height;
					$aPicturesParam['small_image_preserve_aspect_ratio'] = $aPicturesParam['large_image_preserve_aspect_ratio'];

					// Удаляем старое большое изображение
					if ($oField_Value->file != '')
					{
						if ($sDestinationFolder . $oField_Value->file != $sSourceFile)
						{
							try
							{
								Core_File::delete($sDestinationFolder . $oField_Value->file);
							} catch (Exception $e) {}
						}
					}

					// Удаляем старое малое изображение
					if ($oField_Value->file_small != '')
					{
						if ($sDestinationFolder . $oField_Value->file_small != $sSourceFileSmall)
						{
							try
							{
								Core_File::delete($sDestinationFolder . $oField_Value->file_small);
							} catch (Exception $e) {}
						}
					}

					try {
						Core_Event::notify('Lead_Exchange_Import_Csv_Controller.onBeforeAdminUpload', $this, array($aPicturesParam));
						$aTmpReturn = Core_Event::getLastReturn();
						is_array($aTmpReturn) && $aPicturesParam = $aTmpReturn;

						$aResult = Core_File::adminUpload($aPicturesParam);
					}
					catch (Exception $e)
					{
						Core_Message::show(strtoupper($this->encoding) == 'UTF-8'
							? $e->getMessage()
							: @iconv($this->encoding, "UTF-8//IGNORE//TRANSLIT", $e->getMessage())
						, 'error');

						$aResult = array('large_image' => FALSE, 'small_image' => FALSE);
					}

					if ($aResult['large_image'])
					{
						$oField_Value->file = $sTargetFileName;
						$oField_Value->file_name = $sSourceFileName;
					}

					if ($aResult['small_image'])
					{
						$oField_Value->file_small = $sTargetFileNameSmall;
						$oField_Value->file_small_name = '';
					}

					if (isset($this->_aExternalFieldsDesc[$oField->id][$position]))
					{
						$oField_Value->file_description = $this->_aExternalFieldsDesc[$oField->id][$position];
						unset($this->_aExternalFieldsDesc[$oField->id][$position]);
					}

					$oField_Value->save();

					clearstatcache();

					if (!is_null($sSourceFile) && strpos(basename($sSourceFile), "CMS") === 0 && Core_File::isFile($sSourceFile))
					{
						// Файл временный, подлежит удалению
						Core_File::delete($sSourceFile);
					}

					if (!is_null($sSourceFile) && strpos(basename($sSourceFileSmall), "CMS") === 0 && Core_File::isFile($sSourceFileSmall))
					{
						// Файл временный, подлежит удалению
						Core_File::delete($sSourceFileSmall);
					}
				}
			}
			else
			{
				$oField_Value->setValue($changedValue);
				$oField_Value->save();
			}

			return $oField_Value;
		}

		return FALSE;
	}

	/**
	 * Convert object to string
	 * @return string
	 */
	public function __toString()
	{
		$aReturn = array();

		foreach ($this->_allowedProperties as $propertyName)
		{
			$aReturn[] = $propertyName . '=' . $this->$propertyName;
		}

		return implode(', ', $aReturn) . "<br/>";
	}

	/**
	 * Get CSV line from file
	 * @param handler file descriptor
	 * @return array
	 */
	public function getCSVLine($fileDescriptor)
	{
		if (strtoupper($this->encoding) != 'UTF-8' && defined('ALT_SITE_LOCALE'))
		{
			setlocale(LC_ALL, ALT_SITE_LOCALE);
		}

		$aCsvLine = @fgetcsv($fileDescriptor, 0, $this->separator, $this->limiter, "\\");

		if ($aCsvLine === FALSE)
		{
			return $aCsvLine;
		}

		setlocale(LC_ALL, SITE_LOCAL);
		setlocale(LC_NUMERIC, 'POSIX');

		return Core_Str::iconv($this->encoding, 'UTF-8', $aCsvLine);
	}

	/**
	 * Clear object
	 * @return self
	 */
	public function clear()
	{
		$this->_oLead = NULL;

		return $this;
	}

	/**
	 * Convert url to Punycode
	 * @param string $url
	 * @return string
	 */
	protected function _convertToPunycode($url)
	{
		return preg_replace_callback('~(https?://)([^/]*)(.*)~', function($a) {
			$aTmp = array_map('rawurlencode', explode('/', $a[3]));

			return (preg_match('/[А-Яа-яЁё]/u', $a[2])
				? $a[1] . Core_Str::idnToAscii($a[2])
				: $a[1] . $a[2]
			) . implode('/', $aTmp);

			}, $url
		);
	}

	/**
	 * Download file to the TMP dir
	 * @param string $sSourceFile
	 * @return path to the file
	 */
	protected function _downloadHttpFile($sSourceFile)
	{
		$sSourceFile = $this->_convertToPunycode($sSourceFile);

		$Core_Http = Core_Http::instance()
			->clear()
			->url($sSourceFile)
			->timeout(10)
			->addOption(CURLOPT_FOLLOWLOCATION, TRUE)
			->execute();

		$aHeaders = $Core_Http->parseHeaders();
		$sStatus = Core_Array::get($aHeaders, 'status');
		$iStatusCode = $Core_Http->parseHttpStatusCode($sStatus);

		$contentType = isset($aHeaders['Content-Type'])
			? strtolower(substr(is_array($aHeaders['Content-Type']) ? $aHeaders['Content-Type'][0] : $aHeaders['Content-Type'], 0, 9))
			: 'unknown';

		if ($iStatusCode != 200 || $contentType == 'text/html')
		{
			throw new Core_Exception("Lead_Exchange_Import_Controller::_downloadHttpFile error, code: %code, Content-Type: %contentType.\nSource URL: %url",
				array('%code' => $iStatusCode, '%contentType' => $contentType, '%url' => $sSourceFile));
		}

		$content = $Core_Http->getDecompressedBody();

		// Файл из WEB'а, создаем временный файл
		$sTempFileName = tempnam(CMS_FOLDER . TMP_DIR, "CMS");

		Core_File::write($sTempFileName, $content);

		return $sTempFileName;
	}

	/**
	 * Correct checkbox value
	 * @param string $value
	 * @return bool
	 */
	protected function _correctCheckbox($value)
	{
		return $value == 1 || strtolower($value) === 'true' || strtolower($value) === 'да'
			? 1
			: 0;
	}

	/**
	 * Execute some routine before serialization
	 * @return array
	 */
	public function __sleep()
	{
		$this->clear();

		return array_keys(
			get_object_vars($this)
		);
	}

	/**
	 * Reestablish any database connections that may have been lost during serialization and perform other reinitialization tasks
	 * @return self
	 */
	public function __wakeup()
	{
		date_default_timezone_set(Core::$mainConfig['timezone']);

		$this->init();

		// Инициализация текущего элемента
		$this->_oLead = Core_Entity::factory('Lead');
		$this->_oLead->site_id = intval($this->_oSite->id);

		return $this;
	}
}