<?php
/**
 * Copyright 2010, 2011 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Silvercart
 * @subpackage Forms Checkout
 */

/**
 * form step for customers shipping/billing address
 *
 * @package Silvercart
 * @subpackage Forms Checkout
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @copyright Pixeltricks GmbH
 * @since 03.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartPaymentPrepaymentCheckoutFormStep3 extends SilvercartCheckoutFormStepDefaultOrderConfirmation {

    /**
     * Render this step with the default template
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 06.04.2011
     */
    public function init() {
        $paymentConfirmationText = '';
        $checkoutData            = $this->controller->getCombinedStepData();
        
        if (isset($checkoutData['PaymentMethod'])) {
            $this->paymentMethodObj = DataObject::get_by_id(
                'SilvercartPaymentMethod',
                $checkoutData['PaymentMethod']
            );
            
            if ($this->paymentMethodObj) {
                
                if (isset($checkoutData['orderId'])) {
                    $orderObj = DataObject::get_by_id(
                        'SilvercartOrder',
                        $checkoutData['orderId']
                    );

                    if ($orderObj) {
                        $paymentConfirmationText = $this->paymentMethodObj->processPaymentConfirmationText($orderObj);
                        $this->paymentMethodObj->processPaymentAfterOrder($orderObj);
                    }
                }
            }
        }
        
        $templateVariables = array(
            'PaymentConfirmationText' => $paymentConfirmationText
        );
        
        $output = $this->customise($templateVariables)->renderWith('SilvercartCheckoutFormStepDefaultOrderConfirmation');
        
        return $output;
    }
}

