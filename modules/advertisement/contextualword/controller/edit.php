<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Contextualword Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Contextualword_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		if (!$object->id)
		{
			$object->advertisement_id = intval(Core_Array::getGet('advertisement_id'));
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

		$this->title(
			$this->_object->id
				? Core::_('Advertisement_Contextualword.title_edit')
				: Core::_('Advertisement_Contextualword.title_add')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		// <select> со списком структуры для всплывающих баннеров
		$oAdmin_Form_Entity_Select_Advertisement = Admin_Form_Entity::factory('Select');
		$oAdmin_Form_Entity_Select_Advertisement
			->options(
				$this->_fillAdvertisementList($this->_object->Advertisement->site_id)
			)
			->name('advertisement_id')
			->value($this->_object->advertisement_id)
			->caption(Core::_('Advertisement_Contextualword.advertisement'));

		$oAdditionalTab->delete($this->getField('advertisement_id'));

		$oMainTab->addBefore($oAdmin_Form_Entity_Select_Advertisement, $this->getField('value'));

		//	При редактировании контекстной фразы выводит <input> вместо <textarea>
		if ($this->_object->id)
		{
			$oAdmin_Form_Entity_Input_Value = Admin_Form_Entity::factory('Input');
			$oAdmin_Form_Entity_Input_Value
				->name('value')
				->value($this->_object->value)
				->caption(Core::_('Advertisement_Contextualword.value'));

			$oMainTab->delete($this->getField('value'));

			$oMainTab->addAfter($oAdmin_Form_Entity_Input_Value, $oAdmin_Form_Entity_Select_Advertisement);
		}

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Advertisement_Contextualword_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$id = $this->_object->id;

		if (!$id)
		{
			$sValue = trim(Core_Array::getPost('value'));

			// Массив контекстных фраз
			$aWords = explode("\n", $sValue);

			// Значение для первой контекстной фразы
			$this->_formValues['value'] = array_shift($aWords);
		}

		$this->_formValues['value'] = trim($this->_formValues['value']);

		if (!empty($this->_formValues['value']))
		{
			parent::_applyObjectProperty();
		}

		if (!$id)
		{
			foreach ($aWords as $sWord)
			{
				$sWord = trim($sWord);

				if (!empty($sWord))
				{
					$oNewWord = clone $this->_object;

					$oNewWord->value = $sWord;
					$oNewWord->save();
				}
			}
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}

	/**
	 * Формирует список баннеров
	 * @param int $site_id site ID
	 * @return array
	 */
	protected function _fillAdvertisementList($site_id)
	{
		$site_id = intval($site_id);

		$aAdvertisement = Core_Entity::factory('Advertisement')->getBySiteId($site_id);

		$aResult = array();

		foreach ($aAdvertisement as $oAdvertisement)
		{
			$aResult[$oAdvertisement->id] = $oAdvertisement->name;
		}

		return $aResult;
	}
}