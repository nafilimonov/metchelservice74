<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Note_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Note_Controller_Edit extends Crm_Note_Controller_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$iSiteuserId = Core_Array::getGet('siteuser_id', 0, 'int');

		$this->_relatedObject = Core_Entity::factory('Siteuser')->getById($iSiteuserId);

		if (is_null($this->_relatedObject))
		{
			throw new Core_Exception('_relatedObject is NULL.');
		}

		return parent::setObject($object);
	}

	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title($this->_object->id
			? Core::_('Siteuser_Note.edit_title')
			: Core::_('Siteuser_Note.add_title'));

		$oMainTab = $this->getTab('main');
		$oMainTab
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainRow4->add(
			Admin_Form_Entity::factory('Code')
				->html('<input type="hidden" name="siteuser_id" value="' . $this->_relatedObject->id .'" />')
			);

		return $this;
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		/*$iSiteusertId = intval(Core_Array::getGet('siteuser_id'));

		$sJsRefresh = '<script>
			// Refresh siteuser notes list
			if ($("#id_content_timeline").length)
			{
				$.adminLoad({ path: hostcmsBackend + \'/siteuser/timeline/index.php\', additionalParams: \'siteuser_id=' . $iSiteusertId . '\', windowId: \'id_content_timeline\' });
			}
			if ($("#id_content_notes").length)
			{
				$.adminLoad({ path: hostcmsBackend + \'/siteuser/note/index.php\', additionalParams: \'siteuser_id=' . $iSiteusertId . '\', windowId: \'id_content_notes\' });
			}
		</script>';*/

		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$aExplodeWindowId = explode('-', $windowId);

		$sJsRefresh = "<script>
			var jA = $('#" . $aExplodeWindowId[0] . " li[data-type=timeline] a');
			// console.log(jA);
			if (jA.length)
			{
				$.adminLoad({ path: jA.data('path'), additionalParams: jA.data('additional'), windowId: jA.data('window-id') });
			}

			var jANote = $('#" . $aExplodeWindowId[0] . " li[data-type=note] a');
			// console.log(jANote);
			if (jANote.length)
			{
				$.adminLoad({ path: jANote.data('path'), additionalParams: jANote.data('additional'), windowId: jANote.data('window-id') });
			}
		</script>";

		switch ($operation)
		{
			case 'saveModal':
				$this->addMessage($sJsRefresh);
			break;
			case 'applyModal':
				$this->addContent($sJsRefresh);
			break;
		}

		return parent::execute($operation);
	}
}