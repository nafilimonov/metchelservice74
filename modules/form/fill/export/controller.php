<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Fill_Export_Controller
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Form_Fill_Export_Controller extends Core_Servant_Properties
{
	/**
	 * Form object
	 * @var Form_Model
	 */
	private $_Form = NULL;

	/**
	 * CSV data
	 * @var array
	 */
	private $_aCurrentData = array();

	/**
	 * @var array
	 */
	protected $_aForm_Fields = array();

	/**
	 * @var array
	 */
	protected $_aForm_Fields_List = array();

	/**
	 * @var array
	 */
	protected $_aForm_Field_Dir_Tree = array();

	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'from',
		'to'
	);

	/**
	 * Constructor.
	 * @param object $oForm Form_Model object
	 */
	public function __construct(Form_Model $oForm)
	{
		parent::__construct();

		$this->_Form = $oForm;

		$aForm_Fields = $this->_Form->Form_Fields->findAll(FALSE);
		foreach ($aForm_Fields as $oForm_Field)
		{
			$this->_aForm_Fields[$oForm_Field->form_field_dir_id][] = $oForm_Field;
		}

		$aForm_Field_Dirs = $this->_Form->Form_Field_Dirs->findAll(FALSE);
		foreach ($aForm_Field_Dirs as $oForm_Field_Dir)
		{
			$this->_aForm_Field_Dir_Tree[$oForm_Field_Dir->parent_id][] = $oForm_Field_Dir;
		}

		$this->_createFormFieldList(0);

		$aTmp = array(
			'"' . Core::_('Form_Fill_Export.id') . '"',
			'"' . Core::_('Form_Fill_Export.datetime') . '"',
			'"' . Core::_('Form_Fill_Export.ip') . '"',
			'"' . Core::_('Form_Fill_Export.read') . '"',
			'"' . Core::_('Form_Fill_Export.status') . '"'
		);

		foreach ($this->_aForm_Fields_List as $oForm_Field)
		{
			$aTmp[] = $oForm_Field->caption;
		}

		$this->_aCurrentData[] = $aTmp;
	}

	/**
	 * Create list of fields depends on the tree and sorting
	 * @return self
	 */
	protected function _createFormFieldList($dir_id)
	{
		if (isset($this->_aForm_Field_Dir_Tree[$dir_id]))
		{
			foreach ($this->_aForm_Field_Dir_Tree[$dir_id] as $subDir)
			{
				$this->_createFormFieldList($subDir->id);
			}
		}

		if (isset($this->_aForm_Fields[$dir_id]))
		{
			foreach ($this->_aForm_Fields[$dir_id] as $oForm_Field)
			{
				$this->_aForm_Fields_List[] = $oForm_Field;
			}
		}

		return $this;
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

		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename = "' . $this->_Form->name . '_' . date("Y_m_d_H_i_s") . '.csv' . '";');
		header('Content-Transfer-Encoding: binary');

		foreach ($this->_aCurrentData as $aData)
		{
			$this->_printRow($aData);
		}

		$offset = 0;
		$limit = 100;

		do {
			$oForm_Fills = $this->_Form->Form_Fills;
			$oForm_Fills->queryBuilder()
				->clearOrderBy()
				->orderBy('form_fills.id', 'DESC')
				->offset($offset)
				->limit($limit);

			$this->from != ''
				&& $oForm_Fills->queryBuilder()->where('datetime', '>=', $this->from);

			$this->to != ''
				&& $oForm_Fills->queryBuilder()->where('datetime', '<=', $this->to);

			$aForm_Fills = $oForm_Fills->findAll(FALSE);

			foreach ($aForm_Fills as $oForm_Fill)
			{
				$read = $oForm_Fill->read
					? Core::_('Admin_Form.yes')
					: Core::_('Admin_Form.no');

				$status = $oForm_Fill->form_status_id
					? $oForm_Fill->Form_Status->name
					: Core::_('Admin.none');

				$aForm_Fill_Fields = $oForm_Fill->Form_Fill_Fields->findAll();

				$aData = array(
					sprintf('"%s"', $this->_prepareString($oForm_Fill->id)),
					sprintf('"%s"', $this->_prepareString(Core_Date::sql2datetime($oForm_Fill->datetime))),
					sprintf('"%s"', $this->_prepareString($oForm_Fill->ip)),
					sprintf('"%s"', $this->_prepareString($read)),
					sprintf('"%s"', $this->_prepareString($status))
				);

				$aTmp = array();
				foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
				{
					$aTmp[$oForm_Fill_Field->form_field_id] = $oForm_Fill_Field;
				}

				foreach ($this->_aForm_Fields_List as $oForm_Field)
				{
					$value = '';
					if (isset($aTmp[$oForm_Field->id]))
					{
						/*switch($oForm_Field->type)
						{
							case 0:
							case 1:
							default:
								$value = $aTmp[$oForm_Field->id]->value;
							break;
							case 3:

							break;
						}*/

						$value = $aTmp[$oForm_Field->id]->value;
					}

					$aData[] = sprintf('"%s"', $this->_prepareString($value));
				}

				$this->_printRow($aData);
			}

			$offset += $limit;
		}
		while (count($aForm_Fills));

		exit();
	}

	/**
	 * Prepare string
	 * @param string $string
	 * @return string
	 */
	protected function _prepareString($string)
	{
		return str_replace('"', '""', trim($string));
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