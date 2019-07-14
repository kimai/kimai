{% extends 'base.html.twig' %}
{% import "macros/widgets.html.twig" as widgets %}
{% import "macros/datatables.html.twig" as tables %}
{% import "macros/toolbar.html.twig" as toolbar %}
{% import "macros/actions.html.twig" as actions %}
{% import _self as timesheet %}

{% set tableName = 'timesheet' %}
{% set canSeeRate = is_granted('view_rate_own_timesheet') %}
{% set columns = {
    'date': 'alwaysVisible',
} %}

{% if showStartEndTime %}
    {% set columns = columns|merge({
        'starttime': '',
        'endtime': 'hidden-xs'
    }) %}
{% endif %}
{% set columns = columns|merge({'duration': ''}) %}
{% if canSeeRate %}
    {% set columns = columns|merge({'rate': 'hidden-xs'}) %}
{% endif %}
{% set columns = columns|merge({
    'customer': 'hidden-xs hidden-sm hidden-md',
    'project': 'hidden-xs hidden-sm hidden-md',
    'activity': 'hidden-xs hidden-sm',
    'description': 'hidden-xs hidden-sm',
    'tags': 'hidden-xs hidden-sm',
    'actions': 'actions alwaysVisible',
}) %}

{% block page_title %}{{ 'timesheet.title'|trans }}{% endblock %}
{% block page_subtitle %}{{ 'timesheet.subtitle'|trans }}{% endblock %}
{% block page_actions %}{{ actions.timesheets('index') }}{% endblock %}

{% block main_before %}
    {{ toolbar.toolbar(toolbarForm, 'collapseTimesheet', showFilter) }}
    {{ tables.data_table_column_modal(tableName, columns) }}
{% endblock %}

{% block main %}

    {% if entries.count == 0 %}
        {{ widgets.callout('warning', 'error.no_entries_found') }}
    {% else %}
        {{ tables.data_table_header(tableName, columns, showSummary, 'kimai.timesheetUpdate') }}

        {% set day = null %}
        {% set dayDuration = 0 %}
        {% set dayRate = {} %}
        {% for entry in entries %}
            {%- set customerCurrency = entry.project.customer.currency -%}
            {%- if day is same as(null) -%}
                {% set day = entry.begin|date_short %}
            {% endif %}
            {%- if showSummary and day is not same as(entry.begin|date_short) -%}
                {{ timesheet.summary(day, dayDuration, dayRate, columns, canSeeRate, showStartEndTime, tableName) }}
                {% set day = entry.begin|date_short %}
                {% set dayDuration = 0 %}
                {% set dayRate = {} %}
            {%- endif -%}
            <tr{% if is_granted('edit', entry) %} class="modal-ajax-form open-edit" data-href="{{ path('timesheet_edit', {'id': entry.id}) }}"{% endif %}>
                <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'date') }}">{{ entry.begin|date_short }}</td>

                {% if showStartEndTime %}
                    <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'starttime') }}">{{ entry.begin|time }}</td>
                    <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'endtime') }}">
                        {% if entry.end %}
                            {{ entry.end|time }}
                        {% else %}
                            &dash;
                        {% endif %}
                    </td>
                {% endif %}

                {% if entry.end %}
                    <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'duration') }}">{{ entry.duration|duration }}</td>
                {% else %}
                    <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'duration') }}">
                        <i data-since="{{ entry.begin.format(constant('DATE_ISO8601')) }}" data-format="{{ get_format_duration() }}">{{ entry|duration }}</i>
                    </td>
                {% endif %}

                {% if canSeeRate %}
                <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'rate') }}">
                    {% if not entry.end %}
                        &dash;
                    {% else %}
                        {{ entry.rate|money(entry.project.customer.currency) }}
                    {% endif %}
                </td>
                {% endif %}

                <td class="{{ tables.data_table_column_class(tableName, columns, 'customer') }}">{{ widgets.label_customer(entry.project.customer) }}</td>
                <td class="{{ tables.data_table_column_class(tableName, columns, 'project') }}">{{ widgets.label_project(entry.project) }}</td>
                <td class="{{ tables.data_table_column_class(tableName, columns, 'activity') }}">{{ widgets.label_activity(entry.activity) }}</td>
                <td class="{{ tables.data_table_column_class(tableName, columns, 'description') }} timesheet-description">{{ entry.description|escape|desc2html }}</td>
                <td class="{{ tables.data_table_column_class(tableName, columns, 'tags') }}">{{ widgets.tag_list(entry.tags) }}</td>
                <td class="actions">
                    {{- actions.timesheet(entry, 'index') -}}
                </td>
            </tr>
            {%- if entry.end -%}
                {% if dayRate[customerCurrency] is not defined %}
                    {% set dayRate = dayRate|merge({(customerCurrency): 0}) %}
                {% endif %}
                {% set dayRate = dayRate|merge({(customerCurrency): dayRate[customerCurrency] + entry.rate}) %}
            {%- endif -%}
            {%- set dayDuration = dayDuration + entry.duration -%}
        {% endfor %}

        {% if showSummary %}
            {{ timesheet.summary(day, dayDuration, dayRate, columns, canSeeRate, showStartEndTime, tableName) }}
        {% endif %}

        {{ tables.data_table_footer(entries, 'timesheet_paginated') }}
    {% endif %}

{% endblock %}

{% macro summary(day, duration, dayRates, columns, canSeeRate, showStartEndTime, tableName) %}
    {% import "macros/datatables.html.twig" as tables %}
    <tr class="summary info">
        <td class="text-nowrap">{{ day }}</td>
        {% if showStartEndTime %}
            <td class="{{ tables.data_table_column_class(tableName, columns, 'starttime') }}"></td>
            <td class="{{ tables.data_table_column_class(tableName, columns, 'endtime') }}"></td>
        {% endif %}
        <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'duration') }}">{{ duration|duration }}</td>
        {% if canSeeRate %}
            <td class="text-nowrap {{ tables.data_table_column_class(tableName, columns, 'rate') }}">
                {% for currency, rate in dayRates %}
                    {{ rate|money(currency) }}
                    {% if not loop.last %}
                        <br>
                    {% endif %}
                {% endfor %}
            </td>
        {% endif %}
        <td class="{{ tables.data_table_column_class(tableName, columns, 'customer') }}"></td>
        <td class="{{ tables.data_table_column_class(tableName, columns, 'project') }}"></td>
        <td class="{{ tables.data_table_column_class(tableName, columns, 'activity') }}"></td>
        <td class="{{ tables.data_table_column_class(tableName, columns, 'description') }}"></td>
        <td class="{{ tables.data_table_column_class(tableName, columns, 'tags') }}"></td>
        <td class="actions"></td>
    </tr>
{% endmacro %}
