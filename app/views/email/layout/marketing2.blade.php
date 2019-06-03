<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title></title>
<style type="text/css">
@media screen and (max-width: 600px) {
    .responsive-table {
        width: 100% !important; }
    .responsive-image {
        max-width: 100% !important;
        height: auto !important;
    }

    .responsive-td{
        width: 50% !important;
    }

    .responsive-hr {
        width: 90% !important;
    }
    .responsive-logo {
        height: 50px !important;
        width: auto !important;
    }
    .mobile-title{
        font-size: 30px !important;
        line-height: 36px !important;
    }

    .mobile-logo {
        width: 70px !important;
        height: 45px !important;
    }

    .mobile-nav {
        font-size: 11px !important;
    }

    div, p, a, li, td { -webkit-text-size-adjust:none; }

    .pad20 {
        padding-left: 20px !important;
        padding-right: 20px !important;
    }
    .mobile-hide {
        display: none !important;
    }
    .mobile-right {
        text-align: right !important;
        padding-right: 10px !important;
    }
    .width-inherit {
        min-width: inherit !important;
    }
}
</style>
<custom name="opencounter" type="tracking">
    <table bgcolor="#f6f6f6" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tbody>
            <tr>
                <td align="center">
                    <table bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="responsive-table width-inherit" style="min-width: 600px;" width="600">
                        <tbody>
                            <tr>
                                <td>
                                    <table bgcolor="#f6f6f6" class="responsive-table" style="color:#fff; font-family: arial, helvetica, sans-serif;" width="600">
                                        <tbody>
                                            <tr>
                                                <td align="left" class="responsive-td" style="color:#737373;font-family: arial, helvetica, sans-serif; font-size:10px;" width="300"><i><custom name="preview-text" type="content"></custom></i></td>
                                                <td align="right" class="responsive-td" style="color:#737373;font-family: arial, helvetica, sans-serif; font-size:10px;" width="300">Trouble viewing this email? <a href="%%view_email_url%%" style="color: #6d7172;" target="_blank">Click here.</a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor="#66c8cd">
                                    <table cellpadding="1" cellspacing="0" class="responsive-table" style="color: #ffffff; font-family: arial, helvetica, sans-serif;" width="600">
                                        <tbody>
                                            <tr>
                                                <td colspan="2" height="7">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" class="responsive-td" width="200"><a conversion="true" href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/?utm_source=exacttarget&amp;utm_medium=email&amp;utm_campaign=template-1-col" target="_blank"><img alt="Pley Logo" border="0" class="mobile-logo" height="55" src="https://dnqe9n02rny0n.cloudfront.net/email-assets/logo-delight.png" width="85" /></a></td>
                                                <td align="right" class="responsive-td" width="380"><custom name="nav-bar-2" type="content"> </custom></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" height="7">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="20">
                                    
                                    @yield('content')

                                    <div style="clear:both;">&nbsp;</div>
                                </td>
                            </tr>
                            <tr>
                                <td class="pad20" style="padding-left: 65px; padding-right: 65px;font-family:arial,helvetica,sans-serif; text-align:left; line-height: 20px; font-size:16px; color: #737373;">
                                    <custom name="text" type="content"> </custom>
                                </td>
                            </tr>
                            <tr>
                                <td height="20">&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="center">
                                    <table border="0" cellpadding="0" cellspacing="0">
                                        <tbody>
                                            <tr>
                                                <td><a href="https://www.facebook.com/pages/Pley/377120975714927" target="_blank"><img align="middle" border="0" height="30" src="https://dnqe9n02rny0n.cloudfront.net/imgs/email_assets_1/facebook_email_ico-2.png" width="30" /></a></td>
                                                <td width="10">&nbsp;</td>
                                                <td><a href="https://twitter.com/MyPley" target="_blank"><img align="middle" border="0" height="30" src="https://dnqe9n02rny0n.cloudfront.net/imgs/email_assets_1/twitter_email_ico.png" width="30" /></a></td>
                                                <td width="10">&nbsp;</td>
                                                <td><a href="http://www.pinterest.com/pleygo/" target="_blank"><img align="middle" border="0" height="30" src="https://dnqe9n02rny0n.cloudfront.net/imgs/email_assets_1/pinterest_email_ico-2.jpg" width="30" /></a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="20">&nbsp;</td>
                            </tr>
                            <tr>
                                <td bgcolor="#66c8cd">
                                    <table class="responsive-table" style="padding-left: 20px;padding-right: 20px; color: #fff;font-family: arial, helvetica,sans-serif;" width="600">
                                        <tbody>
                                            <tr>
                                                <td height="7">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="color:#ffffff; font-family: arial, helvetica,
                                                    sans-serif; ">
                                                    <p style="font-family:arial,helvetica,sans-serif;text-align: center;line-height: 14px; font-size:10px; color: #ffffff;padding-left: 20px;padding-right: 20px; ">
                                                        &copy; 2016 Pley All Rights Reserved.&nbsp;&nbsp;|&nbsp;&nbsp; <a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/contact-support?utm_source=exacttarget&amp;utm_medium=email&amp;utm_campaign=template-1-col" style="color:#ffffff;" target="_blank">Contact Us</a>&nbsp;&nbsp;<br />
                                                        You are receiving this email because you signed up on&nbsp;<a href="{{$siteUrl['pley']['protocol']}}://{{$siteUrl['pley']['domain']}}/?utm_source=exacttarget&amp;utm_medium=email&amp;utm_campaign=template-1-col" style="color: #ffffff;" target="_blank">pley.com</a>.<br />
                                                        Pley, 3031 Tisch Way, San Jose, CA 95128
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="7">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody> 
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</custom>​