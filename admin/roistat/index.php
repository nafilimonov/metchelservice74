<?php
/**
 * Roistat.
 *
 * @package HostCMS
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2017 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'roistat');

$sAdminFormAction = '/admin/roistat/index.php';
// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create();
$oAdmin_Form_Controller
	->module(Core_Module::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Roistat.title'));

ob_start();

$oAdmin_View = Admin_View::create();
$oAdmin_View
	->module(Core_Module::factory($sModule))
	->pageTitle(Core::_('Roistat.title'));

$oMainTab = Admin_Form_Entity::factory('Tab')->name('main');

$oMainTab
	->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
	->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
	->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'));

$oMainRow1->add(Admin_Form_Entity::factory('Code')->html('
	<style>
		.roistat-patch {text-align: left; margin-bottom: 10px;}
		.roistat-form input {margin-right: 10px;}
		.roistat-form red {color: #f55555;}
	</style>
'));

$oMainRow1->add(
	Admin_Form_Entity::factory('Code')->html('
		<div class="col-xs-12 col-md-3">
			<a href="http://roistat.com/ru/" target="_blank"><img class="img-responsive" src="/modules/roistat/image/logo_black.png"></a>
		</div>
		<div class="col-xs-12 col-md-9 roistat-patch">
			<h2>До 67% ваших денег вы выкидываете на неэффективную рекламу</h2>

			<p>Сервис анализирует данные из вашей CRM системы, рекламных площадок и вашего сайта. Считает показатели на основе данных о реальных продажах, учитывая сделки в работе, отказы и возвраты. Из этих данных формирует отчеты по ключевым бизнес-показателям.</p>

			<p>Roistat интегрирован со всеми популярными CMS и CRM системами, включая Мегаплан, amoCRM, Мойсклад, retailCRM, Freshoffice, Битрикс24, InSales и многие другие. Это позволяет клиентам быстро настроить обмен данными и получить результаты.</p>

			<p>Автоматически загружает расходы из Яндекс.Директ, Яндекс.Маркет, Google Adwords, Вконтакте, myTarget и др.</p>
		</div>

	')
);

$oMainRow2->add(Admin_Form_Entity::factory('Div')->class('col-xs-12 margin-bottom-10')
	->add(
		Admin_Form_Entity::factory('Code')->html('
			<p><b>Чтобы воспользоваться нашим предложением пройдите регистрацию:</b></p>
		')
	)
);

$oMainRow3->add(Admin_Form_Entity::factory('Div')->class('col-xs-12 margin-bottom-10 margin-top-10')
	->add(
		Admin_Form_Entity::factory('Code')->html('
			<p><b>Бонусы для клиентов HostCMS:</b> 1 месяц использования Roistat бесплатно. <span class="red small">*</span></p>
			<p class="red small">* Бонусом могут воспользоваться только новые клиенты Roistat.</p>
			<p class="small">** Телефон необходимо указывать в формате 7xxxxxxxxxx</p>
		')
	)
);

// http://cloud.roistat.com/referral/register?email=analitica83@mail.ru&coupon=hostcms&phone=79262235066
Admin_Form_Entity::factory('Form')
	->controller($oAdmin_Form_Controller)
	->class('form-inline roistat-form text-align-center')
	->action('http://cloud.roistat.com/referral/register')
	->add($oMainRow1)
	->add($oMainRow2)
	->add(
		Admin_Form_Entity::factory('input')
			->divAttr(array('class' => 'form-group margin-bottom-10'))
			->type('email')
			->name('email')
			->placeholder('E-mail')
	)->add(
		Admin_Form_Entity::factory('input')
			->divAttr(array('class' => 'form-group margin-bottom-10'))
			->type('tel')
			->name('phone')
			->placeholder('Телефон')
	)->add(
		Admin_Form_Entity::factory('input')
			->type('hidden')
			->name('coupon')
			->value('hostcms')
	)->add(
		Admin_Form_Entity::factory('input')
			->type('submit')
			->divAttr(array('class' => 'form-group margin-bottom-10'))
			->class('applyButton btn btn-blue')
			->value('Зарегистрироваться')
	)
	->add($oMainRow3)
	->target('_blank')
	->method('GET')
	->execute();

$content = ob_get_clean();

ob_start();
$oAdmin_View
	->content($content)
	->show();

Core_Skin::instance()
	->answer()
	->ajax(Core_Array::getRequest('_', FALSE))
	->content(ob_get_clean())
	->title(Core::_('roistat.title'))
	->module($sModule)
	->execute();