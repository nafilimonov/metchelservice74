<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 121;
$sAdminFormAction = '/{admin}/counter/crawler/summary/index.php';

$sCounterPath = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sTitle = Core::_('Counter.crawler_summary');

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

(!isset($oAdmin_Form_Controller->request['admin_form_filter_from_541'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_from_541'] == '')
		&& $oAdmin_Form_Controller->request['admin_form_filter_from_541'] = Core_Date::timestamp2date(time());

(!isset($oAdmin_Form_Controller->request['admin_form_filter_to_541'])
	|| $oAdmin_Form_Controller->request['admin_form_filter_to_541'] == '')
		&& $oAdmin_Form_Controller->request['admin_form_filter_to_541'] = Core_Date::timestamp2date(time());

$bSeveralDates = $oAdmin_Form_Controller->request['admin_form_filter_from_541'] != $oAdmin_Form_Controller->request['admin_form_filter_to_541'];

$oAdmin_Form_Dataset->addCondition(
	array('select' => array('counter_useragents.*', array($bSeveralDates ? 'SUM(count)' : 'count', 'dataCount')))
)
->addCondition(
	array('where' => array('site_id', '=', CURRENT_SITE))
)
->addCondition(
	array('where' => array('crawler', '=', 1))
);

$bSeveralDates && $oAdmin_Form_Dataset->addCondition(
	array('groupBy' => array('useragent'))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

class Counter_Crawler_Summary_Observer
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
			
			$aColors = Core_Array::get(Core::$config->get('counter_color'), 'Pie3D', array());
			$iCountColors = count($aColors);
			$sWindowId = $oAdmin_Form_Controller->getWindowId();
			?>
			<div class="col-xs-12 col-lg-8">
				<div class="widget counter">
					<div class="widget-body">
						<div class="row">
							<div class="col-xs-12">
								<div id="summaryDiagram" class="chart"></div>
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
									label: '<?php echo Core_Str::escapeJavascriptVariable(htmlspecialchars(Core_Str::cut($oObject->useragent, $aSetting['nameLen'])))?>',
									data: [[1, <?php echo $oObject->dataCount?>]],
									color: '#<?php echo $aColors[$key % $iCountColors]?>'
								}
							);
							<?php
							$i++;
						}
						?>
						var placeholderSummaryDiagram = $("#<?php echo $sWindowId?> #summaryDiagram");

						$.plot(placeholderSummaryDiagram, diagramData, {
							series: {
								pie: {
									show: true,
									radius: 1,
									//radius: 120,
									innerRadius: 0.7,

									label: {
											show: true,
											radius: 0,
											formatter: function(label, series) {
															return "<div style='font-size:8pt;'>" + label + "</div>";
											}
									}
								}
							},

							legend: {
								labelFormatter: function (label, series) {
									//return "<div style='font-size:8pt; text-align:center; padding:2px;'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
									return label + ", " + Math.round(series.percent  * 100) / 100 + "%";
								}
							}
							,
							grid: {
								hoverable: true,
								//clickable: true
							}

						});

						placeholderSummaryDiagram.bind("plothover", function (event, pos, obj) {
							if (!obj) {
								return;
							}

							$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel']").hide();
							$("#<?php echo $sWindowId?> #summaryDiagram span[id = 'pieLabel" + obj.seriesIndex + "']").show();
						});

						placeholderSummaryDiagram.resize(function(){$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel']").hide();});

						$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel']").hide();
					});
				});
			</script>
			<?php
		}
	}
}

Core_Event::attach('Admin_Form_Controller.onAfterSetConditionsAndLimits', array('Counter_Crawler_Summary_Observer', 'onAfterSetConditionsAndLimits'));

$oAdmin_Form_Controller->execute();