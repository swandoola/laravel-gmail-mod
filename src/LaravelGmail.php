<?php

namespace Swandoola\LaravelGmail;

use Illuminate\Support\Facades\Config;
use Swandoola\LaravelGmail\Exceptions\AuthException;
use Swandoola\LaravelGmail\Services\Message;

class LaravelGmail extends GmailConnection
{
    protected $service;

	public function __construct($service, $userId = null)
	{
	    $this->service = $service;

	    $config = Config::get('gmail');

	    if ($this->service === 'gmail'){
	        $config['redirect_url'] = env('GOOGLE_REDIRECT_URI');
            $config['state'] = auth()->user()->mailConfig->state_uuid;
        } else if ($this->service === 'calendar'){
            $config['redirect_url'] = env('GOOGLE_CALENDAR_REDIRECT_URI');
            $config['state'] = auth()->user()->calendarIntegrationConfig->state_uuid;
        }


		parent::__construct($config, $userId);
	}

	/**
	 * @return Message
	 * @throws AuthException
	 */
	public function message()
	{
		if (!$this->getToken()) {
			throw new AuthException('No credentials found.');
		}

		return new Message($this);
	}

	/**
	 * Returns the Gmail user email
	 *
	 * @return \Google_Service_Gmail_Profile
	 */
	public function user()
	{
		return $this->config('email');
	}

	/**
	 * Updates / sets the current userId for the service
	 *
	 * @return \Google_Service_Gmail_Profile
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
		return $this;
	}

	public function redirect()
	{
	    if ($this->service === 'gmail'){
            return $this->createAuthUrl($this->prepareScopes());
        } else if ($this->service === 'calendar') {
	        return $this->createAuthUrl($this->prepareCalendarScopes());
        }
	}

	public function prepareCalendarScopes() {

    }

	public function logout()
	{
		$this->revokeToken();
		$this->deleteAccessToken();
	}

}
