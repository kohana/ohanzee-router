<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Ohanzee
 * @author     Kohana Team <team@kohanaframework.org>
 * @copyright  2007-2014 Kohana Team
 * @link       http://ohanzee.org/
 * @license    http://ohanzee.org/license
 * @version    0.1.0
 */
namespace Ohanzee\Router;

class Core {

	// Matches a URI group and captures the contents
	const REGEX_GROUP   = '\(((?:(?>[^()]+)|(?R))*)\)';

	// Defines the pattern of a <segment>
	const REGEX_KEY     = '<([a-zA-Z0-9_]++)>';

	// What can be part of a <segment> value
	const REGEX_SEGMENT = '[^/.,;?\n]++';

	// What must be escaped in the route regex
	const REGEX_ESCAPE  = '[.\\+*?[^\\]${}=!|]';

	/**
	 * @var  string  default protocol for all routes
	 *
	 * @example  'http://'
	 */
	public $default_protocol = 'http://';

	/**
	 * @var  array   list of valid localhost entries
	 */
	public $localhosts = array(FALSE, '', 'local', 'localhost');

	/**
	 * @var  string  default action for all routes
	 */
	public $default_action = 'index';

	/**
	 * @var  bool Indicates whether routes are cached
	 */
	public $cache = FALSE;

	/**
	 * @var  array
	 */
	protected $_routes = array();

	public function set($name, $uri = NULL, $regex = NULL)
	{
		return Route::$_routes[$name] = new Route($uri, $regex);
	}

	public function get($name)
	{
		if ( ! isset(Route::$_routes[$name]))
		{
			throw new Kohana_Exception('The requested route does not exist: :route',
				array(':route' => $name));
		}

		return Route::$_routes[$name];
	}

	public function all()
	{
		return Route::$_routes;
	}

	public function name(Route $route)
	{
		return array_search($route, Route::$_routes);
	}

	public function cache($save = FALSE, $append = FALSE)
	{
		if ($save === TRUE)
		{
			try
			{
				// Cache all defined routes
				Kohana::cache('Route::cache()', Route::$_routes);
			}
			catch (Exception $e)
			{
				// We most likely have a lambda in a route, which cannot be cached
				throw new Kohana_Exception('One or more routes could not be cached (:message)', array(
					':message' => $e->getMessage(),
				), 0, $e);
			}
		}
		else
		{
			if ($routes = Kohana::cache('Route::cache()'))
			{
				if ($append)
				{
					// Append cached routes
					Route::$_routes += $routes;
				}
				else
				{
					// Replace existing routes
					Route::$_routes = $routes;
				}

				// Routes were cached
				return Route::$cache = TRUE;
			}
			else
			{
				// Routes were not cached
				return Route::$cache = FALSE;
			}
		}
	}

	public function url($name, array $params = NULL, $protocol = NULL)
	{
		$route = Route::get($name);

		// Create a URI with the route and convert it to a URL
		if ($route->is_external())
			return $route->uri($params);
		else
			return URL::site($route->uri($params), $protocol);
	}


}