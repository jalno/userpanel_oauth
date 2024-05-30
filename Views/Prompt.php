<?php

namespace packages\userpanel_oauth\Views;

use packages\userpanel\Views\Form;
use packages\userpanel_oauth\App;

class Prompt extends Form
{
    /**
     * @var App|null the app which requests to get the permissions
     */
    protected $app;

    /**
     * @var string|null an URI which after successfull grant/deny permissions the user will redirect to
     */
    protected $redirect;

    /**
     * @var string|null some random string which is important for App gateway validator after redirecting user
     */
    protected $state;

    /**
     * @var string|null an URI which after reject access the user will redirect to
     */
    protected $rejectRedirect;

    public function setApp(App $app): void
    {
        $this->app = $app;
    }

    public function getApp(): ?App
    {
        return $this->app;
    }

    /**
     * Get an URI which after successfull grant/deny permissions the user will redirect to.
     */
    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    /**
     * Set an URI which after successfull grant/deny permissions the user will redirect to.
     */
    public function setRedirect(?string $redirect): void
    {
        $this->redirect = $redirect;
    }

    /**
     * Get some random string which is important for App gateway validator after redirecting user.
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Set some random string which is important for App gateway validator after redirecting user.
     */
    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * Get an URI which after reject access the user will redirect to.
     */
    public function getRejectRedirect(): ?string
    {
        return $this->rejectRedirect;
    }

    /**
     * Set an URI which after reject access the user will redirect to.
     */
    public function setRejectRedirect(?string $rejectRedirect): void
    {
        $this->rejectRedirect = $rejectRedirect;
    }
}
