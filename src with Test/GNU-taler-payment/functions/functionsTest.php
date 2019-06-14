<?php


use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
require 'functions.php';


/**
 * Class functionsTest
 */
class functionsTest extends TestCase
{
    /**
     * Tests the call_api function
     */
    public function test_call_api(): void
    {
        $method_post = 'POST';
        $method_get = 'GET';
        $method_different = '1234';
        $wc_order_test_request = '';
        $backend_url = 'https://backend.demo.taler.net';
        $api_key = 'ApiKey sandbox';

        try {
            $wc_order_test_request = 'wc_test_' . random_int(0, 1000);
        } catch (Exception $e) {
        }
        $body_request_1 = array(
            'order' => array(
                'amount' => 'KUDOS:0.1',
                'fulfillment_url' => 'http://gnutaler.hofmd.ch',
                'summary' => 'Test_order',
                'order_id' => $wc_order_test_request,
            )
        );
        $purpose_1 = 'create_order';

        $body_request_2 = $wc_order_test_request;
        $purpose_2 = 'confirm_payment';

        $result_create_order = call_api( $method_post, $backend_url, json_encode($body_request_1), $purpose_1, $api_key );
        $result_confirm_payment = call_api( $method_get, $backend_url, $body_request_2, $purpose_2, $api_key );
        $result_verify_backend_url = call_api( $method_get, $backend_url, '', '', $api_key );
        $result_method_different = call_api( $method_different, $backend_url, json_encode($body_request_1), $purpose_1, $api_key );

        Assert::assertTrue($result_create_order['result']);
        Assert::assertEquals($wc_order_test_request, json_decode($result_create_order['message'], true)['order_id']);
        Assert::assertTrue($result_confirm_payment['result']);
        Assert::assertEquals(false, json_decode($result_confirm_payment['message'], true)['paid']);
        Assert::assertTrue($result_verify_backend_url['result']);
        Assert::assertEquals('Hello, I\'m a merchant\'s Taler backend. This HTTP server is not for humans.', trim($result_verify_backend_url['message']));
        Assert::assertFalse($result_method_different['result']);
        Assert::assertEquals('Bad request', $result_method_different['message']);
    }


    /**
     * Tests the create_api_url function
     */
    public function test_create_api_url(): void
    {
        $url_test = 'https://backend.demo.taler.net';
        $wc_test_order = '';
        try {
            $wc_test_order = 'wc_test_' . random_int(0, 1000);
        } catch (Exception $e) {
        }
        $purpose_create_order = 'create_order';
        $purpose_confirm_payment = 'confirm_payment';
        $purpose_create_refund = 'create_refund';

        $create_order_url = create_api_url($url_test, $purpose_create_order, '');
        $confirm_payment_url = create_api_url($url_test, $purpose_confirm_payment, $wc_test_order);
        $create_refund_url = create_api_url($url_test, $purpose_create_refund, '');

        Assert::assertEquals('https://backend.demo.taler.net/order', $create_order_url);
        Assert::assertEquals('https://backend.demo.taler.net/check-payment?order_id=' . $wc_test_order, $confirm_payment_url);
        Assert::assertEquals('https://backend.demo.taler.net/refund', $create_refund_url);
    }

    /**
     * Tests the curl_error_handling function with the http status code 200
     */
    public function test_curl_error_handling_code_200(): void
    {
        $api_key = 'ApiKey sandbox';
        $test_url = 'https://backend.demo.taler.net';

        $curl_error_message_array = call_api('GET', $test_url, '', '', $api_key);

        Assert::assertTrue($curl_error_message_array['result']);
        Assert::assertEquals(200, $curl_error_message_array['http_code']);
    }

    /**
     * Tests the curl_error_handling function with the http status code 400
     */
    public function test_curl_error_handling_code_400(): void
    {
        $api_key = 'ApiKey sandbox';
        $api_key_wrong = 'ApiKey ____***££££èèè';
        $test_url = 'https://backend.demo.taler.net';
        $test_url_wrong = 'https://backend.demo.taler.net/test_if_this_exits';
        $body = json_encode(array(
            'order' => array(
                'wrong_field' => 'Wrong value',
            )
        ));

        $curl_error_message_array_400 = call_api('POST', $test_url, $body, 'create_order', $api_key);
        $curl_error_message_array_401 = call_api('GET', $test_url, '', '', $api_key_wrong);
        $curl_error_message_array_403 = call_api('GET', 'https://httpstat.us/403', '', '', '');
        $curl_error_message_array_404 = call_api('GET', $test_url_wrong, '', '', $api_key);

        Assert::assertFalse($curl_error_message_array_400['result']);
        Assert::assertEquals(400, $curl_error_message_array_400['http_code']);
        Assert::assertFalse($curl_error_message_array_401['result']);
        Assert::assertEquals(401, $curl_error_message_array_401['http_code']);
        Assert::assertFalse($curl_error_message_array_403['result']);
        Assert::assertEquals(403, $curl_error_message_array_403['http_code']);
        Assert::assertFalse($curl_error_message_array_404['result']);
        Assert::assertEquals(404, $curl_error_message_array_404['http_code']);

    }


    /**
     * Tests the curl_error_handling function with the http status code 500
     */
    public function test_curl_error_handling_code_500(): void
    {
        $api_key = 'ApiKey sandbox';
        $test_url = 'https://backend.demo.taler.net';
        $test_url_500 = 'https://httpstat.us/500';
        $test_url_502 = 'https://httpstat.us/502';
        $test_url_503 = 'https://httpstat.us/503';
        $test_url_504 = 'https://httpstat.us/504';

        $curl_error_message_array_500 = call_api('GET', $test_url_500, '', '', $api_key);
        $curl_error_message_array_502 = call_api('GET', $test_url_502, '', '', $api_key);
        $curl_error_message_array_503 = call_api('GET', $test_url_503, '', '', $api_key);
        $curl_error_message_array_504 = call_api('GET', $test_url_504, '', '', $api_key);

        Assert::assertFalse($curl_error_message_array_500['result']);
        Assert::assertEquals(500, $curl_error_message_array_500['http_code']);
        Assert::assertFalse($curl_error_message_array_502['result']);
        Assert::assertEquals(502, $curl_error_message_array_502['http_code']);
        Assert::assertFalse($curl_error_message_array_503['result']);
        Assert::assertEquals(503, $curl_error_message_array_503['http_code']);
        Assert::assertFalse($curl_error_message_array_504['result']);
        Assert::assertEquals(504, $curl_error_message_array_504['http_code']);

    }

}
