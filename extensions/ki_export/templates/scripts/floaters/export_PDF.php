<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php echo $this->translate('export_extension:exportPDF') ?></span>
        <div class="right">
            <a href="#" class="close" onclick="floaterClose();return false;"><?php echo $this->translate('close') ?></a>
        </div>
    </div>
    <div id="help">
        <div class="content"></div>
    </div>
    <div class="floater_content">
        <form id="export_extension_form_export_PDF" action="../extensions/ki_export/processor.php" method="post" target="_blank">
            <input type="hidden" name="axValue" id="axValue" value=""/>
            <input type="hidden" name="first_day" id="first_day" value=""/>
            <input type="hidden" name="last_day" id="last_day" value=""/>
            <input type="hidden" name="axColumns" id="axColumns" value=""/>
            <input type="hidden" name="timeformat" id="timeformat" value=""/>
            <input type="hidden" name="dateformat" id="dateformat" value=""/>
            <input type="hidden" name="default_location" id="default_location" value=""/>
            <input type="hidden" name="filter_cleared" id="filter_cleared" value=""/>
            <input type="hidden" name="filter_refundable" id="filter_refundable" value=""/>
            <input type="hidden" name="filter_type" id="filter_type" value=""/>
            <fieldset>
                <ul>
                    <li>
                        <label for="print_comments"><?php echo $this->translate('export_extension:print_comment') ?>:</label>
                        <input type="checkbox" value="true" name="print_comments" id="print_comments" <?php if ($this->prefs['print_comments']): ?> checked="checked" <?php endif; ?>/>
                    </li>
                    <li>
                        <label for="print_summary"><?php echo $this->translate('export_extension:print_summary') ?>:</label>
                        <input type="checkbox" value="true" name="print_summary" id="print_summary" <?php if ($this->prefs['print_summary']): ?> checked="checked" <?php endif; ?>>
                    </li>
                    <li>
                        <label for="create_bookmarks"><?php echo $this->translate('export_extension:create_bookmarks') ?>:</label>
                        <input type="checkbox" value="true" name="create_bookmarks" id="create_bookmarks" <?php if ($this->prefs['create_bookmarks']): ?> checked="checked" <?php endif; ?>/>
                    </li>
                    <li>
                        <label for="download_pdf"><?php echo $this->translate('export_extension:download_pdf') ?>:</label>
                        <input type="checkbox" value="true" name="download_pdf" id="download_pdf" <?php if ($this->prefs['download_pdf']): ?> checked="checked" <?php endif; ?>/>
                    </li>
                    <li>
                        <label for="customer_new_page"><?php echo $this->translate('export_extension:customer_new_page') ?>:</label>
                        <input type="checkbox" value="true" name="customer_new_page" id="customer_new_page" <?php if ($this->prefs['customer_new_page']): ?> checked="checked" <?php endif; ?>/>
                    </li>
                    <li>
                        <label for="reverse_order"><?php echo $this->translate('export_extension:reverse_order') ?>:</label>
                        <input type="checkbox" value="true" name="reverse_order" id="reverse_order" <?php if ($this->prefs['reverse_order']): ?> checked="checked" <?php endif; ?>/>
                    </li>
                    <li>
                        <label for="grouped_entries"><?php echo $this->translate('export_extension:grouped_entries') ?>:</label>
                        <input type="checkbox" value="true" name="grouped_entries" id="grouped_entries" <?php if ($this->prefs['grouped_entries']): ?> checked="checked" <?php endif; ?>/>
                    </li>
                    <li>
                        <label for="time_type"><?php echo $this->translate('export_extension:time_type')?>:</label>
                        <select name="time_type" id="time_type">
                            <option value="dec_time" <?php if ($this->prefs['time_type'] == 'dec_time'): ?> selected="selected"<?php endif; ?>> <?php echo $this->translate('export_extension:dec_time') ?></option>
                            <option value="time" <?php if ($this->prefs['time_type'] == 'time'): ?> selected="selected"<?php endif; ?>> <?php echo $this->translate('export_extension:time') ?></option>
                        </select>
                    </li>
                    <li>
                        <label for="document_comment"><?php echo $this->translate('comment') ?>:</label>
                        <textarea name="document_comment" id="document_comment"></textarea>
                    </li>
                    <li>
                        <label for="axAction"><?php echo $this->translate('export_extension:pdf_format') ?>:</label>
                        <select name="axAction" id="axAction">
                            <option value="export_pdf" <?php if ($this->prefs['pdf_format'] == 'export_pdf'): ?> selected="selected"<?php endif; ?>> <?php echo $this->translate('export_extension:export_pdf') ?></option>
                            <option value="export_pdf2" <?php if ($this->prefs['pdf_format'] == 'export_pdf2'): ?> selected="selected"<?php endif; ?>> <?php echo $this->translate('export_extension:export_pdf2') ?></option>
                        </select>
                    </li>
                    <li>
                        <?php echo $this->translate('export_extension:dl_hint') ?>
                    </li>
                </ul>
                <div id="formbuttons">
	                <button type="button" class="btn_norm" onclick="floaterClose();"><?php echo $this->translate('cancel') ?></button>
	                <input type="submit" class="btn_ok" value="<?php echo $this->translate('submit') ?>" onclick="floaterClose();"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#help').hide();
        $('#floater input#timeformat').prop('value', $('#export_extension_timeformat').prop('value'));
        $('#floater input#dateformat').prop('value', $('#export_extension_dateformat').prop('value'));
        $('#floater input#default_location').prop('value', $('#export_extension_default_location').prop('value'));
        $('#floater input#axValue').prop('value', filterUsers.join(":") + '|' + filterCustomers.join(":") + '|' + filterProjects.join(":") + '|' + filterActivities.join(":"));
        $('#floater input#filter_cleared').prop('value', $('#export_extension_tab_filter_cleared').prop('value'));
        $('#floater input#filter_refundable').prop('value', $('#export_extension_tab_filter_refundable').prop('value'));
        $('#floater input#filter_type').prop('value', $('#export_extension_tab_filter_type').prop('value'));
        $('#floater input#axColumns').prop('value', export_enabled_columns());
        $('.floater_content fieldset label').css('width', '200px');

        $('#floater input#first_day').prop('value', new Date($('#pick_in').val()).getTime() / 1000);
        $('#floater input#last_day').prop('value', new Date($('#pick_out').val()).getTime() / 1000);
    });
</script>