# v2.3.2
## 12/15/2016

1. [](#improved)
    * Update to latest Shortcode library v0.6.4 to address a parser bug [#25](https://github.com/getgrav/grav-plugin-shortcode-core/issues/25)

# v2.3.1
## 07/14/2016

1. [](#improved)
    * renamed internal `contentMeta` variables to `shortcodeMeta` and `shortcodeAssets`
    * Update to latest Shortcode library
    
# v2.3.0
## 05/20/2016

1. [](#improved)
    * Use new conentmeta approach from Grav 1.1

# v2.2.1
## 05/09/2016

1. [](#bugfix)
    * Always initialize current page even if collection exists [#3](https://github.com/getgrav/grav-plugin-shortcode-ui/issues/3)

# v2.2.0
## 04/23/2016

1. [](#new)
    * Added **new** `fa` FontAwesome shortcode

# v2.1.0
## 04/21/2016

1. [](#new)
    * Added **new** `notice` shortcode
1. [](#improved)    
    * Updated to latest Shortcode library version

# v2.0.2
## 02/17/2016

1. [](#bugfix)
    * Initialized states in constructor

# v2.0.1
## 02/16/2016

1. [](#improved)
    * Support **modular** pages by populating Twig variables in `onTwigPageVariables()` event #8
1. [](#bugfix)
    * Better more flexible regex in the Markdown **block** definition for more reliable markdown in shortcodes. #3
    
# v2.0.0
## 02/11/2016

1. [](#new)
    * Added **new** `section` shortcode
    * Use new `contentMeta` mechanism for storing/caching objects and assets per page
    * Added new `ShortcodeManager::reset()` methods
1. [](#improved)
    * Completely refactored the plugin to use a new extensible mechanism that makes it easier to manage multiple shortcodes
    
# v1.4.0
## 02/03/2016

1. [](#improved)
    * Updated Shortcode to latest `dev-master` that includes Events
1. [](#bugfix)
    * Fixed `raw` shortcode to use new `FilterRawEventHandler` so it doesn't process shortcodes at all

# v1.3.0
## 01/29/2016

1. [](#improved)
    * Added markdown-shortcode-block support to the plugin
1. [](#bugfix)
    * Updated Core Thunderer Shortcode library with some important fixes

# v1.2.0
## 01/25/2016

1. [](#improved)
    * Customizable Parser.  Choose from `WordPress`, `Regex`, and `Regular`

# v1.1.0
## 01/24/2016

1. [](#improved)
    * Updated to latest Shortcode `dev-master` version that contains some important fixes
    * Switched to `WordPressParser` for 2x speed improvements

# v1.0.1
## 01/18/2016

1. [](#bugfix)
    * Fixed blueprint
    * Fixed a default yaml state


# v1.0.0
## 01/18/2016

1. [](#new)
    * ChangeLog started...
