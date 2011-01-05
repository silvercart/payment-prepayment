<?php
/**
 * Paypal Zahlungsmodul
 *
 * @package fashionbids
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2010 pixeltricks GmbH
 * @since 09.11.2010
 * @license none
 */
class PaymentPaypal extends PaymentMethod {

    /**
     * Definition der Datenbankfelder.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 12.11.2010
     */
    public static $db = array(
        'paypalSharedSecret'               => 'VarChar(255)',
        'paypalCheckoutUrl_Dev'            => 'VarChar(255)',
        'paypalCheckoutUrl_Live'           => 'VarChar(255)',
        'paypalApiUsername_Dev'            => 'VarChar(255)',
        'paypalApiUsername_Live'           => 'VarChar(255)',
        'paypalApiPassword_Dev'            => 'VarChar(255)',
        'paypalApiPassword_Live'           => 'VarChar(255)',
        'paypalApiSignature_Dev'           => 'VarChar(255)',
        'paypalApiSignature_Live'          => 'VarChar(255)',
        'paypalNvpApiServerUrl_Dev'        => 'VarChar(255)',
        'paypalNvpApiServerUrl_Live'       => 'VarChar(255)',
        'paypalSoapApiServerUrl_Dev'       => 'VarChar(255)',
        'paypalSoapApiServerUrl_Live'      => 'VarChar(255)',
        'paypalApiVersion_Dev'             => 'VarChar(255)',
        'paypalApiVersion_Live'            => 'VarChar(255)',
        'paypalInfotextCheckout'           => 'VarChar(255)',
        'PaidOrderStatus'                  => 'Int',
        'CanceledOrderStatus'              => 'Int',
        'PendingOrderStatus'               => 'Int',
        'RefundedOrderStatus'              => 'Int'
    );

    /**
     * Definition der Labels fuer die Datenbankfelder.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 12.11.2010
     */
    public static $field_labels = array(
        'paypalSharedSecret'                => 'Shared Secret zur Absicherung der Kommunikation',
        'paypalCheckoutUrl_Dev'             => 'URL zum Paypal Checkout',
        'paypalCheckoutUrl_Live'            => 'URL zum Paypal Checkout',
        'paypalApiUsername_Dev'             => 'API Benutzername',
        'paypalApiUsername_Live'            => 'API Benutzername',
        'paypalApiPassword_Dev'             => 'API Passwort',
        'paypalApiPassword_Live'            => 'API Passwort',
        'paypalApiSignature_Dev'            => 'API Signatur',
        'paypalApiSignature_Live'           => 'API Signatur',
        'paypalApiVersion_Dev'              => 'API Version',
        'paypalApiVersion_Live'             => 'API Version',
        'paypalNvpApiServerUrl_Dev'         => 'URL zum Paypal NVP API Server',
        'paypalNvpApiServerUrl_Live'        => 'URL zum Paypal NVP API Server',
        'paypalSoapApiServerUrl_Dev'        => 'URL zum Paypal SOAP API Server',
        'paypalSoapApiServerUrl_Live'       => 'URL zum Paypal SOAP API Server',
        'paypalInfotextCheckout'            => 'Die Zahlung erfolgt per Paypal',
        'PaidOrderStatus'                   => 'Bestellstatus für Meldung "bezahlt"',
        'CanceledOrderStatus'               => 'Bestellstatus für Meldung "abgebrochen"',
        'PendingOrderStatus'                => 'Bestellstatus für Meldung "in der Schwebe"',
        'RefundedOrderStatus'               => 'Bestellstatus für Meldung "zurückerstattet"'
    );

    /**
     * Definiert die 1:1 Beziehungen der Klasse.
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public static $has_one = array(
        'HandlingCost' => 'HandlingCostPaypal'
    );

    /**
     * Enthaelt den Modulname zur Anzeige in der Adminoberflaeche.
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 12.11.2010
     */
    protected $moduleName = 'Paypal';

    /**
     * Enthaelt den Namen fuer die SharedSecret-Kennung. Diese wird von Paypal
     * bei den IPN-Rueckmeldungen genutzt.
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.11.2010
     */
    protected $sharedSecretVariableName = 'sh';

    /**
     * Enthaelt alle Rueckmeldungsstrings von Paypal, die den Status der
     * Transaktion fuer gescheitert erklaeren.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 25.11.2010
     */
    public $failedPaypalStatus = array(
        'Denied',
        'Expired',
        'Failed',
        'Voided'
    );

