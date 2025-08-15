<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * View profile controller
 *
 * Контроллер просмотра профиля клиента.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Representative_Controller_View extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'title', // Form Title
		'skipColumns', // Array of skipped columns
	);

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation for action
	 * @return boolean
	 * @hostcms-event Siteuser_Representative_Controller_View.onBeforeExecute
	 * @hostcms-event Siteuser_Representative_Controller_View.onAfterExecute
	 */
	public function execute($operation = NULL)
	{
		Core_Event::notify('Siteuser_Representative_Controller_View.onBeforeExecute', $this, array($operation, $this->_Admin_Form_Controller));

		$eventResult = Core_Event::getLastReturn();

		if (!is_null($eventResult))
		{
			return $eventResult;
		}

		switch ($operation)
		{
			case 'modal':
				$this->addContent($this->_showEditForm());

				ob_start();

				$siteuser_id = Core_Array::getGet('siteuser_id', 0, 'int');
				if ($siteuser_id)
				{
					$href = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/index.php') . '?hostcms[action]=edit&hostcms[current]=1&hostcms[sortingfield]=107&hostcms[sortingdirection]=0&hostcms[window]=id_content&hostcms[checked][0][' . $siteuser_id . ']=1';

					$onclick = "mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + '/siteuser/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][" . $siteuser_id . "]=1', windowId: 'id_content', width: '90%'}); return false;";

					$this->_Admin_Form_Controller->getTitleEditIcon($href, $onclick, 'fa fa-pencil-square-o h5-edit-icon sky', 'h4.modal-title')->execute();
					$this->addContent(ob_get_clean());
				}

				$return = TRUE;
			break;

			default:
			case NULL: // Показ формы

				ob_start();

				$content = $this->_showEditForm();

				$oAdmin_View = Admin_View::create();
				$oAdmin_View
					->children($this->_children)
					->pageTitle($this->title)
					->module($this->_Admin_Form_Controller->getModule())
					->content($content)
					->show();

				$this->addContent(ob_get_clean());

				$this->_Admin_Form_Controller
					->title($this->title)
					->pageTitle($this->title);

				$return = TRUE;
			break;
		}

		Core_Event::notify('Siteuser_Representative_Controller_View.onAfterExecute', $this, array($operation, $this->_Admin_Form_Controller));

		return $return;
	}

	/**
	 * Show edit form
	 * @return boolean
	 */
	protected function _showEditForm()
	{
		ob_start();

		$aDirectory_Phones = $this->_object->Directory_Phones->findAll();
		$aDirectory_Emails = $this->_object->Directory_Emails->findAll();
		$aDirectory_Socials = $this->_object->Directory_Socials->findAll();
		$aDirectory_Messengers = $this->_object->Directory_Messengers->findAll();
		$aDirectory_Websites = $this->_object->Directory_Websites->findAll();
		$aDirectory_Addresses = $this->_object->Directory_Addresses->findAll();

		switch (get_class($this->_object))
		{
			case 'Siteuser_Person_Model':
			?>
				<div class="row representative-view">
					<div class="col-md-12">
						<div class="profile-container">
							<div class="profile-header row">
								<div class="col-lg-2 col-md-4 col-sm-12 text-center">
									<img class="header-avatar" src="<?php echo $this->_object->getAvatar()?>" alt="" />
								</div>
								<div class="col-lg-5 col-md-8 col-sm-12 profile-info">
									<div class="header-fullname">
										<?php
										echo htmlspecialchars((string) $this->_object->getFullName());

										$href = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php') . '?hostcms[action]=edit&hostcms[current]=1&hostcms[sortingfield]=107&hostcms[sortingdirection]=0&hostcms[window]=id_content&hostcms[checked][1][' . $this->_object->id . ']=1&siteuser_id=' . $this->_object->siteuser_id;

										$onclick = "mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + '/siteuser/representative/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][1][" . $this->_object->id . "]=1&siteuser_id=" . $this->_object->siteuser_id . "', windowId: 'id_content', width: '90%'}); return false;";
										?><a href="<?php echo $href?>" onclick="<?php echo $onclick?>"><i class="fa fa-pencil-square-o h5-edit-icon sky"></i></a>
									</div>
								</div>
								<div class="col-xs-12 col-lg-5 profile-stats">
									<div class="row">
										<div class="col-xs-12 stats-col">
											<div class="gray"><?php echo htmlspecialchars((string) $this->_object->post)?></div>
										</div>
									</div>
									<div class="row">
										<div class="col-xs-6 inlinestats-col">
											<?php echo Core::_("Siteuser_Representative.sex")?> <strong><?php echo $this->_object->getSex()?></strong>
										</div>
										<div class="col-xs-6 inlinestats-col">
										<?php
										if (!is_null($this->_object->birthday) && $this->_object->birthday != '0000-00-00')
										{
										?>
											<?php echo Core::_("Siteuser_Representative.age")?> <strong><?php echo $this->_object->getAge()?></strong><?php
										}
										?>
										</div>
									</div>
								</div>
							</div>
							<?php
							if (count($aDirectory_Addresses) || count($aDirectory_Phones) || count($aDirectory_Emails) || count($aDirectory_Socials) || count($aDirectory_Messengers) || count($aDirectory_Websites))
							{
							?>
							<div class="profile-body">
								<div class="col-lg-12">
									<div class="tabbable">
										<div class="tab-content tabs-flat">
											<div id="overview" class="tab-pane active">
												<div class="row profile-overview">
													<div class="col-xs-12">
													<!-- <div class="profile-overview-wrapper"> -->
														<?php
														// Адреса
														if (count($aDirectory_Addresses))
														{
														?>
															<div class="row">
																<div class="col-xs-12">
																	<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																		<div class="profile-badge darkorange">
																			<i class="fa fa-map-marker darkorange"></i>
																			<span><?php echo Core::_("Siteuser_Representative.addresses")?></span>
																		</div>
																		<div class="contact-info">
																		<?php
																		foreach ($aDirectory_Addresses as $oDirectory_Address)
																		{
																			$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->find($oDirectory_Address->directory_address_type_id);

																			$sAddressType = !is_null($oDirectory_Address_Type->id)
																				? htmlspecialchars((string) $oDirectory_Address_Type->name) . ": "
																				: '<i class="fa fa-map-marker darkorange margin-right-10"></i>';

																			$address = $oDirectory_Address->getFullAddress();

																			if ($address != '')
																			{
																			?>
																				<p><span class="popup-type"><?php echo $sAddressType?></span><span><?php echo htmlspecialchars($address)?></span></p>
																			<?php
																			}
																		}
																		?>
																		</div>
																	</div>
																</div>
															</div>
														<?php
														}

														// Телефоны
														if (count($aDirectory_Phones) || count($aDirectory_Emails))
														{
														?>
															<div class="row">
																<?php
																if (count($aDirectory_Phones))
																{
																	?>
																	<div class="col-xs-12 col-sm-6 col-md-6">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge palegreen">
																				<i class="fa fa-phone palegreen"></i>
																				<span><?php echo Core::_("Siteuser_Representative.phones")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Phones as $oDirectory_Phone)
																			{
																				$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type')->find($oDirectory_Phone->directory_phone_type_id);

																				$sPhoneType = !is_null($oDirectory_Phone_Type->id)
																					? htmlspecialchars($oDirectory_Phone_Type->name)
																					: '<i class="fa fa-phone palegreen margin-right-10"></i>';
																			?>
																			<div>
																				<div class="semi-bold"><a href="tel:<?php echo htmlspecialchars((string) $oDirectory_Phone->value)?>"><span><?php echo htmlspecialchars((string) $oDirectory_Phone->value)?></span></a></div>
																				<div class="popup-type"><?php echo $sPhoneType?></div>
																			</div>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																	<?php
																}

																// Электронные адреса
																if (count($aDirectory_Emails))
																{
																?>
																	<div class="col-xs-12 col-sm-6 col-md-6">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge warning">
																				<i class="fa fa-envelope-o warning"></i>
																				<span><?php echo Core::_("Siteuser_Representative.emails")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Emails as $oDirectory_Email)
																			{
																				$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->find($oDirectory_Email->directory_email_type_id);

																				$sEmailType = !is_null($oDirectory_Email_Type->id)
																					? htmlspecialchars($oDirectory_Email_Type->name)
																					: '<i class="fa fa-envelope-o warning margin-right-10"></i>';
																			?>
																			<div>
																				<div><a href="mailto:<?php echo htmlspecialchars((string) $oDirectory_Email->value)?>"><span><?php echo htmlspecialchars((string) $oDirectory_Email->value)?></span></a></div>
																				<div class="popup-type"><?php echo $sEmailType?></div>
																			</div>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																<?php
																}
																?>
															</div>
														<?php
														}

														// Социальные сети
														if (count($aDirectory_Socials) || count($aDirectory_Messengers) || count($aDirectory_Websites))
														{
															$countBlocks = 0;

															count($aDirectory_Socials) && $countBlocks += 1;
															count($aDirectory_Messengers) && $countBlocks += 1;
															count($aDirectory_Websites) && $countBlocks += 1;

															$class = 12 / $countBlocks;
															?>
															<div class="row">
																<?php
																if (count($aDirectory_Socials))
																{
																	?>
																	<div class="col-xs-12 col-sm-<?php echo $class?>">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge azure">
																				<i class="fa fa-share-alt azure"></i>
																				<span><?php echo Core::_("Siteuser_Representative.socials")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Socials as $oDirectory_Social)
																			{
																				$oDirectory_Social_Type = Core_Entity::factory('Directory_Social_Type')->find($oDirectory_Social->directory_social_type_id);

																				$sSocialType = !is_null($oDirectory_Social_Type->id) && $oDirectory_Social_Type->ico != ''
																					? '<i class="' . htmlspecialchars($oDirectory_Social_Type->ico) . ' margin-right-10"></i>'
																					: '<i class="fa fa-envelope-o azure margin-right-10"></i>';
																			?>
																				<p><span class="popup-type"><?php echo $sSocialType?></span><a href="<?php echo htmlspecialchars((string) $oDirectory_Social->value)?>" target="_blank"><?php echo htmlspecialchars((string) $oDirectory_Social->value)?></a></p>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																	<?php
																}

																// Мессенджеры
																if (count($aDirectory_Messengers))
																{
																?>
																	<div class="col-xs-12 col-sm-<?php echo $class?>">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge purple">
																				<i class="fa fa-comments-o purple"></i>
																				<span><?php echo Core::_("Siteuser_Representative.messengers")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Messengers as $oDirectory_Messenger)
																			{
																				$oDirectory_Messenger_Type = Core_Entity::factory('Directory_Messenger_Type')->find($oDirectory_Messenger->directory_messenger_type_id);

																				$sMessengerType = !is_null($oDirectory_Messenger_Type->id) && $oDirectory_Messenger_Type->ico != ''
																					? '<i class="' . htmlspecialchars($oDirectory_Messenger_Type->ico) . ' margin-right-10"></i>'
																					: '<i class="fa fa-comments-o purple margin-right-10"></i>';
																			?>
																				<p><span class="popup-type"><?php echo $sMessengerType?></span><a href="<?php echo sprintf($oDirectory_Messenger_Type->link, $oDirectory_Messenger->value)?>" target="_blank"><?php echo htmlspecialchars((string) $oDirectory_Messenger->value)?></a></p>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																<?php
																}

																// Сайты
																if (count($aDirectory_Websites))
																{
																?>
																	<div class="col-xs-12 col-sm-<?php echo $class?>">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge magenta">
																				<i class="fa fa-globe magenta"></i>
																				<span><?php echo Core::_("Siteuser_Representative.websites")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Websites as $oDirectory_Website)
																			{
																			?>
																				<p><a href="<?php echo htmlspecialchars((string) $oDirectory_Website->value)?>" target="_blank"><?php echo htmlspecialchars((string) $oDirectory_Website->value)?></a></p>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																<?php
																}
																?>
															</div>
														<?php
														}
														?>
													<!-- </div> -->
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php
							}
							?>
						</div>
					</div>
				</div>
		<?php
			break;
			case 'Siteuser_Company_Model':
			// $aDirectory_Addresses = $this->_object->Directory_Addresses->findAll();
			?>
				<div class="row representative-view">
					<div class="col-md-12">
						<div class="profile-container">
							<div class="profile-header row">
								<div class="col-lg-2 col-md-4 col-sm-12 text-center">
									<img class="header-avatar" src="<?php echo $this->_object->getAvatar()?>" alt="" />
								</div>
								<div class="col-lg-5 col-md-8 col-sm-12 profile-info">
									<div class="header-fullname">
										<?php
										echo htmlspecialchars((string) $this->_object->name);

										$href = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php') . '?hostcms[action]=edit&hostcms[current]=1&hostcms[sortingfield]=107&hostcms[sortingdirection]=0&hostcms[window]=id_content&hostcms[checked][0][' . $this->_object->id . ']=1&siteuser_id=' . $this->_object->siteuser_id;

										$onclick = "mainFormLocker.unlock(); $.modalLoad({path: hostcmsBackend + '/siteuser/representative/index.php', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][" . $this->_object->id . "]=1&siteuser_id=" . $this->_object->siteuser_id . "', windowId: 'id_content', width: '90%'}); return false;";
										?><a href="<?php echo $href?>" onclick="<?php echo $onclick?>"><i class="fa fa-pencil-square-o h5-edit-icon sky"></i></a>
									</div>
									<?php
										echo !empty($this->_object->tin) ? '<div class="tin small gray">' . Core::_('Siteuser_Company.tin_list', $this->_object->tin) . '</div>' : '';
									?>
									<div class="header-information"><?php echo nl2br(htmlspecialchars((string) $this->_object->description))?></div>
								</div>
								<div class="col-xs-12 col-lg-5 profile-stats">
									<div class="row">
										<div class="col-xs-12 stats-col">
											<div class="gray"><?php echo htmlspecialchars((string) $this->_object->business_area)?></div>
										</div>
									</div>
									<div class="row">
										<div class="col-xs-6 inlinestats-col">
											<?php echo Core::_("Siteuser_Representative.headcount")?> <strong><?php echo $this->_object->headcount?></strong>
										</div>
										<div class="col-xs-6 inlinestats-col">
											<?php echo Core::_("Siteuser_Representative.annual_turnover")?>&nbsp;<strong><?php echo $this->_object->annual_turnover?></strong>
										</div>
									</div>
								</div>
							</div>
							<?php
							if (count($aDirectory_Addresses) || count($aDirectory_Phones) || count($aDirectory_Emails) || count($aDirectory_Socials) || count($aDirectory_Messengers) || count($aDirectory_Websites))
							{
							?>
							<div class="profile-body">
								<div class="col-lg-12">
									<div class="tabbable">
										<div class="tab-content tabs-flat">
											<div id="overview" class="tab-pane active">
												<div class="row profile-overview">
													<div class="col-xs-12">
														<!-- <div class="row"> -->
														<?php
														// Адреса
														if (count($aDirectory_Addresses))
														{
														?>
															<div class="row">
																<div class="col-xs-12">
																	<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																		<div class="profile-badge darkorange">
																			<i class="fa fa-map-marker darkorange"></i>
																			<span><?php echo Core::_("Siteuser_Representative.addresses")?></span>
																		</div>
																		<div class="contact-info">
																		<?php
																		foreach ($aDirectory_Addresses as $oDirectory_Address)
																		{
																			$oDirectory_Address_Type = Core_Entity::factory('Directory_Address_Type')->find($oDirectory_Address->directory_address_type_id);

																			$sAddressType = !is_null($oDirectory_Address_Type->id)
																				? htmlspecialchars($oDirectory_Address_Type->name) . ": "
																				: '<i class="fa fa-map-marker darkorange margin-right-10"></i>';
																		?>
																			<p><span class="popup-type"><?php echo $sAddressType?></span><span><?php echo htmlspecialchars($oDirectory_Address->getFullAddress())?></span></p>
																		<?php
																		}
																		?>
																		</div>
																	</div>
																</div>
															</div>
														<?php
														}
														// Телефоны
														if (count($aDirectory_Phones) || count($aDirectory_Emails))
														{
														?>
															<div class="row">
																<?php
																if (count($aDirectory_Phones))
																{
																	?>
																	<div class="col-xs-12 col-sm-6 col-md-6">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge palegreen">
																				<i class="fa fa-phone palegreen"></i>
																				<span><?php echo Core::_("Siteuser_Representative.phones")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Phones as $oDirectory_Phone)
																			{
																				$oDirectory_Phone_Type = Core_Entity::factory('Directory_Phone_Type')->find($oDirectory_Phone->directory_phone_type_id);

																				$sPhoneType = !is_null($oDirectory_Phone_Type->id)
																					? htmlspecialchars($oDirectory_Phone_Type->name)
																					: '<i class="fa fa-phone palegreen margin-right-10"></i>';
																			?>
																			<div>
																				<div class="semi-bold"><a href="tel:<?php echo htmlspecialchars((string) $oDirectory_Phone->value)?>"><span><?php echo htmlspecialchars((string) $oDirectory_Phone->value)?></span></a></div>
																				<div class="popup-type"><?php echo $sPhoneType?></div>
																			</div>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																<?php
																}

																// Электронные адреса
																if (count($aDirectory_Emails))
																{
																?>
																	<div class="col-xs-12 col-sm-6 col-md-6">
																		<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																			<div class="profile-badge warning">
																				<i class="fa fa-envelope-o warning"></i>
																				<span><?php echo Core::_("Siteuser_Representative.emails")?></span>
																			</div>
																			<div class="contact-info">
																			<?php
																			foreach ($aDirectory_Emails as $oDirectory_Email)
																			{
																				$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->find($oDirectory_Email->directory_email_type_id);

																				$sEmailType = !is_null($oDirectory_Email_Type->id)
																					? htmlspecialchars($oDirectory_Email_Type->name)
																					: '<i class="fa fa-envelope-o warning margin-right-10"></i>';
																			?>

																			<div>
																				<div><a href="mailto:<?php echo htmlspecialchars((string) $oDirectory_Email->value)?>"><span><?php echo htmlspecialchars((string) $oDirectory_Email->value)?></span></a></div>
																				<div class="popup-type"><?php echo $sEmailType?></div>
																			</div>
																			<?php
																			}
																			?>
																			</div>
																		</div>
																	</div>
																<?php
																}
																?>
															</div>
														<?php
														}
														?>
														<!-- </div> -->
														<?php
														if (count($aDirectory_Socials) || count($aDirectory_Messengers) || count($aDirectory_Websites))
														{
															$countBlocks = 0;

															count($aDirectory_Socials) && $countBlocks += 1;
															count($aDirectory_Messengers) && $countBlocks += 1;
															count($aDirectory_Websites) && $countBlocks += 1;

															$class = 12 / $countBlocks;
															?>
															<div class="row">
															<?php
															// Социальные сети
															if (count($aDirectory_Socials))
															{
															?>
																<div class="col-xs-12 col-sm-<?php echo $class?>">
																	<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																		<div class="profile-badge azure">
																			<i class="fa fa-share-alt azure"></i>
																			<span><?php echo Core::_("Siteuser_Representative.socials")?></span>
																		</div>
																		<div class="contact-info">
																		<?php
																		foreach ($aDirectory_Socials as $oDirectory_Social)
																		{
																			$oDirectory_Social_Type = Core_Entity::factory('Directory_Social_Type')->find($oDirectory_Social->directory_social_type_id);

																			$sSocialType = !is_null($oDirectory_Social_Type->id) && $oDirectory_Social_Type->ico != ''
																				? '<i class="' . htmlspecialchars($oDirectory_Social_Type->ico) . ' margin-right-10"></i>'
																				: '<i class="fa fa-envelope-o azure margin-right-10"></i>';
																		?>
																			<p><span class="popup-type"><?php echo $sSocialType?></span><a href="<?php echo htmlspecialchars((string) $oDirectory_Social->value)?>" target="_blank"><?php echo htmlspecialchars((string) $oDirectory_Social->value)?></a></p>
																		<?php
																		}
																		?>
																		</div>
																	</div>
																</div>
															<?php
															}

															// Мессенджеры
															if (count($aDirectory_Messengers))
															{
															?>
																<div class="col-xs-12 col-sm-<?php echo $class?>">
																	<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																		<div class="profile-badge purple">
																			<i class="fa fa-comments-o purple"></i>
																			<span><?php echo Core::_("Siteuser_Representative.messengers")?></span>
																		</div>
																		<div class="contact-info">
																		<?php
																		foreach ($aDirectory_Messengers as $oDirectory_Messenger)
																		{
																			$oDirectory_Messenger_Type = Core_Entity::factory('Directory_Messenger_Type')->find($oDirectory_Messenger->directory_messenger_type_id);

																			$sMessengerType = !is_null($oDirectory_Messenger_Type->id) && $oDirectory_Messenger_Type->ico != ''
																				? '<i class="' . htmlspecialchars($oDirectory_Messenger_Type->ico) . ' margin-right-10"></i>'
																				: '<i class="fa fa-comments-o purple margin-right-10"></i>';
																		?>
																			<p><span class="popup-type"><?php echo $sMessengerType?></span><a href="<?php echo sprintf($oDirectory_Messenger_Type->link, $oDirectory_Messenger->value)?>" target="_blank"><?php echo htmlspecialchars((string) $oDirectory_Messenger->value)?></a></p>
																		<?php
																		}
																		?>
																		</div>
																	</div>
																</div>
															<?php
															}
															?>
															<!-- </div>
															<div class="row"> -->
															<?php
															// Сайты
															if (count($aDirectory_Websites))
															{
															?>
																<div class="col-xs-12 col-sm-<?php echo $class?>">
																	<div class="profile-contacts no-padding-left no-padding-top no-padding-right">
																		<div class="profile-badge magenta">
																			<i class="fa fa-globe magenta"></i>
																			<span><?php echo Core::_("Siteuser_Representative.websites")?></span>
																		</div>
																		<div class="contact-info">
																		<?php
																		foreach ($aDirectory_Websites as $oDirectory_Website)
																		{
																		?>
																			<p><a href="<?php echo htmlspecialchars((string) $oDirectory_Website->value)?>" target="_blank"><?php echo htmlspecialchars((string) $oDirectory_Website->value)?></a></p>
																		<?php
																		}
																		?>
																		</div>
																	</div>
																</div>
															<?php
															}
															?>
															</div>
															<?php
														}
														?>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php
							}
							?>
						</div>
					</div>
				</div>
			<?php
			break;
		}

		return ob_get_clean();
	}
}