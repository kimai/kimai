<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php
            if (isset($this->id) && $this->id !== 0) {
                echo $this->translate('edit') . ': ' . $this->translate('project');
            } else {
                echo $this->translate('new_project');
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
            <li class="tab norm"><a href="#money">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('budget') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#activitiestab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('activities') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#groups">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('groups') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#comment">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('comment') ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>
    <form id="addProject" action="processor.php" method="post">
        <input name="projectFilter" type="hidden" value="0"/>
        <input name="axAction" type="hidden" value="add_edit_CustomerProjectActivity"/>
        <input name="axValue" type="hidden" value="project"/>
        <input name="id" type="hidden" value="<?php echo $this->id ?>"/>
        <div id="floater_tabs" class="floater_content">
            <fieldset id="general">
                <ul>
                    <li><label for="name"><?php echo $this->translate('project') ?>*:</label>
                        <?php echo $this->formText('name', $this->project['name'], ['required' => 'required', 'class' => 'input-width']); ?>
                    </li>
                    <li><label for="customerID"><?php echo $this->translate('customer') ?>:</label>
                        <?php echo $this->formSelect('customerID', $this->project['customerID'], ['class' => 'formfield', 'class' => 'input-width'], $this->customers); ?>
                    </li>
                    <li><label for="visible"><?php echo $this->translate('visibility') ?>:</label>
                        <?php echo $this->formCheckbox('visible', '1', ['checked' => $this->project['visible'] || !$this->id]); ?>
                    </li>
                    <li><label for="internal"><?php echo $this->translate('internalProject') ?>:</label>
                        <?php echo $this->formCheckbox('internal', '1', ['checked' => $this->project['internal']]); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="money">
                <ul>
                    <li><label for="defaultRate"><?php echo $this->translate('default_rate') ?>:</label>
                        <?php echo $this->formText('defaultRate', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->project['defaultRate'])); ?>
                    </li>
                    <li><label for="myRate"><?php echo $this->translate('my_rate') ?>:</label>
                        <?php echo $this->formText('myRate', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->project['myRate'])); ?>
                    </li>
                    <li><label for="fixedRate"><?php echo $this->translate('fixedRate') ?>:</label>
                        <?php echo $this->formText('fixedRate', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->project['fixedRate'])); ?>
                    </li>
                    <li><label for="project_budget"><?php echo $this->translate('budget') ?>:</label>
                        <?php echo $this->formText('project_budget', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->project['budget'])); ?>
                    </li>
                    <li><label for="project_effort"><?php echo $this->translate('effort') ?>:</label>
                        <?php echo $this->formText('project_effort', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->project['effort'])); ?>
                    </li>
                    <li><label for="project_approved"><?php echo $this->translate('approved') ?>:</label>
                        <?php echo $this->formText('project_approved', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->project['approved'])); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="activitiestab">
                <table class="activitiesTable">
                    <tr>
                        <td><label for="assignedActivities" style="text-align: left;"><?php echo $this->translate('activities') ?>:</label></td>
                        <td><label for="budget" style="text-align: left;"><?php echo $this->translate('budget') ?>:</label></td>
                        <td><label for="effort" style="text-align: left;"><?php echo $this->translate('effort') ?>:</label></td>
                        <td><label for="approved" style="text-align: left;"><?php echo $this->translate('approved') ?>:</label></td>
                        <td></td>
                    </tr>
                    <?php
                    $assignedActivities = [];
                    if (isset($this->selectedActivities) && is_array($this->selectedActivities)) {
                        foreach ($this->selectedActivities as $selectedActivity) {
                            $assignedActivities[] = $selectedActivity['activityID'];
                            ?>
                            <tr>
                                <td>
                                    <?php echo $this->escape($selectedActivity['name']), $this->formHidden('assignedActivities[]', $selectedActivity['activityID']); ?>
                                </td>
                                <td>
                                    <?php echo $this->formText('budget[]', $selectedActivity['budget']); ?>
                                </td>
                                <td>
                                    <?php echo $this->formText('effort[]', $selectedActivity['effort']); ?>
                                </td>
                                <td>
                                    <?php echo $this->formText('approved[]', $selectedActivity['approved']); ?>
                                </td>
                                <td>
                                    <a class="deleteButton">
                                        <img src="<?php echo $this->skin('grfx/close.png'); ?>" width="22" height="16"/>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    }

                    $selectArray = [-1 => ''];
                    foreach ($this->allActivities as $activity) {
                        if (array_search($activity['activityID'], $assignedActivities) === false) {
                            $selectArray[$activity['activityID']] = $activity['name'];
                        }
                    }
                    ?>
                    <tr class="addRow" <?php if (count($selectArray) <= 1): ?> style="display:none" <?php endif; ?> >
                        <td colspan="5"><?php echo $this->formSelect('newActivity', null, null, $selectArray); ?></td>
                    </tr>
                </table>
            </fieldset>
            <fieldset id="groups">
                <ul>
                    <li>
                        <?php echo $this->formSelect('projectGroups[]', $this->selectedGroups, [
                            'class' => 'formfield',
                            'id' => 'projectGroups',
                            'multiple' => 'multiple',
                            'size' => 5,
                            'class' => 'input-width'
                        ], $this->groups); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="comment">
                <ul>
                    <li><label for="projectComment"><?php echo $this->translate('comment') ?>:</label>
                        <?php echo $this->formTextarea('projectComment', $this->project['comment'], [
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
	        <button type="button" class="btn_norm" onclick="floaterClose();"><?php echo $this->translate('cancel') ?></button>
            <input type="submit" class="btn_ok" value="<?php echo $this->translate('submit') ?>"/>
        </div>
    </form>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#floater_innerwrap').tabs({selected: 0});

        var $addProject = $('#addProject');
        $addProject.ajaxForm({
            beforeSubmit: function () {
                clearFloaterErrorMessages();

                if ($addProject.attr('submitting')) {
                    return false;
                }
                else {
                    $addProject.attr('submitting', true);
                    return true;
                }
            },
            success: function (result) {
                $addProject.removeAttr('submitting');

                for (var fieldName in result.errors) {
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);
                }

                if (result.errors.length == 0) {
                    floaterClose();
                    hook_projects_changed();
                    hook_activities_changed();
                }
            },
            error: function () {
                $addProject.removeAttr('submitting');
            }
        });

        var $activitiestab = $('#activitiestab');
        var $addRow = $activitiestab.find('.addRow');

        function deleteButtonClicked() {
            var row = $(this).parent().parent()[0];
            var id = $('#assignedActivities', row).val();
            var text = $('td', row).text().trim();
            $('#newActivity').append('<option label = "' + text + '" value = "' + id + '">' + text + '</option>');
            $(row).remove();

            if ($('#newActivity option').length > 1) {
                $addRow.show();
            }
        }

        $('#activitiestab').find('.deleteButton').click(deleteButtonClicked);

        $('#newActivity').change(function () {
            if ($(this).val() == -1) {
                return;
            }

            var row = $('<tr>' +
                '<td>' + $('option:selected', this).text() + '<input type="hidden" name="assignedActivities[]" value="' + $(this).val() + '"/></td>' +
                '<td><input type="text" name="budget[]"/></td>' +
                '<td><input type="text" name="effort[]"/></td>' +
                '<td><input type="text" name="approved[]"/></td>' +
                '<td><a class="deleteButton"><img src="../skins/' + skin + '/grfx/close.png" width="22" height="16" /></a></td>' +
                '</tr>');
            $addRow.before(row);
            $('.deleteButton', row).click(deleteButtonClicked);

            $('option:selected', this).remove();

            $(this).val(-1);

            if ($('option', this).length <= 1) {
                $addRow.hide();
            }
        });
    });
</script>