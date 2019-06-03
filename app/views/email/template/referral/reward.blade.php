@extends('email.layout.base')

@section('content')
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
                @if($isRegistered)
                    <strong>Dear {{$user->getFirstName()}},</strong>
                @else
                    <strong>Hi!</strong>
                @endif

            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                You just earned a @if($user->getVPaymentSystemId() == 2) refund @else credit @endif of <strong>${{$acquisition->getRewardAmount()}}</strong> for inviting friends to Pley.<br/>
            </p>
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                Earn more credits by inviting more friends!
            </p>
            @if (!$isRegistered)
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
              Your total reward pending: <strong>{{$totalRewardAmount}}$</strong><br/>
            </p>
            @endif
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    @if (!$isRegistered)
    <tr style="padding: 0; text-align: center; vertical-align: top; display: block">
        <td style="display: inline-block; -moz-hyphens: auto; -webkit-hyphens: auto; background: #ee3d85; border: none; border-collapse: collapse !important; border-radius: 10px; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 1.5; margin: 0; padding: 8px 16px 8px 16px; text-align: center; vertical-align: top; word-wrap: break-word;">
            <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}"
               style="color: rgb(254, 254, 254); font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0px; padding: 10px 20px; text-align: center; text-decoration: none; border: 0px solid rgb(238, 61, 133); border-radius: 3px; display: inline-block;">
                Subscribe Now!
            </a>
        </td>
    </tr>
    @else
        <tr style="padding: 0; text-align: center; vertical-align: top; display: block">
            <td style="display: inline-block; -moz-hyphens: auto; -webkit-hyphens: auto; background: #ee3d85; border: none; border-collapse: collapse !important; border-radius: 10px; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 1.5; margin: 0; padding: 8px 16px 8px 16px; text-align: center; vertical-align: top; word-wrap: break-word;">
                <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/my-account"
                   style="color: rgb(254, 254, 254); font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0px; padding: 10px 20px; text-align: center; text-decoration: none; border: 0px solid rgb(238, 61, 133); border-radius: 3px; display: inline-block;">
                    Invite Friends!
                </a>
            </td>
        </tr>
    @endif
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
                Your friends and your wallet will thank you!
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px;">
            <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 12px; line-height: 12px;">
                If you have any questions about your credit, contact our support team <a href="https://pley.desk.com/customer/portal/emails/new?b_id=11311">here</a>
            </p>
            <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 18px; line-height: 18px;">
                <strong>The Pley Team</strong><br/>
            </p>
        </td>
    </tr>
@stop
