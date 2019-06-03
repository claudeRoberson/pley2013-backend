<html>
<head>
</head>
<body>
<div style="margin-top: 10px">
@foreach ($subscriptionDataList as $subscriptionData)
<table cellspacing="0" cellpadding="0" dir="ltr" border="1" style="table-layout: fixed; font-size: 13px; font-family: verdana; border-collapse: collapse; border: none; border-color:#ddd">
    <colgroup>
        <col width="184">
        <col width="70">
        <col width="116">
        <col width="96">
        <col width="91">
    </colgroup>
    <tbody>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold; color: rgb(255, 255, 255); white-space: nowrap; text-align: center;">
                {{$subscriptionData->name}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold; color: rgb(255, 255, 255); white-space: nowrap; text-align: center;">Period</td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold; color: rgb(255, 255, 255); white-space: nowrap; text-align: center;">Subscribers</td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold; color: rgb(255, 255, 255); white-space: nowrap; text-align: center;">Period Price</td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold; color: rgb(255, 255, 255); white-space: nowrap; text-align: center;">Revenue</td>
        </tr>
        
    @for ($i = 0; $i < count($subscriptionData->planDataList); $i++)
        @if ($i < (count($subscriptionData->planDataList) - 1))
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->planDataList[$i]->period}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->planDataList[$i]->count}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->planDataList[$i]->getUnitPrice()}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->planDataList[$i]->getRevenue()}}
            </td>
        </tr>
        @else
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);">
                {{$subscriptionData->planDataList[$i]->period}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);">
                {{$subscriptionData->planDataList[$i]->count}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);">
                {{$subscriptionData->planDataList[$i]->getUnitPrice()}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);">
                {{$subscriptionData->planDataList[$i]->getRevenue()}}
            </td>
        </tr>
        @endif
    @endfor
    
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">Total </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->getCountTotal()}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->getRevenueTotal()}}
            </td>
        </tr>
        
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0);"></td>
        </tr>
        
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Daily Sub added</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->dailyData->getStatsItem('new')->getCount()}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->dailyData->getStatsItem('new')->getRevenue()}}
            </td>
        </tr>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Daily Sub Cancelled</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->dailyData->getStatsItem('cancelled')->getCount()}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="color: crimson; padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
               - {{$subscriptionData->dailyData->getStatsItem('cancelled')->getRevenue()}}
            </td>
        </tr>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Daily Sub Stopped</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->dailyData->getStatsItem('stopped')->getCount()}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="color: crimson; padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
               - {{$subscriptionData->dailyData->getStatsItem('stopped')->getRevenue()}}
            </td>
        </tr>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Total Churned</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->canceledCount}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0);"></td>
        </tr>
        <tr style="height: 18px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Total Active Subscribers </td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
                {{$subscriptionData->activeCount}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);"></td>
        </tr>
    </tbody>
</table>
<p> </p>
@endforeach

<table cellspacing="0" cellpadding="0" dir="ltr" border="1" style="table-layout: fixed; font-size: 13px; font-family: verdana; border-collapse: collapse; border: none; border-color:#ddd">
    <colgroup>
        <col width="184">
        <col width="70">
        <col width="116">
        <col width="96">
        <col width="91">
    </colgroup>
    <tbody>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; font-weight: bold; color: rgb(255, 255, 255); white-space: nowrap; text-align: center;">Total </td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; background-color: rgb(0, 0, 0); border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0);"></td>
        </tr>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Daily Sub added</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$totalsData->dailyCount}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
                {{$totalsData->getDailyRevenue()}}
            </td>
        </tr>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Daily Stopped Autorenew</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$dailyStoppedAutorenew}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0);"></td>
        </tr>
        <tr style="height: 17px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Daily Churned</td>
            <td style="padding: 0px 3px; vertical-align: bottom; font-family: verdana; white-space: nowrap; text-align: right;">
                {{$dailyCancelled}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom;"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0);"></td>
        </tr>
        <tr style="height: 18px;">
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0); border-left-width: 2px; border-left-style: solid; border-left-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap;">Total Active Subscribers </td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0); font-family: verdana; white-space: nowrap; text-align: right;">
                {{$totalsData->activeCount}}
            </td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);"></td>
            <td style="padding: 0px 3px; vertical-align: bottom; border-right-width: 2px; border-right-style: solid; border-right-color: rgb(0, 0, 0); border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);"></td>
        </tr>
    </tbody>
</table>

</div>
</body>
</html>