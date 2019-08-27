<?php

namespace SilverCart\Prepayment\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Payment\PaymentMethodTranslation;
use SilverCart\Prepayment\Model\Prepayment;
use SilverStripe\Forms\FieldList;

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
 * 
 * @property string $TextBankAccountInfo Text Bank Account Info
 * @property string $InvoiceInfo         Invoice Info
 * 
 * @method Prepayment Prepayment() Returns the related Prepayment.
 */
class PrepaymentTranslation extends PaymentMethodTranslation
{
    use \SilverCart\ORM\ExtensibleDataObject;
    /**
     * DB attributes.
     *
     * @var array
     */
    private static $db = [
        'TextBankAccountInfo' => 'Text',
        'InvoiceInfo'         => 'Text'
    ];
    /**
     * 1:1 or 1:n relationships.
     *
     * @var array
     */
    private static $has_one = [
        'Prepayment' => Prepayment::class,
    ];
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
     */
    public function singular_name() : string
    {
        return Tools::singular_name_for($this);
    }

    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     */
    public function plural_name() : string
    {
        return Tools::plural_name_for($this);
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     */
    public function fieldLabels($includerelations = true) : array
    {
        return $this->defaultFieldLabels($includerelations, []);
    }
    
    /**
     * CMS fields for this object
     * 
     * @return FieldList
     */
    public function getCMSFields() : FieldList
    {
        $this->beforeUpdateCMSFields(function(FieldList $fields) {
            switch ($this->Prepayment()->PaymentChannel) {
                case 'invoice':
                    $fields->removeByName('TextBankAccountInfo');
                    break;
                case 'prepayment':
                default:
                    $fields->removeByName('InvoiceInfo');
            }
        });
        return parent::getCMSFields();
    }
}