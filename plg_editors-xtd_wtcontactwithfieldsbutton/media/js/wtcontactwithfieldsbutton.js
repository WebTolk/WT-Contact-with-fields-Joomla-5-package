/**
 * @package    WT Contact anywhere with fields package
 * @copyright   Copyright (C) 2024 Sergey Tolkachyov. All rights reserved.
 * @author     Sergey Tolkachyov
 * @link       https://web-tolk.ru
 * @version 	1.0.2
 * @license     GNU General Public License version 3 or later
 */
;(() => {
    document.addEventListener('DOMContentLoaded', () => {
        // Get the elements
        const elements = document.querySelectorAll('[data-contact-id]')

        for (let i = 0, l = elements.length; l > i; i += 1) {
            // Listen for click event
            elements[i].addEventListener('click', (event) => {
                event.preventDefault()
                const { target } = event

                const contact_id = target.getAttribute('data-contact-id')
                const tmpl = document.getElementById('wtcontactwithfieldsbutton_layout').value

                if (!Joomla.getOptions('xtd-wtcontactwithfieldsbutton')) {
                    // Something went wrong!
                    // @TODO Close the modal
                    return false
                }

                const { editor } = Joomla.getOptions('xtd-wtcontactwithfieldsbutton')

                window.parent.Joomla.editors.instances[editor].replaceSelection(
                    '{wt_contact_wf contact_id=' + contact_id + ' tmpl=' + tmpl + '}'
                )

                if (window.parent.Joomla.Modal) {
                    window.parent.Joomla.Modal.getCurrent().close()
                }
            })
        }
    })
})()
