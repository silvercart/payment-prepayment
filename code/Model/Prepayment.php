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
use SilverStripe\Forms\ReadonlyField;
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
class Prepayment extends PaymentMethod
{
    /**
     * A list of possible payment channels.
     *
     * @var array
     */
    private static $possible_payment_channels = [
        'prepayment' => 'Prepayment',
        'invoice'    => 'Invoice',
    ];
    /**
     * classes attributes
     *
     * @var array
     */
    private static $db = [
        'PaymentChannel'  => 'Enum("prepayment,invoice","prepayment")',
        'BankAccountData' => 'Text',
    ];
    /**
     * 1:n relationships.
     *
     * @var array
     */
    private static $has_many = [
        'PrepaymentTranslations' => PrepaymentTranslation::class,
    ];
    /**
     * Casted attributes
     *
     * @var array
     */
    private static $casting = [
        'TextBankAccountInfo' => 'Text',
        'InvoiceInfo'         => 'Text',
        'BankAccounts'        => ArrayList::class,
    ];
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
     */
    public function fieldLabels($includerelations = true) : array
    {
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
                    'BankAccountOwner'       => _t(self::class . '.BankAccountOwner', 'Account Holder'),
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
    public function getCMSFields() : FieldList
    {
        $fields = parent::getCMSFieldsForModules();
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
                $tabTextTemplates->setChildren(FieldList::create($languageFields->fieldByName('TextBankAccountInfo')));
                break;
            default:
                break;
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
     */
    public function requireDefaultRecords() : void
    {
        parent::requireDefaultRecords();
        $infoMail = ShopEmail::get()->filter('TemplateName', 'PaymentPrepaymentBankAccountInfo')->first();
        if (is_null($infoMail)
         || !$infoMail->exists()
        ) {
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
     */
    public function searchableFields() : array
    {
        $searchableFields = [
            "PrepaymentTranslations.Name" => [
                'title'  => $this->fieldLabel('Title'),
                'filter' => PartialMatchFilter::class,
            ],
            'isActive' => [
                'title'  => $this->fieldLabel('isActive'),
                'filter' => ExactMatchFilter::class,
            ],
            'minAmountForActivation' => [
                'title'  => $this->fieldLabel('MinAmountForActivation'),
                'filter' => GreaterThanFilter::class,
            ],
            'maxAmountForActivation' => [
                'title'  => $this->fieldLabel('MaxAmountForActivation'),
                'filter' => LessThanFilter::class,
            ],
            'Zone.ID' => [
                'title'  => $this->fieldLabel('AttributedZones'),
                'filter' => ExactMatchFilter::class,
            ],
            'Countries.ID' => [
                'title'  => $this->fieldLabel('AttributedCountries'),
                'filter' => ExactMatchFilter::class,
            ]
        ];
        $this->extend('updateSearchableFields', $searchableFields);
        return $searchableFields;
    }
    
    /**
     * Called on before write.
     * 
     * @return void
     */
    public function onBeforeWrite() : void
    {
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
    public function getTextBankAccountInfo()
    {
        return $this->getTranslationFieldValue('TextBankAccountInfo');
    }
    
    /**
     * getter for the multilingual attribute InvoiceInfo
     *
     * @return string 
     */
    public function getInvoiceInfo()
    {
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
    public function getBankAccounts() : ArrayList
    {
        if ($this->PaymentChannel != 'prepayment') {
            return ArrayList::create();
        }
        $bankAccounts    = ArrayList::create();
        $bankAccountData = unserialize($this->BankAccountData);
        if (is_array($bankAccountData)) {
            foreach ($bankAccountData as $ID => $data) {
                if (empty($data['Owner']) &&
                    empty($data['Name']) &&
                    empty($data['IBAN']) &&
                    empty($data['BIC'])) {
                    continue;
                }
                $bankAccounts->add(ArrayData::create([
                    'ID'    => $ID,
                    'Owner' => $data['Owner'],
                    'Name'  => $data['Name'],
                    'IBAN'  => $data['IBAN'],
                    'BIC'   => $data['BIC'],
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
    protected function addBankAccountCMSFields(FieldList $fields) : void
    {
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
            $bankAccountGroup = FieldGroup::create('BankAccountGroup' . $bankAccount->ID, '', $fields);
            $bankAccountGroup->push(ReadonlyField::create('BankAccuntLabel' . $bankAccount->ID, "{$this->fieldLabel('BankAccount')} {$index}", '  '));
            $bankAccountGroup->push(TextField::create('BankAccounts[' . $bankAccount->ID . '][Owner]', $this->fieldLabel('BankAccountOwner'), $bankAccount->Owner));
            $bankAccountGroup->push(TextField::create('BankAccounts[' . $bankAccount->ID . '][Name]',  $this->fieldLabel('BankAccountName'),  $bankAccount->Name));
            $bankAccountGroup->push(TextField::create('BankAccounts[' . $bankAccount->ID . '][IBAN]',  $this->fieldLabel('BankAccountIBAN'),  $bankAccount->IBAN));
            $bankAccountGroup->push(TextField::create('BankAccounts[' . $bankAccount->ID . '][BIC]',   $this->fieldLabel('BankAccountBIC'),   $bankAccount->BIC));
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
    protected function writeBankAccounts() : void
    {
        if ($this->PaymentChannel != 'prepayment') {
            return;
        }
        $requests        = Controller::curr()->getRequest();
        $bankAccounts    = $requests->postVar('BankAccounts');
        $bankAccountData = [];
        if (is_array($bankAccounts)) {
            foreach ($bankAccounts as $ID => $data) {
                if (empty($data['Owner'])
                 && empty($data['Name'])
                 && empty($data['IBAN'])
                 && empty($data['BIC'])
                ) {
                    continue;
                }
                $bankAccountData[$ID] = [
                    'Owner' => $data['Owner'],
                    'Name'  => $data['Name'],
                    'IBAN'  => $data['IBAN'],
                    'BIC'   => $data['BIC'],
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
     * @return bool
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.04.2018
     */
    public function canProcessAfterOrder(Order $order, array $checkoutData) : bool
    {
        return true;
    }
    
    /**
     * Is called by default checkout right before placing an order.
     * If this returns false, the order won't be placed and the checkout won't be finalized.
     * 
     * @param array $checkoutData Checkout data
     * 
     * @return bool
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.04.2018
     */
    public function canPlaceOrder(array $checkoutData) : bool
    {
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
    public function processAfterOrder(Order $order, array $checkoutData) : void
    {
        if ($this->PaymentChannel == 'prepayment'
         && !empty($this->TextBankAccountInfo)
         || $this->getBankAccounts()->exists()
        ) {
            // send email with payment information to the customer
            ShopEmail::send(
                'PaymentPrepaymentBankAccountInfo',
                $order->CustomersEmail,
                [
                    'Order' => $order,
                ]
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
    public function processConfirmationText(Order $order, array $checkoutData) : string
    {
        switch ($this->PaymentChannel) {
            case 'invoice':
                $textTemplate = SSViewer_FromString::create($this->InvoiceInfo);
                break;
            case 'prepayment':
                $textTemplate = SSViewer_FromString::create($this->TextBankAccountInfo);
                break;
            default:
                break;
        }
        $text = HTTP::absoluteURLs($textTemplate->process(ArrayData::create(['Order' => $order])));
        return (string) $text;
    }
}