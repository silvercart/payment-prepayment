<?php
/**
 * Verarbeitet Rueckmeldungen vom Zahlungsanbieter.
 *
 * @return void 
 *
 * @package fashionbids
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2010 pixeltricks GmbH
 * @since 23.11.2010
 * @license none
 */
class PaymentPaypalNotification extends DataObject {
    
    /**
     * Enthaelt den Modulname.
     *
     * @var string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 26.11.2010
     */
    protected $moduleName = 'Paypal';

    /**
     * Diese Methode wird vom Verteilerscript aufgerufen und nimmt die Status-
     * meldungen von Paypal entgegen.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 26.11.2010
     */
    public function process() {

        // Zahlungsmodul laden
        $paypalModule = DataObject::get_one(
            'Payment',
            sprintf(
                "`Name` = '%s'",
                $this->moduleName
            )
        );

        if ($paypalModule) {
            // Sicherheitsstufe 1:
            // ----------------------------------------------------------------------------
            // Ueberpruefen, ob das Shared Secret korrekt uebergeben wurde.
            if ($paypalModule->validateSharedSecret() === false) {
                $paypalModule->Log('PaymentPaypalNotification', 'Falsches Shared Secret gesendet! Abbruch.');
                $paypalModule->Log('PaymentPaypalNotification', var_export($_REQUEST, true));
                exit();
            }

            // Sicherheitsstufe 2:
            // ----------------------------------------------------------------------------
            // Bestaetigung an Paypal schicken und Antwort entgegennehmen. So wird sicher-
            // gestellt, dass die Nachricht tatsaechlich von Paypal kam.
            if ($paypalModule->isValidPaypalIPNCall()) {
                $payerId            = $paypalModule->getPayerId();
                $ipnVariables       = $paypalModule->getIpnRequestVariables();
                $customVariables    = $paypalModule->getIpnCustomVariables();

                $paypalModule->Log('PaymentPaypalNotification', 'Postback-Pruefung: Zahlungsbestaetigung kam von Paypal.');

                // Wenn die Bestellung bezahlt ist, dann den Status in der Stamm-
                // bestelltabelle umstellen auf "Bezahlt".
                // Ausserdem wird die Lieferadresse angepasst, wenn die entsprechenden
                // Daten geliefert wurden
                $orderObj = DataObject::get_by_id(
                    'Order',
                    $customVariables['order_id']
                );

                if (in_array($ipnVariables['PAYMENTSTATUS'], $paypalModule->successPaypalStatus)) {
                    $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $paypalModule->PaidOrderStatus));
                }
                if (in_array($ipnVariables['PAYMENTSTATUS'], $paypalModule->failedPaypalStatus)) {
                    $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $paypalModule->CanceledOrderStatus));
                }
                if (in_array($ipnVariables['PAYMENTSTATUS'], $paypalModule->refundedPaypalStatus)) {
                    $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $paypalModule->RefundedOrderStatus));
                }
                if (in_array($ipnVariables['PAYMENTSTATUS'], $paypalModule->pendingPaypalStatus)) {
                    $orderObj->setOrderStatus(DataObject::get_by_id('OrderStatus', $paypalModule->PendingOrderStatus));
                }                                                                                                                                                                                                                                                                                                       

                // Bestellmodul der Zahlungsart laden
                $paymentPaypalOrder = DataObject::get_one(
                    'PaymentPaypalOrder',
                    sprintf(
                        "\"orderId\" = '%d'",
                        $customVariables['order_id']
                    )
                );

                if ($paymentPaypalOrder) {
                    // Von Paypal gelieferte Daten speichern
                    $paymentPaypalOrder->updateOrder(
                        $customVariables['order_id'],
                        $payerId,
                        $ipnVariables
                    );
                } else {
                    $paypalModule->Log('PaymentPaypalNotification', 'Das PaymentPaypalOrder Objekt konnte nicht geladen werden für die orderId '.$customVariables['order_id']);
                }
            } else {
                $paypalModule->Log('PaymentPaypalNotification', 'Kein valider IPN-Call; Requestvariablen: '.var_export($_REQUEST, true));
            }
        }
    }
}
