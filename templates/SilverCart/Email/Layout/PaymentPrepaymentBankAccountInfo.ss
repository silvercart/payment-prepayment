<% with $Order %>
<br /><br />
<p><%t SilverCart\Model\ShopEmail.ThankYouForYourOrder 'Thank you for your order at our shop.' %></p>
<table>
    <colgroup>
      <col width="25%"></col>
      <col width="75%"></col>
   </colgroup>
    <tr>
        <td><strong><%t SilverCart\Model\Pages\Page.ORDER_DATE 'Order date' %>:</strong></td>
        <td>{$Created.Nice}</td>
    </tr>
    <tr>
        <td><strong><%t SilverCart\Model\Order\NumberRange.ORDERNUMBER 'Ordernumber' %>:</strong></td>
        <td>{$OrderNumber}</td>
    </tr>
</table>
    <% if $PaymentMethod.TextBankAccountInfo %>
        <p>{$PaymentMethod.TextBankAccountInfo}</p>
    <% end_if %>
    <% if $PaymentMethod.BankAccounts.count() > 1 %>
        <p><%t SilverCart\Model\ShopEmail.PleaseTransferAmountPlural 'Please transfer the total amount of <strong>{amountTotal}</strong> to one of the following bank accounts:' amountTotal=$AmountTotal.Nice %></p>
    <% else_if $PaymentMethod.BankAccounts.exists() %>
        <p><%t SilverCart\Model\ShopEmail.PleaseTransferAmount 'Please transfer the total amount of <strong>{amountTotal}</strong> to the following bank account:' amountTotal=$AmountTotal.Nice %></p>
    <% end_if %>
    
<table>
    <tr>
        <td><strong><%t SilverCart\Prepayment\Model\Prepayment.BankAccountOwner 'Account Holder' %></strong></td>
        <td><strong><%t SilverCart\Prepayment\Model\Prepayment.BankAccountName 'Bank' %></strong></td>
        <td><strong><%t SilverCart\Prepayment\Model\Prepayment.BankAccountIBAN 'IBAN' %></strong></td>
        <td><strong><%t SilverCart\Prepayment\Model\Prepayment.BankAccountBIC 'BIC / SWIFT' %></strong></td>
    </tr>
<% if $PaymentMethod.BankAccounts.exists() %>
    <% loop $PaymentMethod.BankAccounts %>
    <tr>
        <td>{$Owner}</td>
        <td>{$Name}</td>
        <td>{$IBAN}</td>
        <td>{$BIC}</td>
    </tr>
    <% end_loop %>
<% end_if %>
</table>
<% end_with %>

<p><%t SilverCart\Model\ShopEmail.REGARDS 'Best regards' %>,</p>
<p><%t SilverCart\Model\ShopEmail.YOUR_TEAM 'Your SilverCart ecommerce team' %></p>