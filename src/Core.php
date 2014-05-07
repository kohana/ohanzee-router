<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Ohanzee
 * @author     Kohana Team <team@kohanaframework.org>
 * @copyright  2007-2014 Kohana Team
 * @link       http://ohanzee.org/
 * @license    http://ohanzee.org/license
 * @version    0.1.0
 *
 * Routes are used to determine the controller and action for a requested URI.
 * Every route generates a regular expression which is used to match a URI
 * and a route. Routes may also contain keys which can be used to set the
 * controller, action, and parameters.
 *
 * Each <key> will be translated to a regular expression using a default
 * regular expression pattern. You can override the default pattern by providing
 * a pattern for the key:
 *
 *     // This route will only match when <id> is a digit
 *     Route::set('user', 'user/<action>/<id>', array('id' => '\d+'));
 *
 *     // This route will match when <path> is anything
 *     Route::set('file', '<path>', array('path' => '.*'));
 *
 * It is also possible to create optional segments by using parentheses in
 * the URI definition:
 *
 *     // This is the standard default route, and no keys are required
 *     Route::set('default', '(<controller>(/<action>(/<id>)))');
 *
 *     // This route only requires the <file> key
 *     Route::set('file', '(<path>/)<file>(.<format>)', array('path' => '.*', 'format' => '\w+'));
 *
 * Routes also provide a way to generate URIs (called "reverse routing"), which
 * makes them an extremely powerful and flexible way to generate internal links.
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

	/**
	 * Stores a named route and returns it. The "action" will always be set to
	 * "index" if it is not defined.
	 *
	 *     Route::set('default', '(<controller>(/<action>(/<id>)))')
	 *         ->defaults(array(
	 *             'controller' => 'welcome',
	 *         ));
	 *
	 * @param   string  $name           route name
	 * @param   string  $uri            URI pattern
	 * @param   array   $regex          regex patterns for route keys
	 * @return  Route
	 */
	public function set($name, $uri = NULL, $regex = NULL)
	{
		return Route::$_routes[$name] = new Route($uri, $regex);
	}

	/**
	 * Retrieves a named route.
	 *
	 *     $route = Route::get('default');
	 *
	 * @param   string  $name   route name
	 * @return  Route
	 * @throws  Kohana_Exception
	 */
	public function get($name)
	{
		if ( ! isset(Route::$_routes[$name]))
		{
			throw new Kohana_Exception('The requested route does not exist: :route',
				array(':route' => $name));
		}

		return Route::$_routes[$name];
	}

	/**
	 * Retrieves all named routes.
	 *
	 *     $routes = Route::all();
	 *
	 * @return  array  routes by name
	 */
	public function all()
	{
		return Route::$_routes;
	}

	/**
	 * Get the name of a route.
	 *
	 *     $name = Route::name($route)
	 *
	 * @param   Route   $route  instance
	 * @return  string
	 */
	public function name(Route $route)
	{
		return array_search($route, Route::$_routes);
	}

	/**
	 * Saves or loads the route cache. If your routes will remain the same for
	 * a long period of time, use this to reload the routes from the cache
	 * rather than redefining them on every page load.
	 *
	 *     if ( ! Route::cache())
	 *     {
	 *         // Set routes here
	 *         Route::cache(TRUE);
	 *     }
	 *
	 * @param   boolean $save   cache the current routes
	 * @param   boolean $append append, rather than replace, cached routes when loading
	 * @return  void    when saving routes
	 * @return  boolean when loading routes
	 * @uses    Kohana::cache
	 */
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

	/**
	 * Create a URL from a route name. This is a shortcut for:
	 *
	 *     echo URL::site(Route::get($name)->uri($params), $protocol);
	 *
	 * @param   string  $name       route name
	 * @param   array   $params     URI parameters
	 * @param   mixed   $protocol   protocol string or boolean, adds protocol and domain
	 * @return  string
	 * @since   3.0.7
	 * @uses    URL::site
	 */
	public function url($name, array $params = NULL, $protocol = NULL)
	{
		$route = Route::get($name);

		// Create a URI with the route and convert it to a URL
		if ($route->is_external())
			return $route->uri($params);
		else
			return URL::site($route->uri($params), $protocol);
	}

	/**
	 * Returns the compiled regular expression for the route. This translates
	 * keys and optional groups to a proper PCRE regular expression.
	 *
	 *     $compiled = Route::compile(
	 *        '<controller>(/<action>(/<id>))',
	 *         array(
	 *           'controller' => '[a-z]+',
	 *           'id' => '\d+',
	 *         )
	 *     );
	 *
	 * @return  string
	 * @uses    Route::REGEX_ESCAPE
	 * @uses    Route::REGEX_SEGMENT
	 */
	public function compile($uri, array $regex = NULL)
	{
		// The URI should be considered literal except for keys and optional parts
		// Escape everything preg_quote would escape except for : ( ) < >
		$expression = preg_replace('#'.Route::REGEX_ESCAPE.'#', '\\\\$0', $uri);

		if (strpos($expression, '(') !== FALSE)
		{
			// Make optional parts of the URI non-capturing and optional
			$expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
		}

		// Insert default regex for keys
		$expression = str_replace(array('<', '>'), array('(?P<', '>'.Route::REGEX_SEGMENT.')'), $expression);

		if ($regex)
		{
			$search = $replace = array();
			foreach ($regex as $key => $value)
			{
				$search[]  = "<$key>".Route::REGEX_SEGMENT;
				$replace[] = "<$key>$value";
			}

			// Replace the default regex with the user-specified regex
			$expression = str_replace($search, $replace, $expression);
		}

		return '#^'.$expression.'$#uD';
	}
}