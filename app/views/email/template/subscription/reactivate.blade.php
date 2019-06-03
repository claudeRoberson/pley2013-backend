@extends('email.layout.base')

@section('content')
    <tr>
        <td height="10"></td>
    </tr>

    <tr>
        <td style="width:100.0%;height:20px; padding: 20px"><span style="font-size:16px;"><span
                        style="font-family:arial,helvetica,sans-serif;">Hello, Pleyer!</span></span></td>
    </tr>

    <tr>
        <td style="width:100.0%; padding: 0px 20px 20px 20px"><br/>
            <span style="font-size:16px;"><span style="font-family:arial,helvetica,sans-serif;">

                    Welcome back to Pley!</br>
                    Thank you for re-activating your <span style="font-family:arial,helvetica,sans-serif; font-weight: bold">{{$subscription->getName()}} box</span> subscription!
                    We’ve added some new subscriptions and new adventures.</br> Check them out at <a href="https://pley.com/">Pley.com</a>. Don’t miss a minute of fun!
                    </br>
                    </br>

                </span>
       </span>
        </td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
            <h3>Let’s Pley!</h3>
        </td>
    </tr>
@stop