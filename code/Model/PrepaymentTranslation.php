<?php

namespace SilverCart\Prepayment\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Payment\PaymentMethodTranslation;
use SilverCart\Prepayment\Model\Prepayment;

/**
 * Translations for the multilingual attributes of Prepayment.
 *
 * @package SilverCart
 * @subpackage Prepayment_Model
 * @author Roland Lehmann <rlehmann@pixeltricks.de>
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2017 pixeltricks GmbH
 * @since 22.08.2017
 * @license see license file in modules root directory
 */
class PrepaymentTranslation extends PaymentMethodTranslation {
    
    /**
     * DB attributes.
     *
     * @var array
     */
    private static $db = array(
        'TextBankAccountInfo'   => 'Text',
        'InvoiceInfo'           => 'Text'
    );
    
    /**
     * 1:1 or 1:n relationships.
     *
     * @var array
     */
    private static $has_one = array(
        'Prepayment' => Prepayment::class,
    );

    /**
     * DB table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartPaymentPrepaymentTranslation';
    
    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.08.2017
     */
    public function singular_name() {
        return Tools::singular_name_for($this);
    }


    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.08.2017
     */
    public function plural_name() {
        return Tools::plural_name_for($this);
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 28.01.2012
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'TextBankAccountInfo' => _t(self::class . '.TextBankAccountInfo', 'Informations for payment method prepayment (bank account)'),
                    'InvoiceInfo'         => _t(self::class . '.InvoiceInfo', 'Informations for payment method invoice'),
                )
        );
        $this->extend('updateFieldLabels', $fieldLabels);
        return $fieldLabels;
    }
    
    /**
     * CMS fields for this object
     *
     * @param array $params Params
     * 
     * @return FieldList
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);
        
        switch ($this->Prepayment()->PaymentChannel) {
            case 'invoice':
                $fields->removeByName('TextBankAccountInfo');
                break;
            case 'prepayment':
            default:
                $fields->removeByName('InvoiceInfo');
        }
        
        return $fields;
    }
}