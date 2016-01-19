<?php
/*
 * This file is a part of Wurrd AuthAPI plugin.
 *
 * Copyright 2005-2015 the original author or authors.
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

namespace Wurrd\Mibew\Plugin\AuthAPI;

use Mibew\Database;
use Mibew\Mail\Utils as MailUtils;
use Mibew\Maintenance\Installer;
use Symfony\Component\Yaml\Parser as YamlParser;
use Wurrd\Mibew\Plugin\AuthAPI\Constants;

/**
 * Encapsulates installation process.
 */
class WurrdInstaller extends Installer
{
    /**
     * Class constructor.
     *
     * @param array $system_configs Associative array of system configs.
     */
    public function __construct($system_configs)
    {
    	parent::__construct($system_configs);
    }

    /**
     * Create tables.
     *
     * One can get all logged messages of this step using
     * {@link Installer::getLog()} method. Also the list of all errors can be
     * got using {@link Installer::getErrors()}.
     *
     * @return boolean True if all tables are created and false otherwise.
     */
    public function createTables()
    {
        if ($this->tablesExist() && $this->tablesNeedUpdate()) {
            // Tables already exists but they should be updated
            $this->errors[] = getlocal('The tables are alredy in place but outdated. Run the updater to fix it.');
            return false;
        }

        if ($this->tablesExist()) {
            $this->log[] = getlocal('Tables structure is up to date.');
            return true;
        }

        // There are no tables in the database. We need to create them.
        if (!$this->doCreateTables()) {
            return false;
        }
        $this->log[] = getlocal('Tables are created.');

        if (!$this->prepopulateDatabase()) {
            return false;
        }
        $this->log[] = getlocal('Tables are pre popluated with necessary info.');

        return true;
    }


    /**
     * Drop tables.
     *
     * One can get all logged messages of this step using
     * {@link Installer::getLog()} method. Also the list of all errors can be
     * got using {@link Installer::getErrors()}.
     *
     * @return boolean True if all tables are dropped and false otherwise.
     */
    public function dropTables()
    {
        // Drop the tables.
        if (!$this->doDropTables()) {
            return false;
        }
        $this->log[] = getlocal('Tables are removed.');

		// Remove version info from config
        if (!$this->removeVersionInfo()) {
            return false;
        }
        $this->log[] = getlocal('Plugin version removed from database.');

        return true;
    }

    /**
     * Performs all database updates needed for 0.1.3.
     *
     * @return boolean True if the updates have been applied successfully and
     * false otherwise.
     */
    public function update00103()
    {
        $db = $this->getDatabase();

        if (!$db) {
            return false;
        }

        $db->query('START TRANSACTION');
        try {
            // Alter device table.
            $db->query('ALTER TABLE {waa_device} ADD COLUMN dtmcreated int NOT NULL DEFAULT 0');
            $db->query('ALTER TABLE {waa_device} ADD COLUMN dtmmodified int NOT NULL DEFAULT 0');
            $db->query('ALTER TABLE {waa_device} ADD INDEX idx_device (deviceuuid, platform)');
			
            // Alter authorization table.
            $db->query('ALTER TABLE {waa_authorization} ADD COLUMN dtmcreated int NOT NULL DEFAULT 0 AFTER clientid');
            $db->query('ALTER TABLE {waa_authorization} ADD COLUMN dtmmodified int NOT NULL DEFAULT 0 AFTER dtmcreated');
            $db->query('ALTER TABLE {waa_authorization} ADD INDEX idx_accesstoken (accesstoken)');
            $db->query('ALTER TABLE {waa_authorization} ADD INDEX idx_refreshtoken (refreshtoken)');
            $db->query('ALTER TABLE {waa_authorization} ADD INDEX idx_deviceid (deviceid)');
            $db->query('ALTER TABLE {waa_authorization} ADD INDEX idx_previousaccesstoken (previousaccesstoken)');
			
        } catch (\Exception $e) {
            // Something went wrong. We actually cannot update the database.
            $this->errors[] = getlocal('Cannot update content: {0}', $e->getMessage());
            // The database changes should be discarded.
            $db->query('ROLLBACK');

            return false;
        }

        // All needed data has been updated.
        $db->query('COMMIT');

        return true;
    }
	
	
    /**
     * Loads database schema.
     *
     * @return array Associative array of database schema. Each key of the array
     *   is a table name and each value is its description. Table array itself
     *   is an associative array with the following keys:
     *     - fields: An associative array, which keys are MySQL columns names
     *       and values are columns definitions.
     *     - unique_keys: An associative array. Each its value is a name of a
     *       table's unique key. Each value is an array with names of the
     *       columns the key is based on.
     *     - indexes: An associative array. Each its value is a name of a
     *       table's index. Each value is an array with names of the
     *       columns the index is based on.
     */
    protected function getDatabaseSchema()
    {
        return $this->parser->parse(file_get_contents(__DIR__ . '/database_schema.yml'));
    }
	
