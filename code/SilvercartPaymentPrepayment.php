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
     * Indicates whether a payment module has multiple payment channels or not.
     *
     * @var bool
     *
     * author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 01.07.2011
     */
    public static $has_multiple_payment_channels = true;
    
    /**
     * A list of possible payment channels.
     *
     * @var array
     *
     * author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 01.07.2011
     */
    public static $possible_payment_channels = array(
        'prepayment'    => 'Prepayment',
        'invoice'       => 'Invoice'
    );
    
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
        // Payment attributes
        'PaymentChannel' => 'Enum("prepayment,invoice","prepayment")'
    );

    /**
     * 1:n relationships.
     *
     * @var array
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 28.01.2012
     */
    public static $has_many = array(
        'SilvercartPaymentPrepaymentLanguages' => 'SilvercartPaymentPrepaymentLanguage'
    );
    
    public static $casting = array(
        'TextBankAccountInfo'   => 'Text',
        'InvoiceInfo'           => 'Text'
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
     * getter for the multilingual attribute TextBankAccountInfo
     *
     * @return string 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 28.01.2012
     */
    public function getTextBankAccountInfo() {
        $text = '';
        if ($this->getLanguage()) {
            $text = $this->getLanguage()->TextBankAccountInfo;
        }
        return $text;
    }
    
    /**
     * getter for the multilingual attribute InvoiceInfo
     *
     * @return string 
     * 
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 28.01.2012
     */
    public function getInvoiceInfo() {
        $text = '';
        if ($this->getLanguage()) {
            $text = $this->getLanguage()->InvoiceInfo;
        }
        return $text;
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @copyright 2012 pixeltricks GmbH
     * @since 28.01.2012
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),             array(
                    'TextBankAccountInfo' => _t('SilvercartPaymentPrepayment.BANK_ACCOUNT_INFO'),
                    'InvoiceInfo' => _t('SilvercartPaymentPrepayment.INVOICE_INFO'),
                    'SilvercartPaymentPrepaymentLanguages' => _t('SilvercartPaymentPrepaymentLanguage.PLURALNAME')
                )
        );

        $this->extend('updateFieldLabels', $fieldLabels);
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
        $fields = parent::getCMSFieldsForModules($params);
        $fields->removeByName('InvoiceInfo');
        $fields->removeByName('TextBankAccountInfo');
        // Add fields to default tab ------------------------------------------
        $channelField = new ReadonlyField('DisplayPaymentChannel', _t('SilvercartPaymentPrepayment.PAYMENT_CHANNEL'), $this->getPaymentChannelName($this->PaymentChannel));

        $fields->addFieldToTab('Sections.Basic', $channelField, 'isActive');
        
        // Additional tabs and fields -----------------------------------------
        $tabTextTemplates = new Tab(_t('SilvercartPaymentPrepayment.TEXT_TEMPLATES', 'text templates', null, 'Textvorlagen'));
        $fields->addFieldToTab('Sections.Translations', new ComplexTableField($this, 'SilvercartPaymentPrepaymentLanguages', 'SilvercartPaymentPrepaymentLanguage'));
        $fields->fieldByName('Sections')->push($tabTextTemplates);
        // text templates for tab fields
        // Textvorlagen Tab Felder --------------------------------------------
        $languageFields = SilvercartLanguageHelper::prepareCMSFields($this->getLanguage());
        switch ($this->PaymentChannel) {
            case 'invoice':
                $tabTextTemplates->setChildren(
                    new FieldSet(
                        $languageFields->fieldByName('InvoiceInfo')
                    )
                );
                break;
            case 'prepayment':
            default:
                $tabTextTemplates->setChildren(
                    new FieldSet(
                        $languageFields->fieldByName('TextBankAccountInfo')
                    )
                );
        }

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
        if ($this->PaymentChannel == 'prepayment') {
            // send email with payment information to the customer
            SilvercartShopEmail::send(
                'SilvercartPaymentPrepaymentBankAccountInfo',
                $orderObj->CustomersEmail,
                array(
                    'SilvercartOrder' => $orderObj,
                )
            );
        }
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
            'SilvercartOrder' => $orderObj
        );
        $templateVariables  = new ArrayData($variables);
        
        switch ($this->PaymentChannel) {
            case 'invoice':
                $textTemplate = new SSViewer_FromString($this->InvoiceInfo);
                break;
            case 'prepayment':
            default:
                $textTemplate = new SSViewer_FromString($this->TextBankAccountInfo);
        }
        
        $text = HTTP::absoluteURLs($textTemplate->process($templateVariables));
        
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
            $infoMail->setField('Subject', _t('SilvercartPaymentPrepayment.PAYMENT_INFO'));
            $infoMail->setField('EmailText',    '');
            $infoMail->setField('Variables',    "\$SilvercartOrder");
            $infoMail->write();
        }
    }
    
    /**
     * Searchable fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 5.7.2011
     */
    public function searchableFields() {
        $searchableFields = array(
            "SilvercartPaymentPrepaymentLanguages.Name" => array(
                'title'  => _t('SilvercartProduct.COLUMN_TITLE'),
                'filter' => 'PartialMatchFilter'
            ),
            'isActive' => array(
                'title'  => _t("SilvercartShopAdmin.PAYMENT_ISACTIVE"),
                'filter' => 'ExactMatchFilter'
            ),
            'minAmountForActivation' => array(
                'title'  => _t('SilvercartShopAdmin.PAYMENT_MINAMOUNTFORACTIVATION'),
                'filter' => 'GreaterThanFilter'
            ),
            'maxAmountForActivation' => array(
                'title'  => _t('SilvercartShopAdmin.PAYMENT_MAXAMOUNTFORACTIVATION'),
                'filter' => 'LessThanFilter'
            ),
            'SilvercartZone.ID' => array(
                'title'  => _t("SilvercartCountry.ATTRIBUTED_ZONES"),
                'filter' => 'ExactMatchFilter'
            ),
            'SilvercartCountries.ID' => array(
                'title'  => _t("SilvercartPaymentMethod.ATTRIBUTED_COUNTRIES"),
                'filter' => 'ExactMatchFilter'
            )
        );
        $this->extend('updateSearchableFields', $searchableFields);
        return $searchableFields;
    }
}
