<?php
namespace Lukiman\Cores;

class Loader {
	protected static $_path     = 'Additional/';

	protected static $_config   = 'config/';

	protected static $_assets   = 'Assets/';
	protected static $_assetsHtml   = 'Html/';
	protected static $_assetsImage   = 'Images/';
    
    protected static ?Env $env;

	public static function Load(String $module) : void {
		self::Include_File($module);
	}

	private static function Include_File (String $module) : mixed {
		$file = self::$_path . $module . '/' . $module . '.php';
		if (is_readable($file)) include_once($file);
        else if (is_readable(static::getRootFolder() . $file)) return include_once(static::getRootFolder() . $file);
		else if (is_readable(ROOT_PATH . $file)) include_once(ROOT_PATH . $file);
		else if (is_readable(LUKIMAN_ROOT_PATH . $file)) include_once(LUKIMAN_ROOT_PATH . $file);
	}

    /**
    * Resolve Env file Path
    * this method will check if env config file exists (config.{env}.php)
    * 
    * @param string $file
    *
    * @return Env|null
    * */
    private static function resolveEnv(String $file) : Env|null { 
        $env = null;
        
        if (isset(self::$env) && self::$env instanceof Env) {
            return self::$env;
        }
        
        if (is_readable($file)) $env = include($file);
		else if (is_readable(ROOT_PATH . $file)) $env = include_once(ROOT_PATH . $file);
		else if (is_readable(LUKIMAN_ROOT_PATH . $file)) $env = include_once(LUKIMAN_ROOT_PATH . $file);
        return $env instanceof Env ? $env : null;
    }

    /**
     * Resolve Config file Path 
     * this method will check if env config file exists (config.{env}.php)
     *
     * @param type $file
     * 
     * @return string
     * */
    public static function resolveConfigFile(string $file = '') :string {
        $file = self::$_config . $file;

        $env = self::resolveEnv(self::$_config . 'Env.php');
        
        if (!empty($env) && $env instanceof Env) {
            $envFile = $file.$env->getPathname().'.php';
            
            if (is_readable($envFile)) {
                return $envFile;
            }
        }
        
        return $file. '.php';
    }

    /**
     * Function for load file configuration
     * @param string $file
     *
     * @return mixed
     */
    public static function Config(String $file = '') : mixed {
        $file = self::resolveConfigFile($file);
		if (is_readable($file)) return include($file);
		else if (is_readable(ROOT_PATH . $file)) return include_once(ROOT_PATH . $file);
		else if (is_readable(static::getRootFolder() . $file)) return include_once(static::getRootFolder() . $file);
		else if (is_readable(LUKIMAN_ROOT_PATH . $file)) return include_once(LUKIMAN_ROOT_PATH . $file);
    }

     /**
     * Function for load file AssetsHtml
     * @param type $file
     */
    public static function AssetsHtml($file = '') : String|bool {
        $file = self::$_assets . self::$_assetsHtml . $file . '.htm';
        if (is_readable($file)) return file_get_contents($file);
        else if (is_readable(static::getRootFolder() . $file)) return file_get_contents(static::getRootFolder() . $file);

    }

	public static function Include_Assets($file = '', $type = 'htm') : mixed {
        $file = self::$_assets . self::$_assetsHtml . $file . '.' . $type;
        if (is_readable($file)) return include($file);
        else if (is_readable(static::getRootFolder() . $file)) return include_once(static::getRootFolder() . $file);
    }

    /**
     * Function for load file AssetsImage
     * @param type $file
     */
    public static function AssetsImage($file = '') : mixed {
        $file = self::$_assets . self::$_assetsImage . $file;
            if (is_readable($file)) return $file;
    }

    public static function getRootFolder() : String {
        return dirname(__DIR__) . '/';
    }

}
