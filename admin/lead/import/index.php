<?php
/**
 * Lead.
*
* @package HostCMS
* @version 7.x
* @author Hostmake LLC
* @copyright © 2005-2025, https://www.hostcms.ru
*/
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'lead');

$oSite = Core_Entity::factory('Site', CURRENT_SITE);

$oAdmin_Form_Controller = Admin_Form_Controller::create();

// Контроллер формы
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path('/{admin}/lead/import/index.php');

$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

ob_start();

$oAdmin_View = Admin_View::create();
$oAdmin_View
	->module(Core_Module_Abstract::factory($sModule))
	->pageTitle(Core::_('Lead.import'))
	;

// Первая крошка на список магазинов
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Lead.menu'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/lead/index.php')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/lead/index.php')
		)
);

// Крошка на текущую форму
$oAdmin_Form_Entity_Breadcrumbs->add(
Admin_Form_Entity::factory('Breadcrumb')
	->name(Core::_('Lead.import'))
	->href(
		$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL)
	)
	->onclick(
		$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL)
	)
);

// Формируем массивы данных
$aLangConstNames = array(
	Core::_('Lead_Exchange.!download'),

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
	Core::_('Lead_Exchange.website')
);

$aColors = array(
	'#999999',

	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',
	'#E7A1B0',

	'#92C7C7',
	'#92C7C7',
	'#92C7C7',
	'#92C7C7'
);

$aEntities = array(
	'',

	'lead_id',
	'lead_surname',
	'lead_name',
	'lead_patronymic',
	'lead_company',
	'lead_post',
	'lead_amount',
	'lead_birthday',
	'lead_need',
	'lead_maturity',
	'lead_source',
	'lead_shop',
	'lead_status',
	'lead_comment',
	'lead_last_contacted',

	'lead_address',
	'lead_phone',
	'lead_email',
	'lead_website'
);

if (Core::moduleIsActive('field'))
{
	$aItemFields = Field_Controller::getFields('lead', $oSite->id);
	foreach ($aItemFields as $oItemField)
	{
		$oFieldDir = $oItemField->Field_Dir;

		$aLangConstNames[] = $oItemField->name . " [" . ($oFieldDir->id ? $oFieldDir->name : Core::_('Informationsystem_Item.root_folder')) . "]";
		$aColors[] = "#BBCEF2";
		$aEntities[] = 'field-' . $oItemField->id;

		if ($oItemField->type == 2)
		{
			// Description
			$aLangConstNames[] = Core::_('Informationsystem_Item.import_file_description', $oItemField->name) . " [" . ($oFieldDir->id ? $oFieldDir->name : Core::_('Informationsystem_Item.root_folder')) . "]";
			$aColors[] = "#BBCEF2";
			$aEntities[] = 'fielddesc-' . $oItemField->id;

			// Small Image
			$aLangConstNames[] = Core::_('Informationsystem_Item.import_small_images', $oItemField->name) . " [" . ($oFieldDir->id ? $oFieldDir->name : Core::_('Informationsystem_Item.root_folder')) . "]";
			$aColors[] = "#BBCEF2";
			$aEntities[] = 'fieldsmall-' . $oItemField->id;
		}
	}
}

$oUserCurrent = Core_Auth::getCurrentUser();

$oAdmin_Form_Entity_Form = Admin_Form_Entity::factory('Form')
		->controller($oAdmin_Form_Controller)
		->action($oAdmin_Form_Controller->getPath())
		->enctype('multipart/form-data');

$oAdmin_View->addChild($oAdmin_Form_Entity_Breadcrumbs);

// Количество полей
$iFieldCount = 0;

$sOnClick = NULL;

