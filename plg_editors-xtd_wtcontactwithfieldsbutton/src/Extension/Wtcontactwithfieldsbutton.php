<?php

/**
 * @package    WT Contact anywhere with fields package
 * @version       1.0.2
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\EditorsXtd\Wtcontactwithfieldsbutton\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Event\SubscriberInterface;

/**
 * Editor Article button
 *
 * @since  1.5
 */
final class Wtcontactwithfieldsbutton extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return array
	 *
	 * @since   5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onEditorButtonsSetup'            => 'onEditorButtonsSetup',
			'onAjaxWtcontactwithfieldsbutton' => 'onAjaxWtcontactwithfieldsbutton',
		];
	}

	/**
	 * @param   EditorButtonsSetupEvent  $event
	 *
	 * @return void
	 *
	 * @since   5.0.0
	 */
	public function onEditorButtonsSetup(EditorButtonsSetupEvent $event)
	{
		$subject  = $event->getButtonsRegistry();
		$disabled = $event->getDisabledButtons();

		if (\in_array($this->_name, $disabled)) {
			return;
		}

		$this->loadLanguage();

		$button = $this->onDisplay($event->getEditorId());
		$subject->add($button);
	}

	/**
	 * Display the button
	 *
	 * @param   string  $name  The name of the button to add
	 *
	 * @return  mixed Button or void
	 *
	 * @since   3.7.0
	 */
	public function onDisplay($name)
	{
		$user = $this->getApplication()->getIdentity();
		if (
			$user->authorise('core.create', 'com_contact')
			|| $user->authorise('core.edit', 'com_contact')
			|| $user->authorise('core.edit.own', 'com_contact')
		) {

			// The URL for the contacts list
			$link = 'index.php?option=com_ajax&plugin=wtcontactwithfieldsbutton&group=editors-xtd&format=html&tmpl=component&' . Session::getFormToken() . '=1&editor=' . $name;

			$button = new Button(
				$this->_name,
				[
					'action'  => 'modal',
					'text'    => 'WT ' . (Text::_('PLG_EDITORS-XTD_CONTACT_BUTTON_CONTACT')),
					'icon'    => 'address',
					'link'    => $link,
					'iconSVG' => '<svg viewBox="0 0 448 512" width="24" height="24"><path d="M436 160c6.6 0 12-5.4 12-12v-40c0-6.6-5.4-12-12-12h-20V48c'
						. '0-26.5-21.5-48-48-48H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h320c26.5 0 48-21.5 48-48v-48h20c6.6 0 12-5.4 1'
						. '2-12v-40c0-6.6-5.4-12-12-12h-20v-64h20c6.6 0 12-5.4 12-12v-40c0-6.6-5.4-12-12-12h-20v-64h20zm-228-32c35.3 0 64 28.7'
						. ' 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zm112 236.8c0 10.6-10 19.2-22.4 19.2H118.4C106 384 96 375.4 96 364.'
						. '8v-19.2c0-31.8 30.1-57.6 67.2-57.6h5c12.3 5.1 25.7 8 39.8 8s27.6-2.9 39.8-8h5c37.1 0 67.2 25.8 67.2 57.6v19.2z">'
						. '</path></svg>',
					// This is whole Plugin name, it is needed for keeping backward compatibility
					'name'    => $this->_type . '_' . $this->_name,
				]
			);

			return $button;
		}
	}

	/**
	 * Method working with Joomla com_ajax. Return a HTML form for contact selection
	 * @return string contact selection HTML form
	 * @throws \Exception
	 */
	public function onAjaxWtcontactwithfieldsbutton()
	{
		$app = $this->getApplication();

		if ($app->isClient('site')) {
			Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
		}

		$doc = $app->getDocument();
		$doc->getWebAssetManager()
			->useScript('core')
			->registerAndUseScript(
				'wtcontactwithfieldsbutton',
				'plg_editors-xtd_wtcontactwithfieldsbutton/wtcontactwithfieldsbutton.js'
			);

		$editor                       = $app->getInput()->get('editor', '');
		$wt_wtcontactwithfieldsbutton = Folder::files(JPATH_SITE . "/plugins/content/wtcontactwithfields/tmpl");
		$layout_options               = array();
		foreach ($wt_wtcontactwithfieldsbutton as $file) {
			if (File::getExt($file) == "php") {
				$wt_layout        = File::stripExt($file);
				$layout_options[] = HTMLHelper::_('select.option', $wt_layout, $wt_layout);
			}
		}

		if (!empty($editor)) {

			$doc->addScriptOptions('xtd-wtcontactwithfieldsbutton', array('editor' => $editor));
		}


		$limit = $app->getInput()->get('limit', $app->get('list_limit'), 'int');


		$limitstart = $app->getInput()->get('limitstart', 0, 'int');

		$contacts_model = $app->bootComponent('com_contact')->getMVCFactory()->createModel('Contacts', 'Administrator', ['ignore_request' => true]);

		$contacts_model->setState('context', 'com_contact.contacts');
		$contacts_model->setState('list.start', $limitstart);
		$contacts_model->setState('list.limit', $limit);
		$contacts_model->setState('list.direction', 'asc');

		$filter        = $app->getInput()->get('filter', [], 'array');
		$filter_search = (!empty($filter['search'])) ? $filter['search'] : '';
		$contacts_model->setState('filter.search', $filter_search);

		$filter_category_id = $app->getInput()->get('category_id', 0, 'int');
		$contacts_model->setState('filter.category_id', $filter_category_id);

		// Поле категорий
		$options        = HTMLHelper::_('category.options', 'com_contact', $config = ['filter.published' => [0, 1]]);
		$category_filed = HTMLHelper::_(
			'select.genericlist',
			$options,
			'category_id',
			['class' => 'form-select', 'onchange' => 'Joomla.submitform();return false;'],
			'value',
			'text',
			$filter_category_id,
			'category_id',
			true
		);

		$contacts = $contacts_model->getItems();

?>
		<form
			action="index.php?option=com_ajax&plugin=wtcontactwithfieldsbutton&group=editors-xtd&format=html&tmpl=component&<?php echo Session::getFormToken(); ?>=1&editor=<?php echo $editor; ?>"
			method="post"
			name="adminForm"
			id="adminForm"
			class="container">
			<input type="hidden" name="option" value="com_ajax" />
			<input type="hidden" name="plugin" value="wtcontactwithfieldsbutton" />
			<input type="hidden" name="group" value="editors-xtd" />
			<input type="hidden" name="format" value="html" />
			<input type="hidden" name="tmpl" value="component" />
			<input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1" />
			<input type="hidden" name="editor" value="<?php echo $editor; ?>" />

			<div class="container-fluid">
				<div class="row mb-3 border-bottom">
					<div class="col-6 col-md-4 col-lg-3">
						<div class="input-group mb-3">
							<label for="wtcontactwithfieldsbutton_layout" class="input-group-text">
								<strong>tmpl</strong>
							</label>
							<?php
							$attribs = [
								'class'      => 'form-select',
								'aria-label' => 'Choose layout'
							];

							echo HTMLHelper::_("select.genericlist", $layout_options, $name = "wtcontactwithfieldsbutton_layout", $attribs, $key = 'value', $text = 'text', $selected = "default");

							?>
						</div>

					</div>

					<div class="col-2">
						<?php echo $contacts_model->getPagination()->getLimitBox(); ?>
					</div>
					<div class="col-3">
						<?php echo $category_filed; ?>
					</div>
					<div class="col-6 col-md-4">
						<div class="input-group mb-3">
							<input class="form-control" id="filter_search" type="text" name="filter[search]"
								<?php
								if (!empty($filter_search)) {
									echo 'value="' . $filter_search . '"';
								}
								?> />
							<button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
							<button class="btn btn-danger filter-search-actions__button js-stools-btn-clear"
								type="button"
								onclick="document.getElementById('filter_search').value='';Joomla.submitform();return false;">
								<i class="icon-remove"></i></button>
						</div>
					</div>
				</div>


				<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
					<?php foreach ($contacts as $contact): ?>
						<div class="col">
							<div class="card border">
								<div class="card-body">
									<h5 class="card-title d-flex"><span><?php echo $contact->name; ?></span> <small class="text-muted ms-auto">#<?php echo $contact->id; ?></small></h5>
									<?php ?>
									<a href="#" data-contact-id="<?php echo $contact->id; ?>"
										class="stretched-link"><?php echo Text::_('JSELECT'); ?></a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="">
					<?php echo $contacts_model->getPagination()->getListFooter(); ?>
				</div>
			</div>
		</form>
		<div class="fixed-bottom bg-white shadow-sm border-top">
			<div class="container d-flex justify-content-between align-items-end py-2">
				<span class="">
					<a href="https://web-tolk.ru" target="_blank"
						style="display: inline-flex; align-items: center;">
						<svg width="85" height="18" xmlns="http://www.w3.org/2000/svg">
							<g>
								<title>Go to https://web-tolk.ru</title>
								<text font-weight="bold" xml:space="preserve" text-anchor="start"
									font-family="Helvetica, Arial, sans-serif" font-size="18" id="svg_3" y="18"
									x="8.152073" stroke-opacity="null" stroke-width="0" stroke="#000"
									fill="#0fa2e6">Web</text>
								<text font-weight="bold" xml:space="preserve" text-anchor="start"
									font-family="Helvetica, Arial, sans-serif" font-size="18" id="svg_4" y="18"
									x="45" stroke-opacity="null" stroke-width="0" stroke="#000"
									fill="#384148">Tolk</text>
							</g>
						</svg>
					</a>
				</span>
			</div>
		</div>
		</div>
<?php
	}
}
