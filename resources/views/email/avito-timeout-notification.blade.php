<style>


    body {
        padding: 0;
        margin: 0;
    }

    html { -webkit-text-size-adjust:none; -ms-text-size-adjust: none;}
    @media only screen and (max-device-width: 680px), only screen and (max-width: 680px) {
        *[class="table_width_100"] {
            width: 96% !important;
        }
        *[class="border-right_mob"] {
            border-right: 1px solid #dddddd;
        }
        *[class="mob_100"] {
            width: 100% !important;
        }
        *[class="mob_center"] {
            text-align: center !important;
        }
        *[class="mob_center_bl"] {
            float: none !important;
            display: block !important;
            margin: 0px auto;
        }
        .iage_footer a {
            text-decoration: none;
            color: #929ca8;
        }
        img.mob_display_none {
            width: 0px !important;
            height: 0px !important;
            display: none !important;
        }
        img.mob_width_50 {
            width: 40% !important;
            height: auto !important;
        }
    }
    .table_width_100 {
        width: 680px;
    }
</style>


<div id="mailsub" class="notification" align="center">

    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="min-width: 320px;"><tr><td align="center" bgcolor="#eff3f8">


                <!--[if gte mso 10]>
                <table width="680" border="0" cellspacing="0" cellpadding="0">
                    <tr><td>
                <![endif]-->

                <table border="0" cellspacing="0" cellpadding="0" class="table_width_100" width="100%" style="max-width: 680px; min-width: 300px;">
                    <tr><td>
                            <!-- padding -->
                        </td></tr>
                    <!--header -->
                    <tr><td align="center" bgcolor="#ffffff">
                            <!-- padding -->
                            <table width="90%" border="0" cellspacing="0" cellpadding="0">
                                <tr><td align="center">
                                    </td>
                                    <td align="right">

                                    </td></tr>
                                <!--header END-->

                                <!--content 1 -->
                                <tr><td align="center" bgcolor="#fbfcfd">
                                        <font face="Arial, Helvetica, sans-serif" size="4" color="#57697e" style="font-size: 15px;">
                                            <table width="90%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td>
                                                        {!! $textMessage !!}
                                                        <br>
                                                        @if(!empty($machinery))
                                                            Другая доступная для бронирования техника по данному заказу: <br>
                                                        <hr>
                                                            @foreach($machinery as $m)
                                                                <?= \Carbon\Carbon::parse($m[1])->format('d.m.Y H:i') ?>: Исполнитель - <?= $m[0]->company_branch->company->name?> | Техника - <?= $m[0]->name ?><br><hr>
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center">
                                                        С уважением, команда TRANSBAZA<br>
                                                        тел. службы поддержки: 8-800-7073909<br>
                                                        @if(!empty($link))
                                                        чат службы поддержки: <a href="{!! $link !!}">{!! $link !!}</a>
                                                        @endif
                                                    </td>
                                                </tr>

                                            </table>
                                        </font>
                                    </td></tr>
                                <!--content 1 END-->


                                <!--footer -->
                                <tr><td class="iage_footer" align="center" bgcolor="#ffffff">


                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tr><td align="center" style="padding:20px;flaot:left;width:100%; text-align:center;">
                                                    <font face="Arial, Helvetica, sans-serif" size="3" color="#96a5b5" style="font-size: 13px;">
				<span style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #96a5b5;">
					2018 © TRANS-BAZA.RU
				</span></font>
                                                </td></tr>
                                        </table>
                                    </td></tr>
                                <!--footer END-->
                                <tr><td>

                                    </td></tr>
                            </table>
                            <!--[if gte mso 10]>
                            </td></tr>
                            </table>
                            <![endif]-->

                        </td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
