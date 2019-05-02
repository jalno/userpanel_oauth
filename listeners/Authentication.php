<?php
namespace packages\userpanel_oauth\listeners;

use packages\userpanel\events\AuthenticationHandlersEvent as Event;
use packages\userpanel_oauth\AccessAuthenticationHandler;

class AuthenticationHandlers {

	/**
	 * Add built-in authentication method to the list.
	 * 
	 * @param AuthenticationHandlersEvent $e
	 * @return void
	 */
	public function add(Event $e) {
		$e->addHandler(AccessAuthenticationHandler::class);
	}
}