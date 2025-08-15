<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Controller_Merge
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Controller_Merge extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'title', // Form Title
		'selectCaption', // Select caption, e.g. 'Choose a group'
		'selectOptions', // Array of options
		'buttonName', // Button name, e.g. 'Move'
		'skipColumns' // Array of skipped columns
	);

	/**
	 * Constructor.
	 * @param Admin_Form_Action_Model $oAdmin_Form_Action action
	 */
	public function __construct(Admin_Form_Action_Model $oAdmin_Form_Action)
	{
		parent::__construct($oAdmin_Form_Action);

		// Set default title
		$this->title(
			$this->_Admin_Form_Action->Admin_Word->getWordByLanguage(
				Core_Entity::factory('Admin_Language')->getCurrent()->id
			)->name
		);

		$this->buttonName(Core::_('Admin_Form.apply'));
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		if (is_null($operation))
		{
			// Original windowId
			$windowId = $this->_Admin_Form_Controller->getWindowId();

			$newWindowId = 'Move_' . time();

			$oCore_Html_Entity_Form = Core_Html_Entity::factory('Form');

			$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
				->id($newWindowId)
				->add($oCore_Html_Entity_Form);

			$oCore_Html_Entity_Form->action($this->_Admin_Form_Controller->getPath())
				->method('post');

			$window_Admin_Form_Controller = clone $this->_Admin_Form_Controller;

			// Select на всплывающем окне должен быть найден через ID нового окна, а не id_content
			$window_Admin_Form_Controller->window($newWindowId);

			$placeholder = Core::_('Siteuser.select_siteuser');
			$language = Core_I18n::instance()->getLng();

			ob_start();
			?>
			<select id="destinationId" name="destinationId">
				<option></option>
			</select>
			<script>
				$('#<?php echo $newWindowId?> #destinationId').selectSiteuser({
					url: hostcmsBackend + "/siteuser/index.php?loadSiteusers&types[]=siteuser",
					language: '<?php echo $language?>',
					placeholder: '<?php echo $placeholder?>',
					dropdownParent: $('.siteuser-merge-modal')
				});
				$("#<?php echo $newWindowId?> .select2-container").css('width', '100%');
			</script>
			<?php

			$oCore_Html_Entity_Form->add(Core_Html_Entity::factory('Code')->value(ob_get_clean()));

			// Идентификаторы переносимых указываем скрытыми полями в форме, чтобы не превысить лимит GET
			$aChecked = $this->_Admin_Form_Controller->getChecked();

			// Clear checked list
			$this->_Admin_Form_Controller->clearChecked();

			// $aIds = array();

			foreach ($aChecked as $datasetKey => $checkedItems)
			{
				foreach ($checkedItems as $key => $value)
				{
					$oCore_Html_Entity_Form->add(
						Core_Html_Entity::factory('Input')
							->name('hostcms[checked][' . $datasetKey . '][' . $key . ']')
							->value(1)
							->type('hidden')
							//->controller($window_Admin_Form_Controller)
					);
				}
			}

			$oAdmin_Form_Entity_Button = Admin_Form_Entity::factory('Button')
				->name('apply')
				->type('submit')
				->class('applyButton btn btn-blue')
				->value($this->buttonName)
				->onclick(
					//'$("#' . $newWindowId . '").parents(".modal").remove(); '
					'bootbox.hideAll(); '
					. $this->_Admin_Form_Controller->getAdminSendForm(array('operation' => 'apply'))
				)
				->controller($this->_Admin_Form_Controller);

			$oCore_Html_Entity_Form
				->add(
					Admin_Form_Entity::factory('Div')
						// ->class('form-group col-xs-12')
						->class('margin-top-10')
						->add($oAdmin_Form_Entity_Button)
				);

			$oCore_Html_Entity_Div->execute();

			ob_start();

			Core_Html_Entity::factory('Script')
				->value("$(function() {
				$('#{$newWindowId}').HostCMSWindow({ autoOpen: true, destroyOnClose: false, title: '" . Core_Str::escapeJavascriptVariable($this->title) . "', AppendTo: '#{$windowId}', width: 750, height: 140, className: 'siteuser-merge-modal', addContentPadding: true, modal: false, Maximize: false, Minimize: false }); });")
				->execute();

			$this->addMessage(ob_get_clean());

			// Break execution for other
			return TRUE;
		}
		else
		{
			$destinationId = Core_Array::getPost('destinationId', 0, 'int');

			if (!$destinationId)
			{
				throw new Core_Exception("destinationId is NULL");
			}

			if ($this->_object->id != $destinationId)
			{
				$oSiteuser = Core_Entity::factory('Siteuser')->getById($destinationId);

				if (!is_null($oSiteuser))
				{
					$oSiteuser->merge($this->_object);
				}
			}
		}

		return $this;
	}
}