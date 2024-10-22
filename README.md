# WT Contact with fields plugin for Joomla
Insert Joomla contacts anywhere. Use `{wt_contact_wf contact_id=XXXX tmpl=XXXX}` where you need to insert a contact with your own layout. Create your own layouts in plugin's tmpl folder. It also shows a block of information about the author in Joomla articles.
![image](https://github.com/user-attachments/assets/4ec05fe0-a112-4583-a017-07c2de59a1a4)
# Description
This plugin is needed to output information from the com_contact Joomla contacts component. For example, you have created a catalog of online courses on one of the e-commerce components or Joomla materials and you need to display information about the course teacher - You can use this plugin. The plugin allows you to display contact data with all standard fields, as well as data from user fields. To do this, create your own output layout in the tmpl folder of the plugin and specify it in the shortcode parameter.
The package consists of two plugins:
- **Content plugin** - processes shortcodes and displays information about the author in Joomla materials.
- **Editor button plugin** - allows you to conveniently search and select contacts in the modal window and insert a shortcode with the selected output layout

# Minimum Joomla version
The minimum version of Joomla is **Joomla 5**. The plugins from the package will not work on Joomla 4.

# Information about the author of the article in Joomla
The content plugin can also display a block of information about the author of the material in the article and in the category of materials. You can specify a separate layout for each output. Previously, separate plugins were distributed that added additional fields to the user's profile and output data from them. However, Joomla has a plugin that creates a contact when a user registers. Also, when creating a contact, you can specify the corresponding Joomla user. Joomla can also display the author of the material as a link and this will be a link to the author's profile in the componentContacts or a link to the website from the profile or an email from the contact's profile.

The "Contacts" component allows you to specify a lot of information: address, phone numbers, email, position, photo (avatar), text "about yourself", etc. You can also add any number of custom fields to the component. But the standard Joomla plugin "Content Contact" will not allow you to display all this data.

This plugin also solves this problem.

# Plugin short code
`{wt_contact_wf contact_id=XXXX tmpl=XXXX}`

Parameters
- **contact_id** - contact id in the contacts component. Required parameter
- **tmpl** - The name of the output layout file in the `plugins/content/wtcontactwithfields/tmpl/` directory. Optional parameter.