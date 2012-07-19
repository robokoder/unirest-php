<?php

/*
 * Mashape PHP Client library.
 *
 * Copyright (C) 2011 Mashape, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * The author of this software is Mashape, Inc.
 * For any question or feedback please contact us at: support@mashape.com
 *
 */

require_once(dirname(__FILE__) . "/../json/Json.php");
require_once(dirname(__FILE__) . "/Chunked.php");
require_once(dirname(__FILE__) . "/../exceptions/MashapeClientException.php");
require_once(dirname(__FILE__) . "/HttpMethod.php");
require_once(dirname(__FILE__) . "/UrlUtils.php");
require_once(dirname(__FILE__) . "/AuthUtil.php");

class HttpClient {

	public static function doRequest($httpMethod, $url, $parameters, $publicKey, $privateKey, $encodeJson = true) {
		
		if (!($httpMethod == HttpMethod::DELETE || $httpMethod == HttpMethod::GET ||
		$httpMethod == HttpMethod::POST || $httpMethod == HttpMethod::PUT)) {
			throw new MashapeClientException(EXCEPTION_NOTSUPPORTED_HTTPMETHOD, EXCEPTION_NOTSUPPORTED_HTTPMETHOD_CODE);
		}
		
		UrlUtils::prepareRequest($url, $parameters, ($httpMethod != HttpMethod::GET) ? true : false);
		
		$response = self::execRequest($httpMethod, $url, $parameters, $publicKey, $privateKey);

		if (!$encodeJson) {
			return $response;
		}
		
		$jsonResponse = json_decode($response);
		if (empty($jsonResponse)) {
			// It may be a chunked response
			$jsonResponse = json_decode(http_chunked_decode($response));
			if (empty($jsonResponse)) {
				throw new MashapeClientException(sprintf(EXCEPTION_JSONDECODE_REQUEST, $response), EXCEPTION_SYSTEM_ERROR_CODE);
			}
		}

		return $jsonResponse;
	}

	private static function execRequest($httpMethod, $url, $parameters, $publicKey, $privateKey) {
		$data = null;
		if ($httpMethod != HttpMethod::GET) {
            //$url = self::removeQueryString($url);
			$data = http_build_query($parameters);
		}
		
        //$headers = array();
        $headers = array(
        );
        $headers[] = AuthUtil::generateAuthenticationHeader($publicKey, $privateKey);
        $headers[] = UrlUtils::generateClientHeaders();

        $ch = curl_init ();

        // prepare the request
        //curl_setopt($ch, CURLOPT_USERPWD, "username:password"); for basic auth
        curl_setopt ($ch, CURLOPT_URL , $url); 
		if ($httpMethod != HttpMethod::GET) {
			curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
            //curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
        }
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
		
		return $response;
	}

	private static function removeQueryString($url) {
		$pos = strpos($url, "?");
		if ($pos !== false) {
			return substr($url, 0, $pos);
		}
		return $url;
	}

}



?>
