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

use Mibew\Http\Exception;
use Wurrd\Mibew\Plugin\ClientAuthorization\Constants;
use Wurrd\Mibew\Plugin\ClientAuthorization\Model\Device;
use Wurrd\Mibew\Plugin\ClientAuthorization\Model\Authorization;

/**
 * Interface to manage access to Mibew from devices such as 
 * mobile apps or third-party webapps
 */
class AccessManagerAPI
{
	/**
	 * This method is used to generate grant access to a device/client. 
	 * This call causes previous access to be revoked.
	 * 
	 * @param array $args - An array containing the arguments needed for the
	 * 					    access token to be generated. The arguments are
	 * 						defined in constants.php are.
	 * 
	 * @return Authorization - An instance of Authorization or false if a failure
	 */
	 public static function requestAccess($args) {
	 	// TODO: 1 - Learn and use the template function pattern to validate the parameters
	 	//		 2 - Implement a proper exception handling framework.
	 	
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


		$authorization;
		if ($operator_can_login) {
			 // Step 2 - Get/create the device
			 $newDevice = false;
			 $device = Device::loadByUUID($deviceuuid, $platform);
			 if (!$device) {
			 	// The device is not found, add a new device
			 	$device = Device::createDevice($deviceuuid, $platform, $type, $devicename);
				$device->save();
				$newDevice = true;
			 }

			 if ($device !== false) {
			 	// Step 3 - Create authorization
			 	$authorization = AccessManagerAPI::createAuthorization($operator, $device, $clientID, $newDevice);
				if ($authorization !== false) {
					$authorization->save();
				}
			 }
		}

		if (!is_null($authorization) && $authorization !== false) {

			// TODO: Ensure that everything is persisted before we return the tokens
			return $authorization;
		}
		
		return false;
	 }
	 

	/**
	 * Determines if the given access token is valid to access the system
	 * 
	 * @param string $accessToken	The access token to check
	 * 
	 * @return bool true if allowed. 
	 * 
	 * @throws \Mibew\Exception\AccessDeniedException
	 * 
	 * Exception codes are:
	 * 			1 = invalid token
	 * 			2 = expired token
	 */
	 public static function isAuthorized($accessToken) {
	 	$authorization = Authorization::loadByAccessToken($accessToken);
		if ($authorization == false) {
			throw new Exception\AccessDeniedException("Invalid token", 1);
		}
		
		$currTime = time();
		if ($currTime > ($authorization->dtmaccesscreated + 
						 $authorization->accessduration)) {
			throw new Exception\AccessDeniedException("expired token", 2);
		}
		
		// TODO: At a future iteration we will also check access scopes

		return true;
	 }	 





	 // *********************************************
	 //  PRIVATE HELPER METHODS
	 // *********************************************
	 
	/**
	 * This method is used to create a new authorization record.
	 * Note: 	We want to ensure that only one user is logged in per device/client.
	 * 			If a device is being re-used, delete previous authorizations. 
	 * 
	 * @param array 	$operator	The operator associated with the access token
	 * @param Device 	$device		The device associated with the access token
	 * @param string 	$clientID	The client ID of the app
	 * @param boolean 	$newDevice	Indicates if this is a new device being added
	 * 
	 * @return Authorization|bool	An Authorization instance. 
	 */
	 private static function createAuthorization($operator, $device, $clientID, $newDevice) {
	 	
		// Check if we need to delete previous authorizations.
		if (!$newDevice) {
			$prevAuths = Authorization::allByDevice($device->id);
			foreach($prevAuths as $auth) {
				$auth->delete();
			} 
		}
		
	 	$currTime = time();
		
		// Create access token: sha256 of operator login + time
		$tmp = Constants::$TOKEN_VERSION . "\x0" . $currTime + Constants::$ACCESS_DURATION . "\x0";
		$tmp .= hash("sha256", $operator['login'] . $currTime, true);
		$accesstoken = strtr(base64_encode($tmp), '+/=', '-_,');
		
		// Create refresh token: sha256 of deviceuuid + time
		$tmp = Constants::$TOKEN_VERSION . "\x0" . $currTime + Constants::$REFRESH_DURATION . "\x0";
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
 

