<?php
/*
 * This file is a part of Wurrd Client Authorization Plugin.
 *
 * Copyright 2015 Eyong N <eyongn@scalior.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @file The main file of Wurrd:ClientAuthorization plugin.
 */

namespace Wurrd\Mibew\Plugin\ClientAuthorization;

use Mibew\Plugin\AbstractPlugin;
use Mibew\Plugin\PluginInterface;
use Wurrd\Mibew\Plugin\ClientAuthorization\Constants;
use Wurrd\Mibew\Plugin\ClientAuthorization\WurrdInstaller;

/**
 * The main plugin's file definition.
 *
 * It only attaches handlers to some events.
 */
class Plugin extends AbstractPlugin implements PluginInterface
{
    /**
     * Determine if the plugin was initialized correctly or not.
     */
    protected $initialized = false;

    /**
     * List of the plugin configs.
     *
     * @var array
     */
    protected $config;

    /**
     * Class constructor.
     *
     * @param array $config List of the plugin config. The following options are
     * supported:
     *   - 'client_id': string, an id that the client will present to get an
	 * 					authorization token. It is required.
     */
    public function __construct($config)
    {
    	if (isset($config['client_id'])) {
	        $this->config = $config;
    		$this->initialized = true;
		}
    }

    /**
     * Determine if the plugin is properly initialized.
     *
     * @return boolean
     */
    public function initialized()
    {
        return $this->initialized;
    }

    /**
     * The main entry point of a plugin.
     */
    public function run()
    {
    }

    /**
     * Specify version of the plugin.
     *
     * @return string Plugin's version.
     */
    public static function getVersion()
    {
        return Constants::$WCA_VERSION;
    }


    /**
     * The method installs the necessary tables for this plugin
     *
     * @return boolean - true if successful, false otherwise
     */
    public static function install()
    {
    	$installer = new WurrdInstaller(load_system_configs());
    	return  $installer->createTables();
    }

    /**
     * The method uninstalls the tables created for this plugin.
     *
     * @return boolean
     */
    public static function uninstall()
    {
    	$installer = new WurrdInstaller(load_system_configs());
    	return  $installer->dropTables();
    }
}
