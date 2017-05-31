<?php
return "---
properties:
  slug: 'plugin'
  name: 'Dashboard'
  show_feature_menu_item: true
  storage_key: 'plugin' # should correspond exactly to that in the plugin.yaml
  auto_enabled: true
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
    key: 'key'
    section: 'section_non_ui'
    default: ''
  -
    key: 'pin'
    section: 'section_non_ui'
    default: ''
  -
    key: 'assigned'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'assigned_to'
    section: 'section_non_ui'
    default: ''
  -
    key: 'helpdesk_sso_url'
    section: 'section_non_ui'
    default: ''
  -
    key: 'time_last_check_can_handshake'
    section: 'section_non_ui'
    value: 0
  -
    key: 'can_handshake'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'activated_at'
    section: 'section_non_ui'
  -
    key: 'installation_time'
    section: 'section_non_ui'
  -
    key: 'enable_hide_plugin'
    section: 'section_non_ui'
    default: 'N'
  -
    key: 'feedback_admin_notice'
    section: 'section_non_ui'
  -
    key: 'active_plugin_features'
    section: 'section_non_ui'
    value:
      -
        slug: 'security'
        storage_key: 'security'
        load_priority: 0
      -
        slug: 'compatibility'
        storage_key: 'compatibility'
      -
        slug: 'google_analytics'
        storage_key: 'google_analytics'
      -
        slug: 'autoupdates'
        storage_key: 'autoupdates'
      -
        slug: 'statistics'
        storage_key: 'statistics'
      -
        slug: 'whitelabel'
        storage_key: 'whitelabel'

# Definitions for constant data that doesn't need store in the options
definitions:

  icwp_public_key:  'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0NCk1JSUJJakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBUThBTUlJQkNnS0NBUUVBdWxOM2lKRHZEdURGM2JIcnYrSEYNCjZ3T0RVai9GbGtFY1QvYzB5QWllYXNYTXNUQWRxN3AwWVBPQmtMSy92RFAyTE04b054dHA4MzlVUkI2aGFDa2sNCmRPUUZCdHpwY0UvU0NJZjVDSUJEeWhDVUlhRENtK1JnZDlpWmxISldBbzVGZkRlODlxb3FJTGRodkp2UHlzbTYNCkQ0b3hmcXYzMlF1TTV2VjUyT3ZaU1Q5WG1ydytPcHRCc0Rjbjk5THlOdGhYZ3RweHJEVnlTZGljVzBqelpYUHANCm1xbUE0SEZqMzQ3Z3hMNVB1Q0hXcEgyN3RqMCtYSjE3TFoyWHNSQWtaaE1TdEJtTUtBaW02R25yMkVQTTJBc20NCklCWGtzcEs5M2lHVGZiYUlMZE4vQ0NGTmVaUlh5WGNyV1hNV1Bvd0VFQVN0ZXJHNXN1QWlRSkhjVDBwaW0za2oNCjZ3SURBUUFCDQotLS0tLUVORCBQVUJMSUMgS0VZLS0tLS0='
  urls:
    handshake_verify_test_url: 'https://app.icontrolwp.com/system/verification/test'
    handshake_verify_url: 'https://app.icontrolwp.com/system/verification/check'
    remote_add_site_url: 'https://app.icontrolwp.com/system/remote/add_site'
    reset_site_url: 'https://app.icontrolwp.com/system/verification/reset/'
    package_retrieve_url: 'https://app.icontrolwp.com/system/package/retrieve/'

  permitted_api_channels:
    - 'index'
    - 'status'
    - 'auth'
    - 'internal'
    - 'retrieve'
    - 'execute'
    - 'login'
  internal_api_supported_actions:
    - 'collect_info'
    - 'collect_plugins'
    - 'collect_sync'
    - 'collect_themes'
    - 'comments_retrieve'
    - 'comments_status'
    - 'core_update'
    - 'core_reinstall'
    - 'db_status'
    - 'db_optimise'
    - 'plugin_activate'
    - 'plugin_deactivate'
    - 'plugin_delete'
    - 'plugin_install'
    - 'plugin_rollback'
    - 'plugin_update'
    - 'site_unlink'
    - 'theme_activate'
    - 'theme_delete'
    - 'theme_install'
    - 'theme_update'
    - 'user_create'
    - 'user_delete'
    - 'user_list'
    - 'user_login'
  supported_modules:
    - 'security'
    - 'statistics'
    - 'google_analytics'
    - 'whitelabel'
    - 'autoupdates'
    
  service_ip_addresses:
    ipv6:
      valid:
        - '2001:4801:7817:0072:ca75:cc9b:ff10:4699' #wd01
        - '2001:4801:7817:72:ca75:cc9b:ff10:4699' #wd01
        - '2001:4801:7824:0101:ca75:cc9b:ff10:a7b2' #app01
        - '2001:4801:7824:101:ca75:cc9b:ff10:a7b2' #app01
        - '2001:4801:7828:0101:be76:4eff:fe11:9cd6' #app2
        - '2001:4801:7828:101:be76:4eff:fe11:9cd6' #app2
        - '2001:4801:7822:0103:be76:4eff:fe10:89a9' #wd02
        - '2001:4801:7822:103:be76:4eff:fe10:89a9' #wd02
    ipv4:
      valid:
        - '198.61.176.9' #wd01
        - '23.253.56.59' #app1a
        - '23.253.62.185' #app1b
        - '104.130.217.172' #app2
        - '23.253.32.180' #wd02
      old:
        - '198.61.173.69'
";