    /**
     * Gets version of existing database structure for the Wurrd:AuthAPI plugin.
     *
     * If the plugin is not installed yet boolean false will be returned.
     *
     * @return string|boolean Database structure version or boolean false if the
     *   version cannot be determined.
     */
    protected function getDatabaseVersion()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        try {
            $result = $db->query(
                "SELECT vcvalue AS version FROM {config} WHERE vckey = :key LIMIT 1",
                array(':key' => 'waa_version'),
                array('return_rows' => Database::RETURN_ONE_ROW)
            );
        } catch (\Exception $e) {
            return false;
        }

        if (!$result) {
            // It seems that database structure version isn't stored in the
            // database.
            return false;
        }

        return $result['version'];
    }


    /**
     * Checks if the database structure must be updated.
     *
     * @return boolean
     */
    protected function tablesNeedUpdate()
    {
        return version_compare($this->getDatabaseVersion(), Constants::WAA_VERSION, '<');
    }

    /**
     * Checks if database structure is already created.
     *
     * @return boolean
     */
    protected function tablesExist()
    {
        return ($this->getDatabaseVersion() !== false);
    }


    /**
     * Drop all tables.
     *
     * @return boolean Indicates if tables removed or not.
     */
    protected function doDropTables()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        try {
            // Drop tables as defined by the schema
            $schema = $this->getDatabaseSchema();
			
			// We need to delete backwards such that foreign key constraints are not violated
			// ??? or, we can do it from top to bottom with CASCADE ???
			$tables = array_keys($schema);
			$tableCount = count($tables);
			for ($i = $tableCount - 1; $i >= 0; $i--) {
                $db->query(sprintf(
                    'DROP TABLE IF EXISTS {%s}',
                    $tables[$i]
                ));
			}
        } catch (\Exception $e) {
            $this->errors[] = getlocal(
                'Cannot drop tables. Error: {0}',
                array($e->getMessage())
            );

            return false;
        }

        return true;
    }

    /**
     * Saves some necessary data in the database.
     *
     * This method is called just once after tables are created.
     *
     * @return boolean Indicates if the data are saved to the database or not.
     */
    protected function prepopulateDatabase()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        // Set correct database structure version if needed
        try {
            list($count) = $db->query(
                'SELECT COUNT(*) FROM {config} WHERE vckey = :key',
                array(':key' => 'waa_version'),
                array(
                    'return_rows' => Database::RETURN_ONE_ROW,
                    'fetch_type' => Database::FETCH_NUM
                )
            );
            if ($count == 0) {
                $db->query(
                    'INSERT INTO {config} (vckey, vcvalue) VALUES (:key, :value)',
                    array(
                        ':key' => 'waa_version',
                        ':value' => Constants::WAA_VERSION,
                    )
                );
            }
        } catch (\Exception $e) {
            $this->errors[] = getlocal(
                'Cannot store database structure version. Error {0}',
                array($e->getMessage())
            );

            return false;
        }

        return true;
    }

    /**
     * Removes this plugin's version info from the database
     *
     * This method is called just once after tables are dropped.
     *
     * @return boolean Indicates if the version info is removed or not.
     */
    protected function removeVersionInfo()
    {
        if (!($db = $this->getDatabase())) {
            return false;
        }

        try {
            $db->query(
                'DELETE FROM {config} WHERE vckey = :key',
                array(':key' => 'waa_version')
            );
        } catch (\Exception $e) {
            $this->errors[] = getlocal(
                'Cannot remove the plugin database structure version. Error {0}',
                array($e->getMessage())
            );

            return false;
        }
		
		return true;
    }


}
