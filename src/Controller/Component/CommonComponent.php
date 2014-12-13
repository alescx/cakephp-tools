<?php
namespace Tools\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\Router;
use Tools\Utility\Utility;

/**
 * A component included in every app to take care of common stuff.
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license MIT
 */
class CommonComponent extends Component {

	/**
	 * CommonComponent::beforeFilter()
	 *
	 * @param Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$this->Controller = $event->subject();
	}

	/**
	 * For this helper the controller has to be passed as reference
	 * for manual startup with $disableStartup = true (requires this to be called prior to any other method)
	 *
	 * @return void
	 */
	public function startup(Event $event) {
		// Data preparation
		if (!empty($this->Controller->request->data) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->data = Utility::trimDeep($this->Controller->request->data);
		}
		if (!empty($this->Controller->request->query) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->query = Utility::trimDeep($this->Controller->request->query);
		}
		if (!empty($this->Controller->request->params['pass']) && !Configure::read('DataPreparation.notrim')) {
			$this->Controller->request->params['pass'] = Utility::trimDeep($this->Controller->request->params['pass']);
		}
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * @param object $Controller Controller with components to beforeRender
	 * @return void
	 */
	public function beforeRender(Event $event) {
		// Custom options
		if (isset($Controller->options)) {
			$Controller->set('options', $Controller->options);
		}
	}

	/**
	 * List all direct actions of a controller
	 *
	 * @return array Actions
	 */
	public function listActions() {
		$class = Inflector::camelize($this->Controller->name) . 'Controller';
		$parentClassMethods = get_class_methods(get_parent_class($class));
		$subClassMethods = get_class_methods($class);
		$classMethods = array_diff($subClassMethods, $parentClassMethods);
		foreach ($classMethods as $key => $value) {
			if (substr($value, 0, 1) === '_') {
				unset($classMethods[$key]);
			}
		}
		return $classMethods;
	}

	/**
	 * Convenience method to check on POSTED data.
	 * Doesn't matter if it's POST, PUT or PATCH.
	 *
	 * Note that you can also use request->is(array('post', 'put', 'patch') directly.
	 *
	 * @return bool If it is of type POST/PUT/PATCH
	 */
	public function isPosted() {
		return $this->Controller->request->is(['post', 'put', 'patch']);
	}

	/**
	 * Add component just in time (inside actions - only when needed)
	 * aware of plugins and config array (if passed)
	 *
	 * @param mixed $components (single string or multiple array)
	 * @param bool $callbacks (defaults to true)
	 */
	public function loadComponent($component, array $config = [], $callbacks = true) {
		list($plugin, $componentName) = pluginSplit($component);
		$this->Controller->loadComponent($component, $config);
		if (!$callbacks) {
			return;
		}
		if (method_exists($this->Controller->{$componentName}, 'beforeFilter')) {
			$this->Controller->{$componentName}->beforeFilter(new \Cake\Event\Event('Controller.initialize', $this->Controller->{$componentName}));
		}
		if (method_exists($this->Controller->{$componentName}, 'startup')) {
			$this->Controller->{$componentName}->startup(new \Cake\Event\Event('Controller.startup', $this->Controller->{$componentName}));
		}
	}

	/**
	 * Used to get the value of a passed param.
	 *
	 * @param mixed $var
	 * @param mixed $default
	 * @return mixed
	 */
	public function getPassedParam($var, $default = null) {
		return (isset($this->Controller->request->params['pass'][$var])) ? $this->Controller->request->params['pass'][$var] : $default;
	}

	/**
	 * Returns defaultUrlParams including configured prefixes.
	 *
	 * @return array Url params
	 */
	public static function defaultUrlParams() {
		$defaults = ['plugin' => false];
		$prefixes = (array)Configure::read('Routing.prefixes');
		foreach ($prefixes as $prefix) {
			$defaults[$prefix] = false;
		}
		return $defaults;
	}

	/**
	 * Returns current url (with all missing params automatically added).
	 * Necessary for Router::url() and comparison of urls to work.
	 *
	 * @param bool $asString: defaults to false = array
	 * @return mixed Url
	 */
	public function currentUrl($asString = false) {
		if (isset($this->Controller->request->params['prefix']) && mb_strpos($this->Controller->request->params['action'], $this->Controller->request->params['prefix']) === 0) {
			$action = mb_substr($this->Controller->request->params['action'], mb_strlen($this->Controller->request->params['prefix']) + 1);
		} else {
			$action = $this->Controller->request->params['action'];
		}

		$url = array_merge($this->Controller->request->params['pass'], ['prefix' => isset($this->Controller->request->params['prefix']) ? $this->Controller->request->params['prefix'] : null,
			'plugin' => $this->Controller->request->params['plugin'], 'action' => $action, 'controller' => $this->Controller->request->params['controller']]);

		if ($asString === true) {
			return Router::url($url);
		}
		return $url;
	}

	/**
	 * Smart Referer Redirect - will try to use an existing referer first
	 * otherwise it will use the default url
	 *
	 * @param mixed $url
	 * @param bool $allowSelf if redirect to the same controller/action (url) is allowed
	 * @param int $status
	 * @return void
	 */
	public function autoRedirect($whereTo, $allowSelf = true, $status = null) {
		if ($allowSelf || $this->Controller->referer(null, true) !== '/' . $this->Controller->request->url) {
			$this->Controller->redirect($this->Controller->referer($whereTo, true), $status);
		}
		$this->Controller->redirect($whereTo, $status);
	}

	/**
	 * Should be a 303, but:
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 *
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 *
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * @param mixed $url
	 * @param int $status
	 * @return void
	 */
	public function postRedirect($whereTo, $status = 302) {
		$this->Controller->redirect($whereTo, $status);
	}

	/**
	 * Combine auto with post
	 * also allows whitelisting certain actions for autoRedirect (use Controller::$autoRedirectActions)
	 * @param mixed $url
	 * @param bool $conditionalAutoRedirect false to skip whitelisting
	 * @param int $status
	 * @return void
	 */
	public function autoPostRedirect($whereTo, $conditionalAutoRedirect = true, $status = 302) {
		$referer = $this->Controller->referer($whereTo, true);
		if (!$conditionalAutoRedirect && !empty($referer)) {
			$this->postRedirect($referer, $status);
		}

		if (!empty($referer)) {
			$referer = Router::parse($referer);
		}

		if (!$conditionalAutoRedirect || empty($this->Controller->autoRedirectActions) || is_array($referer) && !empty($referer['action'])) {
			// Be sure that controller offset exists, otherwise you
			// will run into problems, if you use url rewriting.
			$refererController = null;
			if (isset($referer['controller'])) {
				$refererController = Inflector::camelize($referer['controller']);
			}
			// fixme
			if (!isset($this->Controller->autoRedirectActions)) {
				$this->Controller->autoRedirectActions = [];
			}
			foreach ($this->Controller->autoRedirectActions as $action) {
				list($controller, $action) = pluginSplit($action);
				if (!empty($controller) && $refererController !== '*' && $refererController != $controller) {
					continue;
				}
				if (empty($controller) && $refererController != Inflector::camelize($this->Controller->request->params['controller'])) {
					continue;
				}
				if (!in_array($referer['action'], $this->Controller->autoRedirectActions, true)) {
					continue;
				}
				$this->autoRedirect($whereTo, true, $status);
			}
		}
		$this->postRedirect($whereTo, $status);
	}

	/**
	 * Automatically add missing url parts of the current url including
	 * - querystring (especially for 3.x then)
	 * - passed params
	 *
	 * @param mixed $url
	 * @param int $status
	 * @param bool $exit
	 * @return void
	 */
	public function completeRedirect($url = null, $status = null, $exit = true) {
		if ($url === null) {
			$url = $this->Controller->request->params;
			unset($url['pass']);
			unset($url['isAjax']);
		}
		if (is_array($url)) {
			$url += $this->Controller->request->params['pass'];
		}
		return $this->Controller->redirect($url, $status, $exit);
	}

	/**
	 * Only redirect to itself if cookies are on
	 * Prevents problems with lost data
	 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is a concern, the 302 status code may be used instead, since most user agents react to a 302 response as described here for 303.
	 *
	 * @see http://en.wikipedia.org/wiki/Post/Redirect/Get
	 * TODO: change to 303 with backwardscompatability for older browsers...
	 * @param int $status
	 * @return void
	 */
	public function prgRedirect($status = 302) {
		if (!empty($_COOKIE[Configure::read('Session.cookie')])) {
			$this->Controller->redirect('/' . $this->Controller->request->url, $status);
		}
	}

	/**
	 * Set headers to cache this request.
	 * Opposite of Controller::disableCache()
	 * TODO: set response class header instead
	 *
	 * @param int $seconds
	 * @return void
	 */
	public function forceCache($seconds = HOUR) {
		$this->Controller->response->header('Cache-Control', 'public, max-age=' . $seconds);
		$this->Controller->response->header('Last-modified', gmdate("D, j M Y H:i:s", time()) . " GMT");
		$this->Controller->response->header('Expires', gmdate("D, j M Y H:i:s", time() + $seconds) . " GMT");
	}

	/**
	 * Referrer checking (where does the user come from)
	 * Only returns true for a valid external referrer.
	 *
	 * @return bool Success
	 */
	public function isForeignReferer($ref = null) {
		if ($ref === null) {
			$ref = env('HTTP_REFERER');
		}
		if (!$ref) {
			return false;
		}
		$base = Configure::read('App.fullBaseUrl') . '/';
		if (strpos($ref, $base) === 0) {
			return false;
		}
		return true;
	}

}
