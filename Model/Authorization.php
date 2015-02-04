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
 * A class that represents an Authorization entity.
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
     * Duration in seconds for the access token
     *
     * @var int
     */
    public $accessduration;

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
     * Duration in seconds for the refresh token
     *
     * @var int
     */
    public $refreshduration;

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
            "SELECT * FROM {wca_authorization} WHERE authid = :id",
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
            "SELECT * FROM {wca_authorization} WHERE accesstoken = :accesstoken",
            array(':accesstoken' => $accessToken),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no matching authorization in database
        if (!auth_info) {
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
    public static function createNewAuhtorization($accessToken, $accessDuration, $refreshToken, $refreshDuration,
    	$operatorid, $deviceid, $clientid, $createdTime)
    {
    	$db_fields = array('authid' => false,
    					   'operatorid' => (int)$operatorid,
    					   'deviceid' => (int)$deviceid,
    					   'clientid' => $clientid,
    					   'accesstoken' => $accessToken,
    					   'dtmaccesscreated' => $createdTime,
    					   'accessduration' => (int)$accessDuration,
    					   'refreshtoken' => $refreshToken,
    					   'dtmrefreshcreated' => $createdTime,
    					   'refreshduration' => (int)$refreshDuration
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
            "DELETE FROM {wca_authorization} WHERE authid = :id LIMIT 1",
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
                ("INSERT INTO {wca_authorization} (operatorid, deviceid, clientid, accesstoken, "
					. "dtmaccesscreated, accessduration, refreshtoken, dtmrefreshcreated, refreshduration) "
                    . "VALUES (:operatorid, :deviceid, :clientid, :accesstoken, :dtmaccesscreated, "
					. ":accessduration, :refreshtoken, :dtmrefreshcreated, :refreshduration)"),
                array(
                    ':operatorid' => (int)$this->operatorid,
                    ':deviceid' => (int)$this->deviceid,
                    ':clientid' => $this->clientid,
                    ':accesstoken' => $this->accesstoken,
                    ':dtmaccesscreated' => $this->dtmaccesscreated,
                    ':accessduration' => $this->accessduration,
                    ':refreshtoken' => $this->refreshtoken,
                    ':dtmrefreshcreated' => $this->dtmrefreshcreated,
                    ':refreshduration' => $this->refreshduration,
 				)
            );
            $this->id = $db->insertedId();

        } else {
            // Update existing authorization
            $db->query(
                ("UPDATE {wca_authorization} SET accesstoken = :accesstoken, dtmaccesscreated = :dtmaccesscreated, "
                    . "accessduration = :accessduration, refreshtoken = :refreshtoken, "
                    . "dtmrefreshcreated = :dtmrefreshcreated, refreshduration = :refreshduration "
                    . "WHERE authid = :id"),
                array(
                    ':id' => $this->id,
                    ':accesstoken' => $this->accesstoken,
                    ':dtmaccesscreated' => $this->dtmaccesscreated,
                    ':accessduration' => $this->accessduration,
                    ':refreshtoken' => $this->refreshtoken,
                    ':dtmrefreshcreated' => $this->dtmrefreshcreated,
                    ':refreshduration' => $this->refreshduration,
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
        $this->accessduration = $db_fields['accessduration'];
        $this->refreshtoken = $db_fields['refreshtoken'];
        $this->dtmrefreshcreated= $db_fields['dtmrefreshcreated'];
        $this->refreshduration = $db_fields['refreshduration'];
    }
 }