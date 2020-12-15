<?php

namespace Swandoola\LaravelGmail\Traits;

use Google_Service_Gmail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

/**
 * Trait Configurable
 * @package Swandoola\LaravelGmail\Traits
 */
trait Configurable
{

	protected $additionalScopes = [];
	private $_config;

	public function __construct($config)
	{
		$this->_config = $config;
	}

    public function config($string = null)
    {
        $credentials = $this->getClientGmailCredentials();

        $allowJsonEncrypt = $this->_config['allow_json_encrypt'];

        if ($credentials) {
            if ($allowJsonEncrypt) {
                $config = json_decode(decrypt($credentials->config), true);
            } else {
                $config = json_decode($credentials->config, true);
            }

            if ($string) {
                if (isset($config[$string])) {
                    return $config[$string];
                }
            } else {
                return $config;
            }

        }

        return null;
    }

    private function getClientGmailCredentials()
    {
        $userId = auth()->id();

        $mailConfig = \App\Models\MailConfig::where('practitioner_id', $userId)->where('type', 'google')->first();

        $allowMultipleCredentials = $this->_config['allow_multiple_credentials'];

        if ($mailConfig && $allowMultipleCredentials) {

            return $mailConfig;
        }
        return false;
    }

	/**
	 * @return array
	 */
	public function getConfigs()
	{
		return [
			'client_secret' => $this->_config['client_secret'],
			'client_id' => $this->_config['client_id'],
			'redirect_uri' => url($this->_config['redirect_url']),
			'state' => isset($this->_config['state']) ? $this->_config['state'] : null,
		];
	}

	public function setAdditionalScopes(array $scopes)
	{
		$this->additionalScopes = $scopes;

		return $this;
	}

	private function configApi($service)
	{
		$type = $this->_config['access_type'];
		$approval_prompt = $this->_config['approval_prompt'];

		if ($service === 'gmail') {
            $this->setScopes($this->getGmailScopes());
        } else if ($service === 'calendar') {
            $this->setScopes($this->getCalendarScopes());
        }

		$this->setAccessType($type);

		$this->setApprovalPrompt($approval_prompt);
	}

	public abstract function setScopes($scopes);

	private function getGmailScopes()
	{
        $scopes = $this->_config['scopes'];
        $scopes = array_unique(array_filter($scopes));
        $mappedScopes = [];

        if (!empty($scopes)) {
            foreach ($scopes as $scope) {
                $mappedScopes[] = $this->scopeMap($scope);
            }
        }

        return $mappedScopes;
	}

	private function getCalendarScopes()
	{
        $scopes = $this->_config['calendar_scopes'];
        return $scopes;
	}

	private function scopeMap($scope)
	{
		$scopes = [
			'all' => Google_Service_Gmail::MAIL_GOOGLE_COM,
			'compose' => Google_Service_Gmail::GMAIL_COMPOSE,
			'insert' => Google_Service_Gmail::GMAIL_INSERT,
			'labels' => Google_Service_Gmail::GMAIL_LABELS,
			'metadata' => Google_Service_Gmail::GMAIL_METADATA,
			'modify' => Google_Service_Gmail::GMAIL_MODIFY,
			'readonly' => Google_Service_Gmail::GMAIL_READONLY,
			'send' => Google_Service_Gmail::GMAIL_SEND,
			'settings_basic' => Google_Service_Gmail::GMAIL_SETTINGS_BASIC,
			'settings_sharing' => Google_Service_Gmail::GMAIL_SETTINGS_SHARING,
		];

		return Arr::get($scopes, $scope);
	}

	public abstract function setAccessType($type);

	public abstract function setApprovalPrompt($approval);

}
