<?php
/*
 * This file is a part of Wurrd ClientAuthorization Plugin.
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

namespace Wurrd\Mibew\Plugin\ClientAuthorization\Classes;

use Wurrd\Mibew\Plugin\ClientAuthorization\Constants;
use Wurrd\Mibew\Plugin\ClientAuthorization\Model\Device;
use Wurrd\Mibew\Plugin\ClientAuthorization\Model\Authorization;

/**
 * Interface to manage access to Mibew
 */
class AccessManagerAPI
{
	/**
	 * This method is used to generate an access token
	 * @param array $args - An array containing the arguments needed for the
	 * 					    access token to be generated. The arguments are
	 * 						defined in constants.php are.
	 * 
	 * @return Authorization - An instance of Authorization or false if a failure
	 */
	 public static function requestAccess($args) {
	 	// TODO: 1 - Learn and use the template function pattern to validate the parameters
	 	//		 2 - Implement a proper exception handling framework.
	 	
	 	/* Steps:
		 * 1 - Get the operator and confirm access to the system
		 * 2 - Get/create the device
		 * 3 - Create tokens
		 * 4 - Return tokens
	 	*/
	 	
	 	// Step 1 - Get the operator and confirm access to the system
        $login = $args[Constants::$USERNAME_KEY];
        $password = $args[Constants::$PASSWORD_KEY];
        $deviceuuid = $args[Constants::$DEVICEUUID_KEY];
        $platform = $args[Constants::$PLATFORM_KEY];
        $type = $args[Constants::$TYPE_KEY];
        $devicename = $args[Constants::$DEVICENAME_KEY];
		$clientID = $args[Constants::$CLIENTID_KEY];

        $operator = operator_by_login($login);
        $operator_can_login = $operator
            && isset($operator['vcpassword'])
            && check_password_hash($operator['vclogin'], $password, $operator['vcpassword'])
            && !operator_is_disabled($operator);


		$device;
		$authorization;
		if ($operator_can_login) {
			 // Step 2 - Get/create the device
			 $newDevice = false;
			 $device = Device::loadByUUID($deviceuuid, $platform);
			 if (!$device) {
			 	// The device is not found, add a new device
			 	$device = Device::createDevice($deviceuuid, $platform, $type, $devicename);
				$newDevice = true;
			 }

			 if ($device !== false) {
			 	// Step 3 - Create tokens
			 	$authorization = AccessManagerAPI::createAuthorization($operator, $device, $clientID);
				if ($authorization !== false) {
					//$authorization->save();
				}
			 }
		}

		if (!is_null($authorization) && $authorization !== false) {
			/*$authTokens = array('accesstoken' => $authorization->accesstoken,
								 'accessduration' => $authorization->accessduration,
								 'refreshtoken' => $authorization->refreshtoken,
								 'refreshduration' => $authorization->refreshduration);	*/
								 
			// If we get here, persist the device and authorization
			$device->save();
			$authorization->deviceid = $device->id;
			$authorization->save();
			
			// var_dump($authorization);
			
			// TODO: Ensure that everything is persisted before we return the tokens
			return $authorization;
		}
		
		return false;
	 }
	 
	/**
	 * This method is used to create a new authorization record
	 * @param array $operator - The operator associated with the access token
	 * @param Device $device - The device associated with the access token
	 * @param string $clientID - The client ID of the app
	 * 
	 * @return Authorization - An Authorization instance. 
	 */
	 private static function createAuthorization($operator, $device, $clientID) {
	 	$currTime = time();
		
		// Create access token: sha256 of operator login + time
		$tmp = Constants::$TOKEN_VERSION . "\x0" . Constants::$ACCESS_DURATION . "\x0";
		$tmp .= hash("sha256", $operator['login'] . $currTime, true);
		$accesstoken = strtr(base64_encode($tmp), '+/=', '-_,');
		
		// Create refresh token: sha256 of deviceuuid + time
		$tmp = Constants::$TOKEN_VERSION . "\x0" . Constants::$REFRESH_DURATION . "\x0";
		$tmp .= hash("sha256", $device->deviceuuid . $currTime, true);
		$refreshtoken = strtr(base64_encode($tmp), '+/=', '-_,');
		
		$authorization = Authorization::createNewAuhtorization($accesstoken, Constants::$ACCESS_DURATION,
							$refreshtoken, Constants::$REFRESH_DURATION, $operator['operatorid'], 
							$device->id, $clientID, $currTime);
							
		return $authorization;
	 }
	     
    /**
     * This class should not be instantiated
     */
    private function __construct()
    {
    }
}
 
