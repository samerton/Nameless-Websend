<?php
/*
 *	Made by Samerton, updated by Supercrafter100
 *  https://github.com/samerton, https://github.com/supercrafter100
 *  NamelessMC version 2.0.0-pr11
 *
 *  License: MIT
 *
 *  Websend initialisation file
 */

// Language
$websend_language = new Language(ROOT_PATH . '/modules/Websend/language', LANGUAGE);

require_once(ROOT_PATH . '/modules/Websend/module.php');
$module = new Websend_Module($pages, $language, $websend_language, $queries, $cache);