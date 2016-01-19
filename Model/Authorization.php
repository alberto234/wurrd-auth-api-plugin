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
 * A class that represents an Authorization entity.
 * 
 * Note: This class contains methods for persistence. Ideally persistence should be 
 * 		 moved to a persistence manager such that users of this class wouldn't be 
 * 		 able to inadvertently change its state in persistence.
 */
class Authorization
{
    /**
     * Unique authorization ID.
     *
     * @var int
     */
    public $id;

    /**
     * The operator id associated with this authorization
     *
     * @var int
     */
    public $operatorid;

    /**
     * The device id associated with this authorization
     *
     * @var int
     */
    public $deviceid;

    /**
     * ID of the client application that is requesting the authorization.
     *
     * @var string
     */
    public $clientid;

    /**
     * The access token generated for this authorization
     *
     * @var string
     */
    public $accesstoken;

    /**
     * Unix timestamp of the moment when the access token was created.
     *
     * @var int
     */
    public $dtmaccesscreated;

    /**
     * When the access token expires.
     *
     * @var int
     */
    public $dtmaccessexpires;

    /**
     * The refresh token generated for this authorization
     *
     * @var string
     */
    public $refreshtoken;

    /**
     * Unix timestamp of the moment when the refresh token was created.
     *
     * @var int
     */
    public $dtmrefreshcreated;

    /**
     * When the refresh token expires.
     *
     * @var int
     */
    public $dtmrefreshexpires;

    /**
     * The previous access token generated for this authorization
     *
     * @var string
     */
    public $previousaccesstoken;

