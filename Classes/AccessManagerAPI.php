<?php
/*
 * This file is a part of Wurrd AuthAPI Plugin.
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

namespace Wurrd\Mibew\Plugin\AuthAPI\Classes;

use Mibew\Http\Exception;
use Wurrd\Mibew\Plugin\AuthAPI\Constants;
use Wurrd\Mibew\Plugin\AuthAPI\Model\Device;
use Wurrd\Mibew\Plugin\AuthAPI\Model\Authorization;


/**
 * Interface to manage access to Mibew from devices such as 
 * mobile apps or third-party webapps
 */
class AccessManagerAPI
{
	/**
	 * This method is used to grant access to a device/client. 
	 * This call causes previous access to be revoked.
	 * 
	 * @param array $args - An array containing the arguments needed for the
	 * 					    access token to be generated. The arguments are
	 * 						defined in constants.php are.
	 * 						- username
	 * 						- password
	 * 						- clientid
	 * 						- deviceuuid
	 * 						- type
	 * 						- devicename
	 * 						- platform
	 * 						- os
	 * 						- osversion
	 *
	 * @return Authorization - An instance of Authorization when successful
	 * 
	 * @throws	\Mibew\Exception\AccessDeniedException
	 *				With one of the following messages: 
	 * 					- Constants::MSG_BAD_USERNAME_PASSWORD 
	 * 			\Mibew\Exception\HttpException
	 */
	 public static function requestAccess($args) {
	 	// TODO: 1 - Learn and use the template function pattern to validate the parameters
	 	//		 2 - Implement a proper exception handling framework. -- Work in progress
	 	
	 	// Step 1 - Get the operator and confirm access to the system
        $login = $args[Constants::USERNAME_KEY];
        $password = $args[Constants::PASSWORD_KEY];
        $deviceuuid = $args[Constants::DEVICEUUID_KEY];
        $platform = $args[Constants::PLATFORM_KEY];
        $type = $args[Constants::TYPE_KEY];
        $devicename = $args[Constants::DEVICENAME_KEY];
		$clientID = $args[Constants::CLIENTID_KEY];
		$os = $args[Constants::DEVICEOS_KEY];
		$osVersion = $args[Constants::DEVICEOSVERSION_KEY];

        $operator = operator_by_login($login);
        $operator_can_login = $operator
            && isset($operator['vcpassword'])
            && check_password_hash($operator['vclogin'], $password, $operator['vcpassword'])
            && !operator_is_disabled($operator);


		$authorization = null;
		if ($operator_can_login) {
			 // Step 2 - Get/create the device
			 $newDevice = false;
			 $device = Device::loadByUUID($deviceuuid, $platform);
			 if (!$device) {
			 	// The device is not found, add a new device
			 	$device = Device::createDevice($deviceuuid, $platform, $type, $devicename, $os, $osVersion);
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
		} else {
			throw new Exception\AccessDeniedException(Constants::MSG_BAD_USERNAME_PASSWORD);
		}
		
		if (is_null($authorization) || $authorization === false) {
			// This means we have a server-side issue, possibly database related
			throw new Exception\HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,
												Constants::MSG_UNKNOWN_ERROR);
		}
		
		return $authorization;
	 }
	 

