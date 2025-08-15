<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// File download
if (Core_Array::getGet('downloadFile'))
{
	$oSiteuser_Email_Attachment = Core_Entity::factory('Siteuser_Email_Attachment')->find(intval(Core_Array::getGet('downloadFile')));
	if (!is_null($oSiteuser_Email_Attachment->id) && $oSiteuser_Email_Attachment->Siteuser_Email->Siteuser->site_id == CURRENT_SITE)
	{
		$filePath = $oSiteuser_Email_Attachment->getFilePath();
		Core_File::download($filePath, $oSiteuser_Email_Attachment->name, array('content_disposition' => 'inline'));
	}
	else
	{
		throw new Core_Exception('Access denied');
	}
	exit();
}

$iAdmin_Form_Id = 276;
$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Путь к контроллеру формы ЦА
$sAdminFormAction = '/{admin}/siteuser/email/index.php';

$pageTitle = Core::_('Siteuser_Email.title');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($pageTitle)
	->pageTitle($pageTitle)
	->Admin_View(
		Admin_View::getClassName('Admin_Internal_View')
	)
	->addView('email', 'Siteuser_Controller_Email')
	->view('email');

$windowId = $oAdmin_Form_Controller->getWindowId();

$siteuser_id = intval(Core_Array::getGet('siteuser_id'));
// $siteuser_id && $windowId != 'id_content' && $oAdmin_Form_Controller->Admin_View(
// 	Admin_View::getClassName('Admin_Internal_View')
// );

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->class('btn btn-gray')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
		->onclick(
			// $oAdmin_Form_Controller->getAdminActionModalLoad($oAdmin_Form_Controller->getPath(), 'edit', 'modal', 0, 0)
			$oAdmin_Form_Controller->getAdminActionModalLoad(array('path' => $oAdmin_Form_Controller->getPath(), 'action' => 'edit', 'operation' => 'modal', 'datasetKey' => 0, 'datasetValue' => 0, 'width' => '90%'))
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

if (Core_Array::getPost('load_modal') && Core_Array::getPost('siteuser_email_id'))
{
	$aJSON = array();

	$siteuser_email_id = Core_Array::getPost('siteuser_email_id', 0, 'int');

	$oSiteuser_Email = Core_Entity::factory('Siteuser_Email')->getById($siteuser_email_id);

	if (!is_null($oSiteuser_Email))
	{
		$countAttachments = $oSiteuser_Email->Siteuser_Email_Attachments->getCount();

		if ($oSiteuser_Email->type == 0)
		{
			$finalMessage = $oSiteuser_Email->text;

			if (strpos($finalMessage, '<style>') === FALSE)
			{
				$finalMessage = '<style>body { font-family: \'Open Sans\',Arial; font-size: 10pt; color: #333; white-space: pre-wrap }</style>' . htmlspecialchars($finalMessage);
			}
		}
		else
		{
			$finalMessage = '<html><head><style>body { font-family: \'Open Sans\',Arial; font-size: 10pt; color: #333; }</style></head><body>' . $oSiteuser_Email->text .'</body></html>';
		}

		ob_start();
		?>
		<iframe id="frame<?php echo $oSiteuser_Email->id?>" sandbox="allow-same-origin" frameborder="0" width="100%" scrolling="no" srcdoc="<?php echo htmlspecialchars($finalMessage)?>"></iframe>
		<?php
		if ($countAttachments)
		{
			$aSiteuser_Email_Attachments = $oSiteuser_Email->Siteuser_Email_Attachments->findAll(FALSE);
			?>
			<div class="siteuser-mail-attachments">
				<h5><i class="fa fa-paperclip"></i> <?php echo Core::_('Siteuser_Email.attachments')?> <span>(<?php echo $countAttachments?>)</span></h5>
				<ul>
				<?php
				foreach ($aSiteuser_Email_Attachments as $oSiteuser_Email_Attachment)
				{
					$ext = Core_File::getExtension($oSiteuser_Email_Attachment->name);

					$iconFile = Admin_Form_Controller::correctBackendPath('/{admin}/images/icons/') . Core_Array::get(Core::$mainConfig['fileIcons'], $ext, 'file.gif');
					?>
					<li class="margin-right-10">
						<a href="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/email/index.php')?>?downloadFile=<?php echo $oSiteuser_Email_Attachment->id?>" target="_blank" class="name">
							<img class="margin-right-5" src="<?php echo $iconFile?>"><?php echo htmlspecialchars($oSiteuser_Email_Attachment->name)?><span> <?php echo $oSiteuser_Email_Attachment->getTextSize()?></span>
						</a>
					</li>
					<?php
				}
				?>
				</ul>
			</div>
			<?php
		}

		$aJSON['html'] = ob_get_clean();
	}

	Core::showJson($aJSON);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

// Действие "Копировать"
$oAdminFormActionCopy = $oAdmin_Form->Admin_Form_Actions->getByName('copy');

if ($oAdminFormActionCopy && $oAdmin_Form_Controller->getAction() == 'copy')
{
	$oSiteuser_Email_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Siteuser_Email_Controller_Edit', $oAdminFormActionCopy
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Email_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionEdit = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdminFormActionEdit && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oSiteuser_Email_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Siteuser_Email_Controller_Edit', $oAdminFormActionEdit
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Email_Controller_Edit);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser_Email')
);

$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('siteuser_id', '=', $siteuser_id)
	)
);

$oAdmin_Form_Dataset->changeAction('edit', 'modal', 1);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();
