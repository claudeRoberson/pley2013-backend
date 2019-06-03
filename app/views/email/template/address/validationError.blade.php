@extends('email.layout.admin')

@section('content')
    <div style="Margin-left: 20px;Margin-right: 20px;Margin-top: 24px;">
        <div style="mso-line-height-rule: exactly;mso-text-raise: 4px;">
            <h1 style="Margin-top: 0;Margin-bottom: 0;font-style: normal;font-weight: normal;color: #b8bdc9;font-size: 28px;line-height: 36px;text-align: center;">
                Address Validation Error</h1>
        </div>
    </div>

    <div style="Margin-left: 20px;Margin-right: 20px;">
        <div style="mso-line-height-rule: exactly;line-height: 10px;font-size: 1px;">&nbsp;
        </div>
    </div>

    <div style="Margin-left: 20px;Margin-right: 20px;">
        <div style="mso-line-height-rule: exactly;mso-text-raise: 4px;">
            <table style="width:100%">
                <tr>
                    <th>User ID:</th>
                    <th>User Name:</th>
                    <th>User Email:</th>
                </tr>
                <tr>
                    <td>{{$user->getId()}}</td>
                    <td>{{$user->getFirstName()}}</td>
                    <td>{{$user->getLastName()}}</td>
                </tr>
            </table>
            <div style="Margin-left: 20px;Margin-right: 20px;">
                <div style="mso-line-height-rule: exactly;line-height: 10px;font-size: 1px;">&nbsp;
                </div>
            </div>
            <table style="width:100%">
                <tr>
                    <th><strong>Name</strong></th>
                    <th><strong>Value</strong></th>
                </tr>
                <tr>
                    <td>Country</td>
                    <td>{{$address->getCountry()}}</td>
                </tr>

                <tr>
                    <td>State</td>
                    <td>{{$address->getState()}}</td>
                </tr>

                <tr>
                    <td>City</td>
                    <td>{{$address->getCity()}}</td>
                </tr>

                <tr>
                    <td>ZIP</td>
                    <td>{{$address->getZipCode()}}</td>
                </tr>
                <tr>
                    <td>Street 1</td>
                    <td>{{$address->getStreet1()}}</td>
                </tr>
                <tr>
                    <td>Street 2</td>
                    <td>{{$address->getStreet2()}}</td>
                </tr>
            </table>

            <div style="Margin-left: 20px;Margin-right: 20px;">
                <div style="mso-line-height-rule: exactly;line-height: 10px;font-size: 1px;">&nbsp;
                </div>
            </div>
            <p style="Margin-top: 0;Margin-bottom: 0; text-align: center">Customer message:&nbsp;</p>

            <blockquote
                    style="Margin-top: 20px;Margin-bottom: 0;Margin-left: 0;Margin-right: 0;padding-left: 14px;border-left: 4px solid #c7c7c7;">
                <p style="Margin-top: 20px;Margin-bottom: 20px;">
                    {{$customerMessage}}
                </p></blockquote>

            <p style="Margin-top: 0;Margin-bottom: 0; text-align: center">Please contact customer and resolve.&nbsp;</p>
        </div>
    </div>
@stop




