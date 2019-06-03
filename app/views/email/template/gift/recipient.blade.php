@extends('email.layout.base')

@section('content')
<tr>
   <td style="font-family: arial,helvetica,sans-serif; line-height: 20px; font-size: 20px; color: #737373;"><img src="https://dnqe9n02rny0n.cloudfront.net/pleybox/email-assets/gift/pley-gift-email-header.png"  border="0" width="600" height="194"></td>
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
        {{ucfirst($gift->getFromFirstName())}} just sent you an amazing
        @if ($paymentPlan->getPeriod()/$subscription->getPeriod() == 1)
            {{$subscription->getName()}} Pleybox.
        @else
            {{$paymentPlan->getPeriod()/$subscription->getPeriod()}} {{$subscription->getName()}} Pleyboxes
            which you'll get over {{$paymentPlan->getPeriod()}} months.
        @endif
        So awesome!
      </p>
   </td>
</tr>
<tr><td height="20"></td></tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
      <p>
          {{ucfirst($gift->getFromFirstName())}} wrote you:  
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
          Redeem your gift by clicking on this 
          <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/redeem/{{strtoupper($gift->getToken())}}" target="_blank" style="color:#0080FF;">link</a>
          or paste the code below into this
          <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/redeem" target="_blank" style="color:#0080FF;">page</a>.
      </p>
      <p>Your code: <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/redeem/{{strtoupper($gift->getToken())}}" target="_blank" style="color:#0080FF;">{{strtoupper($gift->getToken())}}</a></p>
   </td>
</tr>
<tr><td height="20"></td></tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
      <p>
        Once you redeem your gift and create an account, our Elves will get to work. 
        They'll prepare your first Pleybox and will send you a tracking number once it shipped. 
      </p>
      <p>If you have any questions about your gift please visit our <a href="https://pley.desk.com/" target="_blank" style="color:#0080FF;">support</a>.</p>
      <p>Your loved one will thank you for a long time...</p>
      <p>Once you get your Pleybox, post unboxing videos to YouTube and photos on Instagram and Pinterest with hashtag #Pley to win prizes.</p>
      <p>
         Welcome to Pley and enjoy your new gift!<br/>
         Team Pley
      </p>
      <br>
   </td>
</tr>
@stop
