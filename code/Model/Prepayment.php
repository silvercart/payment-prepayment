<?php

namespace SilverCart\Prepayment\Model;

use SilverCart\Admin\Forms\GridField\GridFieldConfig_ExclusiveRelationEditor;
use SilverCart\Forms\FormFields\FieldGroup;
use SilverCart\Forms\FormFields\TextField;
use SilverCart\Model\Order\Order;
use SilverCart\Model\Payment\PaymentMethod;
use SilverCart\Model\ShopEmail;
use SilverCart\Model\Translation\TranslationTools;
use SilverCart\Prepayment\Model\PrepaymentTranslation;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTP;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\GreaterThanFilter;
use SilverStripe\ORM\Filters\LessThanFilter;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer_FromString;

/**
 * prepayment module
 *
 * @package SilverCart
 * @subpackage Prepayment_Model
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2017 pixeltricks GmbH
 * @since 22.08.2017
 * @license see license file in modules root directory
 */
class Prepayment extends PaymentMethod {

    /**
     * Indicates whether a payment module has multiple payment channels or not.
     *
     * @var bool
     */
    public static $has_multiple_payment_channels = true;
    
    /**
     * A list of possible payment channels.
     *
     * @var array
     */
    public static $possible_payment_channels = array(
        'prepayment'    => 'Prepayment',
        'invoice'       => 'Invoice'
    );
    
    /**
     * classes attributes
     *
     * @var array
     */
    private static $db = array(
        'PaymentChannel'  => 'Enum("prepayment,invoice","prepayment")',
        'BankAccountData' => 'Text',
    );

    /**
     * 1:n relationships.
     *
     * @var array
     */
    private static $has_many = array(
        'PrepaymentTranslations' => PrepaymentTranslation::class,
    );

    /**
     * Casted attributes
     *
     * @var array
     */
    private static $casting = array(
        'TextBankAccountInfo'   => 'Text',
        'InvoiceInfo'           => 'Text',
        'BankAccounts'          => ArrayList::class,
    );

    /**
     * DB table name
     *
     * @var string
     */
    private static $table_name = 'SilvercartPaymentPrepayment';

    /**
     * module name to be shown in backend interface
     *
     * @var string
     */
    protected $moduleName = 'Prepayment';
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>,
     *         Sebastian Diel <sdiel@pixeltricks.de>
     * @since 13.02.2013
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'TextBankAccountInfo'    => PrepaymentTranslation::singleton()->fieldLabel('TextBankAccountInfo'),
                    'InvoiceInfo'            => PrepaymentTranslation::singleton()->fieldLabel('InvoiceInfo'),
                    'PrepaymentTranslations' => PrepaymentTranslation::singleton()->plural_name(),
                    'PaymentChannel'         => _t(self::class . '.PAYMENT_CHANNEL', 'Payment Channel'),
                    'TextTemplates'          => _t(self::class . '.TextTemplates', 'text templates'),
                    'InfoMailSubject'        => _t(self::class . '.InfoMailSubject', 'payment information regarding your order'),
                    'BankAccount'            => _t(self::class . '.BankAccount', 'Bank Account'),
                    'BankAccounts'           => _t(self::class . '.BankAccounts', 'Bank Accounts'),
                    'BankAccountName'        => _t(self::class . '.BankAccountName', 'Bank'),
                    'BankAccountIBAN'        => _t(self::class . '.BankAccountIBAN', 'IBAN'),
                    'BankAccountBIC'         => _t(self::class . '.BankAccountBIC', 'BIC / SWIFT'),
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
     * @return FieldList
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFieldsForModules($params);
        $fields->removeByName('InvoiceInfo');
        $fields->removeByName('TextBankAccountInfo');
        $fields->removeByName('BankAccountData');
        
        $tabTextTemplates = Tab::create($this->fieldLabel('TextTemplates'));
        $fields->fieldByName('Root')->push($tabTextTemplates);
        
        $languageFields = TranslationTools::prepare_cms_fields($this->getTranslationClassName());
        switch ($this->PaymentChannel) {
            case 'invoice':
                $tabTextTemplates->setChildren(FieldList::create($languageFields->fieldByName('InvoiceInfo')));
                break;
            case 'prepayment':
            default:
                $tabTextTemplates->setChildren(FieldList::create($languageFields->fieldByName('TextBankAccountInfo')));
        }
        
        $translations = GridField::create(
                'PrepaymentTranslations',
                $this->fieldLabel('PrepaymentTranslations'),
                $this->PrepaymentTranslations(),
                GridFieldConfig_ExclusiveRelationEditor::create()
        );
        $fields->addFieldToTab('Root.Translations', $translations);
        
        $this->addBankAccountCMSFields($fields);

