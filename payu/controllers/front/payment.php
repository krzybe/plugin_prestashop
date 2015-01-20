<?php

/**
 * PayU payment
 *
 * @author    PayU
 * @copyright Copyright (c) 2014 PayU
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
 * http://www.payu.com
 * http://openpayu.com
 * http://twitter.com/openpayu
 */
class PayUPaymentModuleFrontController extends ModuleFrontController
{
    private $payu;
    private $returnPagePS1_4 = 'order.php?step=3';
    private $returnPagePS1_6 = 'index.php?controller=order&step=3';

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $products = $cart->getProducts();

        if (empty($products))
            Tools::redirect('index.php?controller=order');

        $this->payu = new PayU();
        $this->payu->cart = $cart;

        $_SESSION['sessionId'] = md5($this->payu->cart->id . rand() . rand() . rand() . rand());

        switch ($this->payu->getBusinessPartnerSetting('type')) {
            case PayU::BUSINESS_PARTNER_TYPE_EPAYMENT:
                $lu_form = $this->payu->getLuForm($cart);
                if (!empty($lu_form)) {
                    $result = array('luForm' => $lu_form);
                    $template = 'lu-form.tpl';
                }
                break;
            case PayU::BUSINESS_PARTNER_TYPE_PLATNOSCI:
                SimplePayuLogger::addLog('order', __FUNCTION__, 'payment.php ' . $this->payu->l('Entrance'), $this->payu->payu_order_id);


                $result = $this->payu->orderCreateRequest();
                $this->context->smarty->assign(
                    array('url_address' => $this->context->link->getModuleLink('payu', 'validation'))
                );
                $template = 'order-summary.tpl';
                break;
            default:
                //  incorrect partner
                break;
        }

        if (!empty($result)) {
            $this->context->smarty->assign(
                $result + array(
                    'id_customer' => $this->context->cookie->id_customer,
                    'return_page' => $this->getReturnPage()
                )
            );
            SimplePayuLogger::addLog('order', __FUNCTION__, $this->payu->l('Go to: ') . $this->context->link->getModuleLink('payu', 'validation') . ' used template: ' . $template . ' ' . $this->payu->l('Return page: ') . $this->getReturnPage(), $this->payu->payu_order_id);
            $this->setTemplate($template);
        } else {
            $this->context->smarty->assign(
                array(
                    'message' => $this->payu->l('An error occurred while processing your order.')
                )
            );
            SimplePayuLogger::addLog('order', __FUNCTION__, $this->payu->l('An error occurred while processing your order.'), $this->payu->payu_order_id);
            $this->setTemplate('error.tpl');
        }
    }

    private function getReturnPage()
    {
        if (version_compare(_PS_VERSION_, '1.5', 'gt')) {
            return $this->returnPagePS1_6;
        }
        return $this->returnPagePS1_4;
    }
}
