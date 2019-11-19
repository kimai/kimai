<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php
            if (isset($this->id) && $this->id !== 0) {
                echo $this->translate('edit') . ': ' . $this->translate('activity');
            } else {
                echo $this->translate('new_activity');
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
            <li class="tab norm"><a href="#projectstab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $this->translate('projects') ?></span>
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
    <form id="add_edit_activity" action="processor.php" method="post">
        <input type="hidden" name="activityFilter" value="0"/>
        <input type="hidden" name="axAction" value="add_edit_CustomerProjectActivity"/>
        <input type="hidden" name="axValue" value="activity"/>
        <input type="hidden" name="id" value="<?php echo $this->id; ?>"/>
        <div id="floater_tabs" class="floater_content">
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="name"><?php echo $this->translate('activity') ?>:</label>
                        <?php echo $this->formText('name', $this->activity['name'], ['class' => 'input-width']); ?>
                    </li>
                    <li>
                        <label for="defaultRate"><?php echo $this->translate('default_rate') ?>:</label>
                        <?php echo $this->formText('defaultRate', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->activity['defaultRate'])); ?>
                    </li>
                    <li>
                        <label for="myRate"><?php echo $this->translate('my_rate') ?>:</label>
                        <?php echo $this->formText('myRate', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->activity['myRate'])); ?>
                    </li>
                    <li>
                        <label for="fixedRate"><?php echo $this->translate('fixedRate') ?>:</label>
                        <?php echo $this->formText('fixedRate', str_replace('.', $this->kga['conf']['decimalSeparator'], $this->activity['fixedRate'])); ?>
                    </li>
                    <li>
                        <label for="visible"><?php echo $this->translate('visibility') ?>:</label>
                        <?php echo $this->formCheckbox('visible', '1', ['checked' => $this->activity['visible'] || !$this->id]); ?>
                    </li>
                </ul>
            </fieldset>
            <fieldset id="projectstab">
                <table class="projectsTable">
                    <tr>
                        <td><label style="text-align: left;"><?php echo $this->translate('projects') ?>:</label></td>
                        <td><label style="text-align: left;"><?php echo $this->translate('fixedRate') ?>:</label></td>
                        <td></td>
                    </tr>
                    <?php
                    $assignedProjects = [];
                    if (isset($this->selectedProjects) && is_array($this->selectedProjects)) {
                        foreach ($this->selectedProjects as $selectedProject) {
                            $assignedProjects[] = $selectedProject['projectID'];
                            $name = $this->ellipsis($this->escape($selectedProject['name']), 30) . ' (' . $this->ellipsis($this->escape($selectedProject['customer_name']), 30) . ')';
                            ?>
                            <tr>
                                <td>
                                    <?php echo $name . $this->formHidden('assignedProjects[]', $selectedProject['projectID']); ?>
                                </td>
                                <td>
                                    <?php echo $this->formText('fixedRates[]', $selectedProject['fixedRate']); ?>
                                </td>
                                <td>
                                    <a class="deleteButton">
                                        <img src="../skins/<?php echo $this->escape($this->kga['conf']['skin']) ?>/grfx/close.png" width="22" height="16"/>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    }

                    $selectArray = [-1 => ''];
                    foreach ($this->allProjects as $project) {
                        if (array_search($project['projectID'], $assignedProjects) === false) {
                            $selectArray[$project['projectID']] = $this->ellipsis($project['name'], 30) . ' (' . $this->ellipsis($project['customerName'], 30) . ')';
                        }
                    }
                    ?>
                    <tr class="addRow" <?php if (count($selectArray) <= 1): ?> style="display:none" <?php endif; ?> >
                        <td colspan="5"><?php
                            echo $this->formSelect('newProject', null, null, $selectArray); ?> </td>
                    </tr>
                </table>
            </fieldset>
            <fieldset id="groups">
                <ul>
                    <li>
                        <label for="activityGroups"><?php echo $this->translate('groups') ?>:</label>
                        <?php echo $this->formSelect('activityGroups[]', $this->selectedGroups, [
                            'class' => 'formfield',
                            'id' => 'activityGroups',
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
                        <?php echo $this->formTextarea('comment', $this->activity['comment'], [
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

        var $add_edit_activity = $('#add_edit_activity');
        $add_edit_activity.ajaxForm({
            beforeSubmit: function () {
                clearFloaterErrorMessages();

                if ($add_edit_activity.attr('submitting')) {
                    return false;
                } else {
                    $add_edit_activity.attr('submitting', true);
                    return true;
                }
            },
            success: function (result) {
                $add_edit_activity.removeAttr('submitting');
                for (var fieldName in result.errors) {
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);
                }
                if (result.errors.length == 0) {
                    floaterClose();
                    hook_activities_changed();
                }
            },
            error: function () {
                $add_edit_activity.removeAttr('submitting');
            }
        });

        var $projectstab = $('#projectstab');
        var $addRow = $projectstab.find('.addRow');

        function deleteButtonClicked() {
            var row = $(this).parent().parent()[0];
            var id = $('#assignedProjects', row).val();
            var text = $('td', row).text().trim();
            $('#newProject').append('<option label = "' + text + '" value = "' + id + '">' + text + '</option>');
            $(row).remove();

            if ($('#newProject option').length > 1) {
                $addRow.show();
            }
        }

        $projectstab.find('.deleteButton').click(deleteButtonClicked);

        $('#newProject').change(function () {
            if ($(this).val() == -1) {
                return;
            }

            var row = $('<tr>' +
                '<td>' + $('option:selected', this).text() + '<input type="hidden" name="assignedProjects[]" value="' + $(this).val() + '"/></td>' +
                '<td><input type="text" name="fixedRates[]"/></td>' +
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