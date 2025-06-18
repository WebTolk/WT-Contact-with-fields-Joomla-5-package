<?php

/**
 * @package    WT Contact anywhere with fields package
 * @version       1.1.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Content\Wtcontactwithfields\Extension;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Event\Content\AfterDisplayEvent;
use Joomla\CMS\Event\Content\AfterTitleEvent;
use Joomla\CMS\Event\Content\BeforeDisplayEvent;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
final class Wtcontactwithfields extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 * If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.9.0
	 */
	protected $autoloadLanguage = true;

	public function __construct(DispatcherInterface $dispatcher, array $config = [],)
	{
		parent::__construct($dispatcher, $config);
		Log::addLogger(['text_file' => 'plg_content_wtcontactwithfields.php'], Log::ALL, ['plg_content_wtcontactwithfields']);
	}


	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepare'       => 'onContentPrepare',
			'onContentAfterTitle'    => 'onContentAfterTitle',
			'onContentBeforeDisplay' => 'onContentBeforeDisplay',
			'onContentAfterDisplay'  => 'onContentAfterDisplay',
		];
	}

	/**
	 * Plugin that change short code to article data with specified layout
	 *
	 * @param   string   $context     The context of the content being passed to the plugin.
	 * @param   object & $article     The article object.  Note $article->text is also available
	 * @param   mixed &  $params      The article params
	 * @param   integer  $limitstart  The 'page' number
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */

	public function onContentPrepare(ContentPrepareEvent $event): void
	{

		// Don't run if in the API Application
		// Don't run this plugin when the content is being indexed
		if (!$this->getApplication()->isClient('site') || $event->getContext() === 'com_finder.indexer') {
			return;
		}

		// Get content item
		$article    = $event->getItem();

		if($this->params->get('available_in_code',false)) {
			$article->wtcontactwf = $this->getContactIdByUserId((int)$article->created_by);
		}


		// If the item does not have a text property there is nothing to do
		if (!property_exists($article, 'text')) {
			return;
		}

		// Проверка есть ли строка замены в контенте
		if (strpos($article->text, 'wt_contact_wf') === false) {
			return;
		}


		$regex = '/{wt_contact_wf\s(.*?)}/i';
		preg_match_all($regex, $article->text, $short_codes);

		$i                 = 0;
		$short_code_params = [];

		foreach ($short_codes[1] as $short_code) {

			$settings = explode(" ", $short_code);

			foreach ($settings as $param) {
				$param                        = explode("=", $param);
				$short_code_params[$param[0]] = $param[1];
			}
			if (!empty($short_code_params["contact_id"])) {

				$tmpl = (!empty($short_code_params["tmpl"]) ? $short_code_params["tmpl"] : 'default');

				$contact = $this->getContactInfo((int) $short_code_params["contact_id"]);

				if (!empty($contact)) {
					$html = $this->renderContact($contact, $tmpl);
				}

				$article->text      = str_replace($short_codes[0][$i], $html, $article->text);
				if (property_exists($article, 'introtext') && !empty($article->introtext)) {
					$article->introtext = str_replace($short_codes[0][$i], $html, $article->introtext);
				}
				if (property_exists($article, 'fulltext') && !empty($article->fulltext)) {
					$article->fulltext = str_replace($short_codes[0][$i], $html, $article->fulltext);
				}
			} else {
				return;
			}
			$i++;
			$html = '';
		}
	}

	/**
	 * The display event.
	 *
	 * @param   AfterTitleEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function onContentAfterTitle(AfterTitleEvent $event): void
	{
		if (!$this->params->get('show_article_author_info', 0)) {
			return;
		}

		$view = $this->getApplication()->getInput()->getString('view');

		if ($view === 'article' && $this->params->get('author_info_article_position', 'before_display_content') == 'after_display_title') {
			$event->addResult($this->showAuthorInfo($event->getContext(), $event->getItem(), $event->getParams(), $event->getPage(), $this->params->get('author_info_article_layout', '-1')));
		}

		if (($view === 'category' || $view === 'featured') && $this->params->get('author_info_category_position', 'before_display_content') == 'after_display_title') {
			$event->addResult($this->showAuthorInfo($event->getContext(), $event->getItem(), $event->getParams(), $event->getPage(), $this->params->get('author_info_category_layout', '-1')));
		}
	}

	/**
	 * The display event.
	 *
	 * @param   BeforeDisplayEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function onContentBeforeDisplay(BeforeDisplayEvent $event): void
	{
		if (!$this->params->get('show_article_author_info', 0)) {
			return;
		}

		$view = $this->getApplication()->getInput()->getString('view');

		if ($view === 'article' && $this->params->get('author_info_article_position', 'before_display_content') == 'before_display_content') {
			$event->addResult($this->showAuthorInfo($event->getContext(), $event->getItem(), $event->getParams(), $event->getPage(), $this->params->get('author_info_article_layout', '-1')));
		}

		if (($view === 'category' || $view === 'featured') && $this->params->get('author_info_category_position', 'before_display_content') == 'before_display_content') {
			$event->addResult($this->showAuthorInfo($event->getContext(), $event->getItem(), $event->getParams(), $event->getPage(), $this->params->get('author_info_category_layout', '-1')));
		}
	}

	/**
	 * The display event.
	 *
	 * @param   AfterDisplayEvent  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	public function onContentAfterDisplay(AfterDisplayEvent $event): void
	{
		if (!$this->params->get('show_article_author_info', 0)) {
			return;
		}

		$view = $this->getApplication()->getInput()->getString('view');

		if ($view === 'article' && $this->params->get('author_info_article_position', 'before_display_content') == 'after_display_content') {
			$event->addResult($this->showAuthorInfo($event->getContext(), $event->getItem(), $event->getParams(), $event->getPage(), $this->params->get('author_info_article_layout', '-1')));
		}

		if (($view === 'category' || $view === 'featured') && $this->params->get('author_info_category_position', 'before_display_content') == 'after_display_content') {
			$event->addResult($this->showAuthorInfo($event->getContext(), $event->getItem(), $event->getParams(), $event->getPage(), $this->params->get('author_info_category_layout', '-1')));
		}
	}

	/**
	 * Prepare and render author info
	 *
	 * @param $context string Context
	 * @param $row object article objects
	 * @param $params object article params
	 * @param $limitstart string pagination page
	 * @param $tmpl string layout file name
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 1.0.0
	 */
	private function showAuthorInfo(string $context, object $row, object $params, string $limitstart, string $tmpl = '-1'): string
	{
		$allowed_contexts = ['com_content.category', 'com_content.article', 'com_content.featured'];

		if (!\in_array($context, $allowed_contexts)) {
			return '';
		}

		$categories = $this->params->get('categories', []);
		$condition_mode = $this->params->get('show_condition', 'only_in_specified');

		$show_condition = false;

		if ($condition_mode == 'only_in_specified' && in_array($row->catid, $categories)) {
			$show_condition = true;
		} elseif ($condition_mode == 'everywhere_except_specified' && !in_array($row->catid, $categories)) {
			$show_condition = true;
		}

		$html = '';
		if ($tmpl == '-1' || $show_condition == false) {
			return $html;
		}

		$contact = $this->getContactIdByUserId((int) $row->created_by);

		if (!empty($contact)) {
			$html = $this->renderContact($contact, $tmpl);
		}

		return $html;
	}


	/**
	 * Returns contact data object
	 *
	 * @param   int  $contact_id
	 *
	 * @return mixed
	 *
	 * @since 1.0.0
	 */
	private function getContactInfo(int $contact_id): mixed
	{
		if (empty($contact_id)) {
			return false;
		}

		$model = $this->getApplication()
			->bootComponent('com_contact')
			->getMVCFactory()
			->createModel('Contact', 'Site', ['ignore_request' => false]);

		try {
			return $model->getItem($contact_id);
		} catch (\Exception $e) {

			Log::add('WT Contact anywhere with fields: ' . ($e->getMessage()) . '. Contact id ' . $contact_id, Log::ERROR);
			return null;
		}
	}

	/**
	 * Load contact data by user id.
	 *
	 * Returns false if there is no contact with specified user id
	 *
	 * @param   int  $user_id
	 *
	 * @return mixed Contact data or false
	 *
	 * @since 1.0.0
	 */
	private function getContactIdByUserId(int $user_id): mixed
	{
		$db        = $this->getDatabase();
		$query     = $db->getQuery(true);
		$published = 1;
		$query->select('*')
			->from($db->quoteName('#__contact_details', 'a'))
			->select([
				$db->quoteName('c.title', 'category_title'),
				$db->quoteName('c.alias', 'category_alias'),
				$db->quoteName('c.access', 'category_access'),
			])
			->leftJoin($db->quoteName('#__categories', 'c'), 'c.id = a.catid')
			->select(
				[
					$db->quoteName('parent.title', 'parent_title'),
					$db->quoteName('parent.id', 'parent_id'),
					$db->quoteName('parent.path', 'parent_route'),
					$db->quoteName('parent.alias', 'parent_alias'),

				]
			)
			->select($this->getSlugColumn($query, 'a.id', 'a.alias') . ' AS slug')
			->select($this->getSlugColumn($query, 'c.id', 'c.alias') . ' AS catslug')
			->leftJoin($db->quoteName('#__categories', 'parent'), 'parent.id = c.parent_id')
			->where($db->quoteName('a.user_id') . ' = :user_id')
			->where($db->quoteName('a.published') . ' = :published')
			->bind(':user_id', $user_id, ParameterType::INTEGER)
			->bind(':published', $published, ParameterType::INTEGER);

		// Filter by start and end dates.
		$nowDate = Factory::getDate()->toSql();
		$query->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :publish_up)')
			->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :publish_down)')
			->bind(':publish_up', $nowDate)
			->bind(':publish_down', $nowDate);

		$db->setQuery($query);
		$data = $db->loadObject();

		if (empty($data)) {
			return false;
		}

		return $data;
	}

	/**
	 * Generate column expression for slug or catslug.
	 *
	 * @param   QueryInterface  $query  Current query instance.
	 * @param   string          $id     Column id name.
	 * @param   string          $alias  Column alias name.
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 */
	private function getSlugColumn($query, $id, $alias)
	{
		return 'CASE WHEN '
			. $query->charLength($alias, '!=', '0')
			. ' THEN '
			. $query->concatenate([$query->castAsChar($id), $alias], ':')
			. ' ELSE '
			. $query->castAsChar($id) . ' END';
	}

	/**
	 * Render contact data with specified layout
	 *
	 * @param $contact object Contact data
	 * @param $tmpl string Layout filename in plugin's tmpl folder
	 *
	 * @return string Rendered layout
	 *
	 * @throws \Exception
	 * @since 1.0.0
	 */
	private function renderContact(object $contact, $tmpl = 'default'): string
	{
		$html = '';
		if (!empty($contact)) {
			$contact->jcfields = FieldsHelper::getFields("com_contact.contact", $contact, true);
			ob_start();
			if (file_exists(JPATH_SITE . '/plugins/content/wtcontactwithfields/tmpl/' . $tmpl . '.php')) {
				require JPATH_SITE . '/plugins/content/wtcontactwithfields/tmpl/' . $tmpl . '.php';
			} else {
				require JPATH_SITE . '/plugins/content/wtcontactwithfields/tmpl/default.php';
			}
			$html = ob_get_clean();
		}

		return $html;
	}
}
