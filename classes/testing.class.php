<?

class Testing {
	private static $ClassDirectories = array("classes");
	private static $Classes = array();

	/**
	 * Initialize the testasble classes into a map keyed by class name
	 */
	public static function init() {
		self::load_classes();
	}

	/**
	 * Gets the class
	 */
	public static function get_classes() {
		return self::$Classes;
	}

	/**
	 * Loads all the classes within given directories
	 */
	private static function load_classes() {
		foreach (self::$ClassDirectories as $Directory)  {
			$Directory = SERVER_ROOT . "/" . $Directory . "/";
			foreach (glob($Directory . "*.php") as $FileName) {
				self::get_class_name($FileName);
			}
		}
	}

	/**
	 * Gets the class and adds into the map
	 */
	private static function get_class_name($FileName) {
		$Tokens = token_get_all(file_get_contents($FileName));
		$IsTestable = false;
		$IsClass = false;

		foreach ($Tokens as $Token) {
			if (is_array($Token)) {
				if (!$IsTestable && $Token[0] == T_DOC_COMMENT && strpos($Token[1], "@TestClass")) {
					$IsTestable = true;
				}
				if ($IsTestable && $Token[0] == T_CLASS) {
					$IsClass = true;
				} else if ($IsClass && $Token[0] == T_STRING) {
					$ReflectionClass = new ReflectionClass($Token[1]);
					if (count(self::get_testable_methods($ReflectionClass))) {
						self::$Classes[$Token[1]] = new ReflectionClass($Token[1]);
					}
					$IsTestable = false;
					$IsClass = false;
				}
			}
		}
	}

	/**
	 * Checks if class exists in the map
	 */
	public static function has_class($Class) {
		return array_key_exists($Class, self::$Classes);
	}

	/**
	 * Checks if class has a given testable methood
	 */
	public static function has_testable_method($Class, $Method) {
		$TestableMethods = self::get_testable_methods($Class);
		foreach($TestableMethods as $TestMethod) {
			if ($TestMethod->getName() === $Method) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get testable methods in a class, a testable method has a @Test
	 */
	public static function get_testable_methods($Class) {
		if (is_string($Class)) {
			$ReflectionClass = self::$Classes[$Class];
		} else {
			$ReflectionClass = $Class;
		}
		$ReflectionMethods = $ReflectionClass->getMethods();
		$TestableMethods = array();
		foreach($ReflectionMethods as $Method) {
			if ($Method->isPublic() && $Method->isStatic() && strpos($Method->getDocComment(), "@Test")) {
				$TestableMethods[] = $Method;
			}
		}
		return $TestableMethods;
	}


	/**
	 * Get the class comment
	 */
	public static function get_class_comment($Class) {
		$ReflectionClass = self::$Classes[$Class];
		return trim(str_replace(array("@TestClass", "*", "/"), "", $ReflectionClass->getDocComment()));
	}

	/**
	 * Get the undocumented methods in a class
	 */
	public static function get_undocumented_methods($Class) {
		$ReflectionClass = self::$Classes[$Class];
		$Methods = array();
		foreach($ReflectionClass->getMethods() as $Method) {
			if (!$Method->getDocComment()) {
				$Methods[] = $Method;
			}
		}
		return $Methods;
	}

	/**
	 * Get the documented methods
	 */
	public static function get_documented_methods($Class)  {
		$ReflectionClass = self::$Classes[$Class];
		$Methods = array();
		foreach($ReflectionClass->getMethods() as $Method) {
			if ($Method->getDocComment()) {
				$Methods[] = $Method;
			}
		}
		return $Methods;
	}

	/**
	 * Get all methods in a class
	 */
	public static function get_methods($Class) {
		return self::$Classes[$Class]->getMethods();
	}

	/**
	 * Get a method  comment
	 */
	public static function get_method_comment($Method) {
		return trim(str_replace(array("*", "/"), "", $Method->getDocComment()));
	}

}