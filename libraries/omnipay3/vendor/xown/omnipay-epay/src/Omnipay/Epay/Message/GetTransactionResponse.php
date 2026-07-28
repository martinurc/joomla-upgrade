<?php

namespace Omnipay\Epay\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * Epay GetTransaction Response.
 */
class GetTransactionResponse extends AbstractResponse
{
    const STATUS_PAYMENT_UNDEFINED = 'PAYMENT_UNDEFINED';
    const STATUS_PAYMENT_NEW = 'PAYMENT_NEW';
    const STATUS_PAYMENT_CAPTURED = 'PAYMENT_CAPTURED';
    const STATUS_PAYMENT_DELETED = 'PAYMENT_DELETED';
    const STATUS_PAYMENT_INSTANT_CAPTURE_FAILED = 'PAYMENT_INSTANT_CAPTURE_FAILED';
    const STATUS_PAYMENT_SUBSCRIPTION_INI = 'PAYMENT_SUBSCRIPTION_INI';
    const STATUS_PAYMENT_RENEW = 'PAYMENT_RENEW';
    const STATUS_PAYMENT_EUROLINE_WAIT_CAPTURE = 'PAYMENT_EUROLINE_WAIT_CAPTURE';
    const STATUS_PAYMENT_EUROLINE_WAIT_CREDIT = 'PAYMENT_EUROLINE_WAIT_CREDIT';

    const MODE_TEST = 'MODE_TEST';
    const MODE_PRODUCTION = 'MODE_PRODUCTION';
    const MODE_EPAY = 'MODE_EPAY';

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getValue('gettransactionResult') === true;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->getValue('transactionInformation', 'group');
    }

    /**
     * @return int
     */
    public function getAuthAmount()
    {
        return $this->getValue('transactionInformation', 'authamount');
    }

    /**
     * @return int
     */
    public function getCurrency()
    {
        return $this->getValue('transactionInformation', 'currency');
    }

    /**
     * @return int
     */
    public function getCardTypeId()
    {
        return $this->getValue('transactionInformation', 'cardtypeid');
    }

    /**
     * @return int
     */
    public function getCapturedAmount()
    {
        return $this->getValue('transactionInformation', 'capturedamount');
    }

    /**
     * @return int
     */
    public function getCreditedAmount()
    {
        return $this->getValue('transactionInformation', 'creditedamount');
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->getValue('transactionInformation', 'orderid');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getValue('transactionInformation', 'description');
    }

    /**
     * @return string
     */
    public function getAuthDate()
    {
        return $this->getValue('transactionInformation', 'authdate');
    }

    /**
     * @return string
     */
    public function getCapturedDate()
    {
        return $this->getValue('transactionInformation', 'captureddate');
    }

    /**
     * @return string
     */
    public function getDeletedDate()
    {
        return $this->getValue('transactionInformation', 'deleteddate');
    }

    /**
     * @return string
     */
    public function getCreditedDate()
    {
        return $this->getValue('transactionInformation', 'crediteddate');
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->getValue('transactionInformation', 'status');
    }

    /**
     * @return array|null
     */
    public function getHistory()
    {
        $history = $this->getValue('transactionInformation', 'history');
        if (!$history) {
            return;
        }

        return $history['TransactionHistoryInfo'];
    }

    /**
     * @return int
     */
    public function getTransactionId()
    {
        return $this->getValue('transactionInformation', 'transactionid');
    }

    /**
     * @return string
     */
    public function getCardHolder()
    {
        return $this->getValue('transactionInformation', 'cardholder');
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->getValue('transactionInformation', 'mode');
    }

    /**
     * @return int
     */
    public function getEPayResponse()
    {
        return $this->getValue('epayresponse');
    }

    /**
     * @return array|null
     */
    public function getTransactionInformation()
    {
        return $this->getValue('transactionInformation');
    }

    /**
     * @return bool
     */
    public function isPaymentCaptured()
    {
        return $this->getStatus() === self::STATUS_PAYMENT_CAPTURED;
    }

    /**
     * @return null|string|int
     */
    private function getValue()
    {
        $names = func_get_args();

        $data = $this->getData();
        foreach ($names as $name) {
            if (is_array($data) && isset($data[$name])) {
                $data = $data[$name];
                continue;
            }

            return null;
        }

        return $data;
    }
}
