<?php

class loader {

	public $loaded_files = array();

	//Load a library
	public function library($class = NULL, $params = NULL, $name = NULL, $module = FALSE) {

		//Is this a module's library -or a global system library?
		$path = ($module ? MODULE_PATH. $module. DS : SYSTEM_PATH). 'libraries'. DS;

		//Try to load the class
		return $this->object($class, $name, $path, $params);
	}


	//Load a model
	public function model($class = NULL, $params = NULL, $name = NULL, $module = FALSE) {

		//Is this a module's model -or a site model?
		$path = ($module ? MODULE_PATH. $module. DS : SITE_PATH). 'models'. DS;

		//Try to load the class
		return $this->object($class, $name, $path, $params);
	}


	/**
	 * Loads and instantiates models, libraries, and other classes
	 *
	 * @param	string	the name of the class
	 * @param	string	name for the class
	 * @param	array	params to pass to the model constructor
	 * @param	string	folder name of the class
	 * @return	void
	 */
	public function object($class = NULL, $name = NULL, $path = NULL, $params = NULL) {

		//If a model is NOT given
		if ( ! $class OR ! $path) { return FALSE; }

		//Allow classes to be located in subdirectories (sub/sub2/class)
		if (strpos($class, '/') !== FALSE) {

			// explode the path so we can separate the filename from the path
			$x = explode('/', $class);

			// Get class name from end of string
			$class = array_pop($x);

			// Glue the path back together (minus the filename)
			$path .= implode($x, DS). DS;
		}

		//If a name is not given
		if( ! $name) { $name = $class; }

		//Load the class
		$this->$name = load_class($class, $path, $params);

		return TRUE;
	}


	//Load a helper
	public function helper($name = NULL, $module = FALSE) {

		//Is this a module's library -or a global system library?
		$path = ($module ? MODULE_PATH. $module. DS : SYSTEM_PATH). 'functions'. DS. $name. '.php';

		//If already loaded
		if(in_array($path, $this->loaded_files)) {
			return TRUE;
		}

		//Log this file (faster than using require_once)
		$this->loaded_files[] = $path;

		//Try to load the file
		return require_once($path);
	}


	/**
	 * Load a config file
	 * @param string $config
	 */
	public function config($name=null, $module = FALSE) {

		//Only load once
		if(!empty($this->config[$name])) {
			return $this->config[$name];
		}

		//Is this a module's config -or a site config?
		$path = ($module ? MODULE_PATH. $module. DS : SITE_PATH). 'config'. DS. $name. '.php';

		//include the config
		require($path);

		//Set the values in our config array and return
		return $this->config[$name] = $$name;

	}


	/**
	 * Load and initialize the database connection
	 * @param array $config
	 */
	public function database() {

		//Don't load the DB object twice!!!
		if(!empty($this->db)) { return; }

		//Load the DB class (but don't create the class)
		load_class('db', LIBRARY_PATH, NULL, FALSE);

		//Load the config for this database
		$config = $this->config('database');

		//Create a new instance of the database child class "mysql"
		$this->db = load_class($config['type'], NULL, $config);
	}


	/**
	 * This function is used to load views files.
	 *
	 * @access	private
	 * @param	String	file path/name
	 * @param	array	values to pass to the view
	 * @param	boolean	return the output or print it?
	 * @return	void
	 */
	public function view($__file = NULL, $__variables = NULL, $__return = TRUE, $__module = FALSE) {

		//If no file is given - just return false
		if(!$__file) { return; }

		//Is this a module's library -or a global system library?
		$__path = ($__module ? MODULE_PATH. $__module. DS : SITE_PATH);

		//Add the path
		$__path .= 'views'. DS. $__file. '.php';

		if(is_array($__variables)) {
			//Make each value passed to this view available for use
			foreach($__variables as $__key => $__variable) {
				$$__key = $__variable;
			}
		}

		// Delete them now
		$__variables = null;

		// We just want to print to the screen
		if( ! $__return) {
			if( ! include($__path)) {
				return FALSE;
			}
		}

		//Buffer the output so we can save it to a string
		ob_start();

		// include() vs include_once() allows for multiple views with the same name
		include($__path);

		//Get the output
		$__buffer = ob_get_contents();
		ob_end_clean();

		//Save to the views array (and also return a copy)
		return $this->views[$__module. DS. $__file] = $__buffer;

	}

}
?>