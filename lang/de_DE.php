<?php
/**
 * German (Germany) language pack
 * @package modules: silvercart
 */
i18n::include_locale_file('silvercart', 'en_US');

if (array_key_exists('de_DE', $lang) && is_array($lang['de_DE'])) {
    $lang['de_DE'] = array_merge($lang['en_US'], $lang['de_DE']);
} else {
    $lang['de_DE'] = $lang['en_US'];
}

$lang['de_DE']['SilvercartPaymentPrepayment']['NAME']       = 'Vorkasse';
$lang['de_DE']['SilvercartPaymentPrepayment']['TITLE']      = 'Vorkasse';

