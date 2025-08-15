<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 100;
$sAdminFormAction = '/{admin}/counter/useragent/index.php';

$sCounterPath = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sTitle = Core::_('Counter.useragents');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sTitle)
	->pageTitle($sTitle);

$sFormPath = $oAdmin_Form_Controller->getPath();

// подключение верхнего меню
include CMS_FOLDER . Admin_Form_Controller::correctBackendPath('/{admin}/counter/menu.php');

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Counter.title'))
		->href($oAdmin_Form_Controller->getAdminLoadHref($sCounterPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sCounterPath, NULL, NULL, ''))
)
->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($sTitle)
		->href($oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, ''))
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

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

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Counter_Useragent')
);

(!isset($oAdmin_Form_Controller->request['admin_form_filter_from_421'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_from_421'] == '')
		&& $oAdmin_Form_Controller->request['admin_form_filter_from_421'] = Core_Date::timestamp2date(time());

(!isset($oAdmin_Form_Controller->request['admin_form_filter_to_421'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_to_421'] == '')
		&& $oAdmin_Form_Controller->request['admin_form_filter_to_421'] = Core_Date::timestamp2date(time());

$bSeveralDates = $oAdmin_Form_Controller->request['admin_form_filter_from_421'] != $oAdmin_Form_Controller->request['admin_form_filter_to_421'];

$oAdmin_Form_Dataset->addCondition(
	array('select' => array('counter_useragents.*', array($bSeveralDates ? 'SUM(count)' : 'count', 'dataCount')))
)
->addCondition(
	array('where' => array('site_id', '=', CURRENT_SITE))
)
->addCondition(
	array('where' => array('crawler', '=', 0))
);

$bSeveralDates && $oAdmin_Form_Dataset->addCondition(
	array('groupBy' => array('useragent'))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

class Counter_Useragent_Observer
{
	static public function onAfterSetConditionsAndLimits($controller)
	{
		$aSetting = Core_Array::get(Core::$config->get('counter_setting'), 'setting', array());

		$oAdmin_Form_Controller = $controller->getAdminFormController();

		$oDataset = $oAdmin_Form_Controller->getDataset(0);

		$aObjects = $oDataset->load();

		if (count($aObjects))
		{
			$aObjects = array_slice($aObjects, 0, 12);
			
			$sWindowId = $oAdmin_Form_Controller->getWindowId();
			$aColors = Core_Array::get(Core::$config->get('counter_color'), 'Pie3D', array());
			?><div class="col-xs-12 col-lg-8">
				<div class="widget counter">
					<div class="widget-body">
						<div class="row">
							<div class="col-xs-12">
								<div id="userAgentsDiagram" class="chart"></div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<script type="text/javascript">
			$(function(){
				var aScripts = [
					'jquery.flot.js',
					'jquery.flot.time.min.js',
					'jquery.flot.categories.min.js',
					'jquery.flot.tooltip.min.js',
					'jquery.flot.crosshair.min.js',
					'jquery.flot.selection.min.js',
					'jquery.flot.pie.min.js',
					'jquery.flot.resize.js'
				];

				$.getMultiContent(aScripts, '/modules/skin/bootstrap/js/charts/flot/').done(function() {
					var diagramData = [];
					<?php
					$i = 0;
					foreach ($aObjects as $key => $oObject)
					{
						?>
						diagramData.push(
							{
								label:'<?php echo Core_Str::escapeJavascriptVariable(Core_Str::cut($oObject->useragent, $aSetting['nameLen']))?>',
								data: [[1, <?php echo $oObject->dataCount?>]],
								color: '#<?php echo $aColors[$i % count($aColors)]?>'

							}
						);
						<?php
						$i++;
					}
					?>
					var placeholderUserAgentsDiagram = $("#<?php echo $sWindowId?> #userAgentsDiagram");

					placeholderUserAgentsDiagram.unbind();

					$.plot(placeholderUserAgentsDiagram, diagramData, {
						series: {
							pie: {
								show: true,
								radius: 1,
								innerRadius: 0.5,
								label: {
										show: true,
										radius: 3 / 4,
										formatter: function labelFormatter(label, series) {
														return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + Math.round(series.percent) + "%</div>";
													},
										threshold: 0.1
									}
							}
						}
						,
						grid: {
							hoverable: true
						//	clickable: true
						}

					});

					placeholderUserAgentsDiagram.bind("plothover", function (event, pos, obj) {
						if (!obj) {
							return;
						}

						var percent = parseFloat(obj.series.percent).toFixed(2);
						$("#<?php echo $sWindowId?> #hover").html("<span style='font-weight:bold; color:" + obj.series.color + "'>" + obj.series.label + " (" + percent + "%)</span>");
					});

					placeholderUserAgentsDiagram.bind("plotclick", function (event, pos, obj) {
						if (!obj) {
							return;
						}

						percent = parseFloat(obj.series.percent).toFixed(2);
						alert("" + obj.series.label + ": " + percent + "%");
					});
				});
			});
			</script>
			<?php
		}
	}
}

Core_Event::attach('Admin_Form_Controller.onAfterSetConditionsAndLimits', array('Counter_Useragent_Observer', 'onAfterSetConditionsAndLimits'));

$oAdmin_Form_Controller->execute();