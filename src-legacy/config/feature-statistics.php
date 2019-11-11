<?php
return "---
properties:
  slug: 'statistics'
  name: 'Statistics'
  show_feature_menu_item: false
  storage_key: 'statistics' # should correspond exactly to that in the plugin.yaml
  auto_enabled: false
# Options Sections
sections:
  -
    slug: 'section_non_ui'
    hidden: true

# Define Options
options:
  -
    key: 'current_plugin_version'
    section: 'section_non_ui'
  -
    key: 'enable_statistics'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_daily_statistics'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'enable_monthly_statistics'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'ignore_logged_in_user'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'ignore_from_user_level'
    section: 'section_non_ui'
    default: 11

# Definitions for constant data that doesn't need stored in the options
definitions:
  statistics_table_name: 'site_statistics'
  statistics_table_columns:
    - 'id'
    - 'page_id'
    - 'uri'
    - 'day_id'
    - 'month_id'
    - 'year_id'
    - 'count_total'
    - 'created_at'
    - 'deleted_at'
";