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

/**
 * @file Constants used by the Wurrd:AuthAPI plugin.
 */

namespace Wurrd\Mibew\Plugin\AuthAPI;

/**
 * Constants
 */
class Constants
{
	// Version informatiom    
    const WAA_VERSION 		= '0.1.5';
    const TOKEN_VERSION 	= '1';
	
	// API response messages
	const MSG_SUCCESS			 		= 'Success';
	const MSG_UNKNOWN_ERROR				= 'UnknownError';
	const MSG_BAD_USERNAME_PASSWORD 	= 'BadUsernameOrPassword';
	const MSG_INVALID_ACCESS_TOKEN 		= 'InvalidAccessToken';
	const MSG_EXPIRED_ACCESS_TOKEN 		= 'ExpiredAccessToken';
	const MSG_INVALID_REFRESH_TOKEN 	= 'InvalidRefreshToken';
	const MSG_EXPIRED_REFRESH_TOKEN 	= 'ExpiredRefreshToken';
	const MSG_NEW_TOKEN_GENERATED 		= 'NewTokenGenerated';
	const MSG_INVALID_OPERATOR			= 'InvalidOperator';
	const MSG_INVALID_DEVICE			= 'InvalidDevice';
	const MSG_INVALID_JSON				= 'InvalidJSON';
	
	// Constants for keys used to request access
	const CLIENTID_KEY 			= 'clientid';
	const USERNAME_KEY 			= 'username';
	const PASSWORD_KEY 			= 'password';
	const DEVICEUUID_KEY 		= 'deviceuuid';
	const PLATFORM_KEY 			= 'platform';
	const TYPE_KEY 				= 'type';
	const DEVICENAME_KEY 		= 'devicename';
	const DEVICEOS_KEY 			= 'os';
	const DEVICEOSVERSION_KEY 	= 'osversion';
	
	// Defaults for authorization.
	// These could be provided as plugin configurations
	const ACCESS_DURATION	 	= 3600;			// One hour
	const REFRESH_DURATION 		= 2592000;		// 30 days
	
	// This is the minimum time interval during which subsequent requests to 
	// refresh the access token returns the same access token. This is required
	// to mitigate race conditions on the client where multiple threads may be
	// trying to refresh the access token simultaneously
	const MIN_REFRESH_INTERVAL 	= 30; 		// 30 seconds
	
    /**
     * This class should not be instantiated
     */
    private function __construct()
    {
    }
}
 