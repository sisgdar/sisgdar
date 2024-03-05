<?php

/**
 * Module Manager
 *
 */

namespace Leantime\Domain\Modulemanager\Services {


    use Leantime\Domain\Plugins\Services\Plugins;

    /**
     *
     */
    class Modulemanager
    {
        use \Leantime\Core\Eventhelpers;

        private static array $modules = array(
            "api" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "calendar" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "clients" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "comments" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "dashboard" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "files" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "general" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "help" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "ideas" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "ldap" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "leancanvas" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "projects" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "read" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "reports" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "retroscanvas" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "setting" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "sprints" => array("required" => false, "enabled" => true, "dependsOn" => "tickets", "scope" => "project"),
            "tickets" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "timesheets" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "project"),
            "twoFA" => array("required" => false, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "users" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
            "modulemanager" => array("required" => true, "enabled" => true, "dependsOn" => "", "scope" => "system"),
        );

        /**
         * __construct - get and test Session or make session
         *
         * @access private
         */
        public function __construct(Plugins $plugins)
        {
            $this->pluginService = $plugins;
        }

        /**
         * @param $module
         * @return bool
         */
        public static function isModuleEnabled($module): bool
        {
            if (isset(self::$modules[$module])) {
                if (self::$modules[$module]['enabled'] === true) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks if a module is available.
         * In Progress: This method is a stub to hook into via filters.
         *
         * @param string $module The name of the module to check availability for.
         *
         * @return bool Returns true if the module is available, false otherwise.
         */
        public function isModuleAvailable(string $module): bool
        {
            $available = false;


            $plugins = $this->pluginService->getEnabledPlugins();

            $filtered = collect($plugins)->filter(function ($plugin) use ($module) {
                return strtolower($plugin->foldername) == strtolower($module);
            });

            if($filtered->count() > 0){
                $available = true;
            }

            $available = static::dispatch_filter("moduleAvailability", $available, ["module" => $module]);


            return $available;
        }
    }

}
