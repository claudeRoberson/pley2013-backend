<html>
<head>
</head>
<body>
<div style="margin-top: 10px">
    @foreach ($subscriptions as $subscription)
        <table cellspacing="0" cellpadding="0" dir="ltr" border="1"
               style="table-layout: fixed; font-size: 13px; font-family: verdana; border-collapse: collapse; border: none; border-color:#ddd">
            <tbody>
            <tr style="height: 30px;">
                <td colspan="3"
                    style="padding: 0px 3px; background-color: grey; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold;  white-space: nowrap; text-align: center;">
                    {{$subscription['name']}} for {{$subscription['daysAgo']}} days ago
                </td>
            </tr>
            <tr style="height: 16px;">
                <td style="padding: 0px 3px; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold;  white-space: nowrap; text-align: center;">
                    Customer Email
                </td>
                <td style="padding: 0px 3px; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold;  white-space: nowrap; text-align: center;">
                    Box Name
                </td>
                <td style="padding: 0px 3px; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold;  white-space: nowrap; text-align: center;">
                    Box Delivered At
                </td>
            </tr>
            @if(!empty($subscription['shipmentsDelivered']))
                @foreach ($subscription['shipmentsDelivered'] as $shipment)
                    <tr style="height: 14px;">
                        <td style="padding: 0px 3px; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana;  white-space: nowrap; text-align: center;">
                            {{$shipment->user->getEmail()}}
                        </td>
                        <td style="padding: 0px 3px; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: center;">
                            {{$shipment->boxName}}
                        </td>
                        <td style="padding: 0px 3px; vertical-align: bottom; border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: center;">
                            {{date('m/d/Y H:i:s', $shipment->getDeliveredAt())}}
                        </td>
                    </tr>
                @endforeach
            @else
                <tr style="height: 14px;">
                    <td colspan="3" style="text-align: center">
                        No deliveries for this date.
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
        <p></p>
    @endforeach
</div>
</body>
</html>