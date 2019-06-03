@extends('email.layout.base')

@section('content')
<tr>
    <td style="font-family: arial,helvetica,sans-serif; line-height: 20px; font-size: 20px; color: #737373;"><img src="{{$subscription->getWelcomeEmailHeaderImg()}}" alt="{{$subscription->getName()}} Subscription Box" border="0" width="600" height="242"></td>
</tr>
<tr>
   <td height="20"></td>
</tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px;">
      <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
         <strong>Aloha from the North Pole,</strong>
      </p>
      <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
        Thank you for gifting
        @if ($paymentPlan->getPeriod()/$subscription->getPeriod() == 1)
            1 {{$subscription->getName()}} Pleybox
        @else
            {{$paymentPlan->getPeriod()/$subscription->getPeriod()}} {{$subscription->getName()}} Pleyboxes
            over {{$paymentPlan->getPeriod()}} months 
        @endif
        to {{ucfirst($gift->getToFirstName())}} in the amount of ${{$giftPrice->getPriceTotal()}}.
      </p>
   </td>
</tr>
<tr><td height="20"></td></tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
      <p>
          {{ucfirst($gift->getToFirstName())}} will receive an email on your chosen date, 
          announcing your gift with a lovely message:  
      </p>
   </td>
</tr>
<tr><td height="10"></td></tr>
<tr>
   <td align="center">
      <table cellpadding="0" cellspacing="0" border="0" width="560" class="responsive-table">
         <tr>
            <td align="left" bgcolor="#f6f6f6" style="border: 1px solid #E6E6E6; padding-left: 20px;padding-right: 20px; padding-top: 20px; padding-bottom: 20px; line-height: 18px; font-size: 13px; color: #737373; font-style: italic;">
               {{
                    nl2br(
                        htmlentities($gift->getMessage(), ENT_QUOTES, 'UTF-8', false)
                    )
               }}
            </td>
         </tr>
      </table>
   </td>
</tr>
<tr><td height="20"></td></tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
      <p>
        Once {{ucfirst($gift->getToFirstName())}} redeems your gift, our Elves will get to work, 
        they'll prepare the first Pleybox and will send them a tracking number once it shipped. 
      </p>
      <p>
        If you have any questions about your gift please visit our <a href="https://pley.desk.com/" target="_blank" style="color:#0080FF;">support</a>.
      </p>
      <p>Your loved one will thank you for a long time...</p>
      <p>
          Have others in mind that can enjoy toys? Gift them the
          <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/best-gift-for-kids" target="_blank" style="color:#0080FF;">gift</a>
          that keeps giving.
      </p>
      <p>
         Thanks a bunch and keep Pleying!<br/>
      </p>
      <p>
         <strong>The Pley Team</strong><br/>
      </p>
      <br>
   </td>
</tr>
@stop