	/**
	 * Determines if the given access token is valid to access the system
	 * 
	 * @param string $accessToken	The access token to check
	 * @return bool true if allowed. 
	 * @throws \Mibew\Exception\AccessDeniedException
	 * 
	 * Exception codes are:
	 * 			1 = invalid token
	 * 			2 = expired token
	 * 			3 = new token generated
	 */
	 public static function isAuthorized($accessToken) {
	 	$authorization = Authorization::loadByAccessToken($accessToken);
		if ($authorization == false) {

			// If this request is using an old access token notify the caller that this 
			// access token has been superseded.
			$authorization = Authorization::loadByPreviousAccessToken($accessToken);
			if ($authorization != false) {
				throw new Exception\AccessDeniedException(
						Constants::MSG_NEW_TOKEN_GENERATED,
						7);
			} else {
				throw new Exception\AccessDeniedException(Constants::MSG_INVALID_ACCESS_TOKEN, 1);
			}
		}
		
		$currTime = time();
		if ($currTime > $authorization->dtmaccessexpires) {
			throw new Exception\AccessDeniedException(Constants::MSG_EXPIRED_ACCESS_TOKEN, 2);
		}
		
		// If the previous access token is set, clear it. This constitutes the acknowledgement that the
		// new token was successfully received by the client.
		if ($authorization->previousaccesstoken != null) {
			$authorization->previousaccesstoken = null;
			$authorization->previousrefreshtoken = null;
			$authorization->save();
		}
		
		// TODO: At a future iteration we will also check access scopes

		return true;
	 }	 

	/**
	 * Refreshes the tokens
	 * 
	 * @param string $accessToken	The access token to check
	 * @param string $refresToken	An unexpired refresh token is needed for this
	 * @return Authorization - An instance of Authorization or false if a failure
	 * @throws \Mibew\Http\Exception\HttpException	-- and subclasses
	 * 
	 * Exception codes are:
	 * 			1 = invalid access token
	 * 			3 = invalid refresh token
	 * 			4 = expired refresh token
	 * 			5 = couldn't retrieve the operator
	 * 			6 = couldn't retrieve the device
	 */
	 public static function refreshAccess($accessToken, $refreshToken)
	 {
	 	// TODO: What does it mean for a refresh token to expire? 
		//		 The current algorithm is that the refresh is on a rolling interval
		//		 of Constants::REFRESH_DURATION with each refresh.
		//		 If the client doesn't access the system within that duration, they 
		//		 will need to login again. REFRESH_DURATION can be made configurable
		
		$currTime = time();
	 	$authorization = Authorization::loadByAccessToken($accessToken);
		if ($authorization == false) {

			$authorization = Authorization::loadByPreviousAccessToken($accessToken);
			if ($authorization == false) {
				throw new Exception\AccessDeniedException(
						Constants::MSG_INVALID_ACCESS_TOKEN,
						1);
			}

			// Here, the request is using an old access token. Re-send the new tokens if they
			// have not yet expired.
			if ($currTime < $authorization->dtmaccessexpires) {
				return $authorization;
			}
			
			// Access token has already expired. Replace the new refresh token with the old one and 
			// let the code below proceed to refresh the tokens
			$authorization->refreshtoken = $authorization->previousrefreshtoken;
		}
		
		if ($authorization->refreshtoken != $refreshToken) {
			throw new Exception\AccessDeniedException(
					Constants::MSG_INVALID_REFRESH_TOKEN,
					3);
		} else if (time() > $authorization->dtmrefreshexpires) {
			throw new Exception\AccessDeniedException(
					Constants::MSG_EXPIRED_REFRESH_TOKEN,
					4);
		}
				
				
		// This mitigates race conditions from the client.
		if (($currTime - $authorization->dtmaccesscreated) <= Constants::MIN_REFRESH_INTERVAL) {
			return $authorization;
		}

		$operator = operator_by_id($authorization->operatorid);
		if ($operator == null) {
			throw new Exception\HttpException(
					Response::HTTP_INTERNAL_SERVER_ERROR,
					Constants::MSG_INVALID_OPERATOR,
					null,
					5);
		}
		
		$device = Device::load($authorization->deviceid);
		if ($device == false) {
			throw new Exception\HttpException(
					Response::HTTP_INTERNAL_SERVER_ERROR,
					Constants::MSG_INVALID_DEVICE,
					null,
					6);
		}

		$newAccessToken = AccessManagerAPI::generateAccessToken($operator['vclogin']);
		$newRefreshToken = AccessManagerAPI::generateRefreshToken($device->deviceuuid);
		
		// Update the authorization
		$authorization->previousaccesstoken = $authorization->accesstoken;
		$authorization->accesstoken = $newAccessToken['accesstoken'];
		$authorization->dtmaccessexpires = $newAccessToken['expiretime'];
		$authorization->dtmaccesscreated = $newAccessToken['created'];
		
		$authorization->previousrefreshtoken = $authorization->refreshtoken;
		$authorization->refreshtoken = $newRefreshToken['refreshtoken'];
		$authorization->dtmrefreshexpires = $newRefreshToken['expiretime'];
		$authorization->dtmrefreshcreated = $newRefreshToken['created'];
		
		$authorization->save();
		
		
		return $authorization;
	 }	 


