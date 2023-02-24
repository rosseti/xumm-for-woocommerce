<?php

namespace XummForWoocomerce\XUMM\Facade;

use Xrpl\XummSdkPhp\XummSdk;
use XummForWoocomerce\Woocommerce\XummPaymentGateway;

class URL
{
    public static function getReturnURL($custom_identifier, $order, XummPaymentGateway $xummPaymentGateway)
    {
        $explorer = $xummPaymentGateway->explorer;

        $sdk = new XummSdk($xummPaymentGateway->api, $xummPaymentGateway->api_secret);
        $payload = $sdk->getPayloadByCustomId($custom_identifier);

        $txid = $payload->response->txid;

        try
        {
            if ($payload->response->dispatchedResult != 'tesSUCCESS')
            {
                throw new \Exception(__('Payment failed or cancelled, feel free to try again.', 'xumm-for-woocommerce'));
            }

            $txbody = Transaction::getTransactionDetails($txid);

            if (empty($txbody))
            {
                throw new \Exception(__('Payment failed or cancelled, feel free to try again.', 'xumm-for-woocommerce'));
            }

            $delivered_amount = $txbody['result']['meta']['delivered_amount'];

            Transaction::checkDeliveredAmount($delivered_amount, $order, $xummPaymentGateway->issuers, $txid, $explorer);

            $order->payment_complete();
            wc_reduce_stock_levels( $order->get_id() );
            $order->add_order_note( __('Hi, your order is paid! Thank you!', 'xumm-for-woocommerce') . '<br>'. __('Check the transaction details', 'xumm-for-woocommerce') .' <a href="'.$explorer.$txid.'">'.__('information', 'xumm-for-woocommerce').'</a>', true );
            WC()->cart->empty_cart();

            return $xummPaymentGateway->get_return_url( $order );

        } catch (\Exception $e)
        {
            wc_add_notice($e->getMessage(), 'error');
            return $order->get_checkout_payment_url(false);
        }
    }
}
