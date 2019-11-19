<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php
            if (isset($this->id) && $this->id !== 0) {
                echo $this->translate('edit') . ': ' . $this->translate('customer');
            } else {
                echo $this->translate('new_customer');
            }
        ?></span>
        <div class="right">
            <a href="#" class="close" onclick="floaterClose();return false;"><?php echo $this->translate('close') ?></a>
        </div>
    </div>
    <div class="menuBackground">
        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#general">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('general') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#address">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('address') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#contact">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('contact') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#groups">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('groups') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#commenttab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('comment') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>
    <form id="add_edit_customer" action="processor.php" method="post">
        <input type="hidden" name="customerFilter" value="0"/>
        <input type="hidden" name="axAction" value="add_edit_CustomerProjectActivity"/>
        <input type="hidden" name="axValue" value="customer"/>
        <input type="hidden" name="id" value="<?php echo $this->id ?>"/>
        <div id="floater_tabs" class="floater_content">
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="name"><?php echo $this->translate('customer') ?>*:</label>
                        <?php echo $this->formText('name', $this->customer['name'], ['required' => 'required', 'class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="vat"><?php echo $this->translate('vat') ?>:</label>
                        <?php echo $this->formText('vat', $this->customer['vat'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="visible"><?php echo $this->translate('visibility') ?>:</label>
                        <?php echo $this->formCheckbox('visible', '1', ['checked' => $this->customer['visible'] || !$this->id]); ?>
                    </li>
                    <li>
                        <label for="password"><?php echo $this->translate('password') ?>:</label>
                        <div class="multiFields">
                            <?php echo $this->formPassword('password', '', [
                                'disabled' => (!$this->customer['password']) ? 'disabled' : '',
                                'class' => 'input-width'
                            ]); ?><br/>
                            <?php echo $this->formCheckbox('no_password', '1', ['class' => 'disableInput', 'checked' => !$this->customer['password']]);
                            echo $this->translate('nopassword') ?>
                        </div>
                    </li>
                    <li>
                        <label for="timezone"><?php echo $this->translate('timezone') ?>:</label>
                        <?php echo $this->timeZoneSelect('timezone', $this->customer['timezone'], ['class' => 'input-width']); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="address">
                <ul>
                    <li>
                        <label for="company"><?php echo $this->translate('company') ?>:</label>
                        <?php echo $this->formText('company', $this->customer['company'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="contactPerson"><?php echo $this->translate('contactPerson') ?>:</label>
                        <?php echo $this->formText('contactPerson', $this->customer['contact'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="street"><?php echo $this->translate('street') ?>:</label>
                        <?php echo $this->formText('street', $this->customer['street'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="zipcode"><?php echo $this->translate('zipcode') ?>:</label>
                        <?php echo $this->formText('zipcode', $this->customer['zipcode'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="city"><?php echo $this->translate('city') ?>:</label>
                        <?php echo $this->formText('city', $this->customer['city'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="country"><?php echo $this->translate('country') ?>:</label>
                        <?php echo $this->formSelect('country', $this->customer['country'], [
                            'class' => 'formfield',
                            'id' => 'country',
                            'class' => 'input-width'
                        ], $this->countries); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="contact">
                <ul>
                    <li>
                        <label for="phone"><?php echo $this->translate('telephon') ?>:</label>
                        <?php echo $this->formText('phone', $this->customer['phone'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="fax"><?php echo $this->translate('fax') ?>:</label>
                        <?php echo $this->formText('fax', $this->customer['fax'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="mobile"><?php echo $this->translate('mobilephone') ?>:</label>
                        <?php echo $this->formText('mobile', $this->customer['mobile'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="mail"><?php echo $this->translate('mail') ?>:</label>
                        <?php echo $this->formText('mail', $this->customer['mail'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="homepage"><?php echo $this->translate('homepage') ?>:</label>
                        <?php echo $this->formText('homepage', $this->customer['homepage'], ['class' => 'input-width']); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="groups">
                <ul>
                    <li>
                        <label for="customerGroups"><?php echo $this->translate('groups') ?>:</label>
                        <?php echo $this->formSelect('customerGroups[]', $this->selectedGroups, [
                            'class' => 'formfield',
                            'id' => 'customerGroups',
                            'multiple' => 'multiple',
                            'size' => 5,
                            'class' => 'input-width'
                        ], $this->groups); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="commenttab">
                <ul>
                    <li>
                        <label for="comment"><?php echo $this->translate('comment') ?>:</label>
                        <?php echo $this->formTextarea('comment', $this->customer['comment'], [
                            'cols' => 30,
                            'rows' => 5,
                            'class' => 'comment',
                            'class' => 'input-width'
                        ]); ?>
                    </li>
                </ul>
            </fieldset>
        </div>
        <div id="formbuttons">
	        <button class="btn_norm" type="button" onclick="floaterClose();"><?php echo $this->translate('cancel') ?></button>
            <input class="btn_ok" type="submit" value="<?php echo $this->translate('submit') ?>"/>
        </div>
    </form>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#floater_innerwrap').tabs({selected: 0});
        $('.disableInput').click(function () {
            var input = $(this);
            if (input.is(':checked')) {
                input.siblings().prop("disabled", "disabled");
            } else {
                input.siblings().prop("disabled", "");
            }
        });
        var $add_edit_customer = $('#add_edit_customer');
        $add_edit_customer.ajaxForm({
            beforeSubmit: function () {
                clearFloaterErrorMessages();
                if ($add_edit_customer.attr('submitting')) {
                    return false;
                } else {
                    $add_edit_customer.attr('submitting', true);
                    return true;
                }
            },
            success: function (result) {
                $add_edit_customer.removeAttr('submitting');
                for (var fieldName in result.errors) {
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);
                }
                if (result.errors.length == 0) {
                    floaterClose();
                    hook_customers_changed();
                }
            },
            error: function () {
                $add_edit_customer.removeAttr('submitting');
            }
        });
    });
</script>