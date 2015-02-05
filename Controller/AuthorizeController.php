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

namespace Wurrd\Mibew\Plugin\ClientAuthorization\Controller;

use Mibew\Controller\AbstractController;
use Mibew\Http\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wurrd\Mibew\Plugin\ClientAuthorization\Constants;
use Wurrd\Mibew\Plugin\ClientAuthorization\Classes\AccessManagerAPI;

 
 /**
  * Controller used for authorization.
  * 
  * This controller returns JSON encoded output. The output format can 
  * be abstracted such that there is an output factory that will return
  * the results in the requested format.
  */
class AuthorizeController extends AbstractController
{
    /**
     * Authorizes the user of a client to the system,
	 * and returns access and refresh tokens.
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function requestAccessAction(Request $request)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		
		$authRequest = json_decode($request->getContent(), true);
        $json_error_code = json_last_error();
        if ($json_error_code != JSON_ERROR_NONE) {
            // Not valid JSON
			$response->setContent(array(
            	'message' => "The access request package have invalid json structure. JSON error code is '" . 
            				  $json_error_code . "'"));
			$response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
		
		$authorization = AccessManagerAPI::requestAccess($authRequest);
		
		$response->setContent(json_encode(array(
						'accesstoken' => $authorization->accesstoken,
						'accessduration' => $authorization->accessduration,
						'accesscreatedtime' => $authorization->dtmaccesscreated,
						'refreshtoken' => $authorization->refreshtoken,
						'refreshduration' => $authorization->refreshduration,
						'refreshcreatedtime' => $authorization->dtmrefreshcreated,
						)));
		
		return $response;
    }


    /**
     * Determine if the requester can access the system with this token
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function authorizedAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message;
		
		$accessToken = $request->attributes->get("accesstoken");
		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$message = 'Authorized';
			}
		} catch(Exception\HttpException $e) {
			$httpStatus = $e->getStatusCode();
			$message = $e->getMessage();
		}
		
		$response = new Response(json_encode(array('accesstoken' => $accessToken,
													'message' => $message)),
								$httpStatus,
								array('content-type' => 'application/json'));
		return $response;
    }

    /**
     * This is used for testing purposes
     */
    public function testInheritanceAction(Request $request)
	{
		$response = new Response("Routed well");
		AccessManagerAPI::testInheritance();
			
		return $response;
	}
}

