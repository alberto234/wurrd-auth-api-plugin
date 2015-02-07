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

namespace Wurrd\Mibew\Plugin\AuthAPI\Controller;

use Mibew\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
 
 /**
 * Controller example.
 */
class LoginController extends AbstractController
{
    /**
     * Authorizes the user of a client to the system,
	 * and returns authorization and refresh tokens.
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    //public function loginAction($client_id, $username, $password, Request $request)
    public function loginAction(Request $request)
	{
		$client_id = $request->attributes->get("client_id");
		$username = $request->attributes->get("username");
		$password = $request->attributes->get("password");
		
        return new Response('Hello ' . $client_id . ' ' . $username . ' ' . $password . '!');
    }
}

 