if ($oAdmin_Form_Controller->getAction() == 'show_form')
{
	if (!$oUserCurrent->read_only && !$oUserCurrent->only_access_my_own)
	{
		/*$sFileName = isset($_FILES['csv_file']) && intval($_FILES['csv_file']['size']) > 0
			? $_FILES['csv_file']['tmp_name']
			: CMS_FOLDER . Core_Array::getPost('alternative_file_pointer');*/

		$sFileName = $sTmpPath = NULL;

		// Uploaded File
		if (isset($_FILES['csv_file']) && intval($_FILES['csv_file']['size']) > 0)
		{
			$sFileName = $_FILES['csv_file']['tmp_name'];
		}
		// External file
		else
		{
			$altFile = Core_Array::getPost('alternative_file_pointer');

			if (strpos($altFile, 'http://') === 0 || strpos($altFile, 'https://') === 0)
			{
				$Core_Http = Core_Http::instance('curl')
					->url($altFile)
					->port(80)
					->timeout(20)
					->execute();

				$aHeaders = $Core_Http->parseHeaders();

				if ($Core_Http->parseHttpStatusCode($aHeaders['status']) != 200)
				{
					Core_Message::show('Wrong status: ' . htmlspecialchars($aHeaders['status']), "error");
				}

				$sFileName = $sTmpPath = tempnam(CMS_FOLDER . TMP_DIR, 'CSV');

				Core_File::write($sTmpPath, $Core_Http->getDecompressedBody());
			}
			else
			{
				$sFileName = CMS_FOLDER . $altFile;
			}
		}

		if (Core_File::isFile($sFileName) && is_readable($sFileName))
		{
			Core_Event::notify('Lead_Import.oBeforeImportCSV', NULL, array($sFileName));

			// Обработка CSV-файла
			$sTmpFileName = CMS_FOLDER . TMP_DIR . 'file_' . time() . '.csv';

			try {
				Core_File::upload($sFileName, $sTmpFileName);

				if ($fInputFile = fopen($sTmpFileName, 'rb'))
				{
					$sSeparator = Core_Array::getPost('import_separator');

					switch ($sSeparator)
					{
						case 0:
							$sSeparator = ',';
						break;
						case 1:
						default:
							$sSeparator = ';';
						break;
						case 2:
							$sSeparator = "\t";
						break;
						case 3:
							$sSeparator = Core_Array::getPost('import_separator_text');
						break;
					}

					$sLimiter = Core_Array::getPost('import_stop');

					switch ($sLimiter)
					{
						case 0:
						default:
							$sLimiter = '"';
						break;
						case 1:
							$sLimiter = Core_Array::getPost('import_stop_text');
						break;
					}

					$sLocale = Core_Array::getPost('import_encoding');
					$oLead_Exchange_Import_Controller = new Lead_Exchange_Import_Controller($oSite);

					$oLead_Exchange_Import_Controller
						->encoding($sLocale)
						->separator($sSeparator)
						->limiter($sLimiter);

					$aCsvLine = $oLead_Exchange_Import_Controller->getCSVLine($fInputFile);

					$iFieldCount = is_array($aCsvLine) ? count($aCsvLine) : 0;

					fclose($fInputFile);

					if ($iFieldCount)
					{
						$iValuesCount = count($aLangConstNames);

						$pos = 0;

						$oMainTab = Admin_Form_Entity::factory('Tab')->name('main');

						for($i = 0; $i < $iFieldCount; $i++)
						{
							$oCurrentRow = Admin_Form_Entity::factory('Div')->class('row');

							$oCurrentRow
								->add(Admin_Form_Entity::factory('Span')
									//->caption('')
									->value($aCsvLine[$i])
									->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
									);

							$aOptions = array();

							$isset_selected = FALSE;

							// Генерируем выпадающий список с цветными элементами
							for($j = 0; $j < $iValuesCount; $j++)
							{
								$aCsvLine[$i] = trim($aCsvLine[$i]);

								if (!$isset_selected
								&& (mb_strtolower($aCsvLine[$i]) == mb_strtolower($aLangConstNames[$j])
								|| (strlen($aLangConstNames[$j]) > 0
								&& strlen($aCsvLine[$i]) > 0
								&&
								(strpos($aCsvLine[$i], $aLangConstNames[$j]) !== FALSE
								|| strpos($aLangConstNames[$j], $aCsvLine[$i]) !== FALSE)
								// Чтобы не было срабатывания "Город" -> "Городской телефон"
								// Если есть целиком подходящее поле
								&& !array_search($aCsvLine[$i], $aLangConstNames))
								))
								{
									$selected = $aEntities[$j];

									// Для исключения двойного указания selected для одного списка
									$isset_selected = TRUE;
								}
								elseif (!$isset_selected)
								{
									$selected = -1;
								}

								$aOptions[$aEntities[$j]] = array('value' => $aLangConstNames[$j], 'attr' => array('style' => 'background-color: ' . (!empty($aColors[$pos]) ? $aColors[$j] : '#000')));

								$pos++;
							}

							$pos = 0;

							$oCurrentRow->add(Admin_Form_Entity::factory('Select')
								->name("field{$i}")
								->options($aOptions)
								->value($selected)
								->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')));

							$oMainTab->add($oCurrentRow);
						}

						$oMainTab->add(Admin_Form_Entity::factory('Div')->class('row')
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('csv_filename')->value($sTmpFileName))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('import_separator')->value($sSeparator))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('import_stop')->value($sLimiter))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('firstlineheader')->value(isset($_POST['import_name_field_f']) ? 1 : 0))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('locale')->value($sLocale))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('import_max_time')->value(Core_Array::getPost('import_max_time')))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('import_max_count')->value(Core_Array::getPost('import_max_count')))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('import_load_files_path')->value(Core_Array::getPost('import_load_files_path')))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('import_action_items')->value(Core_Array::getPost('import_action_items')))
							->add(Core_Html_Entity::factory('Input')->type('hidden')->name('delete_field_values')->value(isset($_POST['delete_field_values']) ? 1 : 0))
						);

						$oAdmin_Form_Entity_Form->add($oMainTab);
					}
					else
					{
						throw new Core_Exception("File is empty!");
					}
				}
				else
				{
					throw new Core_Exception("Can't open file");
				}

			} catch (Exception $exc) {
				Core_Message::show($exc->getMessage(), "error");
			}

			$sOnClick = $oAdmin_Form_Controller->getAdminSendForm('start_import');
		}
		else
		{
			Core_Message::show(Core::_('Lead_Exchange.file_does_not_specified'), "error");
			$sOnClick = "";
		}
	}
	else
	{
		Core_Message::show(Core::_('User.demo_mode'), "error");
	}
}
elseif ($oAdmin_Form_Controller->getAction() == 'start_import')
{
	if (!$oUserCurrent->read_only)
	{
		Core_Session::start();

		if (isset($_SESSION['Lead_Exchange_Import_Controller']))
		{
			$oLead_Exchange_Import_Controller = $_SESSION['Lead_Exchange_Import_Controller'];
			unset($_SESSION['Lead_Exchange_Import_Controller']);

			$iNextSeekPosition = $oLead_Exchange_Import_Controller->seek;
		}
		else
		{
			$oLead_Exchange_Import_Controller = new Lead_Exchange_Import_Controller($oSite);

			$aConformity = array();

			foreach ($_POST as $iKey => $sValue)
			{
				if (mb_strpos($iKey, "field") === 0)
				{
					$aConformity[] = $sValue;
				}
			}

			$iNextSeekPosition = 0;

			$sCsvFilename = Core_Array::getPost('csv_filename');

			$oLead_Exchange_Import_Controller
				->file($sCsvFilename)
				->encoding(Core_Array::getPost('locale', 'UTF-8'))
				->csv_fields($aConformity)
				->time(Core_Array::getPost('import_max_time'))
				->step(Core_Array::getPost('import_max_count'))
				->separator(Core_Array::getPost('import_separator'))
				->limiter(Core_Array::getPost('import_stop'))
				->imagesPath(Core_Array::getPost('import_load_files_path'))
				->importAction(Core_Array::getPost('import_action_items'))
				->deleteFieldValues(Core_Array::getPost('delete_field_values') == 1)
			;

			if (Core_Array::getPost('firstlineheader', 0))
			{
				$fInputFile = fopen($oLead_Exchange_Import_Controller->file, 'rb');
				@fgetcsv($fInputFile, 0, $oLead_Exchange_Import_Controller->separator, $oLead_Exchange_Import_Controller->limiter);
				$iNextSeekPosition = ftell($fInputFile);
				fclose($fInputFile);
			}
		}

		$oLead_Exchange_Import_Controller->seek = $iNextSeekPosition;

		ob_start();

		if (($iNextSeekPosition = $oLead_Exchange_Import_Controller->import()) !== FALSE)
		{
			$oLead_Exchange_Import_Controller->seek = $iNextSeekPosition;

			$_SESSION['Lead_Exchange_Import_Controller'] = $oLead_Exchange_Import_Controller;

			$sRedirectAction = $oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/lead/import/index.php', 'start_import');

			showStat($oLead_Exchange_Import_Controller);
		}
		else
		{
			$sRedirectAction = "";
			Core_Message::show(Core::_('Lead_Exchange.msg_download_complete'));
			showStat($oLead_Exchange_Import_Controller);
		}

		$oAdmin_Form_Entity_Form->add(
			Admin_Form_Entity::factory('Code')->html(ob_get_clean())
		);

		Core_Session::close();

		if ($sRedirectAction)
		{
			$iRedirectTime = 1000;
			Core_Html_Entity::factory('Script')
				->type('text/javascript')
				->value('setTimeout(function (){ ' . $sRedirectAction . '}, ' . $iRedirectTime . ')')
				->execute();
		}

		$sOnClick = "";
	}
	else
	{
		Core_Message::show(Core::_('User.demo_mode'), "error");
	}
}
else
{
	$windowId = $oAdmin_Form_Controller->getWindowId();

	$oMainTab = Admin_Form_Entity::factory('Tab')->name('main');

	$aConfig = Core_Config::instance()->get('lead_exchange_csv', array()) + array(
		'maxTime' => 20,
		'maxCount' => 100
	);

	$oAdmin_Form_Entity_Form->add($oMainTab
		->add(
			Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('File')
				->name("csv_file")
				->caption(Core::_('Lead_Exchange.import_list_file'))
				->largeImage(array('show_params' => FALSE))
				->smallImage(array('show' => FALSE))
				->divAttr(array('class' => 'col-xs-12 col-sm-6 col-md-5'))
			)
			->add(
				Admin_Form_Entity::factory('Input')
					->name("alternative_file_pointer")
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-5'))
					->caption(Core::_('Lead_Exchange.alternative_file_pointer_form_import'))
			)
			->add(
				Admin_Form_Entity::factory('Select')
				->name("import_encoding")
				->options(array(
					'Windows-1251' => Core::_('Lead_Exchange.input_file_encoding0'),
					'UTF-8' => Core::_('Lead_Exchange.input_file_encoding1')
				))
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-4 col-md-2', 'id' => 'import_encoding'))
				->caption(Core::_('Lead_Exchange.import_encoding'))
			)
		)
		->add(Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('Checkbox')
			->name("import_name_field_f")
			->caption(Core::_('Lead_Exchange.import_list_name_field_f'))
			->value(TRUE)
			->divAttr(array('id' => 'import_name_field_f','class' => 'form-group col-xs-12'))))
		->add(Admin_Form_Entity::factory('Div')->class('row')
			->add(Admin_Form_Entity::factory('Radiogroup')
				->radio(array(
					Core::_('Lead_Exchange.import_separator1'),
					Core::_('Lead_Exchange.import_separator2'),
					Core::_('Lead_Exchange.import_separator3'),
					Core::_('Lead_Exchange.import_separator4')
				))
				->ico(array(
					'fa-solid fa-asterisk fa-fw',
					'fa-solid fa-asterisk fa-fw',
					'fa-solid fa-asterisk fa-fw',
					'fa-solid fa-asterisk fa-fw'
				))
				->caption(Core::_('Lead_Exchange.import_separator'))
				->divAttr(array('class' => 'no-padding-right rounded-radio-group form-group col-xs-10 col-sm-9', 'id' => 'import_separator'))
				->name('import_separator')
				// Разделитель ';'
				->value(1)
				->add(
					Admin_Form_Entity::factory('Input')
						->name("import_separator_text")
						// ->caption('&nbsp;')
						->size(3)
						->divAttr(array('id' => 'import_separator_text','class' => 'form-group d-inline-block margin-left-10')))
					)
				)
			/*->add(Admin_Form_Entity::factory('Code')
				->html("<script>$(function() {
					$('#{$windowId} #import_separator').buttonset();
				});</script>"))*/
		->add(Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('Radiogroup')
			->radio(array(
				Core::_('Lead_Exchange.import_stop1'),
				Core::_('Lead_Exchange.import_stop2')
			))
			->ico(array(
				'fa-solid fa-quote-right fa-fw',
				'fa-solid fa-bolt fa-fw'
			))
			->caption(Core::_('Lead_Exchange.import_stop'))
			->name('import_stop')
			->divAttr(array('class' => 'no-padding-right rounded-radio-group form-group col-xs-10 col-sm-9', 'id' => 'import_stop'))
			->add(Admin_Form_Entity::factory('Input')
				->name("import_stop_text")
				// ->caption('&nbsp;')
				->size(3)
				->divAttr(array('id' => 'import_stop_text','class' => 'form-group d-inline-block margin-left-10')))
			)
			/*->add(Admin_Form_Entity::factory('Code')
			->html("<script>$(function() {
				$('#{$windowId} #import_stop').buttonset();
			});</script>"))*/
			)
		->add(Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('Input')
			->name("import_load_files_path")
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
			->caption(Core::_('Lead_Exchange.import_images_path'))))
		->add(Admin_Form_Entity::factory('Div')->class('row')->add(
			Admin_Form_Entity::factory('Radiogroup')
				->radio(array(
					1 => Core::_('Lead_Exchange.import_action_items1'),
					2 => Core::_('Lead_Exchange.import_action_items2')
				))
				->ico(array(
					1 => 'fa-solid fa-refresh fa-fw',
					2 => 'fa-solid fa-ban fa-fw'
				))
				->caption(Core::_('Lead_Exchange.import_action_items'))
				->name('import_action_items')
				->divAttr(array('id' => 'import_action_items','class' => 'form-group col-xs-12 rounded-radio-group'))
				->value(1)
			)
		)
		/*->add(Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('Code')
			->html("<script>$(function() {
				$('#{$windowId} #import_action_items').buttonset();
			});</script>")))*/
		->add(Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('Checkbox')
			->name("delete_field_values")
			->class('form-control colored-danger times')
			->caption(Core::_('Lead_Exchange.delete_field_values'))
			->divAttr(array('class' => 'form-group col-xs-12'))
			->value(1))
		)
		->add(Admin_Form_Entity::factory('Div')->class('row')->add(Admin_Form_Entity::factory('Input')
			->name("import_max_time")
			->caption(Core::_('Lead_Exchange.import_max_time'))
			->value($aConfig['maxTime'])
			->divAttr(array('id' => 'import_max_time', 'class' => 'form-group col-xs-12 col-sm-6 col-md-3')))
			->add(Admin_Form_Entity::factory('Input')
			->name("import_max_count")
			->caption(Core::_('Lead_Exchange.import_max_count'))
			->value($aConfig['maxCount'])
			->divAttr(array('id' => 'import_max_count', 'class' => 'form-group col-xs-12 col-sm-6 col-md-3')))))
	;

	$sOnClick = $oAdmin_Form_Controller->getAdminSendForm('show_form');

	Core_Session::start();
	unset($_SESSION['csv_params']);
	unset($_SESSION['Lead_Exchange_Import_Controller']);
	Core_Session::close();
}

function showStat($oLead_Exchange_Import_Controller)
{
	echo Core::_('Lead_Exchange.count_insert_lead') . ' &#151; <b>' . $oLead_Exchange_Import_Controller->getInsertedLeadsCount() . '</b><br/>';
	echo Core::_('Lead_Exchange.count_update_lead') . ' &#151; <b>' . $oLead_Exchange_Import_Controller->getUpdatedLeadsCount() . '</b><br/>';
}

if ($sOnClick)
{
	$oAdmin_Form_Entity_Form->add(
		Admin_Form_Entity::factory('Button')
		->name('show_form')
		->type('submit')
		->value(Core::_('Lead_Exchange.import_button_load'))
		->class('applyButton btn btn-blue')
		->onclick($sOnClick)
	);
}

$oAdmin_Form_Entity_Form->execute();
$content = ob_get_clean();

ob_start();
$oAdmin_View
	->content($content)
	->show();

Core_Skin::instance()
	->answer()
	->ajax(Core_Array::getRequest('_', FALSE))
	//->content(iconv("UTF-8", "UTF-8//IGNORE//TRANSLIT", ob_get_clean()))
	->module($sModule)
	->content(ob_get_clean())
	->title(Core::_('Lead_Exchange.import'))
	->execute();