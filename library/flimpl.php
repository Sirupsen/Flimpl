<?php
final class Flimpl {

	// Static variable to wield files in cores directory
	private static $cores;

	// Static variable to wield all files in helpers directory
	private static $helpers;

	// Static variable to hold our registry
	private static $registry;

	public static function setup() {
		// Set autoloader
		spl_autoload_register(array('Flimpl', 'auto_load'));
	
		// Set error handler
		set_error_handler(array('Flimpl', 'error_handler'));	

		// Set exception handler
		set_exception_handler(array('Flimpl', 'exception_handler'));

		// Scan directories for the auto loader
		self::$cores = scandir('../library');
		self::$helpers = scandir('../application/helpers');

		// Instance registry
		self::$registry = Registry::getInstance();

		// Instance DB into registry
		self::$registry->db = new Database(self::getConfiguration());
		self::$registry->config = self::getConfiguration();
	}

	public static function run() {
		// Explode all the parameters from the URL into chunks
		$param = explode('/', $_GET['url']);

		// The controller to be loaded is the first parameter
		$controller = $param['0'];

		// Remove the first entry from the array [Controller]
		array_shift($param);
		// Get the new first entry, the action [Method]
		$action = $param['0'];

		/*
		*
		* Remove the first entry again [The action/Method]
		* The rest is optional parameters for the method.
		* 
		*/

		array_shift($param);

		// If no action is defined, use the index action [Index method]
		if (!$action) $action = 'index';

		// If no controller is defined [Url is blank], use homepage
		if (!$controller) $controller = 'home';

		// Controller class names are uppercase
		$class = ucfirst($controller);

		// If the action [Method] on the controller [Class] exists:
		if ((int)method_exists($class, $action)) {
			// Call the method equal to the action, and pass all the
			// parameters to it
			
			// Call the class
			$dispatch = new $class($controller, $action);

			call_user_func_array(array($dispatch, $action), $param);

			if ($config['dev_debug'] == 'true') {
				echo 'Method <b>' . $action . '</b> on <b>' . $class . '</b> instanced<br/>';
			}
		} elseif (file_exists(ROOT . 'application/views/' . $controller . '/' . $action . '.php')) {
				require(ROOT . 'application/views/' . $controller . '/' . $action . '.php');
		} else {
			require(ROOT . 'public/misc/errors/404.php');
			exit;
		}
	}

	public static function auto_load($class) {
		$class = strtolower($class) . '.php'; 

		// If the class requested exists in the core folder, include it here
		if (in_array($class, self::$cores)) {
			require('../library/' . $class);
			
			// If we are development debugging, tell dev. we are loading
			if(self::$registry->config['dev_debug']) {
				echo "Loaded Core <b>$class</b>!<br/>";
			}

		// If class is helper, include it from here
		} elseif (in_array($class, self::$helpers)) {
			require('../application/helpers/' . $class);

			if(self::$registry->config['dev_debug']) {
				echo "Loaded Helper <b>$class</b>!<br/>";
			}
		
		// Else, it has to be a controller
		} elseif (file_exists('../application/controllers/' . $class)) {
			require('../application/controllers/' . $class);

			if(self::$registry->config['dev_debug']) {
				echo "Loaded Controller <b>$class</b>!<br/>";
			}

		// 404
		} else {
			if(self::$registry->config['dev_debug']) {
				echo "Couldn't find <b>$class</b>! (Configured root dir?)<br/>";
			}

			require('../public/misc/errors/404.php');
			// Exit, no more to see than this custom page
			exit;
		}
	}

	public static function exception_handler($exception) { ?>
		<div class="error">
			<?php echo $exception->getMessage(); ?>
		</div>
		<?php
	}

	public static function error_handler($errno, $errstr, $errfile, $errline) {
		echo $errorstr;
	}

	public static function getConfiguration() {
		require('config.php');

		return $config;
	}

}