/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiMultiUpdateForm: handle the multi update checkbox list and form
 */

import KimaiPlugin from '../KimaiPlugin';
import jQuery from "jquery";

export default class KimaiMultiUpdateForm extends KimaiPlugin {

    init() {
        const self = this;
        
        jQuery('body').
            on('change', '#multi_update_all', function(event) {
                jQuery('.multi_update_single').prop('checked', jQuery(event.target).prop('checked'));
                self.toggleForm();
            })
            .on('change', '.multi_update_single', function(event) {
                self.toggleForm();
            })
        ;
    }
    
    toggleForm() 
    {
        const checked = jQuery('.multi_update_single:checked');

        var ids = [];
        checked.each(function(i){
            ids[i] = $(this).val();
        });

        const header = jQuery('#multi_update_form h3');
        header.text(header.attr('data-title').replace(/%counter%/, checked.length));
        
        const form = jQuery('#multi_update_form form');
        const id = form.attr('name') + '_' + form.data('entities');
        console.log(id);
        jQuery('#'+id).val(ids.join(','));

        if (checked.length > 0) {
            jQuery('#multi_update_form').show();
        } else {
            jQuery('#multi_update_form').hide();
        }
    }
    
}
