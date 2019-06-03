@extends('email.layout.base')

@section('content')
    <tr>
        <td height="10"></td>
    </tr>

    <tr>
        <td style="width:100.0%;height:20px; padding: 20px"><span style="font-size:16px;"><span
                        style="font-family:arial,helvetica,sans-serif;">Hello Pleyer,</span></span></td>
    </tr>

    <tr>
        <td style="width:100.0%; padding: 0px 20px 20px 20px"><br/>
            <span style="font-size:16px;"><span style="font-family:arial,helvetica,sans-serif;">
                    Even though you are skipping <span style="font-family:arial,helvetica,sans-serif; font-weight: bold">{{$skippedBoxMonth}}’s {{$subscription->getName()}} box</span>, you can still pley in imaginative ways.</br>
                    Check out our other subscriptions and explore the fun! We can’t wait to see you again in
                    <span style="font-family:arial,helvetica,sans-serif; font-weight: bold">{{$nextBoxMonth}}!</span>
                    </br>
                    </br>
                    Keep Pleying!
                </span>
       </span>
        </td>
    </tr>
    <tr>
        <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
            <h3>The Pley Team</h3>
        </td>
    </tr>
@stop