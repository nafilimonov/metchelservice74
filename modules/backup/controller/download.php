<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Backup
 *
 * @package HostCMS
 * @subpackage Backup
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Backup_Controller_Download extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'title',
		'cloud_id'
	);

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		$oUser = Core_Auth::getCurrentUser();
		if (!$oUser->superuser && $oUser->only_access_my_own)
		{
			return FALSE;
		}

		if (is_null($operation))
		{
			$newWindowId = 'Backup_' . time();

			$oCore_Html_Entity_Form = Core_Html_Entity::factory('Form');

			// Clear checked list
			$this->_Admin_Form_Controller->clearChecked();

			$filename = $this->_object->hash;
			$sourcename = $this->_object->name;

			$oCore_Html_Entity_Form
				->add(Core::factory('Admin_Form_Entity_Code')
				->html('
					<h6>' . Core::_('Backup.file_download', $sourcename) . '</h6>
					<div class="progress progress-striped active">
						<div class="progress-bar progress-bar-success" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: 0">
							<span></span>
						</div>
					</div>
				'))
				->add(Core::factory('Admin_Form_Entity_Code')->html('
				<script>
					var percent = 0;
					var stop = false;
					function getFilePart()
					{
						$.ajax({
							type: "POST",
							url: "' . $this->_Admin_Form_Controller->getPath() . '",
							data: "cloud_id=' . $this->cloud_id . '&download=true&file=' . $filename . '&sourcename=' . $sourcename . '",
							success: function(data){
								var parentDiv = $(".progress-bar", "#' . $newWindowId . '"),
									targetSpan = $("span", parentDiv),
									width = parentDiv.width();

								if (typeof data.error != \'undefined\')
								{
									stop = true;

									$("div#id_message").html(data.error);
								}

								if (data == 0 || stop)
								{
									// $("#' . $newWindowId . '").remove();
									bootbox.hideAll();
								}
								else
								{
									percent = data|0;
									parentDiv.width(percent + \'%\');
									targetSpan.html(percent + \'%\');
									getFilePart();
								}
							},
							dataType: "json"
						});
					}
					getFilePart();
				</script>'));

			$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
				->id($newWindowId)
				->add($oCore_Html_Entity_Form);

			$oCore_Html_Entity_Div->execute();

			ob_start();

			$windowId = $this->_Admin_Form_Controller->getWindowId();

			Core_Html_Entity::factory('Script')
				->value("$(function() {
				$('#{$newWindowId}').HostCMSWindow({ autoOpen: true, destroyOnClose: false, title: '" . Core_Str::escapeJavascriptVariable($this->title) . "', AppendTo: '#{$windowId}', width: 750, height: 90, addContentPadding: true, modal: false, Maximize: false, Minimize: false, beforeclose: function(){stop=true;} }); });")
				->execute();

			$this->addMessage(ob_get_clean());

			// Break execution for other
			return TRUE;
		}

		return $this;
	}
}