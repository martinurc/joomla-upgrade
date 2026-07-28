<?php

namespace Omnipay\Epay\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\ResponseInterface;

/**
 * Epay GetTransaction Request.
 */
class GetTransactionRequest extends AbstractRequest
{
    protected $endpoint = 'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx';

    /**
     * @return array
     */
    public function getSupportedKeys()
    {
        return ['merchantnumber', 'transactionid'];
    }

    /**
     * @param int $transactionId
     *
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        return $this->setParameter('transactionid', $transactionId);
    }

    /**
     * @param int $merchantNumber
     *
     * @return $this
     */
    public function setMerchantNumber($merchantNumber)
    {
        return $this->setParameter('merchantnumber', $merchantNumber);
    }

    /**
     * @return array
     */
    public function getData()
    {
        $this->validate('merchantnumber', 'transactionid');

        $data = array();
        foreach ($this->getSupportedKeys() as $key) {
            $value = $this->parameters->get($key);
            if (!empty($value)) {
                $data[$key] = $value;
            }
        }

        /* Hack from SOAP description */
        $data['epayresponse'] = -1;

        return $data;
    }

    /**
     * @param array $data
     *
     * @return GetTransactionResponse
     */
    public function sendData($data)
    {
        $client = new \SoapClient($this->endpoint.'?WSDL');
        $result = $client->gettransaction($data);

        return $this->response = new GetTransactionResponse($this, json_decode(json_encode($result), true));
    }

    /**
     * Send the request.
     *
     * @return ResponseInterface
     */
    public function send()
    {
        return $this->sendData($this->getData());
    }
}
