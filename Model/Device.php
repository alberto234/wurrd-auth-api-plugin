<?php
/*
 * This file is a part of Wurrd ClientAuthorization Plugin.
 *
 * Copyright 2015 Eyong N <eyongn@scalior.com>.
 *
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

namespace Wurrd\Mibew\Plugin\ClientAuthorization\Model;

use Mibew\Database;


/**
 * A class that represents Device entity.
 */
class Device
{
    /**
     * Unique device ID.
     *
     * @var int
     */
    public $id;

    /**
     * The unique identifier for this device by platform
     *
     * @var string
     */
    public $deviceuuid;

    /**
     * The device's platform, e.g, Android, iOS, Windows
     *
     * @var string
     */
    public $platform;

    /**
     * Device type (phone, tablet, etc).
     *
     * @var string
     */
    public $type;

    /**
     * Device name as provided by the manufacturer
     *
     * @var string
     */
    public $name;

    /**
     * Loads device by its ID.
     *
     * @param int $id ID of the device to load
     * @return boolean|Device Returns a Device instance or boolean false on failure.
     */
    public static function load($id)
    {
        // Check $id
        if (empty($id)) {
            return false;
        }

        // Load device info
        $device_info = Database::getInstance()->query(
            "SELECT * FROM {wca_device} WHERE deviceid = :id",
            array(':id' => $id),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no device with such id in database
        if (!$device_info) {
            return false;
        }

        // Create and populate device object
        $device = new self();
        $device->populateFromDbFields($device_info);

        return $device;
    }

    /**
     * Loads device by UUID and platform.
     *
     * @param string $uuid Platform unique identifier for device to load.
	 * @param string $platform Device's platform
     * @return boolean|Device Returns a Device instance or boolean false on failure.
     */
    public static function loadByUUID($uuid, $platform)
    {
        // Check $id
        if (empty($uuid) || empty($platform)) {
            return false;
        }

        // Load device info
        $device_info = Database::getInstance()->query(
            "SELECT * FROM {wca_device} WHERE deviceuuid = :uuid and platform = :platform",
            array(':uuuid' => $uuid,
				  ':platform' => $platform),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no matching device in database
        if (!$device_info) {
            return false;
        }

        // Create and populate device object
        $device = new self();
        $device->populateFromDbFields($device_info);

        return $device;
    }

    /**
     * Creates a new device object from parameters.
     *
     * @param string $uuid Platform unique identifier
	 * @param string $platform Device's platform
	 * @param string $type Type of device
	 * @param string $name The device name
     * @return boolean|Device Returns a Device instance or boolean false on failure.
     */
    public static function createDevice($uuid, $platform, $type, $name)
    {
        // Check $id
        if (empty($uuid) || empty($platform) || empty($type) || empty($name)) {
            return false;
        }

        $device_info = array('deviceid' => false,
        					 'deviceuuid' => $uuid,
        					 'platform' => $platform,
        					 'type' => $type,
        					 'name' => $name);
							 
        // Create and populate device object
        $device = new self();
        $device->populateFromDbFields($device_info);

        return $device;
    }

    /**
     * Loads all bans.
     *
     * @return array List of Ban instances.
     *
     * @throws \RuntimeException If something went wrong and the list could not
     *   be loaded.
     */
     /* We don't need this
    public static function all()
    {
        $rows = Database::getInstance()->query(
            "SELECT * FROM {ban}",
            null,
            array('return_rows' => Database::RETURN_ALL_ROWS)
        );

        if ($rows === false) {
            throw new \RuntimeException('Bans list cannot be retrieved.');
        }

        $bans = array();
        foreach ($rows as $item) {
            $ban = new self();
            $ban->populateFromDbFields($item);
            $bans[] = $ban;
        }

        return $bans;
    }*/

    /**
     * Class constructor.
     */
    public function __construct()
    {
        // Set default values
        $this->id = false;
    }

    /**
     * Remove device from the database.
     *
     */
    public function delete()
    {
        if (!$this->id) {
            throw new \RuntimeException('You cannot delete a device without id');
        }

        Database::getInstance()->query(
            "DELETE FROM {wca_device} WHERE deviceid = :id LIMIT 1",
            array(':id' => $this->id)
        );
    }

    /**
     * Save the device to the database.
     *
     */
    public function save()
    {
        $db = Database::getInstance();

        if (!$this->id) {
            // This device is new.
            $db->query(
                ("INSERT INTO {wca_device} (deviceuuid, platform, type, name) "
                    . "VALUES (:uuid, :platform, :type, :name)"),
                array(
                    ':uuid' => $this->deviceuuid,
                    ':platform' => $this->platform,
                    ':type' => $this->type,
                    ':name' => $this->name,
                )
            );
            $this->id = $db->insertedId();

        } else {
        	// Question: Does an update make sense for a device?
 
            // Update existing device
            $db->query(
                ("UPDATE {wca_device} SET deviceuuid = :uuid, platform = :platform, "
                    . "type = :type, name = :name WHERE deviceid = :id"),
                array(
                    ':id' => $this->id,
                    ':uuid' => $this->deviceuuid,
                    ':platform' => $this->platform,
                    ':name' => $this->name
                )
            );
        }
    }

    /**
     * Sets device's fields according to the fields from Database.
     *
     * @param array $db_fields Associative array of database fields which keys
     *   are fields names and the values are fields values.
     */
    protected function populateFromDbFields($db_fields)
    {
        $this->id = $db_fields['deviceid'];
        $this->deviceuuid = $db_fields['deviceuuid'];
        $this->platform = $db_fields['platform'];
        $this->type = $db_fields['type'];
        $this->name = $db_fields['name'];
    }
}
