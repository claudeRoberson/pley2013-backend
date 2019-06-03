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
         <strong>Welcome to Pley!</strong>
      </p>
   </td>
</tr>
<tr>
   <td style="padding-top:20px; padding-bottom:20px; text-align: center;">
      <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/my-account?utm_source=PleyMail&utm_medium=email&utm_campaign=header%20banner" target="_blank"><img src="https://dnqe9n02rny0n.cloudfront.net/pleybox/email-assets/disney-princess/btnMyAccountSmall.jpg" alt="My Account" border="0" width="193" height="56"></a>
   </td>
</tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
      <p>
      @if ($isGift)
        @if ($paymentPlan->getPeriod()/$subscription->getPeriod() == 1)
            You received a gift of 1 {{$subscription->getName()}} Pleybox
        @else
            You received a gift of {{$paymentPlan->getPeriod()/$subscription->getPeriod()}} {{$subscription->getName()}} Pleyboxes
            delivered over {{$paymentPlan->getPeriod()}} months.
        @endif
        
      @else
        @if ($paymentPlan->getPeriod()/$subscription->getPeriod() == 1)
            You subscribed to receive 1 {{$subscription->getName()}} Pleybox
            @if ($subscription->getPeriod() == 1)
                every month
            @elseif ($subscription->getPeriod() == 2)
                every other month
            @else
                every {{$subscription->getPeriod()}} months
            @endif
        @else
            You subscribed to receive {{$paymentPlan->getPeriod()/$subscription->getPeriod()}} {{$subscription->getName()}} Pleyboxes
            delivered over {{$paymentPlan->getPeriod()}} months
            @if ($subscription->getPeriod() == 1)
                (one box a month)
            @elseif ($subscription->getPeriod() == 2)
                (one box every other month)
            @else
                (one box every {{$subscription->getPeriod()}} months)
            @endif
        @endif
        
        in the amount of {{$formattedTotal}}.
        
      @endif
      </p>
       @if ($couponDescription != false)
           <p>
               You've used a coupon : {{$couponDescription}}
           </p>
       @endif
      <p>
         Our Elves will send a tracking number when shipping your PleyBox. 
         If you have any questions about your account please visit our <a href="https://pley.desk.com/?b_id=11311" target="_blank" style="color:#0080FF;">support</a>.
      </p>
      <p>
         Thanks again for joining Pley! Post unboxing videos, photos, and connect with other pleyers on 
         <a href="https://www.facebook.com/mypleyer/" target="_blank" style="color:#0080FF;">Facebook</a> and <a href="https://www.instagram.com/mypley/" target="_blank" style="color:#0080FF;">Instagram</a>.
      </p>
      @if (!$isGift)
      <p>Your next payment is scheduled for {{date('F d, Y', $newSubsResult->firstRecurringPaymentDate - 86400)}}.</p>
      @endif
      <p>Keep Pleying!</p>
      <p>
         <strong>The Pley Team</strong><br/>
      </p>
      <br>
   </td>
</tr>
@if (!$isGift && ($paymentPlan->getPeriod()/$subscription->getPeriod()) == 1)
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 12px; line-height: 20px;">
      <p>
         Your subscription will renew automatically at the end of each period in the amount of {{$formattedTotal}}.
         Your renewal will occur on the {{date('j', $sequenceItem->getChargeTime()) - 1}}th of the month in which your PleyBox ships.
      </p>
   </td>
</tr>
@endif
@stop
