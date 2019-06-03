@extends('email.layout.base')

@section('content')
    <tr>
        <td style="font-family: arial,helvetica,sans-serif; line-height: 20px; font-size: 20px; color: #737373;"><img
                    src="{{$subscription->getWelcomeEmailHeaderImg()}}"
                    alt="{{$subscription->getName()}} Subscription Box" border="0" width="600" height="242"></td>
    </tr>
    <tr>
        <td height="20"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
                <strong>Hi, {{$user->getFirstName()}}</strong>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                You are now on the Waitlist for a <strong>{{$subscription->getName()}}</strong> Pleybox
                subscription!<br/>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                We’ll move you to a subscription as soon as possible, and you’ll receive a confirmation email once your
                subscription becomes active. We’ll bill you after we’ve moved you to a subscription. <br/>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                So, what can you do in the meantime? Lots!<br/>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                1. Like us on <a href="https://www.facebook.com/pages/Pley/377120975714927" target="_blank"
                                 style="color:#0080FF;">Facebook</a> and follow us
                on <a href="https://twitter.com/MyPley" target="_blank" style="color:#0080FF;">Twitter</a> and <a
                        href="https://www.instagram.com/mypley" target="_blank" style="color:#0080FF;">Instagram</a>
                to stay up to date.<br/>
                2. Explore our <a href="https://pley.com/" target="_blank" style="color:#0080FF;">other
                    subscriptions</a>.<br/>
                3. Tell your friends about Pley.<br/>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 12px; line-height: 20px;">
                If you need to remove yourself from the Waitlist prior to becoming a subscriber, please login to your account and click on the Unsubscribe link in the Payment Info section.
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 18px; line-height: 20px;">
                Thank you for being a Pleyer!<br/>
            </p>
            <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 22px; line-height: 22px;">
                <strong>The Pley Team</strong><br/>
            </p>
        </td>
    </tr>
@stop
