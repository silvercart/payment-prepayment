<?php
/**
 * Copyright 2012 pixeltricks GmbH
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
 * Russian language pack
 *
 * @package SilvercartPaymentPrepayment
 * @subpackage i18n
 * @ignore
 */

global $lang;

i18n::include_locale_file('silvercart_payment_prepayment', 'en_US');

if (array_key_exists('ru_RU', $lang) && is_array($lang['ru_RU'])) {
    $lang['ru_RU'] = array_merge($lang['en_US'], $lang['ru_RU']);
} else {
    $lang['ru_RU'] = $lang['en_US'];
}

$lang['ru_RU']['SilvercartPaymentPrepayment']['BANK_ACCOUNT_INFO'] = 'Банковские реквизиты';
$lang['ru_RU']['SilvercartPaymentPrepayment']['INVOICE_INFO'] = 'Информация по оплате после получения заказа';
$lang['ru_RU']['SilvercartPaymentPrepayment']['NAME'] = 'Предоплата';
$lang['ru_RU']['SilvercartPaymentPrepayment']['PAYMENT_INFO'] = 'информация по оплате Вашего заказа';
$lang['ru_RU']['SilvercartPaymentPrepayment']['TEXT_TEMPLATES'] = 'Текстовые макеты';
$lang['ru_RU']['SilvercartPaymentPrepayment']['TITLE'] = 'Предоплата';
$lang['ru_RU']['SilvercartPaymentPrepayment']['PAYMENT_CHANNEL'] = 'Способ оплаты';
$lang['ru_RU']['SilvercartPaymentPrepayment']['PAYMENT_CHANNEL_INVOICE'] = 'Счет – фактура';
$lang['ru_RU']['SilvercartPaymentPrepayment']['PAYMENT_CHANNEL_PREPAYMENT'] = 'Предоплата';