    /**
     * The previous refresh token generated for this authorization
     *
     * @var string
     */
    public $previousrefreshtoken;
	
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
     * Loads authorization by its ID.
     *
     * @param int $id ID of the authorization to load
     * @return boolean|Authorization Returns an Authorization instance or boolean false on failure.
     */
    public static function load($id)
    {
        // Check $id
        if (empty($id)) {
            return false;
        }

        // Load device info
        $auth_info = Database::getInstance()->query(
            "SELECT * FROM {waa_authorization} WHERE authid = :id",
            array(':id' => $id),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no authorization with such id in database
        if (!$auth_info) {
            return false;
        }

        // Create and populate authorization object
        $authorization = new self();
        $authorization->populateFromDbFields($auth_info);

        return $authorization;
    }

    /**
     * Returns an array of authorizations for a given device.
     *
     * @param int	$deviceID 	ID of the device to query
     * @return array	Returns an array of Authorizations.
     */
    public static function allByDevice($deviceID) {
    	$authorizations = array();
		
        $rows = Database::getInstance()->query(
            "SELECT * FROM {waa_authorization} WHERE deviceid = :deviceid",
            array(':deviceid' => (int)$deviceID),
            array('return_rows' => Database::RETURN_ALL_ROWS)
        );

        if ($rows === false) {
            return $authorizations;
        }

        foreach ($rows as $item) {
            $auth = new self();
            $auth->populateFromDbFields($item);
            $authorizations[] = $auth;
        }

        return $authorizations;
	}
    /**
     * Loads authorization by access token.
     *
     * @param string $accessToken The access token.
     * @return boolean|Authorization Returns an Authorization instance or boolean false on failure.
     */
    public static function loadByAccessToken($accessToken)
    {
        // Check parameters
        if (empty($accessToken)) {
            return false;
        }

        // Load authorization info
        $auth_info = Database::getInstance()->query(
            "SELECT * FROM {waa_authorization} WHERE accesstoken = :accesstoken",
            array(':accesstoken' => $accessToken),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no matching authorization in database
        if (!$auth_info) {
            return false;
        }

        // Create and populate authorization object
        $authorization = new self();
        $authorization->populateFromDbFields($auth_info);

        return $authorization;
    }

    /**
     * Loads authorization by previous access token.
     *
     * @param string $previousAccessToken The previous access token.
     * @return boolean|Authorization Returns an Authorization instance or boolean false on failure.
     */
    public static function loadByPreviousAccessToken($previousAccessToken)
    {
        // Check parameters
        if (empty($previousAccessToken)) {
            return false;
        }

        // Load authorization info
        $auth_info = Database::getInstance()->query(
            "SELECT * FROM {waa_authorization} WHERE previousaccesstoken = :previousAccessToken",
            array(':previousAccessToken' => $previousAccessToken),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no matching authorization in database
        if (!$auth_info) {
            return false;
        }

        // Create and populate authorization object
        $authorization = new self();
        $authorization->populateFromDbFields($auth_info);

        return $authorization;
    }

    /**
     * Create a new authorization
     *
     */
    public static function createNewAuhtorization($accessToken, $accessExpire, $accessCreated,
    	$refreshToken, $refreshExpire, $refreshCreated, $operatorid, $deviceid, $clientid)
    {
		$now = time();
    	$db_fields = array('authid' => false,
    					   'operatorid' => (int)$operatorid,
    					   'deviceid' => (int)$deviceid,
    					   'clientid' => $clientid,
    					   'accesstoken' => $accessToken,
    					   'dtmaccesscreated' => $accessCreated,
    					   'dtmaccessexpires' => $accessExpire,
    					   'refreshtoken' => $refreshToken,
    					   'dtmrefreshcreated' => $refreshCreated,
    					   'dtmrefreshexpires' => $refreshExpire,
    					   'previousaccesstoken' => null,
    					   'previousrefreshtoken' => null,
						   'dtmcreated' => $now,
						   'dtmmodified' => $now,
    				);

        // Create and populate authorization object
        $authorization = new self();
        $authorization->populateFromDbFields($db_fields);

        return $authorization;
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
     * Remove authorization from the database.
     *
     */
    public function delete()
    {
        if (!$this->id) {
            throw new \RuntimeException('You cannot delete an authorization without id');
        }

        Database::getInstance()->query(
            "DELETE FROM {waa_authorization} WHERE authid = :id LIMIT 1",
            array(':id' => $this->id)
        );
    }

    /**
     * Save the authorization to the database.
     *
     */
    public function save()
    {
        $db = Database::getInstance();

        if (!$this->id) {
            // This authorization is new.
            $db->query(
                ("INSERT INTO {waa_authorization} (operatorid, deviceid, clientid, accesstoken, "
					. "dtmaccesscreated, dtmaccessexpires, refreshtoken, dtmrefreshcreated, dtmrefreshexpires, "
					. "previousaccesstoken, previousrefreshtoken, dtmcreated, dtmmodified) "
                    . "VALUES (:operatorid, :deviceid, :clientid, :accesstoken, :dtmaccesscreated, "
					. ":dtmaccessexpires, :refreshtoken, :dtmrefreshcreated, :dtmrefreshexpires, "
					. ":previousaccesstoken, :previousrefreshtoken, :dtmcreated, :dtmmodified)"),
                array(
                    ':operatorid' => (int)$this->operatorid,
                    ':deviceid' => (int)$this->deviceid,
                    ':clientid' => $this->clientid,
                    ':accesstoken' => $this->accesstoken,
                    ':dtmaccesscreated' => $this->dtmaccesscreated,
                    ':dtmaccessexpires' => $this->dtmaccessexpires,
                    ':refreshtoken' => $this->refreshtoken,
                    ':dtmrefreshcreated' => $this->dtmrefreshcreated,
                    ':dtmrefreshexpires' => $this->dtmrefreshexpires,
                    ':previousaccesstoken' => $this->previousaccesstoken,
                    ':previousrefreshtoken' => $this->previousrefreshtoken,
                    ':dtmcreated' => $this->created,
                    ':dtmmodified' => $this->modified,
                )
            );
            $this->id = $db->insertedId();

        } else {
            // Update existing authorization
 			$this->modified = time();
            $db->query(
                ("UPDATE {waa_authorization} SET accesstoken = :accesstoken, dtmaccesscreated = :dtmaccesscreated, "
                    . "dtmaccessexpires = :dtmaccessexpires, refreshtoken = :refreshtoken, "
                    . "dtmrefreshcreated = :dtmrefreshcreated, dtmrefreshexpires = :dtmrefreshexpires, "
                    . "previousaccesstoken = :previousaccesstoken, previousrefreshtoken = :previousrefreshtoken, dtmmodified = :dtmmodified "
                    . "WHERE authid = :id"),
                array(
                    ':id' => $this->id,
                    ':accesstoken' => $this->accesstoken,
                    ':dtmaccesscreated' => $this->dtmaccesscreated,
                    ':dtmaccessexpires' => $this->dtmaccessexpires,
                    ':refreshtoken' => $this->refreshtoken,
                    ':dtmrefreshcreated' => $this->dtmrefreshcreated,
                    ':dtmrefreshexpires' => $this->dtmrefreshexpires,
                    ':previousaccesstoken' => $this->previousaccesstoken,
                    ':previousrefreshtoken' => $this->previousrefreshtoken,
                    ':dtmmodified' => $this->modified,
                )
            );
        }
    }

    /**
     * Sets authorization's fields according to the fields from Database.
     *
     * @param array $db_fields Associative array of database fields which keys
     *   are fields names and the values are fields values.
     */
    protected function populateFromDbFields($db_fields)
    {
        $this->id = $db_fields['authid'];
        $this->operatorid = $db_fields['operatorid'];
        $this->deviceid = $db_fields['deviceid'];
        $this->clientid = $db_fields['clientid'];
        $this->accesstoken = $db_fields['accesstoken'];
        $this->dtmaccesscreated = $db_fields['dtmaccesscreated'];
        $this->dtmaccessexpires = $db_fields['dtmaccessexpires'];
        $this->refreshtoken = $db_fields['refreshtoken'];
        $this->dtmrefreshcreated= $db_fields['dtmrefreshcreated'];
        $this->dtmrefreshexpires = $db_fields['dtmrefreshexpires'];
        $this->previousaccesstoken = $db_fields['previousaccesstoken'];
        $this->previousrefreshtoken = $db_fields['previousrefreshtoken'];
		$this->created = $db_fields['dtmcreated'];
		$this->modified = $db_fields['dtmmodified'];
    }
 }