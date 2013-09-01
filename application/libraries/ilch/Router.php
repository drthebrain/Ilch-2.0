<?php
/**
 * @author Meyer Dominik
 * @copyright Ilch CMS 2.0
 * @package ilch
 */

defined('ACCESS') or die('no direct access');

class Ilch_Router
{
	/**
	 * @var array
	 */
	protected $_routes;

	/**
	 * Injects request and config object.
	 *
	 * @param Ilch_Request $request
	 * @param Ilch_Config $config
	 */
	public function __construct(Ilch_Request $request, Ilch_Config $config)
	{
		$this->_request = $request;
		$this->_config = $config;
	}

	/**
	 * Adds a route to router object.
	 *
	 * @param string $name
	 * @param array $routingArray
	 */
	public function addRoute($name, $routingArray)
	{
		$this->_routes[$name] = $routingArray;
	}

	/**
	 * Fills the request object with the best matched route.
	 */
	public function execute()
	{
		$query = ltrim(substr($_SERVER['REQUEST_URI'], strlen(REWRITE_BASE)), '/');
		$this->_request->setModuleName('news');
		$this->_request->setControllerName('index');
		$this->_request->setActionName('index');

		if($this->_config->getConfig('rewrite') == true)
		{
			$this->_executeRewrite($query);
		}
		else
		{
			$this->_executeNonRewrite($query);
		}
	}

	/**
	 * Fills the request object if rewrite is possible.
	 *
	 * @param string $query
	 */
	protected function _executeRewrite($query)
	{
		if(empty($query))
		{
			return;
		}

		$queryParts = explode('/', $query);

		if(isset($queryParts[0]))
		{
			$this->_request->setModuleName($queryParts[0]);
			unset($queryParts[0]);
		}

		if(isset($queryParts[1]))
		{
			$this->_request->setControllerName($queryParts[1]);
			unset($queryParts[1]);
		}

		if(isset($queryParts[2]))
		{
			$this->_request->setActionName($queryParts[2]);
			unset($queryParts[2]);
		}

		if(!empty($queryParts))
		{
			$paramKey = $paramValue = '';

			foreach($queryParts as $value)
			{
				if(!empty($paramKey))
				{
					$this->_request->setParam($paramKey, $value);
					$paramKey = '';
				}

				$paramKey = $value;
			}
		}
	}

	/**
	 * Fills the request object if no rewrite is possible.
	 *
	 * @param string $query
	 */
	protected function _executeNonRewrite($query)
	{
		$query = str_replace('index.php?', '', $query);
		$query = str_replace('index.php', '', $query);
		$queryParts = explode('&', $query);

		if(empty($queryParts))
		{
			return;
		}

		foreach($queryParts as $value)
		{
			$get = explode('=', $value);

			if($get[0] == 'module')
			{
				$this->_request->setModuleName($get[1]);
			}
			elseif($get[0] == 'controller')
			{
				$this->_request->setControllerName($get[1]);
			}
			elseif($get[0] == 'action')
			{
				$this->_request->setActionName($get[1]);
			}
			elseif(!empty($get[1]))
			{
				$this->_request->setParam($get[0], $get[1]);
			}
		}
	}
}