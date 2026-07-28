<?php

namespace Omnipay\TargetPay\Message;

abstract class PurchaseRequest extends AbstractRequest
{
    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function sendData($data)
    {
        $httpResponse = $this->httpClient->request(
            'GET',
            $this->getEndpoint().'?'.http_build_query($data)
        );

        return $this->response = new PurchaseResponse($this, $httpResponse->getBody()->getContents());
    }
}
