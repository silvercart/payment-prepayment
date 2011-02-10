<?php
/**
 * Vorkasse Zahlungsmodul
 *
 * @package fashionbids
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2011 pixeltricks GmbH
 * @since 05.01.2011
 * @license none
 */
class PaymentPrepayment extends PaymentMethod {

    /**
     * Definition der Datenbankfelder.
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
     * Definition der Labels fuer die Datenbankfelder.
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
     * Definiert die 1:1 Beziehungen der Klasse.
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public static $has_one = array(
        'HandlingCost' => 'HandlingCostPrepayment'
    );

    /**
     * Enthaelt den Modulname zur Anzeige in der Adminoberflaeche.
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    protected $moduleName = 'Vorkasse';

    /**
     * Liefert die Eingabefelder zum Bearbeiten des Datensatzes.
     *
     * @param mixed $params Optionale Parameter
     *
     * @return FieldSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function getCMSFields($params = null) {
        $fields         = parent::getCMSFields_forPopup($params);
        $fieldLabels    = self::$field_labels;
        
        $tabTextTemplates = new Tab('Textvorlagen');
        
        $fields->fieldByName('Sections')->push($tabTextTemplates);
        
        // Textvorlagen Tab Felder --------------------------------------------
        $tabTextTemplates->setChildren(
            new FieldSet(
                new TextareaField('TextBankAccountInfo', $fieldLabels['TextBankAccountInfo'], 10, 10)
            )
        );

        return $fields;
    }

    // ------------------------------------------------------------------------
    // Verarbeitungsmethoden
    // ------------------------------------------------------------------------
    
    /**
     * Bietet die Moeglichkeit, Code nach dem Anlegen der Bestellung
     * auszufuehren.
     *
     * @param Order $orderObj Das Order-Objekt, mit dessen Daten die Abwicklung
     * erfolgen soll.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 05.01.2011
     */
    public function processPaymentAfterOrder($orderObj) {
        $member = Member::currentMember();
        
        if ($member) {
            // Eine Email mit Zahlungsanweisungen an den Kunde schicken
            ShopEmail::send(
                'PaymentPrepaymentBankAccountInfo',
                $member->Email,
                array(
                    'Order' => $orderObj,
                )
            );
        }
        parent::processPaymentAfterOrder($orderObj);
    }

    /**
     * Bietet die Moeglichkeit, Code vor dem Anlegen der Bestellung
     * auszufuehren.
     *
     * Holt sich das Paypal-Token und speichert es in der Session.
     * Anschliessend wird zum Checkout auf Paypal weitergeleitet.
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
     * Bietet die Moeglichkeit, Code nach dem Ruecksprung vom Payment
     * Provider auszufuehren.
     * Diese Methode wird vor dem Anlegen der Bestellung durchgefuehrt.
     *
     * Von Paypal wird in diesem Schritt die PayerId gesendet, die wir hier
     * in der Session speichern.
     * Anschliessend wird zum naechsten Schritt der Checkoutreihe
     * weitergeleitet.
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
     * Bietet die Moeglichkeit, nach dem Ende der Bestellung noch einen Text
     * auszugeben.
     * Diese Methode wird nach dem Ende der Bestellung aufgerufen.
     *
     * @param Order $orderObj Das Order-Objekt, mit dessen Daten die Abwicklung
     * erfolgen soll.
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
    // Methoden, die nur fuer das Vorkasse-Modul von Belang sind.
    // ------------------------------------------------------------------------
    
    /**
     * Legt benoetigte Datensaetze an.
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
            'ShopEmail',
            sprintf(
                "\"Identifier\" = '%s'",
                'PaymentPrepaymentBankAccountInfo'
            )
        );
        
        if (!$checkInfoMail) {
            $infoMail = new ShopEmail();
            $infoMail->setField('Identifier',   'PaymentPrepaymentBankAccountInfo');
            $infoMail->setField('Subject',      'Zahlungsinformationen zu Ihrer Bestellung');
            $infoMail->setField('EmailText',    '');
            $infoMail->setField('Variables',    "\$orderInfo\$\n\$orderTotal\$");
            $infoMail->write();
        }
    }
}
