<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 **/

/**
 * Dummy model to simulate the other ActiveRecord models
 *
 * @package Omeka
 * @subpackage Models
 * @author CHNM
 * @copyright Center for History and New Media, 2007-2010
 **/ 
class Theme 
{
    const THEME_IMAGE_FILE_NAME = 'theme.jpg';
    const THEME_INI_FILE_NAME = 'theme.ini';
    const THEME_CONFIG_FILE_NAME = 'config.ini';
    
    const PUBLIC_THEME_OPTION = 'public_theme';
    const ADMIN_THEME_OPTION = 'admin_theme';
    
    public $path;
    public $directory;
    public $image;
    public $author;
    public $title;
    public $description;
    public $license;
    public $website;
    
    public function setDirectoryName($dir)
    {
        // Define a hard theme path for the theme
        $this->path = PUBLIC_THEME_DIR . DIRECTORY_SEPARATOR . $dir;
        $this->directory = $dir;
    }
    
    public function setImage($fileName)
    {
        // Test to see if an image is available to present the user
        // when switching themes
        $imageFile = $this->path . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($imageFile) && is_readable($imageFile)) {
            $img = WEB_PUBLIC_THEME . '/' . $this->directory . '/' . $fileName;
            $this->image = $img;
        }
    }
    
    public function setIni($fileName)
    {
        $themeIni = $this->path . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($themeIni) && is_readable($themeIni)) {
            $ini = new Zend_Config_Ini($themeIni, 'theme');
            foreach ($ini as $key => $value) {
                $this->$key = $value;
            }
        }
    }
    
    public function setConfig($fileName)
    {
        // Get the theme's config file
        $themeConfig = $this->path . DIRECTORY_SEPARATOR . $fileName;
        
        // If the theme has a config file, set hasConfig to true.
        $this->hasConfig = (file_exists($themeConfig) && is_readable($themeConfig));
    }
    
    /**
     * Retrieve an available theme (or all themes).
     *
     * @param string|null $themeName  The name of the theme.  If null, it returns all available themes.
     * @return Theme|array A theme object or array of theme objects
     **/
    static public function getAvailable($themeName=null) 
    {
        /**
         * Create an array of themes, with the directory paths
         * theme.ini files and images paths if they are present
         */
        if (!$themeName) {
            $themes = array();
            $iterator = new VersionedDirectoryIterator(PUBLIC_THEME_DIR);
            $themeDirs = $iterator->getValid();
            foreach ($themeDirs as $themeName) {
                $themes[$themeName] = self::getAvailable($themeName);
            }
            return $themes;
        } else {
            $theme = new Theme();            
            $theme->setDirectoryName($themeName);
            $theme->setImage(self::THEME_IMAGE_FILE_NAME);
            $theme->setIni(self::THEME_INI_FILE_NAME);
            $theme->setConfig(self::THEME_CONFIG_FILE_NAME);
            return $theme;
        }
    }
    
    /** 
     * Set theme configuration options.
     * 
     * @param string $themeName  The name of the theme
     * @param array $themeConfigOptions An associative array of configuration options, 
     *                                  where each key is a configuration form input name and 
     *                                  each value is a string value of that configuration form input
     * @return void 
     **/    
    static public function setOptions($themeName, $themeConfigOptions)
    {
        $themeOptionName = self::getOptionName($themeName);
        set_option($themeOptionName, serialize($themeConfigOptions));
    }
    
    /** 
     * Get theme configuration options.
     * 
     * @param string $themeName  The name of the theme
     * @return array An associative array of configuration options, 
     *               where each key is a configuration form input name and 
     *               each value is a string value of that configuration form input
     **/
    static public function getOptions($themeName)
    {
        $themeOptionName = self::getOptionName($themeName);
        $themeConfigOptions = get_option($themeOptionName);
        $themeConfigOptions = apply_filters('theme_options', $themeConfigOptions, $themeName);
        if ($themeConfigOptions) {
            $themeConfigOptions = unserialize($themeConfigOptions);
        } else {
            $themeConfigOptions = array();
        }
        return $themeConfigOptions;
    }
    
    /** 
     * Get the value of a theme configuration option.
     * 
     * @param string $themeName  The name of the theme
     * @param string $themeOptionName The name of the theme option
     * @return string The value of the theme option
     */
    static public function getOption($themeName, $themeOptionName)
    {
        $themeOptionValue = null;
        $themeName = trim($themeName);
        $themeOptionName = Inflector::underscore($themeOptionName);
        $themeConfigOptions = self::getOptions($themeName);
        if ($themeConfigOptions && array_key_exists($themeOptionName, $themeConfigOptions)) {        
            $themeOptionValue = $themeConfigOptions[$themeOptionName];
        }
        return $themeOptionValue;
    }
    
    /** 
     * Set the value of a theme configuration option.
     * 
     * @param string $themeName  The name of the theme
     * @param string $themeOptionName The name of the theme option
     * @param string The value of the theme option
     * @return void
     */
    static public function setOption($themeName, $themeOptionName, $themeOptionValue)
    {
        $themeName = trim($themeName);
        $themeOptionName = Inflector::underscore($themeOptionName);
        $themeConfigOptions = self::getOptions($themeName);
        $themeConfigOptions[$themeOptionName] = $themeOptionValue;
        self::setOptions($themeName, $themeConfigOptions);        
    }
    
    /** 
     * Get the name of a specific theme's option.  Each theme has a single option in the option's table, 
     * which stores all of the configuration options for that theme
     * 
     * @param string $themeName  The name of the theme
     * @return string The name of a specific theme's option.
     **/
    static public function getOptionName($themeName)
    {
        $themeOptionName = 'theme_' . trim(strtolower($themeName)) . '_options';
        return $themeOptionName;
    }
    
    /** 
     * Get the name of a file uploaded as a theme configuration option.  
     * This is the name of the file after it has been uploaded and renamed.
     * 
     * @param string $themeName  The name of the theme
     * @param string $optionName The name of the theme option associated with the uploaded file
     * @param string $fileName The name of the uploaded file
     * @return string The name of an uploaded file for the theme.
     **/
    static public function getUploadedFileName($themeName, $optionName, $fileName)
    {
        return trim(strtolower($themeName)) . '_' . trim($optionName) . '_' . trim($fileName);
    }
}