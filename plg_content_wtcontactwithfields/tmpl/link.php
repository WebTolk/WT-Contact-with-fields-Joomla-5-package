<?php

/**
 * @package       WT Contacts anywhere with fields
 * @version       1.0.2
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

defined('_JEXEC') or die('Restricted access');

/*
*   Copyright (C) 2021  Sergey Tolkachyov
*   Released under GNU GPL Public License
*   License: http://www.gnu.org/copyleft/gpl.html
*   https://web-tolk.ru
* 	Layout file for WT contacts with fields content plugin
*/

use Joomla\CMS\Router\Route;
use Joomla\Component\Contact\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;

// For full contact object info uncomment this echos
//  echo "<pre>";
//  print_r($contact);
//  echo "</pre>";

// Show only contact fields for constructing your own layout
//  echo "<pre>";
//  print_r($contact->jcfields);
//  echo "</pre>";

/* $contact->id                      contact id
 * $contact->name                    contact name
 * $contact->alias                   contact alias
 * $contact->con_position            con_position
 * $contact->address                 address
 * $contact->suburb                  city
 * $contact->state                   Region, state
 * $contact->country                 country
 * $contact->state                   Region, state
 * $contact->postcode                postcode
 * $contact->telephone               telephone
 * $contact->mobile                  mobile phone
 * $contact->fax                     fax
 * $contact->webpage                 site url, webpage
 * $contact->misc                    description
 * $contact->image                   image url
 * $contact->email_to                email
 * $contact->user_id                 Joomla user id
 * $contact->catid                   Contact category id
 * $contact->published               contact published or not
 * $contact->created                 contact date created
 * $contact->created_by              contact created by user id
 * $contact->created_by_alias        contact created by user alias
 * $contact->modified                contact date modified
 * $contact->publish_up              contact publish date start
 * $contact->publish_down            contact publish date end
 * $contact->ordering                contact ordering
 * $contact->metakey                 contact meta keywords
 * $contact->metadesc                contact meta description
 *
 * **** Custom fields ****
 * $contact->jcfields                [Array][stdClass Object] Array of objects of contact custom fields
 *                    You can access to fields via
 *
 *                    $contact->jcfields[0]->title                    field title    - [0] - field order number fron fields list in administrator panel
 *                    $contact->jcfields[0]->value                    field value
 *                    $contact->jcfields[0]->rawvalue                 field rawvalue     JSON for repeatable fields
 *                    $contact->jcfields[0]->fieldparams->options->options0 (1,2,3,4 etc)     stdClass Object
 *                    $contact->jcfields[0]->fieldparams->options->options0->name     field name
 *                    $contact->jcfields[0]->fieldparams->options->options0->value     field rawvalue
 *
 */

//$additional_images = (array)json_decode($contact->jcfields[1]->rawvalue);
/** @var  $contact object Contact data */

$contact_sef_link = Route::_(RouteHelper::getContactRoute($contact->slug, $contact->catid, $contact->language));

$open_new_window = $this->params->get('open_new_window', 0) ? 'target="_blank"' : ''; // open new window params YES/NO
?>
<a href="<?php echo $contact_sef_link; ?>" <?= $open_new_window; ?>><?php echo $contact->name; ?></a>