ZFMultilingual
==============

Library for simplifying Zend Framework 1.12 multilingual sites

Sample Configuration
--------------------

    ; -------------
    ;  Basic Setup
    ; -------------
    autoloaderNamespaces.ZFMultilingual = "ZFMultilingual_"
    pluginpaths.ZFMultilingual_Application_Resource = "ZFMultilingual/Application/Resource"
    
    resources.multilingual.localeParameter = 'locale'
    resources.multilingual.allowedLocales = "en, es, fr"

    ; --- Translator for routes ---
    resources.multilingual.translate.adapter = "tmx"
    resources.multilingual.translate.content = APPLICATION_PATH "/configs/translated.routes.xml";
    resources.multilingual.translate.locale = "en"
    
    ; --- Adding a custom route ---
    ;resources.multilingual.routes.contact.type = "Zend_Controller_Router_Route_Static"
    ;resources.multilingual.routes.contact.route = "contact"
    ;resources.multilingual.routes.contact.default.module = "default"
    ;resources.multilingual.routes.contact.default.controller = "index"
    ;resources.multilingual.routes.contact.default.action = "contact"

