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
                <strong>Hi {{$user->getFirstName()}},</strong>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                We just wanted to send you a quick reminder to update your credit card information so we can send you
                your PleyBox. <br/>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                We recently attempted to move you forward from the PleyBox waitlist to receive your first shipment, but
                we ran into trouble when attempting to process your credit card. <br/>
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr style="padding: 0; text-align: center; vertical-align: top; display: block">
        <td style="display: inline-block; -moz-hyphens: auto; -webkit-hyphens: auto; background: #ee3d85; border: none; border-collapse: collapse !important; border-radius: 10px; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 1.5; margin: 0; padding: 8px 16px 8px 16px; text-align: center; vertical-align: top; word-wrap: break-word;">
            <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/my-account?updatecc=true"
               style="color: rgb(254, 254, 254); font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0px; padding: 10px 20px; text-align: center; text-decoration: none; border: 0px solid rgb(238, 61, 133); border-radius: 3px; display: inline-block;">
                Update your billing information
            </a>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 12px; line-height: 20px;">
                If you have any questions please visit our <a href="https://pley.desk.com/"
                                                                              target="_blank" style="color:#0080FF;">support</a>.
            </p>
            <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 18px; line-height: 18px;">
                <strong>The Pley Team</strong><br/>
            </p>
        </td>
    </tr>
@stop
