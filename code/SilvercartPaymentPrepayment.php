<?php
/**
 * Copyright 2010, 2011 pixeltricks GmbH
 *
 * This file is part of SilvercartPrepaymentPayment.
 *
 * SilvercartPaypalPayment is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilvercartPrepaymentPayment is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilvercartPrepaymentPayment.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Silvercart
 * @subpackage Payment
 */

/**
 * prepayment module
 *
 * @package Silvercart
 * @subpackage Payment
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 05.01.2011
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartPaymentPrepayment extends SilvercartPaymentMethod {

    /**
     * classes attributes
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public static $db = array(
        'TextBankAccountInfo' => 'Text'
    );

    /**
     * label definition for attributes
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public static $field_labels = array(
        'TextBankAccountInfo' => 'Bankverbindung'
    );

    /**
     * define 1:1 relations
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public static $has_one = array(
        'SilvercartHandlingCost' => 'SilvercartHandlingCostPrepayment'
    );

    /**
     * module name to be shown in backend interface
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    protected $moduleName = 'Prepayment';

    /**
     * Constructor. We localize the static variables here.
     *
     * @param array|null $record      This will be null for a new database record.
     *                                  Alternatively, you can pass an array of
     *                                  field values.  Normally this contructor is only used by the internal systems that get objects from the database.
     * @param boolean    $isSingleton This this to true if this is a singleton() object, a stub for calling methods.  Singletons
     *                                  don't have their defaults set.
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 20.02.2011
     */
    public function  __construct($record = null, $isSingleton = false) {
        parent::__construct($record, $isSingleton);
    }

    /**
     * i18n for labels
     *
     * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 28.2.2011
     * @return array
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = parent::fieldLabels($includerelations);
        $fieldLabels['TextBankAccountInfo'] = _t('SilvercartPaymentPrepayment.BANK_ACCOUNT_INFO', 'bank account information');
        return $fieldLabels;
    }

    /**
     * input fields for editing
     *
     * @param mixed $params optional
     *
     * @return FieldSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function getCMSFields($params = null) {
        $fields         = parent::getCMSFields_forPopup($params);
        $fieldLabels    = self::fieldLabels();
        
        $tabTextTemplates = new Tab(_t('SilvercartPaymentPrepayment.TEXT_TEMPLATES', 'text templates', null, 'Textvorlagen'));
        
        $fields->fieldByName('Sections')->push($tabTextTemplates);

        // text templates for tab fields
        // Textvorlagen Tab Felder --------------------------------------------
        $tabTextTemplates->setChildren(
            new FieldSet(
                new TextareaField('TextBankAccountInfo', $fieldLabels['TextBankAccountInfo'], 10, 10)
            )
        );

        return $fields;
    }

    // ------------------------------------------------------------------------
    // methods
    // ------------------------------------------------------------------------
    
    /**
     * Hook
     *
     * Bietet die Moeglichkeit, Code nach dem Anlegen der Bestellung
     * auszufuehren.
     *
     * @param SilvercartOrder $orderObj the order object
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function processPaymentAfterOrder($orderObj) {

        // send email with payment information to the customer
        // Eine Email mit Zahlungsanweisungen an den Kunde schicken
        SilvercartShopEmail::send(
            'SilvercartPaymentPrepaymentBankAccountInfo',
            $orderObj->CustomersEmail,
            array(
                'Order' => $orderObj,
            )
        );
        parent::processPaymentAfterOrder($orderObj);
    }

    /**
     * hook
     *
     * Bietet die Moeglichkeit, Code vor dem Anlegen der Bestellung
     * auszufuehren.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function processPaymentBeforeOrder() {
        parent::processPaymentBeforeOrder();
    }
    
    /**
     * hook
     *
     * processed before order creation
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function processReturnJumpFromPaymentProvider() {
        parent::processReturnJumpFromPaymentProvider();
    }
    
    /**
     * display a text message after order creation
     *
     * @param Order $orderObj the order object
     * 
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 06.01.2011
     */
    public function processPaymentConfirmationText($orderObj) {
        parent::processPaymentConfirmationText($orderObj);
        
        $variables = array(
            'order' => $orderObj
        );
        
        $templateVariables  = new ArrayData($variables);
        $textTemplate       = new SSViewer_FromString($this->TextBankAccountInfo);
        $text               = HTTP::absoluteURLs($textTemplate->process($templateVariables));
        
        return $text;
    }

    // ------------------------------------------------------------------------
    // methods specific to the prepayment module
    // ------------------------------------------------------------------------
    
    /**
     * creates default objects
     * 
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function requireDefaultRecords() {
        parent::requireDefaultRecords();
        
        $checkInfoMail = DataObject::get_one(
            'SilvercartShopEmail',
            sprintf(
                "\"Identifier\" = '%s'",
                'SilvercartPaymentPrepaymentBankAccountInfo'
            )
        );
        
        if (!$checkInfoMail) {
            $infoMail = new SilvercartShopEmail();
            $infoMail->setField('Identifier',   'SilvercartPaymentPrepaymentBankAccountInfo');
            $infoMail->setField('Subject', _t('SilvercartPaymentPrepayment.PAYMENT_INFO', 'payment information regarding your order', null, 'Zahlungsinformationen zu Ihrer Bestellung'));
            $infoMail->setField('EmailText',    '');
            $infoMail->setField('Variables',    "\$orderInfo\$\n\$orderTotal\$");
            $infoMail->write();
        }
    }
}
