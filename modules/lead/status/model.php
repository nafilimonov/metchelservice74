<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Status_Model
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Status_Model extends Core_Entity
{
	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'lead' => array(),
		'lead_step' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'user' => array(),
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'lead_statuses.sorting' => 'ASC'
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
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function nameBackend()
	{
		return '<i class="fa fa-circle" style="margin-right: 5px; color: ' . ($this->color ? htmlspecialchars($this->color) : '#aebec4') . '"></i> '
			. '<span class="editable" id="apply_check_0_' . $this->id . '_fv_1524">' . htmlspecialchars($this->name) . '</span>';
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		switch ($this->type)
		{
			case 0:
			default:
				$color = '#999';
			break;
			case 1:
				$color = '#05a805';
			break;
			case 2:
				$color = '#f50a2d';
			break;
		}

		echo '<span class="badge badge-square margin-left-5" style="background-color: ' . Core_Str::hex2lighter($color, 0.88) . '; color: ' . $color . '">' . Core::_('Lead_Status.type_' . $this->type) . '</span>';

		if (Core::moduleIsActive('bot'))
		{
			$oModule = Core_Entity::factory('Module')->getByPath('lead');

			$aBot_Modules = Bot_Controller::getBotModules($oModule->id, 0, $this->id);

			foreach ($aBot_Modules as $oBot_Module)
			{
				$oBot = $oBot_Module->Bot;

				$sParents = $oBot->bot_dir_id
					? $oBot->Bot_Dir->dirPathWithSeparator() . ' → '
					: '';

				Core_Html_Entity::factory('Span')
					->class('badge badge-square badge-hostcms')
					->value('<i class="fa fa-android"></i> ' . $sParents . htmlspecialchars($oBot->name))
					->execute();
			}
		}
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event lead_status.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Lead_Steps->deleteAll(FALSE);

		Core_QueryBuilder::update('leads')
			->set('lead_status_id', 0)
			->where('lead_status_id', '=', $this->id)
			->execute();

		if (Core::moduleIsActive('bot'))
		{
			$oModule = Core_Entity::factory('Module')->getByPath('lead');

			if ($oModule)
			{
				$aBot_Modules = Bot_Controller::getBotModules($oModule->id, 0, $this->id);

				foreach ($aBot_Modules as $oBot_Module)
				{
					$oBot_Module->delete();
				}
			}
		}

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event lead_status.onBeforeGetRelatedSite
	 * @hostcms-event lead_status.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}