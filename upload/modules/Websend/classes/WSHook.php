<?php
/*
 *	Made by Samerton
 *  https://github.com/samerton
 *  NamelessMC version 2.0.0-pr4
 *
 *  License: MIT
 *
 *  Websend hook class
 */

class WSHook {
	private static $_events = array();
	private static $_ws = null;

	public static function setEvents($events){
		self::$_events = $events;
	}

	public static function initWebsend($conf){
		self::$_ws = new Websend($conf['websend_server_address'], $conf['websend_server_port']);
		self::$_ws->setPassword($conf['websend_password']);
	}

	public static function execute($params = array()){
		if(!isset($params['event']) || is_null(self::$_ws))
			return false;

		if(array_key_exists($params['event'], self::$_events)){
			$event = self::$_events[$params['event']];

			if(count($event)){
				self::$_ws->connect();

				foreach($event as $command){
					self::$_ws->doCommandAsConsole(str_replace('{USERNAME}', $params['username'], $command));
				}

				self::$_ws->disconnect();

				return true;
			}
		}

		return false;

	}
}