<?php

class IssuuPanelOptionEntity
{
	/**
	*	issuu_painel_api_bearer_token option
	*
	*	@var string
	*/
	private $apiBearerToken = '';

	/**
	*	issuu_painel_enabled_user option
	*
	*	@var string
	*/
	private $enabledUser = 'Administrator';

	/**
	*	issuu_painel_debug option
	*
	*	@var string
	*/
	private $debug = 'disable';

	/**
	*	issuu_painel_shortcode_cache option
	*
	*	@var array
	*/
	private $shortcodeCache = array();

	/**
	*	issuu_painel_cache_status option
	*
	*	@var string
	*/
	private $cacheStatus = 'disable';

	/**
	*	issuu_painel_reader option
	*
	*	@var string
	*/
	private $reader = 'issuu_embed';

	/**
	*	issuu_painel_cron option
	*
	*	@var array
	*/
	private $cron = array();

	public function setApiBearerToken($apiBearerToken)
	{
		if (is_string($apiBearerToken))
		{
			$this->apiBearerToken = trim($apiBearerToken);
		}
	}

	public function getApiBearerToken()
	{
		return $this->apiBearerToken;
	}

	public function setEnabledUser($enabledUser)
	{
		$valids = array(
			'Administrator',
			'Editor',
			'Author',
		);
		$enabledUser = trim($enabledUser);

		if (in_array($enabledUser, $valids))
		{
			$this->enabledUser = $enabledUser;
		}
	}

	public function getEnabledUser()
	{
		return $this->enabledUser;
	}

	public function setDebug($debug)
	{
		$valids = array(
			'active',
			'disable',
		);
		$debug = trim($debug);

		if (in_array($debug, $valids))
		{
			$this->debug = $debug;
		}
	}

	public function getDebug()
	{
		return $this->debug;
	}

	public function setShortcodeCache($shortcodeCache)
	{
		if (is_array($shortcodeCache))
		{
			$this->shortcodeCache = $shortcodeCache;
		}
		else if(is_string($shortcodeCache))
		{
			$this->shortcodeCache = unserialize($shortcodeCache);
		}
	}

	public function getShortcodeCache($serialize = false)
	{
		return (($serialize === true)? serialize($this->shortcodeCache) : $this->shortcodeCache);
	}

	public function setCacheStatus($cacheStatus)
	{
		$valids = array(
			'active',
			'disable',
		);
		$cacheStatus = trim($cacheStatus);

		if (in_array($cacheStatus, $valids))
		{
			$this->cacheStatus = $cacheStatus;
		}
	}

	public function getCacheStatus()
	{
		return $this->cacheStatus;
	}

	public function setReader($reader)
	{
		$valids = array(
			'issuu_embed',
			'issuu_panel_simple_reader',
		);
		$reader = trim($reader);

		if (in_array($reader, $valids))
		{
			$this->reader = $reader;
		}
	}

	public function getReader()
	{
		return $this->reader;
	}

	public function setCron($cron)
	{
		if (is_array($cron))
		{
			$this->cron = $cron;
		}
		else if (is_string($cron))
		{
			$this->cron = unserialize($cron);
		}
	}

	public function getCron($serialize = false)
	{
		return (($serialize === true)? serialize($this->cron) : $this->cron);
	}

	public function toArray($serialize = false)
	{
		return array(
			ISSUU_PANEL_PREFIX . 'api_bearer_token' => $this->getApiBearerToken(),
			ISSUU_PANEL_PREFIX . 'enabled_user' => $this->getEnabledUser(),
			ISSUU_PANEL_PREFIX . 'debug' => $this->getDebug(),
			ISSUU_PANEL_PREFIX . 'shortcode_cache' => $this->getShortcodeCache($serialize),
			ISSUU_PANEL_PREFIX . 'cache_status' => $this->getCacheStatus(),
			ISSUU_PANEL_PREFIX . 'reader' => $this->getReader(),
			ISSUU_PANEL_PREFIX . 'cron' => $this->getCron($serialize),
		);
	}

	public function toJSON()
	{
		return json_encode($this->toArray());
	}

	public function exchangeArray(array $options)
	{
		foreach ($options as $key => $value) {
			$name = strtr($key, array(ISSUU_PANEL_PREFIX => '', '_' => ' '));
			$method = 'set' . strtr(ucwords($name), array(' ' => ''));

			if (method_exists($this, $method))
			{
				call_user_func(array($this, $method), $value);
			}
		}
	}
}