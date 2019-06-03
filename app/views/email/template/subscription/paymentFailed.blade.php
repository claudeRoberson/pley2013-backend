@extends('email.layout.base')

@section('content')
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="width:100.0%;height:20px; padding: 20px"><span style="font-size:16px;"><span
                        style="font-family:arial,helvetica,sans-serif;">Dear {{ucfirst($user->getFirstName())}},</span></span></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
                We failed to charge your credit card!
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
                Amount Due: <span style="color: #da2672; font-size: 25px">{{$amountDue}} USD</span>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
                We'll try again in a few days.
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
        <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
            <p>
                If you need further assistance, please contact us at
                <a href="https://pley.desk.com/?utm_source=PleyMail&amp;utm_medium=email&amp;utm_campaign=password%20change"
                   style="color:#49B2B8; text-decoration: none;" target="_blank">
                    https://pley.desk.com/
                </a>
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
            <h3>The Pley Team</h3>
        </td>
    </tr>
@stop