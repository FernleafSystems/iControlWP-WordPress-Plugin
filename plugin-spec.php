{
  "properties":   {
    "version":                 "3.7.0",
    "release_timestamp":       1573226583,
    "build":                   "201911.0801",
    "slug_parent":             "icwp",
    "slug_plugin":             "app",
    "text_domain":             "worpit-admin-dashboard-plugin",
    "base_permissions":        "manage_options",
    "options_encoding":        "yaml",
    "wpms_network_admin_only": true,
    "logging_enabled":         false,
    "autoupdate":              "confidence"
  },
  "requirements": {
    "php":       "5.2.4",
    "wordpress": "3.5.0"
  },
  "paths":        {
    "source":    "src",
    "assets":    "assets",
    "temp":      "tmp",
    "languages": "languages",
    "templates": "templates",
    "flags":     "flags",
    "cache":     "icwp"
  },
  "includes":     {
    "admin":        {
      "css": [
        "global-plugin"
      ]
    },
    "plugin_admin": {
      "css": [
        "bootstrap-wpadmin",
        "bootstrap-wpadmin-fixes",
        "plugin"
      ]
    },
    "frontend":     {
      "css": null
    }
  },
  "menu":         {
    "show":           true,
    "top_level":      true,
    "do_submenu_fix": true,
    "callback":       "onDisplayTopMenu",
    "icon_image":     "icontrolwp_16x16.png",
    "has_submenu":    true
  },
  "labels":       {
    "Name":           "iControlWP",
    "Description":    "Take Control Of All WordPress Sites From A Single Dashboard",
    "Title":          "iControlWP",
    "Author":         "iControlWP",
    "AuthorName":     "iControlWP",
    "PluginURI":      "http://icwp.io/home",
    "AuthorURI":      "http://icwp.io/home",
    "icon_url_16x16": "icontrolwp_16x16.png",
    "icon_url_32x32": "icontrolwp_32x32.png"
  },
  "plugin_meta":  null,
  "action_links": {
    "remove": null,
    "add":    [
      {
        "name":            "Dashboard",
        "url_method_name": "getPluginUrl_AdminMainPage"
      }
    ]
  }
}