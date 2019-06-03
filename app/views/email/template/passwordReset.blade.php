@extends('email.layout.base')

@section('content')
<tr>
    <td style="padding-left: 20px; padding-right: 20px;">
        <p style="color: #244151; font-family: arial,helvetica,sans-serif; font-size: 24px; line-height: 24px;">
            <strong>Dear {{{ucfirst($user->getFirstName())}}},</strong>
        </p>
        <p style="color: #515151; font-family: arial,helvetica,sans-serif; font-size: 17px; line-height: 20px;">
            As requested, here is a link to allow you to create a new Pley password:<br/>
            <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/password/reset/{{strtoupper($userPassReset->getToken())}}?utm_source=PleyMail&amp;utm_medium=email&amp;utm_campaign=password%20reset">
                {{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/tb/password/reset/{{strtoupper($userPassReset->getToken())}}
            </a>
        </p>
    </td>
</tr>
<tr><td height="10"></td></tr>
<tr>
    <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
        <p>
            If you need further assistance, please contact us at
            <a href="https://pley.desk.com/?utm_source=PleyMail&amp;utm_medium=email&amp;utm_campaign=password%20change" style="color:#49B2B8; text-decoration: none;" target="_blank">
                https://pley.desk.com/
            </a>.
        </p>
    </td>
</tr>
<tr><td height="10"></td></tr>
<tr>
   <td style="padding-left: 20px; padding-right: 20px; color: #515151; font-family: arial,helvetica,sans-serif; font-size: 15px; line-height: 20px;">
      <h3>The Pley Team</h3>
   </td>
</tr>
@stop