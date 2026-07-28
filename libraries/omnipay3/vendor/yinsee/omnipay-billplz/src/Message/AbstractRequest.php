<?php

namespace Omnipay\Billplz\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use GuzzleHttp\Middleware;

/**
 * Abstract Request
 */
abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    private $liveEndpoint = "www.billplz.com/api/v3/";
    private $testEndpoint = "billplz-staging.herokuapp.com/api/v3/";

    private function getEndpoint()
    {
        $url = ($this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint);
        $base = 'https://'.$this->getAPIKey().':@'. $url . $this->getAPI();
        return $base;
    }

    public function sendData($data)
    {
        // don't throw exceptions for 4xx errors
        /*$this->httpClient->getEventDispatcher()->addListener(
            'request.error',
            function ($event) {
                if ($event['response']->isClientError()) {
                    $event->stopPropagation();
                }
            }
        );*/

        try {

        // Guzzle HTTP Client createRequest does funny things when a GET request
        // has attached data, so don't send the data if the method is GET.
        if ($this->getHttpMethod() == 'GET') {
            $httpResponse = $this->httpClient->request(
                $this->getHttpMethod(),
                $this->getEndpoint() . '?' . http_build_query($data),
                array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $this->getToken(),
                    'Content-type' => 'application/json',
                    'curl' => array(
                        CURLOPT_SSLVERSION => 6
                    ),
                    'http_errors' => false
                )
            );
        } else {
            $httpResponse = $this->httpClient->request(
                $this->getHttpMethod(),
                $this->getEndpoint(),
                array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $this->getToken(),
                    'Content-type' => 'application/json',
                    'curl' => array(
                        CURLOPT_SSLVERSION => 6
                    ),
                    'http_errors' => false
                ),
                $this->toJSON($data)
            );
        }

        // Might be useful to have some debug code here, PayPal especially can be
        // a bit fussy about data formats and ordering.  Perhaps hook to whatever
        // logging engine is being used.
        // echo "Data == " . json_encode($data) . "\n";

        
            //$httpRequest->getOptions()->set(CURLOPT_SSLVERSION, 6); // CURL_SSLVERSION_TLSv1_2 for libcurl < 7.35
            //$httpResponse = $httpRequest->send();
            // Empty response body should be parsed also as and empty array
            $body = $httpResponse->getBody()->getContents();
            $jsonToArrayResponse = !empty($body) ? json_decode($body, true) : array();
            return $this->response = $this->createResponse($jsonToArrayResponse, $httpResponse->getStatusCode());
        } catch (\Exception $e) {
            throw new InvalidResponseException(
                'Error communicating with payment gateway: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function toJSON($data, $options = 0)
    {
	    return json_encode($data, $options | 64);
    }

    public function getToken()
    {
        return base64_encode($this->getAPIKey().":");
    }
    
    // essenstial parameters
    public function getName()
    {
        return $this->getParameter('name');
    }

    public function setName($value)
    {
        return $this->setParameter('name', $value);
    }

    public function getEmail()
    {
        return $this->getParameter('email');
    }

    public function setEmail($value)
    {
        return $this->setParameter('email', $value);
    }

    public function getCollectionId()
    {
        return $this->getParameter('collectionId');
    }

    public function setCollectionId($value)
    {
        return $this->setParameter('collectionId', $value);
    }

    public function getAPIKey()
    {
        return $this->getParameter('apikey');
    }

    public function setAPIKey($value)
    {
        return $this->setParameter('apikey', $value);
    }

    public function getId()
    {
        return $this->getParameter('id');
    }

    public function setId($value)
    {
        return $this->setParameter('id', $value);
    }
}
