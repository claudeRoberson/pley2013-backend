@extends('email.layout.base')

@section('content')
<tr>
    <td style="padding-left: 20px; padding-right: 20px;">
        <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
            <strong>Aloha to Pley!</strong>
        </p>
    </td>
</tr>
<tr>
    <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
        Thank you for spreading the word about Pley on Facebook!
        <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
            Here’s your
            @if ($coupon->getType() == \Pley\Enum\CouponTypeEnum::FIXED)
            $ {{$coupon->getDiscountAmount()}}
            @else
            {{$coupon->getDiscountAmount()}}%
            @endif
            off 1st Pleybox coupon: <strong style="color:#66c8cd">{{$coupon->getCode()}}</strong>.
        </p>
        <p>
            Click 
            <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}?emailPromoCode={{$coupon->getCode()}}">here</a>
            to redeem your coupon and have fun and surprises delivered directly to your door.
        </p>
    </td>
</tr>
<tr>
    <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
        <p style="color:red; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
            <strong>Hurry, offer expires in 2 days!</strong>
        </p>
    </td>
</tr>
<tr>
    <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
        <p>
            <strong>Let’s Pley!</strong><br/>
        </p>
        <br>
    </td>
</tr>
@stop


