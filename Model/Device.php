<?php
/*
 * This file is a part of Wurrd AuthAPI Plugin.
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

namespace Wurrd\Mibew\Plugin\AuthAPI\Model;

use Mibew\Database;


/**
 * A class that represents Device entity.
 * 
 * Note: This class contains methods for persistence. Ideally persistence should be 
 * 		 moved to a persistence manager such that users of this class wouldn't be 
 * 		 able to inadvertently change its state in persistence.
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
     * Device operation system
     *
     * @var string
     */
    public $os;

    /**
     * Device operation system version
     *
     * @var string
     */
    public $osVersion;
	
    /**
     * Unix timestamp of the moment this record was created.
     * @var int
     */
    public $created;

    /**
     * Unix timestamp of the moment this record was modified.
     * @var int
     */
    public $modified;


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
            "SELECT * FROM {waa_device} WHERE deviceid = :id",
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
            "SELECT * FROM {waa_device} WHERE deviceuuid = :uuid and platform = :platform",
            array(':uuid' => $uuid,
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
    public static function createDevice($uuid, $platform, $type, $name, $os, $osVersion)
    {
        // Check parameters
        if (empty($uuid) || empty($platform) || empty($type) || empty($name) ||
        	empty($os) || empty($osVersion)) {
            return false;
        }

		$now = time();
        $device_info = array('deviceid' => false,
        					 'deviceuuid' => $uuid,
        					 'platform' => $platform,
        					 'type' => $type,
        					 'name' => $name,
							 'os' => $os,
							 'osversion' => $osVersion,
							 'dtmcreated' => $now,
							 'dtmmodified' => $now,
							 );
							 
        // Create and populate device object
        $device = new self();
        $device->populateFromDbFields($device_info);

        return $device;
    }

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
            "DELETE FROM {waa_device} WHERE deviceid = :id LIMIT 1",
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
                ("INSERT INTO {waa_device} (deviceuuid, platform, type, name, os, osversion, dtmcreated, dtmmodified) "
                    . "VALUES (:uuid, :platform, :type, :name, :os, :osversion, :dtmcreated, :dtmmodified)"),
                array(
                    ':uuid' => $this->deviceuuid,
                    ':platform' => $this->platform,
                    ':type' => $this->type,
                    ':name' => $this->name,
                    ':os' => $this->os,
                    ':osversion' => $this->osVersion,
                    ':dtmcreated' => $this->created,
                    ':dtmmodified' => $this->modified,
                                        
                )
            );
            $this->id = $db->insertedId();

        } else {
        	// Question: Does an update make sense for a device?
        	// Yes it does. We can modify the OS and the OS version.
        	// This has not yet been implemented
 
 			$this->modified = time();
            // Update existing device
            $db->query(
                ("UPDATE {waa_device} SET os = :os, osversion = :osversion, "
                    . "dtmmodified = :dtmmodified"),
                array(
                    ':os' => $this->os,
                    ':osversion' => $this->osVersion,
                    ':dtmmodified' => $this->modified,
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
		$this->os = $db_fields['os'];
		$this->osVersion = $db_fields['osversion'];
		$this->created = $db_fields['dtmcreated'];
		$this->modified = $db_fields['dtmmodified'];
    }
}