        return $fields;
    }
    /**
     * creates default objects
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.08.2017
     */
    public function requireDefaultRecords() {
        parent::requireDefaultRecords();
        
        $infoMail = ShopEmail::get()->filter('TemplateName', 'PaymentPrepaymentBankAccountInfo')->first();
        
        if (is_null($infoMail) ||
            !$infoMail->exists()) {
            $infoMail = ShopEmail::create();
            $infoMail->TemplateName = 'PaymentPrepaymentBankAccountInfo';
            $infoMail->Subject      = $this->fieldLabel('InfoMailSubject');
            $infoMail->write();
        }
    }
    
    /**
     * Searchable fields
     *
     * @return array
     *
     * @author Roland Lehmann <rlehmann@pixeltricks.de>
     * @since 5.7.2011
     */
    public function searchableFields() {
        $searchableFields = array(
            "PrepaymentTranslations.Name" => array(
                'title'  => $this->fieldLabel('Title'),
                'filter' => PartialMatchFilter::class,
            ),
            'isActive' => array(
                'title'  => $this->fieldLabel('isActive'),
                'filter' => ExactMatchFilter::class,
            ),
            'minAmountForActivation' => array(
                'title'  => $this->fieldLabel('MinAmountForActivation'),
                'filter' => GreaterThanFilter::class,
            ),
            'maxAmountForActivation' => array(
                'title'  => $this->fieldLabel('MaxAmountForActivation'),
                'filter' => LessThanFilter::class,
            ),
            'Zone.ID' => array(
                'title'  => $this->fieldLabel('AttributedZones'),
                'filter' => ExactMatchFilter::class,
            ),
            'Countries.ID' => array(
                'title'  => $this->fieldLabel('AttributedCountries'),
                'filter' => ExactMatchFilter::class,
            )
        );
        $this->extend('updateSearchableFields', $searchableFields);
        return $searchableFields;
    }
    
    /**
     * Called on before write.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 18.04.2018
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $this->writeBankAccounts();
    }

    /***********************************************************************************************
     ***********************************************************************************************
     **                                                                                           ** 
     **                          Mutator methods for casted attributes                            ** 
     **                                                                                           ** 
     ***********************************************************************************************
     **********************************************************************************************/
    
    /**
     * getter for the multilingual attribute TextBankAccountInfo
     *
     * @return string 
     */
    public function getTextBankAccountInfo() {
        return $this->getTranslationFieldValue('TextBankAccountInfo');
    }
    
    /**
     * getter for the multilingual attribute InvoiceInfo
     *
     * @return string 
     */
    public function getInvoiceInfo() {
        return $this->getTranslationFieldValue('InvoiceInfo');
    }

    /***********************************************************************************************
     ***********************************************************************************************
     **                                                                                           ** 
     **                               Bank account handling methods                               ** 
     **                                                                                           ** 
     ***********************************************************************************************
     **********************************************************************************************/
    
    /**
     * Returns the bank accounts.
     * 
     * @return ArrayList
     */
    public function getBankAccounts() {
        if ($this->PaymentChannel != 'prepayment') {
            return ArrayList::create();
        }
        $bankAccounts    = ArrayList::create();
        $bankAccountData = unserialize($this->BankAccountData);
        if (is_array($bankAccountData)) {
            foreach ($bankAccountData as $ID => $data) {
                if (empty($data['Name']) &&
                    empty($data['IBAN']) &&
                    empty($data['BIC'])) {
                    continue;
                }
                $bankAccounts->add(ArrayData::create([
                    'ID'   => $ID,
                    'Name' => $data['Name'],
                    'IBAN' => $data['IBAN'],
                    'BIC'  => $data['BIC'],
                ]));
            }
        }
        return $bankAccounts;
    }
    
    /**
     * Adds the bank account related CMS fields.
     * 
     * @param FieldList $fields Field list
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 18.04.2018
     */
    protected function addBankAccountCMSFields(FieldList $fields) {
        if ($this->PaymentChannel != 'prepayment') {
            return;
        }
        $fields->findOrMakeTab('Root.BankAccounts', $this->fieldLabel('BankAccounts'));
        $bankAccounts = $this->getBankAccounts();
        $highestID    = 0;
        if ($bankAccounts->exists()) {
            $highestID = $bankAccounts->sort('ID', 'DESC')->first()->ID;
        }
        $bankAccounts->add(new ArrayData(['ID' => $highestID+1, 'Name' => '', 'IBAN' => '', 'BIC' => '']));
        $index = 1;
        foreach ($bankAccounts as $bankAccount) {
            $bankAccountGroup = new FieldGroup('BankAccountGroup' . $bankAccount->ID, '', $fields);
            $bankAccountGroup->push(new \SilverStripe\Forms\ReadonlyField('BankAccuntLabel' . $bankAccount->ID, $this->fieldLabel('BankAccount') . ' ' . $index, '  '));
            $bankAccountGroup->push(new TextField('BankAccunts[' . $bankAccount->ID . '][Name]', $this->fieldLabel('BankAccountName'), $bankAccount->Name));
            $bankAccountGroup->push(new TextField('BankAccunts[' . $bankAccount->ID . '][IBAN]', $this->fieldLabel('BankAccountIBAN'), $bankAccount->IBAN));
            $bankAccountGroup->push(new TextField('BankAccunts[' . $bankAccount->ID . '][BIC]',  $this->fieldLabel('BankAccountBIC'),  $bankAccount->BIC));
            $fields->addFieldToTab('Root.BankAccounts', $bankAccountGroup);
            $index++;
        }
    }
    
    /**
     * Writes the bank account data on before write.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 18.04.2018
     */
    protected function writeBankAccounts() {
        if ($this->PaymentChannel != 'prepayment') {
            return;
        }
        $requests        = Controller::curr()->getRequest();
        $bankAccounts    = $requests->postVar('BankAccunts');
        $bankAccountData = [];
        if (is_array($bankAccounts)) {
            foreach ($bankAccounts as $ID => $data) {
                if (empty($data['Name']) &&
                    empty($data['IBAN']) &&
                    empty($data['BIC'])) {
                    continue;
                }
                $bankAccountData[$ID] = [
                    'Name' => $data['Name'],
                    'IBAN' => $data['IBAN'],
                    'BIC'  => $data['BIC'],
                ];
            }
            if (!empty($bankAccountData)) {
                $this->BankAccountData = serialize($bankAccountData);
            }
        }
    }

    /***********************************************************************************************
     ***********************************************************************************************
     **                                                                                           ** 
     ** Payment processing section. SilverCart checkout will call these methods:                  ** 
     **                                                                                           ** 
     **     - canProcessBeforePaymentProvider                                                     ** 
     **     - canProcessAfterPaymentProvider                                                      ** 
     **     - canProcessBeforeOrder                                                               ** 
     **     - canProcessAfterOrder                                                                ** 
     **     - canPlaceOrder                                                                       ** 
     **     - processBeforePaymentProvider                                                        ** 
     **     - processAfterPaymentProvider                                                         ** 
     **     - processBeforeOrder                                                                  ** 
     **     - processAfterOrder                                                                   ** 
     **     - processNotification                                                                 ** 
     **     - processConfirmationText                                                             ** 
     **                                                                                           ** 
     ***********************************************************************************************
     **********************************************************************************************/
    
    /**
     * Returns whether the checkout is ready to call self::processAfterOrder().
     * 
     * @param array $checkoutData Checkout data
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.04.2018
     */
    public function canProcessAfterOrder(Order $order, array $checkoutData) {
        return true;
    }
    
    /**
     * Is called by default checkout right before placing an order.
     * If this returns false, the order won't be placed and the checkout won't be finalized.
     * 
     * @param array $checkoutData Checkout data
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.04.2018
     */
    public function canPlaceOrder(array $checkoutData) {
        return true;
    }
    
    /**
     * Is called by default checkout right after placing an order.
     * If payed by prepayment, an email containing the bank account data will be sent to the
     * customer.
     * 
     * @param \SilverCart\Model\Order\Order $order        Order
     * @param array                         $checkoutData Checkout data
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 13.04.2018
     */
    public function processAfterOrder(Order $order, array $checkoutData) {
        if ($this->PaymentChannel == 'prepayment' &&
            (!empty($this->TextBankAccountInfo)) ||
             $this->getBankAccounts()->exists()) {
            // send email with payment information to the customer
            ShopEmail::send(
                'PaymentPrepaymentBankAccountInfo',
                $order->CustomersEmail,
                array(
                    'Order' => $order,
                )
            );
        }
    }
    
    /**
     * Is called before rendering the order confirmation page right after the order placement is 
     * finalized.
     * Expects an optional string to display additional information to the customer (e.g. showing
     * the shop owners bank account data if the customer chose prepayment).
     * 
     * @param \SilverCart\Model\Order\Order $order        Order
     * @param array                         $checkoutData Checkout data
     * 
     * @return string
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 13.04.2018
     */
    public function processConfirmationText(Order $order, array $checkoutData) {
        switch ($this->PaymentChannel) {
            case 'invoice':
                $textTemplate = new SSViewer_FromString($this->InvoiceInfo);
                break;
            case 'prepayment':
            default:
                $textTemplate = new SSViewer_FromString($this->TextBankAccountInfo);
        }
        
        $text = HTTP::absoluteURLs($textTemplate->process(new ArrayData(['Order' => $order])));
        
        return $text;
    }
    
}