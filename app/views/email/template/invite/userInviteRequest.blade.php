@extends('email.layout.base')

@section('content')
    <tr>
        <td style="font-family: arial,helvetica,sans-serif; line-height: 20px; font-size: 20px; color: #737373;"><img
                    align="center" class="float-center small-float-center"
                    src="https://dnqe9n02rny0n.cloudfront.net/pleybox/email-assets/refer-friend/pley-invite-email-header.png"
                    style="border: 0px; clear: both; display: block; margin: 0px auto; outline: none; width: 640px; height: 157px;"/>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="font-size: 14.6667px; font-family: Helvetica, Arial, sans-serif; vertical-align: baseline; font-weight: normal; color: #515151">
                Pleyboxes are full of surprises and provide hours of entertainment and joy for my kids!
            </p>
            <p style="font-size: 14.6667px; font-family: Helvetica, Arial, sans-serif; vertical-align: baseline; font-weight: normal; color: #515151">
                Try it and get <strong>${{$discountAmount}}</strong> off your first month!
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr style="padding: 0; text-align: center; vertical-align: top; display: block">
        <td style="display: inline-block; -moz-hyphens: auto; -webkit-hyphens: auto; background: #ee3d85; border: none; border-collapse: collapse !important; border-radius: 10px; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 1.5; margin: 0; padding: 8px 16px 8px 16px; text-align: center; vertical-align: top; word-wrap: break-word;">
            <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/?referralToken={{$token->getToken()}}"
               style="color: rgb(254, 254, 254); font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0px; padding: 10px 20px; text-align: center; text-decoration: none; border: 0px solid rgb(238, 61, 133); border-radius: 3px; display: inline-block;">
                Sign Up Now
            </a>
        </td>
    </tr>

@stop