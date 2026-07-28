<?php

namespace Omnipay\NetBanx\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class HostedAbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $liveEndpoint = 'https://api.netbanx.com/hosted/v1';
    protected $testEndpoint = 'https://api.test.netbanx.com/hosted/v1';

    /**
     * @param $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setKeyId($value)
    {
        return $this->setParameter('keyId', $value);
    }

    /**
     * @return mixed
     */
    public function getKeyId()
    {
        return $this->getParameter('keyId');
    }

    /**
     * @param $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setKeyPassword($value)
    {
        return $this->setParameter('keyPassword', $value);
    }

    /**
     * @return mixed
     */
    public function getKeyPassword()
    {
        return $this->getParameter('keyPassword');
    }

    /**
     * @param        $action
     * @param null   $data
     * @param string $method
     *
     * @return ResponseInterface
     */
    public function sendRequest($action, $data = null, $method = 'POST')
    {
        $username = $this->getKeyId();
        $password = $this->getKeyPassword();
        $base64 = base64_encode($username.':'.$password);

        $url = $this->getEndpoint().$action;

        $headers = array(
            'Authorization' => 'Basic '.$base64,
            'Content-Type'  => 'application/json'
        );

        // For some reason the native json encoder built into Guzzle 3 is not encoding the nested data correctly,
        // breaking the http request. We need to do it manually.
        $data = json_encode($data);

        // Return the response we get back
        return $this->httpClient->request($method, $url, $headers, $data);
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }

    /**
     * @return array
     */
    protected function getBaseData()
    {
        $data = array();

        return $data;
    }
}
