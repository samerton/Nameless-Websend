<?php
/*
 *	Made by Samerton
 *  https://github.com/samerton
 *  NamelessMC version 2.0.0-pr4
 *
 *  License: MIT
 *
 *  Websend initialisation file
 */

// Ensure module has been installed
$module_installed = $cache->retrieve('module_websend');
if(!$module_installed){
	// Hasn't been installed
	$admin_permissions = $queries->getWhere('groups', array('id', '=', 2));
	$admin_permissions = $admin_permissions[0]->permissions;

	$admin_permissions = json_decode($admin_permissions, true);
	$admin_permissions['admincp.websend'] = 1;

	$admin_permissions_updated = json_encode($admin_permissions);

	$queries->update('groups', 2, array(
		'permissions' => $admin_permissions_updated
	));

	try {
		$engine = Config::get('mysql/engine');
		$charset = Config::get('mysql/charset');
	} catch(Exception $e){
		$engine = 'InnoDB';
		$charset = 'utf8mb4';
	}

	try {
		if(!$queries->tableExists('websend_commands')){
			$queries->createTable('websend_commands', ' `id` int(11) NOT NULL AUTO_INCREMENT, `hook` varchar(64) NOT NULL, `command` text NOT NULL, `enabled` tinyint(1) NOT NULL DEFAULT \'0\', PRIMARY KEY (`id`)', "ENGINE=$engine DEFAULT CHARSET=$charset");
		}
	} catch(Exception $e){
		$ws_db_err = true;
	}

	$ws_conf =  '<?php' . PHP_EOL .
				'$ws_config = array(' . PHP_EOL .
				'   \'websend_server_address\' => \'\',' . PHP_EOL .
				'   \'websend_server_port\' => 4445,' . PHP_EOL .
				'   \'websend_password\' => \'\'' . PHP_EOL .
				');';

	if(file_put_contents(ROOT_PATH . '/modules/Websend/config.php', $ws_conf) === false){
		$ws_conf_err = true;

	} else {
		if(!isset($ws_db_err))
			$cache->store('module_websend', true);

	}

} else {
	// Installed
}

// Language
$websend_language = new Language(ROOT_PATH . '/modules/Websend/language', LANGUAGE);

// Permissions
PermissionHandler::registerPermissions('Websend', array(
	'admincp.websend' => $language->get('admin', 'admin_cp') . ' &raquo; ' . $websend_language->get('language', 'websend')
));

// Pages
$pages->add('Websend', '/admin/websend', 'pages/admin/websend.php');

// Add link to admin sidebar
if($user->isLoggedIn() && $user->hasPermission('admincp.websend')) {
	if (!isset($admin_sidebar)) $admin_sidebar = array();
	$admin_sidebar['websend'] = array(
		'title' => $websend_language->get('language', 'websend'),
		'url' => URL::build('/admin/websend')
	);
}

// Hooks
$ws_hooks = $queries->getWhere('websend_commands', array('enabled', '=', 1));
if(count($ws_hooks)){
	require_once(ROOT_PATH . '/modules/Websend/config.php');
	require_once(ROOT_PATH . '/modules/Websend/classes/Websend.php');
	require_once(ROOT_PATH . '/modules/Websend/classes/WSHook.php');

	WSHook::initWebsend($ws_config);

	$ws_events = array();
	foreach($ws_hooks as $hook){
		$ws_events[$hook->hook] = explode(PHP_EOL, $hook->commands);

		HookHandler::registerHook($hook->hook, 'WSHook::execute');
	}

	WSHook::setEvents($ws_events);
}