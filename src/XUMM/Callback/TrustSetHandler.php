<?php

namespace XummForWoocomerce\XUMM\Callback;

use XummForWoocomerce\XUMM\Facade\Notice;

class TrustSetHandler extends AbstractHandler
{
    public function handle() : void
    {
        $gateway = $this->getXummPaymentGateway();
        $request = $this->payload->payload->request;

        if (!empty($request['LimitAmount']['issuer']))
        {
            $gateway->update_option('issuer', $request['LimitAmount']['issuer']);

            Notice::add_flash_notice(__('Trust Line Set successfull please check address & test payment', 'xumm-for-woocommerce'));
        }
    }
}