    /**
     * Enthaelt alle Rueckmeldungsstrings von Paypal, die den Status der
     * Transaktion fuer erfolgreich erklaeren.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 25.11.2010
     */
    public $successPaypalStatus = array(
        'Completed',
        'Processed',
        'Canceled-Reversal'
    );

    /**
     * Enthaelt alle Rueckmeldungsstrings von Paypal, die anzeigen, dass die
     * Zahlung zurueckgezogen wird.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 25.11.2010
     */
    public $refundedPaypalStatus = array(
        'Refunded',
        'Reversed'
    );

    /**
     * Enthaelt alle Rueckmeldungsstrings von Paypal, die anzeigen, dass die
     * Transaktion in der Schwebe ist.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 25.11.2010
     */
    public $pendingPaypalStatus = array(
        'Pending'
    );

    /**
     * Liefert die Eingabefelder zum Bearbeiten des Datensatzes.
     *
     * @param mixed $params Optionale Parameter
     *
     * @return FieldSet
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 12.11.2010
     */
    public function getCMSFields_forPopup($params = null) {
        $fields         = parent::getCMSFields_forPopup($params);
        $fieldLabels    = self::$field_labels;

        $tabApi         = new Tab('PaypalAPI');
        $tabUrls        = new Tab('PaypalURLs');
        $tabOrderStatus = new Tab('OrderStatus', 'Zuordnung Bestellstatus');

        $fields->fieldByName('Sections')->push($tabApi);
        $fields->fieldByName('Sections')->push($tabUrls);
        $fields->fieldByName('Sections')->push($tabOrderStatus);

        // Grundeinstellungen -------------------------------------------------
        $fields->addFieldToTab(
            'Sections.Basic',
            new TextField('paypalSharedSecret', $fieldLabels['paypalSharedSecret'])
        );

        // API Tabset ---------------------------------------------------------
        $tabApiTabset   = new TabSet('APIOptions');
        $tabApiTabDev   = new Tab('API Entwicklungsmodus');
        $tabApiTabLive  = new Tab('API Livemodus');

        // API Tabs -----------------------------------------------------------
        $tabApiTabset->push($tabApiTabDev);
        $tabApiTabset->push($tabApiTabLive);

        $tabApi->push($tabApiTabset);

        // URL Tabset ---------------------------------------------------------
        $tabUrlTabset   = new TabSet('URLOptions');
        $tabUrlTabDev   = new Tab('URLs Entwicklungsmodus');
        $tabUrlTabLive  = new Tab('URLs Livemodus');

        // URL Tabs -----------------------------------------------------------
        $tabUrlTabset->push($tabUrlTabDev);
        $tabUrlTabset->push($tabUrlTabLive);

        $tabUrls->push($tabUrlTabset);

        // API Tab Dev Felder -------------------------------------------------
        $tabApiTabDev->setChildren(
            new FieldSet(
                new TextField('paypalApiUsername_Dev',     $fieldLabels['paypalApiUsername_Dev']),
                new TextField('paypalApiPassword_Dev',     $fieldLabels['paypalApiPassword_Dev']),
                new TextField('paypalApiSignature_Dev',    $fieldLabels['paypalApiSignature_Dev']),
                new TextField('paypalApiVersion_Dev',      $fieldLabels['paypalApiVersion_Dev'])
            )
        );

        // API Tab Live Felder ------------------------------------------------
        $tabApiTabLive->setChildren(
            new FieldSet(
                new TextField('paypalApiUsername_Live',   $fieldLabels['paypalApiUsername_Live']),
                new TextField('paypalApiPassword_Live',   $fieldLabels['paypalApiPassword_Live']),
                new TextField('paypalApiSignature_Live',  $fieldLabels['paypalApiSignature_Live']),
                new TextField('paypalApiVersion_Live',    $fieldLabels['paypalApiVersion_Live'])
            )
        );

        // URL Tab Dev Felder -------------------------------------------------
        $tabUrlTabDev->setChildren(
            new FieldSet(
                new TextField('paypalCheckoutUrl_Dev',             $fieldLabels['paypalCheckoutUrl_Dev']),
                new TextField('paypalNvpApiServerUrl_Dev',         $fieldLabels['paypalNvpApiServerUrl_Dev']),
                new TextField('paypalSoapApiServerUrl_Dev',        $fieldLabels['paypalSoapApiServerUrl_Dev'])
            )
        );

        // URL Tab Live Felder ------------------------------------------------
        $tabUrlTabLive->setChildren(
            new FieldSet(
                new TextField('paypalCheckoutUrl_Live',            $fieldLabels['paypalCheckoutUrl_Live']),
                new TextField('paypalNvpApiServerUrl_Live',        $fieldLabels['paypalNvpApiServerUrl_Live']),
                new TextField('paypalSoapApiServerUrl_Live',       $fieldLabels['paypalSoapApiServerUrl_Live'])
            )
        );

        // Bestellstatus Tab Felder -------------------------------------------
        $OrderStatus = DataObject::get('OrderStatus');
        $tabOrderStatus->setChildren(
            new FieldSet(
                new DropdownField('PaidOrderStatus',     $fieldLabels['PaidOrderStatus'],     $OrderStatus->map('ID', 'Title'), $this->PaidOrderStatus),
                new DropdownField('CanceledOrderStatus', $fieldLabels['CanceledOrderStatus'], $OrderStatus->map('ID', 'Title'), $this->CanceledOrderStatus),
                new DropdownField('PendingOrderStatus',  $fieldLabels['PendingOrderStatus'],  $OrderStatus->map('ID', 'Title'), $this->PendingOrderStatus),
                new DropdownField('RefundedOrderStatus', $fieldLabels['RefundedOrderStatus'], $OrderStatus->map('ID', 'Title'), $this->RefundedOrderStatus)
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
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function processPaymentAfterOrder($orderObj) {
        return $this->doExpressCheckoutPayment($orderObj);
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
     * @copyright 2010 pixeltricks GmbH
     * @since 16.11.2010
     */
    public function processPaymentBeforeOrder() {
        parent::processPaymentBeforeOrder();

        $token = $this->fetchPaypalToken();

        if (!$this->errorOccured) {
            $this->saveToken($token);
        }

        $this->controller->addCompletedStep($this->controller->getCurrentStep());
        $this->controller->addCompletedStep($this->controller->getNextStep());
        $this->controller->setCurrentStep($this->controller->getNextStep());

        if ($this->mode == 'Live') {
            Director::redirect($this->paypalCheckoutUrl_Live.'cmd=_express-checkout&token='.$token);
        } else {
            Director::redirect($this->paypalCheckoutUrl_Dev.'cmd=_express-checkout&token='.$token);
        }
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
     * @copyright 2010 pixeltricks GmbH
     * @since 19.11.2010
     */
    public function processReturnJumpFromPaymentProvider() {
        parent::processReturnJumpFromPaymentProvider();

        if (!isset($_REQUEST['token'])) {
            $this->log('processReturnJumpFromPaymentProvider', var_export($_REQUEST, true));
            $this->errorOccured = true;
            $this->addError('In der Kommunikation mit Paypal ist ein Fehler aufgetreten.');
        }
        if (!$this->errorOccured &&
            !isset($_REQUEST['PayerID'])) {

            $this->log('processReturnJumpFromPaymentProvider', var_export($_REQUEST, true));
            $this->errorOccured = true;
            $this->addError('In der Kommunikation mit Paypal ist ein Fehler aufgetreten.');
        }

        if (!$this->errorOccured) {
            $this->savePayerid($_REQUEST['PayerID']);
            $this->controller->NextStep();
        }
    }

    // -----------------------------------------------------------------------
    // Methoden, die nur fuer das Paypal-Modul von Belang sind.
    // -----------------------------------------------------------------------

    /**
     * Holt die Zahlungs- und Versandinformationen von Paypal.
     * 
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function getExpressCheckoutDetails() {
        
        $parameters = array(
            'TOKEN'     => $_SESSION['Paypal_Token'],
            'PAYERID'   => $this->getPayerId()
        );
        
        $response = $this->hash_call('GetExpressCheckoutDetails', $this->generateUrlParams($parameters));
        
        $this->log('getExpressCheckoutDetails: Got Response', var_export($response, true));
        $this->log('getExpressCheckoutDetails: With Parameters', var_export($parameters, true));

        return $response;
    }

    /**
     * Bestaetigung und Abwicklung der Zahlung.
     * 
     * @param Order $orderObj Das Order-Objekt, mit dessen Daten die Abwicklung
     * erfolgen soll.
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function doExpressCheckoutPayment($orderObj) {
        // Rundungsdifferenzen beseitigen
        $cartAmountGross = round((float) $orderObj->getAmountGross(), 2);
        $cartAmountNet   = round((float) $orderObj->getAmountNet(), 2);
        $itemAmountGross = round((float) $orderObj->getPriceGross(), 2);
        $itemAmountNet   = round((float) $orderObj->getPriceNet(), 2);
        $shippingAmt     = round((float) $orderObj->getShippingCosts(), 2);
        $handlingAmt     = round((float) $orderObj->getHandlingCosts(), 2);
        $taxAmt          = round((float) $orderObj->getTax(), 2);
        $total           = $itemAmountNet + $taxAmt + $shippingAmt + $handlingAmt;

        if ($total < $cartAmountGross) {
            $difference  = $cartAmountGross - $total;
            $total      += $difference;
        }

        $this->Log(
            'doExpressCheckoutPayment: Amounts',
            '  warenkorb_summe_brutto: '.$total.
            ', warenkorb summe netto: '.$cartAmountNet.
            ', itemamt: '.$itemAmountGross.
            ', shippingamt: '.$shippingAmt.
            ', handlingamt: '.$handlingAmt.
            ', taxamt: '.$taxAmt
        );

        // Pflichtparameter:
        $parameters = array(
            'TOKEN'         => $this->getPaypalToken(),
            'PAYERID'       => $this->getPayerId(),
            'PAYMENTACTION' => 'Sale',
            'AMT'           => $cartAmountGross, // Gesamtbetrag der Bestellung inklusive Versandkosten und Steuern
            // Informationen zum Gesamtbetrag:
            'ITEMAMT'       => $itemAmountNet,    // Nettobeträge aller Bestellungsposten
            'SHIPPINGAMT'   => $shippingAmt, // Versandkosten
            'HANDLINGAMT'   => $handlingAmt, // Die Verpackung- und Bearbeitungskosten
            'TAXAMT'        => $taxAmt,            // die Summe aller anfallenden Steuern
            'DESC'          => 'Order Nr. '.$orderObj->ID,
            'CURRENCYCODE'  => 'EUR',
            'CUSTOM'        => 'order_id='.$orderObj->ID
        );
        
        $notifyUrl               = Director::absoluteBaseURL().'payment-notification/process/'.$this->moduleName;
        $notifyUrl              .= '?'.$this->sharedSecretVariableName.'='.urlencode($this->paypalSharedSecret).'&';
        $parameters['NOTIFYURL'] = $notifyUrl;
        $response                = $this->hash_call('DoExpressCheckoutPayment', $this->generateUrlParams($parameters));

        // Antwortwerte fuer Eintragung in Datenbank vorbereiten
        if (isset($response['ORDERTIME'])) {
            $orderTime = str_replace(
                array(
                    'T',
                    'Z'
                ),
                array(
                    ' ',
                    ''
                ),
                $response['ORDERTIME']
            );
            $response['ORDERTIME_CUSTOM'] = $orderTime;
        } else {
            $response['ORDERTIME_CUSTOM'] = '';
        }
        
        // Paypal-Bestellung anlegen
        $paypalOrder = new PaymentPaypalOrder();
        $paypalOrder->updateOrder(
            $orderObj->ID,
            $this->getPayerId(),
            $response
        );

        if (isset($response['PAYMENTSTATUS'])) {
            // Den Bestellstatus an die Rueckmeldung anpassen
            if (in_array($response['PAYMENTSTATUS'], $this->successPaypalStatus)) {
                $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $this->PaidOrderStatus));
            } else if (in_array($response['PAYMENTSTATUS'], $this->failedPaypalStatus)) {
                $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $this->CanceledOrderStatus));
            } else if (in_array($response['PAYMENTSTATUS'], $this->pendingPaypalStatus)) {
                $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $this->PendingOrderStatus));
            } else if (in_array($response['PAYMENTSTATUS'], $this->refundedPaypalStatus)) {
                $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $this->RefundedOrderStatus));
            } else {
                $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $this->CanceledOrderStatus));
            }
        } else {
            $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $this->CanceledOrderStatus));
        }

        $this->Log('doExpressCheckoutPayment: Got Response', var_export($response, true));
        $this->Log('doExpressCheckoutPayment: With Parameters', var_export($parameters, true));

        // Status zurueckgeben
        if (strtolower($response['ACK']) != 'success') {
            $this->errorOccured = true;
            $this->addError('Leider konnte uns Paypal keine positive Rückmeldung zu der Zahlungsfähigkeit Ihrer gewählten Bezahlart geben (Paypal Fehler 10417). Aus diesem Grund haben wir Ihre Bestellung storniert.');
            return false;
        } else {
            return true;
        }
    }

    /**
     * Stellt sicher, dass das Shared Secret korrekt uebergeben wurde.
     * 
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.11.2010
     */
    public function validateSharedSecret() {
        
        $secretIsValid = false;
        
        if (isset($_REQUEST[$this->sharedSecretVariableName])) {
            $ownSharedSecret  = mb_convert_encoding($this->paypalSharedSecret, 'UTF-8');
            $sentSharedSecret = mb_convert_encoding(urldecode($_REQUEST[$this->sharedSecretVariableName]), 'UTF-8');

            if (mb_strstr($ownSharedSecret, $sentSharedSecret) === $ownSharedSecret) {
                $secretIsValid = true;
            } else {
                $this->Log('validateSharedSecret', 'Gesendetes Secret: '.$sentSharedSecret.', Eigenes Secret: '.$ownSharedSecret);
            }
        }
        
        return $secretIsValid;
    }

    /**
     * Wird vom IPN-Script aufgerufen und kuemmert sich um die Bestaetigung der
     * gesendeten Anfrage und ggfs. die Anpassung des Status der Bestellung.
     * 
     * Paypal ruft das IPN-Script auf und sendet alle fuer die Zahlung
     * relevanten Daten. Um zu ueberpruefen, ob das IPN-Script tatsaechlich von
     * Paypal aufgerufen wurde, senden wir alle erhaltenen Parameter plus einen
     * Zusatzparameter an Paypal zurueck und erhalten als Antwort entweder
     * "VERIFIED" oder "INVALID".
     * Ist die Antwort "VERIFIED", pruefen wir, ob der Bestellstatus angepasst
     * werden muss.
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function isValidPaypalIPNCall() {
        $requestIsFromPaypal    = false;
        $req                    = 'cmd=_notify-validate';
        $header                 = '';

        // Alle gesendeten Variablen muessen korrekt zusammengefasst werden.
        foreach ($_REQUEST as $key => $value) {
            if (get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }

        // Zusammenfasste Variablen an Paypal zuruecksenden.
        $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

        if ($this->mode == 'Live') {
            $url = 'ssl://www.paypal.com';
        } else {
            $url = 'ssl://sandbox.paypal.com';
        }
        $fp = fsockopen($url, 443, $errno, $errstr, 30);

        if (!$fp) {
            // Socket konnte nicht geoeffnet werden, Abbruch.
            $this->Log('isValidPaypalIPNCall', 'Bestaetigung konnte nicht an Paypal zurueckgesendet werden. URL: '.$url.', Errno: '.$errno.', Errstr: '.$errstr);
            $requestIsFromPaypal = false;
        } else {
            // Socket ist offen, zusammengefasste Variablen senden und Antwort
            // entgegennehmen.
            fputs ($fp, $header.$req);
            
            while (!feof($fp)) {
                
                $res = fgets($fp, 1024);
                
                if (strcmp($res, "VERIFIED") == 0) {
                    // Erfolgreiche Bestaetigung von Paypal: Zahlung kann untersucht
                    // werden.
                    $requestIsFromPaypal = true;
                } else if (strcmp($res, "INVALID") == 0) {
                    // Die Zahlungsbestaetigung kam nicht von Paypal
                    $this->Log('isValidPaypalIPNCall', 'Zahlungsbestaetigung kam nicht von Paypal! Abbruch');
                    $this->Log('isValidPaypalIPNCall', 'Antwort von Paypal: '.var_export($res, true));
                    $requestIsFromPaypal = false;
                }
            }
            fclose ($fp);
        }
        
        return $requestIsFromPaypal;
    }
    
    /**
     * Holt sich die Paypal PayerID aus der URL. IPN-Benachrichtigungsvariable
     * unterscheidet sich von Checkout-Benachrichtung.
     * 
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function getPayerId() {
        
        $payerId = '';
        
        if (isset($_REQUEST['payer_id'])) {
            $payerId = $_REQUEST['payer_id'];
        } elseif (isset($_REQUEST['PayerID'])) {
            $payerId = $_REQUEST['PayerID'];
        } elseif (isset($_SESSION['paypal_module_payer_id'])) {
            $payerId = $_SESSION['paypal_module_payer_id'];
        }
        
        return $payerId;
    }

    /**
     * Nimmt die per IPN gesendeten Variablen und Werte entgegen und ueber-
     * traegt sie in ein assoziatives Array.
     * 
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function getIpnRequestVariables() {
        $variables  = array();
        $ipnKeysMap = array(
            'txn_id'                    => 'TRANSACTIONID',
            'txn_type'                  => 'TRANSACTIONTYPE',
            'payment_type'              => 'PAYMENTTYPE',
            'payment_status'            => 'PAYMENTSTATUS',
            'payment_date'              => 'ORDERTIME_CUSTOM',
            'pending_reason'            => 'PENDINGREASON',
            'reason_code'               => 'REASONCODE',
            'mc_currency'               => 'CURRENCYCODE',
            'mc_fee'                    => 'FEEAMT',
            'mc_gross'                  => 'AMT',
            'tax'                       => 'TAXAMT',
            'shipping'                  => 'SHIPPINGAMT',
            'address_city'              => 'SHIPTOCITY',
            'address_country'           => 'SHIPTOCOUNTRYNAME',
            'address_country_code'      => 'SHIPTOCOUNTRYCODE',
            'address_name'              => 'SHIPTONAME',
            'address_state'             => 'SHIPTOSTATE',
            'address_status'            => 'ADDRESSSTATUS',
            'address_street'            => 'SHIPTOADDRESS',
            'address_zip'               => 'SHIPTOZIP',
            'first_name'                => 'FIRSTNAME',
            'last_name'                 => 'LASTNAME',
            'payer_email'               => 'PAYEREMAIL',
            'payer_status'              => 'PAYERSTATUS',
            'verify_sign'               => 'VERIFYSIGN'
        );

        // Empfangene Werte in das richtige Charset konvertieren
        foreach ($ipnKeysMap as $ipnVariable => $checkoutVariable) {
            if (isset($_REQUEST[$ipnVariable])) {
                if ($encoding = mb_detect_encoding($_REQUEST[$ipnVariable])) {
                    if ($encoding != 'UTF-8') {
                        $variables[$checkoutVariable] = iconv($encoding, 'UTF-8', $_REQUEST[$ipnVariable]);
                    } else {
                        $variables[$checkoutVariable] = utf8_encode($_REQUEST[$ipnVariable]);
                    }
                }
            }
        }
        
        // Empfangene Werte aufbereiten
        $variables['ORDERTIME_CUSTOM'] = date('Y-m-d H:i:s', strtotime($variables['ORDERTIME_CUSTOM']));
        
        $this->Log('getIpnRequestVariables: Incoming Request Variables', var_export($_REQUEST, true));
        $this->Log('getIpnRequestVariables: Translated Request Variables', var_export($variables, true));
        
        return $variables;
    }

    /**
     * Liefert die im Feld "Custom" uebergebenen Key-Value-Paare als
     * assoziatives Array zurueck.
     * 
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function getIpnCustomVariables() {
        $variables = array();
        
        if (isset($_REQUEST['custom'])) {
            if (strpos($_REQUEST['custom'], ',') !== false) {
                $pairStr = explode(',', $_REQUEST['custom']);
            } else {
                $pairStr = $_REQUEST['custom'];
            } 
            
            $pairArr = explode('=', $pairStr);
            $variables[$pairArr[0]] = $pairArr[1];
        }
        
        return $variables;
    }

    /**
     * Aktualisiert die Lieferadresse der Bestellung
     * 
     * @param int   $ordersId     Die ID der Bestellung
     * @param array $ipnVariables Die per Request von Paypal gesendeten Variablen
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 24.11.2010
     */
    public function updateOrderDeliveryAddress($ordersId, $ipnVariables) {
        global $xanario_Database;
        
        $updateOrder = $xanario_Database->query('update :shop_orders set delivery_name=:delivery_name, delivery_street_address=:delivery_street_address, delivery_street_address_number=:delivery_street_addressnumber, delivery_city=:delivery_city, delivery_postcode=:delivery_postcode, delivery_state=:delivery_state, delivery_country=:delivery_country where orders_id=:orders_id');
        $updateOrder->bindTable(':shop_orders',                     TABLE_ORDERS);
        $updateOrder->bindInt(':orders_id',                         $ordersId);
        $updateOrder->bindValue(':delivery_name',                   $ipnVariables['SHIPTONAME']);
        $updateOrder->bindValue(':delivery_street_address',         $ipnVariables['SHIPTOADDRESS']);
        $updateOrder->bindValue(':delivery_street_addressnumber',   '');
        $updateOrder->bindValue(':delivery_city',                   $ipnVariables['SHIPTOCITY']);
        $updateOrder->bindValue(':delivery_postcode',               $ipnVariables['SHIPTOZIP']);
        $updateOrder->bindValue(':delivery_state',                  $ipnVariables['SHIPTOSTATE']);
        $updateOrder->bindValue(':delivery_country',                $ipnVariables['SHIPTOCOUNTRYNAME']);
        $updateOrder->execute();
        $this->log('updateOrderDeliveryAddress', $updateOrder->getQuery());
    }

    /**
     * Liefert das in der Session gespeicherte PaypalToken zurueck.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 25.11.2010
     */
    protected function getPaypalToken() {
        $token = '';

        if (isset($_SESSION['paypal_module_token'])) {
            $token = $_SESSION['paypal_module_token'];
        }

        return $token;
    }

    /**
     * Holt sich ueber einen API-Aufruf bei Paypal ein Token, das fuer die
     * restlichen Schritte als Identifikation verwendet wird.
     * Name der Paypal API Methode: SetExpressCheckout
     *
     * @return string|boolean false
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 17.11.2010
     */
    protected function fetchPaypalToken() {
        $token          = false;
        $deliveryData   = $this->data['customer']['deliveryAddress'];
        $customerData   = $this->data['customer']['details'];
        $parameters     = array(
            'AMT'               => $this->data['order']['amount_gross'],
            'RETURNURL'         => $this->addSessionToUrl($this->getReturnLink()),
            'CANCELURL'         => $this->addSessionToUrl($this->getCancelLink()),
            #'HDRIMG'            => MODULE_PAYMENT_PAYPAL_HDRIMG,
            'CUSTOM'            => '',

            'SHIPTONAME'        => iconv('iso-8859-1', 'utf-8', $deliveryData['FirstName'].' '.$deliveryData['SurName']),
            'SHIPTOSTREET'      => iconv('iso-8859-1', 'utf-8', $deliveryData['Street'].' '.$deliveryData['StreetNumber']),
            'SHIPTOCITY'        => iconv('iso-8859-1', 'utf-8', $deliveryData['City']),
            'SHIPTOZIP'         => iconv('iso-8859-1', 'utf-8', $deliveryData['PostCode']),
            'SHIPTOSTATE'       => iconv('iso-8859-1', 'utf-8', $deliveryData['State']),
            'SHIPTOCOUNTRYCODE' => iconv('iso-8859-1', 'utf-8', 'DE'),
            'SHIPTOCOUNTRYNAME' => iconv('iso-8859-1', 'utf-8', $deliveryData['Country']),
            'PHONENUM'          => iconv('iso-8859-1', 'utf-8', $customerData['Phone']),
            'CURRENCYCODE'      => 'EUR'
        );

        // Optionale Parameter definieren
        if ($this->mode == 'Live') {
            if (!empty($this->paypalBackLinkGiropaySucess_Live)) {
                $parameters['GIROPAYSUCCESSURL'] = $this->paypalBackLinkGiropaySucess_Live;
            }
            if (!empty($this->paypalBackLinkGiropayCancel_Live)) {
                $parameters['GIROPAYCANCELURL'] = $this->paypalBackLinkGiropayCancel_Live;
            }
            if (!empty($this->paypalBackLinkBanktransfer_Live)) {
                $parameters['BANKTXNPENDINGURL'] = $this->paypalBackLinkBanktransfer_Live;
            }
        } else {
            if (!empty($this->paypalBackLinkGiropaySucess_Dev)) {
                $parameters['GIROPAYSUCCESSURL'] = $this->paypalBackLinkGiropaySucess_Dev;
            }
            if (!empty($this->paypalBackLinkGiropayCancel_Dev)) {
                $parameters['GIROPAYCANCELURL'] = $this->paypalBackLinkGiropayCancel_Dev;
            }
            if (!empty($this->paypalBackLinkBanktransfer_Dev)) {
                $parameters['BANKTXNPENDINGURL'] = $this->paypalBackLinkBanktransfer_Dev;
            }
        }
        
        $apiCallResult = $this->hash_call('SetExpressCheckout', $this->generateUrlParams($parameters));
    
        // Es ist ein Fehler aufgetreten
        if (strtolower($apiCallResult['ACK']) != 'success') {
            $this->log('fetchPaypalToken', var_export($apiCallResult, true));
            $this->log('fetchPaypalToken', var_export($parameters, true));
            $this->errorOccured = true;
            $this->addError('Die Kommunikation mit Paypal konnte nicht initialisiert werden.');
        }
        
        $this->log('fetchPaypalToken: Got Response', var_export($apiCallResult, true));
        $this->log('fetchPaypalToken: With Parameters', var_export($parameters, true));

        return $apiCallResult['TOKEN'];
    }
    
    /**
     * Fuehrt einen Methodenaufruf ueber die NVP-API von Paypal durch.
     *
     * @param string $methodName Name der Methode, die aufgerufen werden soll.
     * @param string $nvpStr     Der originale String, der an den NVP-Server gesendet werden soll
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 17.11.2010
     */
    protected function hash_call($methodName, $nvpStr) {
        //setting the curl parameters.
        $ch = curl_init();
        
        if ($this->mode == 'Live') {
            curl_setopt($ch, CURLOPT_URL, $this->paypalNvpApiServerUrl_Live);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->paypalNvpApiServerUrl_Dev);
        }
        
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);

        //NVPRequest for submitting to server
        if ($this->mode == 'Live') {
            $nvpreq = "METHOD=".urlencode($methodName).
                      "&VERSION=".urlencode($this->paypalApiVersion_Live).
                      "&PWD=".urlencode($this->paypalApiPassword_Live).
                      "&USER=".urlencode($this->paypalApiUsername_Live).
                      "&SIGNATURE=".urlencode($this->paypalApiSignature_Live).
                      $nvpStr;
        } else {
            $nvpreq = "METHOD=".urlencode($methodName).
                      "&VERSION=".urlencode($this->paypalApiVersion_Dev).
                      "&PWD=".urlencode($this->paypalApiPassword_Dev).
                      "&USER=".urlencode($this->paypalApiUsername_Dev).
                      "&SIGNATURE=".urlencode($this->paypalApiSignature_Dev).
                      $nvpStr;
        }

        //setting the nvpreq as POST FIELD to curl
        curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

        //getting response from server
        $response = curl_exec($ch);

        //convrting NVPResponse to an Associative Array
        $nvpResArray = $this->deformatNVP($response);
        $nvpReqArray = $this->deformatNVP($nvpreq);
        $_SESSION['nvpReqArray'] = $nvpReqArray;

        if (curl_errno($ch)) {
            // moving to display page to display curl errors
            $_SESSION['curl_error_no']  = curl_errno($ch) ;
            $_SESSION['curl_error_msg'] = curl_error($ch);
            $this->log('hash_call', 'curl_errno: '.curl_errno($ch).', curl_error_msg: '.curl_error($ch));
            print "FEHLER!<br />";
            exit();
        } else {
            //closing the curl
            curl_close($ch);
        }

        return $nvpResArray;
    }
    
    /** 
     * This method will take a NVPString and convert it to an Associative Array and it will decode the response.
     * It is usefull to search for a particular key and displaying arrays.
     *
     * @param string $nvpstr NVPString
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 17.11.2010
     */
    protected function deformatNVP($nvpstr) {
        $intial     = 0;
        $nvpArray   = array();

        while (strlen($nvpstr)) {
            //postion of Key
            $keypos= strpos($nvpstr,'=');
            //position of value
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval=substr($nvpstr,$intial,$keypos);
            $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] =urldecode( $valval);
            $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
        }
        return $nvpArray;
    }
    
    /**
     * Erzeugt aus einem assoziativen Array einen String im Format
     * "key=value&key=value&..." und gibt diesen zurueck.
     *
     * @param array $parameters Ein assoziatives Array
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 17.11.2010
     */
    protected function generateUrlParams($parameters) {

        $paramString = '';

        foreach ($parameters as $key => $value) {
            $paramString .= '&'.urlencode($key).'='.urlencode($value);
        }

        return $paramString;
    }
    
    /**
     * Haengt die Sessionkennung und ID an eine URL an.
     * 
     * @param string $url Die URL
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 17.11.2010
     */
    protected function addSessionToUrl($url) {
        
        if (strpos($url, '?') === false) {
            $url .= '?';
        }
        
        $url .= session_name().'='.session_id().'&';
        
        return $url;
    }

    /**
     * Speichert das Paypal-Token in der Session.
     *
     * @param string $token Das Paypal-Token
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 18.11.2010
     */
    protected function saveToken($token) {
        $_SESSION['paypal_module_token'] = $token;
    }

    /**
     * Speichert die PayerId in der Session.
     *
     * @param string $payerId Die PayerId
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 19.11.2010
     */
    protected function savePayerid($payerId) {
        $_SESSION['paypal_module_payer_id'] = $payerId;
    }
}
