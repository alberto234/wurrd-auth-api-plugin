<?php
/*
 * This file is a part of Wurrd Client Authorization Plugin.
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
 * @file Constants used by the Wurrd:ClientAuthorization plugin.
 */

namespace Wurrd\Mibew\Plugin\ClientAuthorization;

/**
 * Constants
 */
class Constants
{    
	/**
     * The version of the plugin
     */
    public static  $WCA_VERSION = '0.1.0';
	
	// Constants for keys used to request access
	public static $CLIENTID_KEY 	= 'clientid';
	public static $USERNAME_KEY 	= 'username';
	public static $PASSWORD_KEY 	= 'password';
	public static $DEVICEUUID_KEY 	= 'deviceuuid';
	public static $PLATFORM_KEY 	= 'platform';
	public static $TYPE_KEY 		= 'type';
	public static $DEVICENAME_KEY 	= 'devicename';
	
	// Defaults for authorization
	public static $ACCESS_DURATION	 	= 3600;			// One hour
	public static $REFRESH_DURATION 	= 604800;		// One week
	
    /**
     * This class should not be instantiated
     */
    private function __construct()
    {
    }
}
 