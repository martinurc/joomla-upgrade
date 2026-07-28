<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\GoCardless\Gateway;

/**
 * GoCardless Complete Purchase Request
 */
class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $data = array();
        $data['resource_uri'] = $this->httpRequest->get('resource_uri');
        $data['resource_id'] = $this->httpRequest->get('resource_id');
        $data['resource_type'] = $this->httpRequest->get('resource_type');

        if (! is_null($this->httpRequest->get('state'))) {
            $data['state'] = $this->httpRequest->get('state');
        }


        if ($this->generateSignature($data) !== $this->httpRequest->get('signature')) {
            throw new InvalidResponseException;
        }

        unset($data['resource_uri']);

        return $data;
    }

    public function sendData($data)
    {
        $credentials = base64_encode($this->getAppId() . ':' . $this->getAppSecret());
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getEndpoint() . '/api/v1/confirm',
            array('Accept' => 'application/json', 'Authorization' => 'Basic ' . $credentials),
            Gateway::generateQueryString($data)
        );

        return $this->response = new CompletePurchaseResponse(
            $this,
            json_decode($httpResponse->getBody()->getContents()),
            $this->httpRequest->get('resource_id')
        );
    }
}
