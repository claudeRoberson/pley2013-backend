@extends('email.layout.base')

@section('content')
    <tr>
        <td height="10"></td>
    </tr>

    <tr>
        <td style="width:100.0%;height:20px; padding: 20px"><span style="font-size:16px;"><span
                        style="font-family:arial,helvetica,sans-serif;">Hi Pleyer,</span></span></td>
    </tr>

    <tr>
        <td style="width:100.0%; padding: 20px"><br/>
            <span style="font-size:16px;"><span style="font-family:arial,helvetica,sans-serif;">We&rsquo;re sorry to see you go!<br/>
                    Your <strong>{{$subscription->getName()}}</strong> subscription for <strong>{{$userProfile->getFirstName()}}</strong> has been cancelled.
                                                                                                        <br/>
                                                                                                        If you haven&rsquo;t already, please take our survey to tell us why you left. We&rsquo;re always looking for ways to improve and we&rsquo;d appreciate your feedback.<br/>
                                                                                                        <br/>
                                                                                                        When ready, you can easily <a
                            href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/login">reactivate your subscription</a>&nbsp;by logging in to your account profile.<br/>
                                                                                                        <br/>
                                                                                                        <strong>Please note:</strong> You will receive any remaining PleyBoxes on your plan but will not be charged moving forward.<br/>
                                                                                                        <br/>
                                                                                                        Waiting to delight you again,&nbsp;<br/>
                                                                                                        <br/>
                                                                                                        Pley&#39;s Elves,&nbsp;<br/>
                                                                                                        North Pole</span>
                                                                                                    </span>
        </td>
    </tr>
@stop