	/**
	 * Drop access from the system -- revoke tokens
	 * 
	 * @param string $accessToken	The access token to drop
	 * @param string $deviceuuid	The unique id of the device associated with this token
	 * @return bool true if successful. 
	 * @throws \Mibew\Exception\AccessDeniedException
	 * 
	 * Exception codes are:
	 * 			1 = invalid token
	 * 			2 = invalid device
	 */
	 public static function dropAccess($accessToken, $deviceuuid) {
	 	$authorization = Authorization::loadByAccessToken($accessToken);
		if ($authorization == false) {
			throw new Exception\AccessDeniedException(Constants::MSG_INVALID_ACCESS_TOKEN, 1);
		}
		
		$device = Device::load($authorization->deviceid);
		if ($device == false) {
			throw new Exception\HttpException(
					Response::HTTP_INTERNAL_SERVER_ERROR,
					Constants::MSG_INVALID_DEVICE,
					null,
					2);
		}
		
		if ($device->deviceuuid != $deviceuuid) {
			throw new Exception\AccessDeniedException(Constants::MSG_INVALID_DEVICE, 2);
		}

		// Given that there currently is a one-to-one relationship between the device and authorization,
		// i.e., only one access can be given to a particular device, we also have to remove the device
		// from the database.
		$authorization->delete();
		$device->delete();
		
		return true;
	 }	 

	/**
	 * Returns the version of this plugin
	 * 
	 * @return string plugin version. 
	 */
	public static function getAuthAPIPluginVersion()
	{
		return Constants::WAA_VERSION;
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

		$newAccessToken = AccessManagerAPI::generateAccessToken($operator['vclogin']);
		$newRefreshToken = AccessManagerAPI::generateRefreshToken($device->deviceuuid);
		
		$authorization = Authorization::createNewAuhtorization(
							$newAccessToken['accesstoken'],
							$newAccessToken['expiretime'],
							$newAccessToken['created'],
							$newRefreshToken['refreshtoken'],
							$newRefreshToken['expiretime'],
							$newRefreshToken['created'],
							$operator['operatorid'], 
							$device->id,
							$clientID);

		return $authorization;
	 }

	private static function generateAccessToken($login) 
	{
		$currTime = time();
		$expireTime = $currTime + Constants::ACCESS_DURATION;
		
		// Create access token: sha256 of operator login + time
		$tmp = Constants::TOKEN_VERSION . "\x0" . $expireTime . "\x0";
		$tmp .= hash("sha256", $login . $currTime, true);
		$accesstoken = strtr(base64_encode($tmp), '+/=', '-_,');
		
		return array('accesstoken' => $accesstoken,
					 'expiretime' => $expireTime,
					 'created' => $currTime);
	}
	
	
	private static function generateRefreshToken($deviceuuid) 
	{
		$currTime = time();
		$expireTime = $currTime + Constants::REFRESH_DURATION;
		
		// Create refresh token: sha256 of deviceuuid + time
		$tmp = Constants::TOKEN_VERSION . "\x0" . $expireTime . "\x0";
		$tmp .= hash("sha256", $deviceuuid . $currTime, true);
		$refreshtoken = strtr(base64_encode($tmp), '+/=', '-_,');
		
		return array('refreshtoken' => $refreshtoken,
					 'expiretime' => $expireTime,
					 'created' => $currTime);
	}
	 
    /**
     * This class should not be instantiated
     */
    private function __construct()
    {
    }
}
 

