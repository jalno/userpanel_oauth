<?php
namespace packages\userpanel_oauth\Listeners;

use packages\userpanel\Events\AuthenticationHandlersEvent as Event;
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