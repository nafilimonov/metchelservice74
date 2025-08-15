<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_List_Export_Controller
{
	/**
	 * Site object
	 * @var Site_Model
	 */
	private $_oSite = NULL;

	/**
	 * Additional properties of siteusers
	 * Дополнительные свойства пользователей
	 * @var array
	 */
	private $_aSiteuser_Properties = array();

	/**
	 * Siteuser properties count
	 * Требуется хранить количество свойств отдельно, т.к. количество полей файла CSV для свойств не равно количеству свойств (из-за файлов)
	 * @var int
	 */
	private $_iSiteuser_Properties_Count;

	/**
	 * CSV data
	 * @var array
	 */
	private $_aCurrentData;

	/**
	 * Data array
	 * @var array
	 */
	protected $_aData = array();

	/**
	 * Constructor.
	 * @param Site_Model $oSite
	 */
	public function __construct(Site_Model $oSite)
	{
		$this->_oSite = $oSite;

		$this->_iSiteuser_Properties_Count = 0;

		// Заполняем дополнительные свойства пользователей
		$this->_aSiteuser_Properties = Core_Entity::factory('Siteuser_Property_List', $this->_oSite->id)->Properties->findAll(FALSE);

		$iCurrentDataPosition = 0;

		$this->_aCurrentData[$iCurrentDataPosition] = array(
			// 12
			'"' . Core::_('Siteuser_List_Export.id') . '"',
			'"' . Core::_('Siteuser_List_Export.login') . '"',
			'"' . Core::_('Siteuser_List_Export.email') . '"',
			'"' . Core::_('Siteuser_List_Export.datetime') . '"',
			'"' . Core::_('Siteuser_List_Export.ip') . '"',
			'"' . Core::_('Siteuser_List_Export.guid') . '"',
			'"' . Core::_('Siteuser_List_Export.active') . '"',
			'"' . Core::_('Siteuser_List_Export.last_activity') . '"',
			'"' . Core::_('Siteuser_List_Export.siteuser_type') . '"',
			'"' . Core::_('Siteuser_List_Export.siteuser_status') . '"',
			'"' . Core::_('Siteuser_List_Export.crm_source') . '"',
			'"' . Core::_('Siteuser_List_Export.siteuser_groups') . '"',

			// 16. Siteuser_People
			'"' . Core::_('Siteuser_List_Export.name') . '"',
			'"' . Core::_('Siteuser_List_Export.surname') . '"',
			'"' . Core::_('Siteuser_List_Export.patronymic') . '"',
			'"' . Core::_('Siteuser_List_Export.post') . '"',
			'"' . Core::_('Siteuser_List_Export.birthday') . '"',
			'"' . Core::_('Siteuser_List_Export.sex') . '"',
			'"' . Core::_('Siteuser_List_Export.photo') . '"',
			'"' . Core::_('Siteuser_List_Export.country') . '"',
			'"' . Core::_('Siteuser_List_Export.postcode') . '"',
			'"' . Core::_('Siteuser_List_Export.city') . '"',
			'"' . Core::_('Siteuser_List_Export.address') . '"',
			'"' . Core::_('Siteuser_List_Export.phones') . '"',
			'"' . Core::_('Siteuser_List_Export.emails') . '"',
			'"' . Core::_('Siteuser_List_Export.socials') . '"',
			'"' . Core::_('Siteuser_List_Export.messengers') . '"',
			'"' . Core::_('Siteuser_List_Export.websites') . '"',

			// 14. Siteuser_Company
			'"' . Core::_('Siteuser_List_Export.company_name') . '"',
			'"' . Core::_('Siteuser_List_Export.description') . '"',
			'"' . Core::_('Siteuser_List_Export.logo') . '"',
			'"' . Core::_('Siteuser_List_Export.headcount') . '"',
			'"' . Core::_('Siteuser_List_Export.annual_turnover') . '"',
			'"' . Core::_('Siteuser_List_Export.business_area') . '"',
			'"' . Core::_('Siteuser_List_Export.tin') . '"',
			'"' . Core::_('Siteuser_List_Export.bank_account') . '"',
			'"' . Core::_('Siteuser_List_Export.addresses') . '"',
			'"' . Core::_('Siteuser_List_Export.phones') . '"',
			'"' . Core::_('Siteuser_List_Export.emails') . '"',
			'"' . Core::_('Siteuser_List_Export.socials') . '"',
			'"' . Core::_('Siteuser_List_Export.messengers') . '"',
			'"' . Core::_('Siteuser_List_Export.websites') . '"', // 42
		);

		// Добавляем в заголовок информацию о свойствах
		foreach ($this->_aSiteuser_Properties as $oProperty)
		{
			$this->_aCurrentData[$iCurrentDataPosition][] = sprintf('"%s"', $this->_prepareString($oProperty->name));
			$this->_iSiteuser_Properties_Count++;

			if ($oProperty->type == 2)
			{
				$this->_aCurrentData[$iCurrentDataPosition][] = 'Small ' . $this->_prepareString($oProperty->name);
				$this->_iSiteuser_Properties_Count++;
			}
		}
	}

	/**
	 * Get person data
	 * @param Siteuser_Person_Model $oSiteuser_Person
	 */
	protected function _person($oSiteuser_Person)
	{
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->name));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->surname));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->patronymic));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->post));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->birthday != '0000-00-00' ? Core_Date::sql2date($oSiteuser_Person->birthday) : ''));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->sex == 0 ? Core::_('Admin_Form.male') : Core::_('Admin_Form.female')));
		$this->_aData[] = $oSiteuser_Person->image == '' ? '' : sprintf('"%s"', $oSiteuser_Person->getImageFileHref());
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->country));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->postcode));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->city));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Person->address));

		$aDataPhones = $aDataEmails = $aDataSocials = $aDataMessengers = $aDataWebsites = array();

		// Directory_Phones
		$aDirectory_Phones = $oSiteuser_Person->Directory_Phones->findAll(FALSE);
		foreach ($aDirectory_Phones as $oDirectory_Phone)
		{
			$aDataPhones[] = $oDirectory_Phone->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataPhones)));

		// Directory_Emails
		$aDirectory_Emails = $oSiteuser_Person->Directory_Emails->findAll(FALSE);
		foreach ($aDirectory_Emails as $oDirectory_Email)
		{
			$aDataEmails[] = $oDirectory_Email->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataEmails)));

		// Directory_Socials
		$aDirectory_Socials = $oSiteuser_Person->Directory_Socials->findAll(FALSE);
		foreach ($aDirectory_Socials as $oDirectory_Social)
		{
			$aDataSocials[] = $oDirectory_Social->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataSocials)));

		// Directory_Messengers
		$aDirectory_Messengers = $oSiteuser_Person->Directory_Messengers->findAll(FALSE);
		foreach ($aDirectory_Messengers as $oDirectory_Messenger)
		{
			$aDataMessengers[] = $oDirectory_Messenger->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataMessengers)));

		// Directory_Websites
		$aDirectory_Websites = $oSiteuser_Person->Directory_Websites->findAll(FALSE);
		foreach ($aDirectory_Websites as $oDirectory_Website)
		{
			$aDataWebsites[] = $oDirectory_Website->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataWebsites)));
	}

	/**
	 * Get company data
	 * @param Siteuser_Company_Model $oSiteuser_Company
	 */
	protected function _company($oSiteuser_Company)
	{
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->name));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->description));
		$this->_aData[] = $oSiteuser_Company->image == '' ? '' : sprintf('"%s"', $oSiteuser_Company->getImageFileHref());
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->headcount));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->annual_turnover));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->business_area));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->tin));
		$this->_aData[] = sprintf('"%s"', $this->_prepareString($oSiteuser_Company->bank_account));

		$aDataAddresses = $aDataPhones = $aDataEmails = $aDataSocials = $aDataMessengers = $aDataWebsites = array();

		// Directory_Addresses
		$aDirectory_Addresses = $oSiteuser_Company->Directory_Addresses->findAll(FALSE);
		foreach ($aDirectory_Addresses as $oDirectory_Address)
		{
			$aDataAddresses[] = $oDirectory_Address->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataAddresses)));

		// Directory_Phones
		$aDirectory_Phones = $oSiteuser_Company->Directory_Phones->findAll(FALSE);
		foreach ($aDirectory_Phones as $oDirectory_Phone)
		{
			$aDataPhones[] = $oDirectory_Phone->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataPhones)));

		// Directory_Emails
		$aDirectory_Emails = $oSiteuser_Company->Directory_Emails->findAll(FALSE);
		foreach ($aDirectory_Emails as $oDirectory_Email)
		{
			$aDataEmails[] = $oDirectory_Email->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataEmails)));

		// Directory_Socials
		$aDirectory_Socials = $oSiteuser_Company->Directory_Socials->findAll(FALSE);
		foreach ($aDirectory_Socials as $oDirectory_Social)
		{
			$aDataSocials[] = $oDirectory_Social->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataSocials)));

		// Directory_Messengers
		$aDirectory_Messengers = $oSiteuser_Company->Directory_Messengers->findAll(FALSE);
		foreach ($aDirectory_Messengers as $oDirectory_Messenger)
		{
			$aDataMessengers[] = $oDirectory_Messenger->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataMessengers)));

		// Directory_Websites
		$aDirectory_Websites = $oSiteuser_Company->Directory_Websites->findAll(FALSE);
		foreach ($aDirectory_Websites as $oDirectory_Website)
		{
			$aDataWebsites[] = $oDirectory_Website->value;
		}
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(implode(', ', $aDataWebsites)));
	}

	/**
	 * Get property data
	 * @param Siteuser_Model $oSiteuser
	 * @param Property_Model $oProperty
	 * @param Property_Value_Model $oProperty_Value
	 */
	protected function _property($oSiteuser, $oProperty, $oProperty_Value)
	{
		$this->_aData[] = sprintf('"%s"', $this->_prepareString(!is_null($oProperty_Value)
			? ($oProperty->type != 2
				? ($oProperty->type == 3 && $oProperty_Value->value != 0 && Core::moduleIsActive('list')
					? $oProperty_Value->List_Item->value
					: ($oProperty->type == 8
						? Core_Date::sql2date($oProperty_Value->value)
						: ($oProperty->type == 9
							? Core_Date::sql2datetime($oProperty_Value->value)
							: ($oProperty->type == 5
								? Core_Entity::factory('Informationsystem_Item', $oProperty_Value->value)->name
								: ($oProperty->type == 12
									? Core_Entity::factory('Shop_Item', $oProperty_Value->value)->name
									: $oProperty_Value->value)))))
							: ($oProperty_Value->file == '' ? '' : $oProperty_Value->setHref($oSiteuser->getDirHref())->getLargeFileHref())
				)
			: ''));

		if ($oProperty->type == 2)
		{
			$this->_aData[] = !is_null($oProperty_Value)
				? ($oProperty_Value->file_small == '' ? '' : sprintf('"%s"', $oProperty_Value->getSmallFileHref()))
				: '';
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

		// Stop buffering
		//ob_get_clean();
		while (ob_get_level() > 0)
		{
			ob_end_flush();
		}

		header('Pragma: public');
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-Type: text/html; charset=utf-8');
		// Disable Nginx cache
		header('X-Accel-Buffering: no');
		header('Content-Encoding: none');

		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Transfer-Encoding: binary');
		header("Content-Disposition: attachment; filename = " . 'siteusers_' . date("Y_m_d_H_i_s") . '.csv' . ";");

		// Автоматический сброс буфера при каждом выводе
		ob_implicit_flush(TRUE);

		// Устанавливаем лимит времени выполнения в 1 час
		(!defined('DENY_INI_SET') || !DENY_INI_SET)
			&& function_exists('set_time_limit') && ini_get('safe_mode') != 1 && @set_time_limit(3600);

		foreach ($this->_aCurrentData as $aData)
		{
			$this->_printRow($aData);
		}

		$offset = 0;
		$limit = 500;

		do {
			$oSiteusers = $this->_oSite->Siteusers;
			$oSiteusers->queryBuilder()
				->clearOrderBy()
				->orderBy('siteusers.id')
				->offset($offset)
				->limit($limit);

			$aSiteusers = $oSiteusers->findAll(FALSE);
			foreach ($aSiteusers as $oSiteuser)
			{
				$siteuser_type = $oSiteuser->siteuser_type_id
					? $oSiteuser->Siteuser_Type->name
					: Core::_('Siteuser.not');

				$siteuser_status = $oSiteuser->siteuser_status_id
					? $oSiteuser->Siteuser_Status->name
					: Core::_('Siteuser.not');

				$crm_source = $oSiteuser->crm_source_id
					? $oSiteuser->Crm_Source->name
					: Core::_('Siteuser.not');

				$siteuser_groups = '';

				$aTmp = array();
				$aSiteuser_Groups = $oSiteuser->Siteuser_Groups->findAll(FALSE);
				foreach ($aSiteuser_Groups as $oSiteuser_Group)
				{
					$aTmp[] = $oSiteuser_Group->name;
				}
				$siteuser_groups = implode(',', $aTmp);

				$this->_aData = array(
					sprintf('"%s"', $this->_prepareString($oSiteuser->id)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->login)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->email)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->datetime)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->ip)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->guid)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->active)),
					sprintf('"%s"', $this->_prepareString($oSiteuser->last_activity)),
					sprintf('"%s"', $this->_prepareString($siteuser_type)),
					sprintf('"%s"', $this->_prepareString($siteuser_status)),
					sprintf('"%s"', $this->_prepareString($crm_source)),
					sprintf('"%s"', $this->_prepareString($siteuser_groups)),
				);

				// People
				$aSiteuser_People = $oSiteuser->Siteuser_People->findAll(FALSE);
				count($aSiteuser_People)
					? $this->_person(array_shift($aSiteuser_People))
					: $this->_aData = array_pad($this->_aData, 28, '""'); // 12 + 16

				// Company
				$aSiteuser_Companies = $oSiteuser->Siteuser_Companies->findAll(FALSE);
				count($aSiteuser_Companies)
					? $this->_company(array_shift($aSiteuser_Companies))
					: $this->_aData = array_pad($this->_aData, 42, '""'); // 12 + 16 + 14

				// Дополнительные свойства, первые значения
				$aTotal_Property_Values = array();
				foreach ($this->_aSiteuser_Properties as $oProperty)
				{
					$aTotal_Property_Values[$oProperty->id] = $oProperty->getValues($oSiteuser->id, FALSE);

					$oProperty_Value = array_shift($aTotal_Property_Values[$oProperty->id]);

					$this->_property($oSiteuser, $oProperty, $oProperty_Value);
				}

				$this->_printRow($this->_aData);

				// Оставшиеся множественные свойства
				if (count($aTotal_Property_Values))
				{
					$countProperties = 0;

					foreach ($aTotal_Property_Values as $aProperty_Values)
					{
						if (count($aProperty_Values))
						{
							foreach ($aProperty_Values as $oProperty_Value)
							{
								$this->_aData = array(
									sprintf('"%s"', $this->_prepareString($oSiteuser->id)),
									sprintf('"%s"', $this->_prepareString($oSiteuser->login)),
									'""',
									'""',
									'""',
									sprintf('"%s"', $this->_prepareString($oSiteuser->guid))
								);

								$this->_aData = array_pad($this->_aData, 42 + $countProperties, '""');

								$this->_property($oSiteuser, $oProperty_Value->Property, $oProperty_Value);

								$this->_printRow($this->_aData);
							}
						}

						$countProperties++;
					}
				}

				// Additional people and companies
				$max = max(count($aSiteuser_People), count($aSiteuser_Companies));
				for ($i = 0; $i < $max; $i++)
				{
					// $this->_aData = array('""', '""', '""', '""', '""', '""', '""', '""', '""', '""', '""', '""'); // 12

					// 12
					$this->_aData = array(
						sprintf('"%s"', $this->_prepareString($oSiteuser->id)),
						sprintf('"%s"', $this->_prepareString($oSiteuser->login)),
						'""',
						'""',
						'""',
						sprintf('"%s"', $this->_prepareString($oSiteuser->guid)),
						'""',
						'""',
						'""',
						'""',
						'""',
						'""'
					);

					isset($aSiteuser_People[$i])
						? $this->_person($aSiteuser_People[$i])
						: array_pad($this->_aData, 28, '""'); // 12 + 16

					isset($aSiteuser_Companies[$i])
						? $this->_company($aSiteuser_Companies[$i])
						: array_pad($this->_aData, 42, '""'); // 12 + 16 + 14

					$this->_printRow($this->_aData);
				}
			}

			$offset += $limit;

			//break;
		}
		while (count($aSiteusers));

		exit();
	}

	/**
	 * Prepare string
	 * @param mixed $string
	 * @return string
	 */
	protected function _prepareString($string)
	{
		return is_scalar($string)
			? str_replace('"', '""', trim($string))
			: '';
	}

	/**
	 * Print array
	 * @param array $aData
	 * @return self
	 */
	protected function _printRow($aData)
	{
		//echo Core_Str::iconv('UTF-8', 'Windows-1251', implode(';', $aData) . "\n");
		echo implode(';', $aData) . "\n";
		return $this;
	}
}