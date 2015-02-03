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

/**
 * Utility class to handle the authorizations
 */
class AuthorizationUtil
{
	/**
	 * This method is used to generate an access token
	 * @param array $args - An array containing the arguments needed for the
	 * 					    access token to be generated. The arguments are
	 * 						defined in constants.php are.
	 * 
	 * @return array of token parameters
	 */
	 public static function requestAccess($args) {
	 	// TODO: Learn and use the template function pattern to validate the parameters
	 	
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

        $operator = operator_by_login($login);
        $operator_can_login = $operator
            && isset($operator['vcpassword'])
            && check_password_hash($operator['vclogin'], $password, $operator['vcpassword'])
            && !operator_is_disabled($operator);


		if ($operator_can_login) {
			 // Step 2 - Get/create the device
			 $newDevice = false;
			 $device = Device::loadByUUID($deviceuuid, $platform);
			 if (!$device) {
			 	// The device is not found, add a new device
			 	$device = Device::createDevice($deviceuuid, $platform, $type, $devicename);
				$newDevice = true;
			 }
			 
			 if (!$device) {
			 	// Step 3 - Create tokens
			 }
		}
		
		$message = null;
        if ($operator_can_login) {
        	$message = 'Successfully logged in';
		} else {
			$message = 'Failed to log in';
		}
		
		$authTokens = array('message' => $message . " in AuthorizationUtil");
		
		return $authTokens;		
	 	
	 }
	 
	 
	     
    /**
     * This class should not be instantiated
     */
    private function __construct()
    {
    }
}
 
