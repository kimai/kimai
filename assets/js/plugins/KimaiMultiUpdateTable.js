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

export default class KimaiMultiUpdateTable extends KimaiPlugin {

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

        jQuery('#multi_update_table_action')
            .on('change', function(event) {
                const selected = jQuery(this).val();
                if (selected !== '') {
                    jQuery('#multi_update_form form').attr('action', selected).submit();
                }
            });
    }
    
    toggleForm() 
    {
        const checked = jQuery('.multi_update_single:checked');

        var ids = [];
        checked.each(function(i){
            ids[i] = $(this).val();
        });

        jQuery('#multi_update_table_entities').val(ids.join(','));

        if (checked.length > 0) {
            jQuery('#multi_update_form').show();
        } else {
            jQuery('#multi_update_form').hide();
        }
    }
    
}
