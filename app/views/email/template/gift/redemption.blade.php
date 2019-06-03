@extends('email.layout.base')

@section('content')
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="text-align: center; color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
                <strong>{{ucfirst($gift->getToFirstName())}} {{ucfirst($gift->getToLastname())}} has successfully
                    activated your gift.</strong><br/>
                <strong>Thank you!</strong>
            </p>
        </td>
    </tr>
    <tr>
        <td height="20"></td>
    </tr>
    @if ($gift->getSubscriptionId() == 1)
    <tr>
        <td style="font-family: arial,helvetica,sans-serif; line-height: 20px; font-size: 20px; color: #737373;"><img
                    src="https://dnqe9n02rny0n.cloudfront.net/pleybox/email-assets/gift/ab-ugc-collage-with-border.jpg"
                    alt="Happy Gifted Children" border="0" width="600" height="200"></td>
    </tr>
    @endif
    <tr>
        <td height="20"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
                Hi {{ucfirst($gift->getFromFirstName())}}, <br/> <br/>
                You will be delighted to know,
                that {{ucfirst($gift->getToFirstName())}} {{ucfirst($gift->getToLastName())}} has
                <strong>activated</strong> the Pley
                subscription.
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px; padding-left: 20px; padding-right: 20px;">
            <p>
                Pley is an awesome gift for family members and special occasions (birthdays, holidays). Give it to
                another loved one who can enjoy hours of creativity and fun.
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr style="color: #515151; font-family: arial,helvetica,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0; padding: 0;">
        <td style="color: #515151; text-align: center; font-family: arial,helvetica,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;"
            valign="top">
            <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/best-gift-for-kids"
               target="_blank"
               style="font-family: arial,helvetica,sans-serif; box-sizing: border-box; font-size: 14px; color: #FFF; text-decoration: none; line-height: 2; font-weight: bold; text-align: center; cursor: pointer; display: inline-block; border-radius: 5px; text-transform: capitalize; background-color: #CF246A; margin: 0; padding: 0; border-color: #CF246A; border-style: solid; border-width: 10px 20px;">Give
                Another Gift</a>
        </td>
    </tr>
    <tr>
        <td style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px; padding-left: 20px; padding-right: 20px;">
            <p>
                If you have any questions about your gift please visit our <a href="https://pley.desk.com/"
                                                                              target="_blank" style="color:#0080FF;">support</a>.
            </p>
            <p>
                <strong>The Pley Team</strong><br/>
            </p>
            <br>
        </td>
    </tr>
@stop
