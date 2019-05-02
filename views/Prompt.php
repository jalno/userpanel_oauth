<?php
namespace packages\userpanel_oauth\views;

use packages\userpanel\views\Form;
use packages\userpanel_oauth\App;

class Prompt extends Form {
	/**
	 * @var App|null The app which requests to get the permissions.
	 */
	protected $app;

	/**
	 * @var string|null An URI which after successfull grant/deny permissions the user will redirect to.
	 */
	protected $redirect;

	/**
	 * @var string|null some random string which is important for App gateway validator after redirecting user.
	 */
	protected $state;

	/**
	 * @var string|null An URI which after reject access the user will redirect to.
	 */
	protected $rejectRedirect;


	public function setApp(App $app): void {
		$this->app = $app;
	}

	public function getApp(): ?App {
		return $this->app;
	}

	/**
	 * Get an URI which after successfull grant/deny permissions the user will redirect to.
	 *
	 * @return string|null
	 */ 
	public function getRedirect(): ?string {
		return $this->redirect;
	}

	/**
	 * Set an URI which after successfull grant/deny permissions the user will redirect to.
	 *
	 * @param string|null  $redirect
	 * @return void
	 */ 
	public function setRedirect(?string $redirect): void {
		$this->redirect = $redirect;
	}

	/**
	 * Get some random string which is important for App gateway validator after redirecting user.
	 *
	 * @return  string|null
	 */ 
	public function getState(): ?string {
		return $this->state;
	}

	/**
	 * Set some random string which is important for App gateway validator after redirecting user.
	 *
	 * @param string|null  $state
	 * @return void
	 */ 
	public function setState(?string $state): void {
		$this->state = $state;
	}

	/**
	 * Get an URI which after reject access the user will redirect to.
	 *
	 * @return string|null
	 */ 
	public function getRejectRedirect(): ?string {
		return $this->rejectRedirect;
	}

	/**
	 * Set an URI which after reject access the user will redirect to.
	 *
	 * @param string|null  $rejectRedirect
	 * @return void
	 */ 
	public function setRejectRedirect(?string $rejectRedirect): void {
		$this->rejectRedirect = $rejectRedirect;
	}
}
