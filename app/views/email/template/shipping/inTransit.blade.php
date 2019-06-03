@extends('email.layout.base')

@section('content')

    <tr>
        <td style="padding-left: 20px; padding-right: 20px; padding-top: 10px">
            <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
                <strong>Hey {{ucfirst($userProfile->getFirstName())}},</strong>
            </p>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>
    <tr>
        <td style="width:100.0%;font-size:16px; padding-left: 20px; padding-right: 20px;font-family:arial,helvetica,sans-serif;">
            <br/>
            @if ($subscription->getId() == 1)
                We have exciting news for you! Our Elves shipped your {{$subscription->getName()}} mystery box and it’s
                on its way.<br/>
            @elseif ($subscription->getId() == 2)
                We have exciting news for you! Kia and Kyle have shipped your {{$subscription->getName()}} exploration
                box and it’s
                on its way.<br/>
            @else
                We have exciting news for you! Your {{$subscription->getName()}} Pleybox has been shipped and it’s on its way.
                <br/>
            @endif
            <br/>
            See when the fun is arriving <a href="{{$trackingUrl}}">here</a>.<br/>
            <br/>
            @if (isset($specialNote))
                <p style="color: lightgray; font-family: arial,helvetica,sans-serif; font-size: 8px; line-height: 8px;">
                    {{$specialNote}}
                </p>
                <br/>
            @endif
            <hr/>
            <strong>Once it arrives...</strong><br/>
            <br/>
            Take a picture showing your excitement and creativity and send to
            @if ($subscription->getId() == 1)
                <a href="mailto:princess@pley.com" target="_blank">princess@pley.com</a>
            @elseif ($subscription->getId() == 2)
                <a href="mailto:explorer@pley.com" target="_blank">explorer@pley.com</a>
            @else
                <a href="mailto:hotwheels@pley.com" target="_blank">hotwheels@pley.com</a>
            @endif
            - you can win amazing prizes!<br/>
            <br/>
            Tell your friends
            <span style="font-size:13.5pt;font-family:Segoe UI Emoji,sans-serif;color:#515151">
                            <img style="margin:0 0.2ex;vertical-align:middle;max-height:24px"
                                 src="https://mail.google.com/mail/e/1f60a">
                        </span><br/>
            <br/>
            Enjoy pleying!<br/>
        </td>
    </tr>
    <tr>
        <td height="10"></td>
    </tr>

@stop

