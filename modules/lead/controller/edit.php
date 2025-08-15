<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this
			->addSkipColumn('siteuser_id')
			->addSkipColumn('shop_order_id')
			->addSkipColumn('deal_id');

		if (!$object->id && !is_null(Core_Array::getGet('lead_status_id')))
		{
			$object->lead_status_id = Core_Array::getGet('lead_status_id', 0, 'int');
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
			? Core::_('Lead.edit_title', $this->_object->getFullName(), FALSE)
			: Core::_('Lead.add_title')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab
			->add(Admin_Form_Entity::factory('Div')->class('row')
				->add($oDivLeft = Admin_Form_Entity::factory('Div')->class('col-xs-12 col-md-6 col-lg-7 left-block'))
				->add($oDivRight = Admin_Form_Entity::factory('Div')->class('col-xs-12 col-md-6 col-lg-5 right-block'))
			);

		$oMainTab
			->add(Admin_Form_Entity::factory('Script')
				->value('
					$(function(){
						var bodyWidth = parseInt($("body").width()),
							timer = setInterval(function(){
								if (bodyWidth >= 992)
								{
									if ($("#' . $windowId . ' .left-block").height())
									{
										clearInterval(timer);

										$("#' . $windowId . ' .right-block").find("#' . $windowId . '-lead-timeline, #' . $windowId . '-lead-notes, #' . $windowId . '-lead-shop-items, #' . $windowId . '-related-events").slimscroll({
											height: $("#' . $windowId . ' .left-block").height() - 75,
											color: "rgba(0, 0, 0, 0.3)",
											size: "5px"
										});
									}
								}
						}, 500);
					});
				'));

		$oDivLeft
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'));

		$oDivRight
			->add($oMainRowRight1 = Admin_Form_Entity::factory('Div')->class('row'));

		$oAdditionalTab
			->add($oAdditionalRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oAdditionalRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oAdditionalRow3 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab->delete($this->getField('amount'));
		$oAdditionalTab->delete($this->getField('shop_currency_id'));

		$oDiv_Amount = Admin_Form_Entity::factory('Div')
			->class('form-group col-xs-12 col-sm-6 col-md-4 amount-currency')
			->add(Admin_Form_Entity::factory('Input')
				->name('amount')
				->value($this->_object->amount)
				->caption(Core::_("Lead.amount"))
				->class('form-control input-lg')
				->divAttr(array('class' => ''))
			)
			->add(
				Admin_Form_Entity::factory('Select')
					->class('form-control input-lg no-padding-left no-padding-right')
					// ->caption(Core::_('Shop_Order.order_currency'))
					->divAttr(array('class' => ''))
					->options(
						Shop_Controller::fillCurrencies()
					)
					->name('shop_currency_id')
					->value($this->_object->shop_currency_id)
			);

		$oMainRow2->add($oDiv_Amount);

		$oMainTab
			->move($this->getField('surname')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))->class('form-control input-lg semi-bold black'), $oMainRow1)
			->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))->class('form-control input-lg semi-bold black'), $oMainRow1)
			->move($this->getField('patronymic')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))->class('form-control input-lg'), $oMainRow1)
			->move($this->getField('company')->divAttr(array('class' => 'form-group col-xs-12 col-sm-8'))->class('form-control input-lg semi-bold black'), $oMainRow2)
			->move($this->getField('post')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4')), $oMainRow2);

		$oMainTab
			// ->move($this->getField('amount')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))->class('form-control'), $oMainRow2)
			->move($this->getField('birthday')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4')), $oMainRow2)
			->move($this->getField('last_contacted')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4')), $oMainRow2);

		$oMainTab
			->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow2);

		$oAdditionalTab
			->move($this->getField('site_id')->divAttr(array('class' => 'form-group col-xs-12 col-sm-2')), $oAdditionalRow1)
			->move($this->getField('lead_status_id')->readonly('readonly')->divAttr(array('class' => 'form-group col-xs-12 col-sm-2')), $oAdditionalRow1)
			->move($this->getField('user_id')->divAttr(array('class' => 'form-group col-xs-12 col-sm-5')), $oAdditionalRow1)
			// ->move($this->getField('siteuser_id')->disabled('disabled')->divAttr(array('class' => 'form-group col-xs-12 col-sm-2')), $oAdditionalRow2)
			// ->move($this->getField('shop_order_id')->disabled('disabled')->divAttr(array('class' => 'form-group col-xs-12 col-sm-2')), $oAdditionalRow2)
			// ->move($this->getField('deal_id')->disabled('disabled')->divAttr(array('class' => 'form-group col-xs-12 col-sm-2')), $oAdditionalRow2)
			;

		$oAdditionalTab->delete($this->getField('lead_need_id'));

		$aMasLeadNeeds = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aLead_Needs = Core_Entity::factory('Lead_Need')->getAllBySite_id(CURRENT_SITE);

		foreach ($aLead_Needs as $oLead_Need)
		{
			$aMasLeadNeeds[$oLead_Need->id] = array(
				'value' => $oLead_Need->name,
				'color' => $oLead_Need->color
			);
		}

		$oDropdownlistLeadNeeds = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasLeadNeeds)
			->name('lead_need_id')
			->value($this->_object->lead_need_id)
			->caption(Core::_('Lead.lead_need_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

		$oMainRow3->add($oDropdownlistLeadNeeds);

		$oAdditionalTab->delete($this->getField('lead_maturity_id'));

		$aMasLeadMaturities = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aLead_Maturities = Core_Entity::factory('Lead_Maturity')->getAllBySite_id(CURRENT_SITE);

		foreach ($aLead_Maturities as $oLead_Maturity)
		{
			$aMasLeadMaturities[$oLead_Maturity->id] = array(
				'value' => $oLead_Maturity->name,
				'color' => $oLead_Maturity->color
			);
		}

		$oDropdownlistLeadMaturities = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasLeadMaturities)
			->name('lead_maturity_id')
			->value($this->_object->lead_maturity_id)
			->caption(Core::_('Lead.lead_maturity_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

		$oMainRow3->add($oDropdownlistLeadMaturities);

		$oAdditionalTab->delete($this->getField('crm_source_id'));

		$aMasCrmSources = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
		foreach ($aCrm_Sources as $oCrm_Source)
		{
			$aMasCrmSources[$oCrm_Source->id] = array(
				'value' => $oCrm_Source->name,
				'color' => $oCrm_Source->color,
				'icon' => $oCrm_Source->icon
			);
		}

		$oDropdownlistCrmSources = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasCrmSources)
			->name('crm_source_id')
			->value($this->_object->crm_source_id)
			->caption(Core::_('Lead.crm_source_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

		$oMainRow3
			->add($oDropdownlistCrmSources);

		$oMainTab
			->move($this->getField('comment')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow3);

		ob_start();

		?>
		<div class="lead-step-wrapper">
			<?php

			$aLead_Statuses = Core_Entity::factory('Lead_Status')->getAllBySite_id(CURRENT_SITE);

			foreach ($aLead_Statuses as $key => $oLead_Status)
			{
				$class = $oLead_Status->id == $this->_object->lead_status_id
					? 'active'
					: ($this->_object->lead_status_id == 0 && $key == 0
						? 'active'
						: '');

				switch ($oLead_Status->type)
				{
					case 1:
						$statusClass = ' finish';
					break;
					case 2:
						$statusClass = ' failed';
					break;
					default:
						$statusClass = '';
				}

				?>
				<div id="simplewizardstep<?php echo $oLead_Status->id?>" class="lead-step-item-wrapper <?php echo $class?> <?php echo $statusClass?>" data-target="#simplewizardstep<?php echo $oLead_Status->id?>" data-id="<?php echo $oLead_Status->id?>">
					<span class="lead-step-item" style="background-color: <?php echo $oLead_Status->color?>"><?php echo htmlspecialchars($oLead_Status->name)?></span>
					<span class="triangle" style="border-left-color: <?php echo $oLead_Status->color?>"></span>
				</div>
				<?php
			}
			?>
		</div>
		<script>
			$(function() {
				$("#<?php echo $windowId?> .lead-step-wrapper .lead-step-item-wrapper").on("click", function(){
					$("#<?php echo $windowId?> .lead-step-wrapper .lead-step-item-wrapper").each(function(){
						$(this).removeClass("active");
					});

					$(this).addClass("active");
					$("#<?php echo $windowId?> input[name=lead_status_id]").val($(this).data("id"));

					if ($(this).hasClass("finish"))
					{
						mainFormLocker.unlock();

						var lead_status_id = $(this).data("id"),
							id = "hostcms[checked][0][<?php echo $this->_object->id?>]",
							post = {},
							operation = "";

						post["last_step"] = 0;
						post["mode"] = "edit";

						if ($(this).hasClass("finish"))
						{
							operation = "finish";
							post["last_step"] = 1;
						}

						post[id] = 1;
						post["lead_status_id"] = lead_status_id;

						$.adminLoad({path: hostcmsBackend + "/lead/index.php", action: "morphLead", operation: operation, post: post, additionalParams: "", windowId: "<?php echo $windowId?>"});
					}
				});

				var jFirstStatus = $("#<?php echo $windowId?> .lead-step-wrapper .lead-step-item-wrapper:first-child");
				jFirstStatus.length && $("#<?php echo $windowId?> input[name=lead_status_id]").val() == 0
					? $("#<?php echo $windowId?> input[name=lead_status_id]").val(jFirstStatus.data("id"))
					: 0;
			});
		</script>
		<?php

		$this->_Admin_Form_Entity_Form->add(
			// Admin_Form_Entity::factory('Code')->html($sWizard . $css)
			Admin_Form_Entity::factory('Code')->html(ob_get_clean())
		);

		// Телефоны
		$oLeadPhonesRow = Directory_Controller_Tab::instance('phone')
			->title(Core::_('Directory_Phone.phones'))
			->relation($this->_object->Lead_Directory_Phones)
			->showPublicityControlElement(TRUE)
			->execute();

		// Email'ы
		$oLeadEmailsRow = Directory_Controller_Tab::instance('email')
			->title(Core::_('Directory_Email.emails'))
			->relation($this->_object->Lead_Directory_Emails)
			->showPublicityControlElement(TRUE)
			->execute();

		// Сайты
		$oLeadWebsitesRow = Directory_Controller_Tab::instance('website')
			->title(Core::_('Directory_Website.sites'))
			->relation($this->_object->Lead_Directory_Websites)
			->showPublicityControlElement(TRUE)
			->execute();

		// Адреса
		$oLeadAddressesRow = Directory_Controller_Tab::instance('address')
			->title(Core::_('Directory_Address.addresses'))
			->relation($this->_object->Lead_Directory_Addresses)
			->showPublicityControlElement(TRUE)
			->execute();

		$oDivLeft
			->add($oLeadPhonesRow)
			->add($oLeadEmailsRow)
			->add($oLeadAddressesRow)
			->add($oLeadWebsitesRow);

		$oDivLeft
			// ->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow7 = Admin_Form_Entity::factory('Div')->class('row'))
			// ->add($oMainRow8 = Admin_Form_Entity::factory('Div')->class('row'))
		;

		$oAdditionalTab->delete($this->getField('shop_id'));

		$aShops = Core_Entity::factory('Site', CURRENT_SITE)->Shops->findAll();

		$aShopsSelectOptions = array();

		foreach($aShops as $oShop)
		{
			$aShopsSelectOptions[$oShop->id] = htmlspecialchars($oShop->name);
		}

		$oAdmin_Form_Entity_Select_Shops = Admin_Form_Entity::factory('Select')
			->options($aShopsSelectOptions)
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
			->id('select_shop_id')
			->name('select_shop_id')
			->value($this->_object->shop_id)
			->caption(Core::_('Lead.shop_id'));

		$oAdditionalRow3
			->add($oAdmin_Form_Entity_Select_Shops)
			->add(
				Admin_Form_Entity::factory('Input')
					->divAttr(array('class' => 'hidden'))
					->type('hidden')
					->id('shop_id')
					->name('shop_id')
					->value($this->_object->shop_id)
			);

		// При редактировании сделки и наличии связанных с ней товаров выбор магазина не доступен
		if ($this->_object->id && $this->_object->Lead_Shop_Items->getCount())
		{
			$oAdmin_Form_Entity_Select_Shops->disabled('disabled');
		}
		else
		{
			$oMainRow6
				->add(
					 Admin_Form_Entity::factory('Script')
						->value('$("#' . $windowId . ' #shop_id").val($("#' . $windowId . ' #select_shop_id").val()); $("#' . $windowId . ' #select_shop_id").on("change", function (){
								$("#' . $windowId . ' #shop_id").val($(this).val());
							});'
						)
				);
		}


		// Tags
		if (Core::moduleIsActive('tag'))
		{
			$oAdditionalTagsSelect = Admin_Form_Entity::factory('Select')
				->caption(Core::_('Lead.tags'))
				->options($this->_fillTagsList($this->_object))
				->name('tags[]')
				->class('lead-tags')
				->style('width: 100%')
				->multiple('multiple')
				->divAttr(array('class' => 'form-group col-xs-12'));

			$oMainRow7->add($oAdditionalTagsSelect);

			$html = '<script>
			$(function(){
				$("#' . $windowId . ' .lead-tags").select2({
					dropdownParent: $("#' . $windowId . '"),
					language: "' . Core_I18n::instance()->getLng() . '",
					minimumInputLength: 1,
					placeholder: "' . Core::_('Lead.type_tag') . '",
					tags: true,
					allowClear: true,
					multiple: true,
					ajax: {
						url: hostcmsBackend + "/tag/index.php?hostcms[action]=loadTagsList&hostcms[checked][0][0]=1",
						dataType: "json",
						type: "GET",
						processResults: function (data) {
							var aResults = [];
							$.each(data, function (index, item) {
								aResults.push({
									"id": item.id,
									"text": item.text
								});
							});
							return {
								results: aResults
							};
						}
					}
				});
			});</script>';

			$oMainRow7->add(Admin_Form_Entity::factory('Code')->html($html));
		}

		ob_start();
		?>
		<div class="tabbable">
			<ul class="nav nav-tabs tabs-flat" id="dealTabs">
				<li class="active" data-type="timeline">
					<a data-toggle="tab" href="#<?php echo $windowId?>_timeline" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/lead/timeline/index.php')?>" data-window-id="<?php echo $windowId?>-lead-timeline" data-additional="lead_id=<?php echo $this->_object->id?>">
						<i class="fa fa-bars"></i>
					</a>
				</li>
				<li data-type="note">
					<a data-toggle="tab" href="#<?php echo $windowId?>_notes" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/lead/note/index.php')?>" data-window-id="<?php echo $windowId?>-lead-notes" data-additional="lead_id=<?php echo $this->_object->id?>">
						<?php echo Core::_("Lead.tabNotes")?> <?php echo ($count = $this->_object->Crm_Notes->getCount())
							? '<span class="badge badge-yellow">' . $count . '</span>'
							: ''?>
					</a>
				</li>
				<?php
				if (Core::moduleIsActive('shop'))
				{
				?>
					<li data-type="shop_item">
						<a data-toggle="tab" href="#<?php echo $windowId?>_items" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/lead/shop/item/index.php')?>" data-window-id="<?php echo $windowId?>-lead-shop-items" data-additional="lead_id=<?php echo $this->_object->id?>">
							<?php echo Core::_("Lead.tabShopItems")?> <?php echo ($count = $this->_object->Lead_Shop_Items->getCount())
								? '<span class="badge badge-palegreen">' . $count . '</span>'
								: ''?>
						</a>
					</li>
				<?php
				}

				if (Core::moduleIsActive('event'))
				{
					$oCurrentUser = Core_Auth::getCurrentUser();

					$oEventsQb = $this->_object->Events;
					$oEventsQb
						->queryBuilder()
						->join('event_users', 'events.id', '=', 'event_users.event_id')
						->where('event_users.user_id', '=', $oCurrentUser->id);
					?>
					<li data-type="event">
						<a data-toggle="tab" href="#<?php echo $windowId?>_events" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/event/index.php')?>" data-window-id="<?php echo $windowId?>-related-events" data-additional="show_subs=1&hideMenu=1&lead_id=<?php echo $this->_object->id?>&parent_id=<?php echo $this->_object->id?>">
							<?php echo Core::_("Lead.tabEvents")?> <?php echo ($count = $oEventsQb->getCount())
								? '<span class="badge badge-orange">' . $count . '</span>'
								: ''?>
						</a>
					</li>
					<?php
				}

				if (Core::moduleIsActive('dms'))
				{
				?>
					<li data-type="dms_document">
						<a data-toggle="tab" href="#<?php echo $windowId?>_documents" data-path="<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/lead/dms/document/index.php')?>" data-window-id="<?php echo $windowId?>-lead-dms-documents" data-additional="lead_id=<?php echo $this->_object->id?>">
							<?php echo Core::_("Lead.tabDmsDocuments")?> <?php echo ($count = $this->_object->Dms_Documents->getCount())
								? '<span class="badge badge-purple">' . $count . '</span>'
								: ''?>
						</a>
					</li>
				<?php
				}
				?>
			</ul>
			<div class="tab-content tabs-flat">
				<div id="<?php echo $windowId?>_timeline" class="tab-pane in active">
					<?php
						Admin_Form_Entity::factory('Div')
							->controller($this->_Admin_Form_Controller)
							->id("{$windowId}-lead-timeline")
							->add(
								$this->_object->id
									? $this->_addLeadTimeline()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Lead.enable_after_save'), 'warning')
									)
							)
							->execute();
					?>
				</div>
				<div id="<?php echo $windowId?>_notes" class="tab-pane">
				<?php
				Admin_Form_Entity::factory('Div')
					->controller($this->_Admin_Form_Controller)
					->id("{$windowId}-lead-notes")
					->add(
						$this->_object->id
							? $this->_addLeadNotes()
							: Admin_Form_Entity::factory('Code')->html(
								Core_Message::get(Core::_('Lead.enable_after_save'), 'warning')
							)
					)
					->execute();
				?>
				</div>
				<?php
				if (Core::moduleIsActive('shop'))
				{
				?>
					<div id="<?php echo $windowId?>_items" class="tab-pane">
					<?php
					Admin_Form_Entity::factory('Div')
						->id("{$windowId}-lead-shop-items")
						->add(
							$this->_object->id
								? $this->_addLeadShopItems()
								: Admin_Form_Entity::factory('Code')->html(
									Core_Message::get(Core::_('Lead.enable_after_save'), 'warning')
								)
						)
						->execute();
					?>
					</div>
				<?php
				}

				if (Core::moduleIsActive('event'))
				{
				?>
					<div id="<?php echo $windowId?>_events" class="tab-pane">
					<?php
						Admin_Form_Entity::factory('Div')
							->id("{$windowId}-related-events")
							// ->class('related-events related-events-inner')
							->add(
								$this->_object->id
									? $this->_addLeadEvents()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Lead.enable_after_save'), 'warning')
									)
							)
							->execute();
					?>
					</div>
				<?php
				}

				if (Core::moduleIsActive('dms'))
				{
				?>
					<div id="<?php echo $windowId?>_documents" class="tab-pane">
					<?php
						Admin_Form_Entity::factory('Div')
							->id("{$windowId}-lead-dms-documents")
							->add(
								$this->_object->id
									? $this->_addLeadDmsDocuments()
									: Admin_Form_Entity::factory('Code')->html(
										Core_Message::get(Core::_('Lead.enable_after_save'), 'warning')
									)
							)
							->execute();
					?>
					</div>
				<?php
				}
				?>
			</div>
		</div>
		<?php
		$oMainRowRight1->add(Admin_Form_Entity::factory('Div')
			->class('form-group col-xs-12')
			->add(
				Admin_Form_Entity::factory('Code')
					->html(ob_get_clean())
			)
		);

		return $this;
	}

	/**
	 * Add timeline
	 * @return Admin_Form_Entity
	 */
	protected function _addLeadTimeline()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function() {
				$.adminLoad({ path: hostcmsBackend + '/lead/timeline/index.php', additionalParams: 'lead_id={$this->_object->id}&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-lead-timeline' });
			});");
	}

	/**
	 * Add notes
	 * @return Admin_Form_Entity
	 */
	protected function _addLeadNotes()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/lead/note/index.php', additionalParams: 'lead_id=" . $this->_object->id . "', windowId: '{$windowId}-lead-notes' });
			});");
	}

	/**
	 * Add shop items
	 * @return Admin_Form_Entity
	 */
	protected function _addLeadShopItems()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/lead/shop/item/index.php', additionalParams: 'lead_id=" . $this->_object->id . "', windowId: '{$windowId}-lead-shop-items' });
			});");
	}

	/**
	 * Add dms documents
	 * @return Admin_Form_Entity
	 */
	protected function _addLeadDmsDocuments()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/lead/dms/document/index.php', additionalParams: 'lead_id=" . $this->_object->id . "', windowId: '{$windowId}-lead-dms-documents' });
			});");
	}

	/*
	 * Add lead events
	 * @return Admin_Form_Entity
	 */
	protected function _addLeadEvents()
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$targetWindowId = $modalWindowId ? $modalWindowId : $windowId;

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				$.adminLoad({ path: hostcmsBackend + '/lead/event/index.php', additionalParams: 'lead_id={$this->_object->id}&show_subs=1&parentWindowId={$targetWindowId}', windowId: '{$targetWindowId}-related-events' });
			});");
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Lead_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$bAddLead = is_null($this->_object->id);

		$oCurrentLeadStatus = $bAddLead ? NULL : $this->_object->Lead_Status;

		$previousObject = clone $this->_object;

		parent::_applyObjectProperty();

		// $object = $this->_object;

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oCurrentUser = Core_Auth::getCurrentUser();

		if ($bAddLead)
		{
			ob_start();
			$this->_addLeadTimeline()->execute();
			$this->_addLeadNotes()->execute();
			Core::moduleIsActive('shop') && $this->_addLeadShopItems()->execute();
			Core::moduleIsActive('event') && $this->_addLeadEvents()->execute();
			Core::moduleIsActive('dms') && $this->_addLeadDmsDocuments()->execute();
			?>
			<script>
				$(function(){
					$("#<?php echo $windowId?> a[data-additional='lead_id=']").data('additional', 'lead_id=<?php echo $this->_object->id?>');
				});
			</script>
			<?php
			$this->_Admin_Form_Controller->addMessage(ob_get_clean());
		}

		Directory_Controller_Tab::instance('address')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('email')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('phone')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('website')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);

		if ($bAddLead || !$bAddLead && !is_null($oCurrentLeadStatus) && $oCurrentLeadStatus->id != $this->_object->lead_status_id)
		{
			$sNewLeadStepDatetime = Core_Date::timestamp2sql(time());

			Core_Entity::factory('Lead_Step')
				->lead_id($this->_object->id)
				->lead_status_id($this->_object->lead_status_id)
				->user_id($oCurrentUser->id)
				->datetime($sNewLeadStepDatetime)
				->save();
		}

		if ($previousObject->lead_status_id != $this->_object->lead_status_id)
		{
			$this->_object->notifyBotsChangeStatus();
		}

		if ($previousObject->lead_need_id != $this->_object->lead_need_id)
		{
			$oLead_Need = $this->_object->Lead_Need;

			$lead_need_name = $oLead_Need->id
				? $oLead_Need->name
				: Core::_('Admin.none');

			$lead_need_color = $oLead_Need->id
				? $oLead_Need->color
				: '#aebec4';

			$this->_object->pushHistory(Core::_('Lead.history_change_need', $lead_need_name), $lead_need_color);
		}

		if ($previousObject->lead_maturity_id != $this->_object->lead_maturity_id)
		{
			$oLead_Maturity = $this->_object->Lead_Maturity;

			$lead_maturity_name = $oLead_Maturity->id
				? $oLead_Maturity->name
				: Core::_('Admin.none');

			$lead_maturity_color = $oLead_Maturity->id
				? $oLead_Maturity->color
				: '#aebec4';

			$this->_object->pushHistory(Core::_('Lead.history_change_maturity', $lead_maturity_name), $lead_maturity_color);
		}

		if ($previousObject->crm_source_id != $this->_object->crm_source_id)
		{
			$oCrm_Source = $this->_object->Crm_Source;

			$crm_source_name = $oCrm_Source->id
				? $oCrm_Source->name
				: Core::_('Admin.none');

			$crm_source_color = $oCrm_Source->id
				? $oCrm_Source->color
				: '#aebec4';

			$this->_object->pushHistory(Core::_('Lead.history_change_crm_source', $crm_source_name), $crm_source_color);
		}

		// Обработка меток
		if (Core::moduleIsActive('tag'))
		{
			$aRecievedTags = Core_Array::getPost('tags', array());
			!is_array($aRecievedTags) && $aRecievedTags = array();

			$this->_object->applyTagsArray($aRecievedTags);
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		// $windowId = $this->_Admin_Form_Controller->getWindowId();

		// Всегда id_content
		$sJsRefresh = '<script>
		if ($("#id_content .kanban-board").length && typeof _windowSettings != \'undefined\') {
			$("#id_content .btn-view-selector #kanban").click();
		}
		</script>';

		switch ($operation)
		{
			case 'saveModal':
				$this->addMessage($sJsRefresh);
			break;
			case 'applyModal':
				$this->addContent($sJsRefresh);
			break;
			case 'markDeleted':
				$this->_object->markDeleted();
				$this->addMessage($sJsRefresh);
			break;
		}

		return parent::execute($operation);
	}

	/**
	 * Fill tags list
	 * @param Lead_Model $oLead item
	 * @return array
	 */
	protected function _fillTagsList(Lead_Model $oLead)
	{
		$aReturn = array();

		$aTags = $oLead->Tags->findAll(FALSE);

		foreach ($aTags as $oTag)
		{
			$aReturn[$oTag->name] = array(
				'value' => $oTag->name,
				'attr' => array('selected' => 'selected')
			);
		}

		return $aReturn;
	}
}