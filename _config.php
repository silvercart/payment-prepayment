<?php

use SilverCart\Admin\Dev\ExampleData;
use SilverCart\Model\ShopEmail;
use SilverCart\Prepayment\Model\Prepayment;

/***********************************************************************************************
 ***********************************************************************************************
 **                                                                                           ** 
 ** Registers the email template to send bank account information to the customer after an    ** 
 ** order was placed using prepayment.                                                        ** 
 **                                                                                           ** 
 ** Registers a callback function to use for the email example data to show a preview in      ** 
 ** backend.                                                                                  ** 
 **                                                                                           ** 
 ***********************************************************************************************
 **********************************************************************************************/
ShopEmail::register_email_template('PaymentPrepaymentBankAccountInfo');
ExampleData::register_email_example_data('PaymentPrepaymentBankAccountInfo', function() {
    $order   = ExampleData::get_order();
    $payment = Prepayment::get()->filter('PaymentChannel', 'prepayment')->first();
    $order->PaymentMethodID = $payment->ID;
    return [
        'Order' => $order,
    ];
});