<?php

/**
 * Sends a request to a url via HTTP.
 *
 * Sends a request to a GNU Taler Backend over HTTP and returns the result.
 * The request can be sent as POST, GET, PUT or another method.
 *
 * @param $method - POST, GET, PUT or another method.
 * @param $backend_url - URL to the GNU Taler Backend.
 * @param $body - The content of the request.
 * @param $purpose - What return value is to be expected.
 * @param $api_key
 * @return array The return array will either have the successful return value or a detailed error message.
 * @since 0.6.0
 */
function call_api( $method, $backend_url, $body, $purpose, $api_key ): array
{
    //create_url
    $url = create_api_url( $backend_url, $purpose, $body );

    //Initialize curl request
    $curl = curl_init_request( $method, $body, $url, $api_key);

    // EXECUTE:
    $result = curl_exec( $curl );

    //HTTP Status Error handling
    $message_array = curl_error_handling( $curl, $result );
    curl_close( $curl );
    return $message_array;
}

/**
 * Checks if the return http code is a success and if not what kind of error status it is.
 *
 * If the request was successful an array will be returned with the boolean value true, the http code and the result of the response.
 * If the request failed an array will be returned with the boolean value false, the http code and a detailed error message.
 *
 * @param $curl - Created curl request for error handling.
 * @param $result - The response from the backend, that will be returned if the request was successful
 * @return array - Array with a boolean, a http code and a message will be returned
 * @since 0.6.0
 *
 */
function curl_error_handling( $curl, $result ): array
{
    $http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
    if ( curl_error( $curl ) ) {
        $error_msg = curl_error( $curl );
    }
    if ( isset( $error_msg ) ) {
        return array(
            'result' => false,
            'http_code' => $http_code,
            'message' => $error_msg,
        );
    }
    if ( $http_code === 200 ) {
        return array(
            'result' => true,
            'http_code' => $http_code,
            'message' => $result,
        );
    }
    if ( preg_match( '(4[0-9]{2})', $http_code ) ) {
        switch ($http_code) {
            case 400:
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Bad request',
                );
                break;
            case 401:
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Unauthorized',
                );
                break;
            case 403:
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Forbidden',
                );
                break;
            case 404:
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Page Not Found',
                );
                break;
            default:
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => '4xx Client Error',
                );
                break;
        }
    } elseif ( preg_match( '(5[0-9]{2})', $http_code ) ) {
        switch ( $http_code ) {
            case '500':
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Internal Server Error',
                );
                break;
            case '502':
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Bad Gateway',
                );
                break;
            case '503':
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Service Unavailable',
                );
                break;
            case '504':
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => 'Gateway Timeout',
                );
                break;
            default:
                return array(
                    'result' => false,
                    'http_code' => $http_code,
                    'message' => '5xx Client Error',
                );
                break;
        }
    } else {
        return array(
            'result' => false,
            'http_code' => $http_code,
            'message' => 'http status error',
        );
    }
}

/**
 * Initialises the curl request and sets some necessary options depending on the method.
 *
 * Depending of the method chosen different options for the curl request will be set.
 * Not depending on the method the settings for a return value, Authorization and Content-Type are being set.
 *
 * @param $method - POST, GET, PUT or another method.
 * @param $body - Content of the request.
 * @param $url - URL where the request will be send
 * @param $api_key
 * @return false|resource - Either the configured curl request will be returned or false if an error appears.
 * @since 0.6.0
 */
function curl_init_request( $method, $body, $url, $api_key )
{
    $curl = curl_init();

    switch ( $method ) {
        case 'POST':
            curl_setopt( $curl, CURLOPT_POST, 1 );
            if ( $body ) {
                curl_setopt( $curl, CURLOPT_POSTFIELDS, $body );
            }
            break;
        case 'PUT':
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
            if ( $body ) {
                curl_setopt( $curl, CURLOPT_POSTFIELDS, $body );
            }
            break;
        case 'GET':
            curl_setopt( $curl, CURLOPT_VERBOSE, 1 );
            break;
        default:
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );
            break;
    }

    // OPTIONS:
    curl_setopt( $curl, CURLOPT_URL, $url );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
        'Authorization: ' . $api_key,
        'Content-Type: application/json',
    ) );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );

    return $curl;
}

/**
 * Creates the final url depending on the purpose.
 *
 * @param $url - URL where the request will be send.
 * @param $purpose - What will be added to the url.
 * @param $body - Content of the request.
 * @return string - return the final url.
 * @since 0.6.0
 */
function create_api_url ($url, $purpose, $body ): string
{
    if ( $purpose === 'create_order' ) {
        return $url . '/order';
    }
    if ( $purpose === 'confirm_payment' ) {
        return $url . '/check-payment?order_id=' . $body;
    }
    if ( $purpose === 'create_refund' ) {
        return $url . '/refund';
    }
    return $url;
}


