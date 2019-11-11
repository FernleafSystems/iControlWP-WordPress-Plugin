{
  "properties": {
    "slug":                   "security",
    "name":                   "Security",
    "show_feature_menu_item": false,
    "storage_key":            "security",
    "auto_enabled":           false
  },
  "sections":   [
    {
      "slug":   "section_non_ui",
      "hidden": true
    }
  ],
  "options":    [
    {
      "key":     "current_plugin_version",
      "section": "section_non_ui"
    },
    {
      "key":     "enable_security",
      "section": "section_non_ui",
      "default": "N"
    },
    {
      "key":     "disallow_file_edit",
      "section": "section_non_ui",
      "default": "N"
    },
    {
      "key":     "force_ssl_admin",
      "section": "section_non_ui",
      "default": "N"
    },
    {
      "key":     "hide_wp_version",
      "section": "section_non_ui",
      "default": "N"
    },
    {
      "key":     "hide_wlmanifest_link",
      "section": "section_non_ui",
      "default": "N"
    },
    {
      "key":     "hide_rsd_link",
      "section": "section_non_ui",
      "default": "N"
    },
    {
      "key":     "cloudflare_flexible_ssl",
      "section": "section_non_ui",
      "default": "N"
    }
  ]
}