@php
    // The website's boho invitation, rebuilt for email: email clients only honour inline styles and tables,
    // and draw no code, so the painted corner flowers are PNGs (resources: public/images/email) attached inline.
    // No external fonts or stylesheets (remote resources count against deliverability): the stacks use
    // Apple's script font where present, else Georgia and Helvetica.
    $photo = $imagePath ? $message->embed($imagePath) : $imageUrl;
    $floraTopSrc = $floraTop ? $message->embed($floraTop) : null;
    $floraBottomSrc = $floraBottom ? $message->embed($floraBottom) : null;
    $floraTopRightSrc = $floraTopRight ? $message->embed($floraTopRight) : null;
    $floraBottomLeftSrc = $floraBottomLeft ? $message->embed($floraBottomLeft) : null;
    $serif = "'Cormorant Garamond', Georgia, 'Times New Roman', serif";
    $script = "'Pinyon Script', 'Snell Roundhand', 'Apple Chancery', Georgia, serif";
    $sans = "'Jost', 'Helvetica Neue', Helvetica, Arial, sans-serif";
    // The website's colours: cream paper, caramel names, cocoa details, rust accents.
    $paper = '#fcf7ef';
    $ink = '#3a2a20';
    $soft = '#7b6453';
    $caramel = '#a9714b';
    $cocoa = '#7b5539';
    $rust = '#8e3b1f';
    $line = '#d9c3a3';
    $first = $events[0] ?? null;
    $second = $events[1] ?? null;
    $more = array_slice($events, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    {{-- Ask mail apps to keep the cream paper light (Apple Mail honours this; some apps darken it anyway). --}}
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $title }}</title>
    <style>
        :root { color-scheme: light only; supported-color-schemes: light only; }
        body { margin: 0; padding: 0; background: #efe4d2; -webkit-text-size-adjust: 100%; }
        a { color: {{ $rust }}; }
        @media (max-width: 620px) {
            .card { width: 100% !important; }
            .px { padding-left: 22px !important; padding-right: 22px !important; }
            .flora, .flora-cell { width: 112px !important; }
            .flora { height: 112px !important; }
            .name { font-size: 48px !important; }
            .day { font-size: 46px !important; }
            .side { font-size: 11px !important; letter-spacing: 2px !important; }
            .stack { display: block !important; width: 100% !important; }
            .stack-next { padding-top: 26px !important; }
        }
    </style>
</head>
<body class="body" style="margin:0;padding:0;background:#efe4d2;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">{{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#efe4d2;">
    <tr>
        <td align="center" style="padding:32px 10px;">
            <table role="presentation" class="card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:{{ $paper }};border:1px solid #e6d5bb;">

                {{-- Corner flowers, monogram --}}
                <tr>
                    <td>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td class="flora-cell" width="170" valign="top" style="width:170px;line-height:0;font-size:0;">
                                    @if ($floraTopSrc)
                                        <img class="flora" src="{{ $floraTopSrc }}" width="170" height="170" alt="" style="display:block;width:170px;height:170px;border:0;">
                                    @endif
                                </td>
                                <td align="center" valign="middle" style="padding:24px 0 0;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr>
                                            <td align="center" valign="middle" width="86" height="86" style="width:86px;height:86px;border:1px solid #c8a783;border-radius:50%;font-family:{!! $serif !!};font-size:30px;line-height:1;color:{{ $rust }};">
                                                {{ $initials[0] ?? '' }}<span style="font-family:{!! $script !!};font-size:20px;color:{{ $caramel }};">&amp;</span>{{ $initials[1] ?? '' }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td class="flora-cell" width="170" valign="top" align="right" style="width:170px;line-height:0;font-size:0;">
                                    @if ($floraTopRightSrc)
                                        <img class="flora" src="{{ $floraTopRightSrc }}" width="170" height="170" alt="" style="display:block;width:170px;height:170px;border:0;margin-left:auto;">
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- The invitation --}}
                <tr>
                    <td class="px" align="center" style="padding:6px 44px 0;">
                        @if ($kicker)
                            <p style="margin:0;font-family:{!! $sans !!};font-size:11px;line-height:1.9;letter-spacing:4px;text-transform:uppercase;color:{{ $soft }};">{!! nl2br(e($kicker)) !!}</p>
                        @endif

                        <p class="name" style="margin:10px 0 0;font-family:{!! $script !!};font-size:62px;line-height:1.1;font-weight:400;color:{{ $caramel }};">{{ $bride }}</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:2px auto;">
                            <tr>
                                <td width="54" valign="middle"><div style="height:1px;line-height:1px;font-size:1px;background:{{ $line }};">&nbsp;</div></td>
                                <td valign="middle" style="padding:0 12px;font-family:{!! $script !!};font-size:26px;line-height:1;color:{{ $cocoa }};">and</td>
                                <td width="54" valign="middle"><div style="height:1px;line-height:1px;font-size:1px;background:{{ $line }};">&nbsp;</div></td>
                            </tr>
                        </table>
                        <p class="name" style="margin:0;font-family:{!! $script !!};font-size:62px;line-height:1.1;font-weight:400;color:{{ $caramel }};">{{ $groom }}</p>

                        @if ($inviteLine)
                            <p style="margin:18px auto 0;max-width:430px;font-family:{!! $sans !!};font-size:11px;line-height:2;letter-spacing:3px;text-transform:uppercase;color:{{ $soft }};">{{ $inviteLine }}</p>
                        @endif
                    </td>
                </tr>

                {{-- Month and time | day | weekday and year --}}
                <tr>
                    <td class="px" align="center" style="padding:26px 44px 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                            <tr>
                                <td class="side" align="right" valign="middle" style="padding-right:18px;font-family:{!! $serif !!};font-size:14px;line-height:1.7;letter-spacing:3px;text-transform:uppercase;color:{{ $cocoa }};">
                                    <strong>{{ $month }}</strong><br>{{ $time }}
                                </td>
                                <td class="day" align="center" valign="middle" style="padding:4px 20px;border-left:1px solid {{ $line }};border-right:1px solid {{ $line }};font-family:{!! $serif !!};font-size:58px;line-height:1;color:{{ $rust }};">{{ $day }}</td>
                                <td class="side" align="left" valign="middle" style="padding-left:18px;font-family:{!! $serif !!};font-size:14px;line-height:1.7;letter-spacing:3px;text-transform:uppercase;color:{{ $cocoa }};">
                                    <strong>{{ $weekday }}</strong><br>{{ $year }}
                                </td>
                            </tr>
                        </table>

                        @if ($first)
                            <p style="margin:24px 0 0;font-family:{!! $serif !!};font-size:20px;line-height:1.3;letter-spacing:2px;text-transform:uppercase;font-weight:600;color:{{ $cocoa }};">{{ $first['venue'] }}</p>
                        @endif
                        @if ($city)
                            <p style="margin:4px 0 0;font-family:{!! $serif !!};font-size:16px;font-style:italic;color:{{ $soft }};">{{ $city }}</p>
                        @endif
                        @if ($hashtag)
                            <p style="margin:18px 0 0;font-family:{!! $serif !!};font-size:19px;letter-spacing:1px;font-weight:600;color:{{ $rust }};">{{ $hashtag }}</p>
                        @endif
                    </td>
                </tr>

                {{-- Photo --}}
                @if ($photo)
                    <tr>
                        <td class="px" align="center" style="padding:30px 44px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="background:#ffffff;border:1px solid #eadcc6;">
                                <tr>
                                    <td style="padding:8px;">
                                        <img src="{{ $photo }}" width="480" alt="{{ $bride }} &amp; {{ $groom }}" style="display:block;width:100%;max-width:480px;height:auto;border:0;outline:none;text-decoration:none;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif

                {{-- Personal note --}}
                <tr>
                    <td class="px" align="center" style="padding:30px 54px 0;">
                        <p style="margin:0;font-family:{!! $script !!};font-size:34px;line-height:1.2;color:{{ $caramel }};">Dear {{ $guest }},</p>
                        @if ($note)
                            <p style="margin:12px 0 0;font-family:{!! $serif !!};font-size:18px;line-height:1.65;color:{{ $soft }};">{!! nl2br(e($note)) !!}</p>
                        @endif
                    </td>
                </tr>

                {{-- Reserved seats --}}
                <tr>
                    <td align="center" style="padding:26px 44px 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="border:1px dashed {{ $caramel }};">
                            <tr>
                                <td align="center" style="padding:14px 26px;">
                                    <p style="margin:0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;white-space:nowrap;color:{{ $soft }};">Reserved in your honor</p>
                                    <p style="margin:4px 0 0;font-family:{!! $serif !!};font-size:30px;line-height:1.2;color:{{ $rust }};">{{ $seatsLabel }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Call to action --}}
                <tr>
                    <td class="px" align="center" style="padding:28px 44px 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                            <tr>
                                <td align="center" bgcolor="{{ $cocoa }}" style="background:{{ $cocoa }};">
                                    <a href="{{ $link }}" target="_blank" style="display:inline-block;padding:17px 40px;font-family:{!! $sans !!};font-size:12px;letter-spacing:4px;text-transform:uppercase;color:#fcf7ef;text-decoration:none;">Open your invitation</a>
                                </td>
                            </tr>
                        </table>
                        @if ($deadline)
                            <p style="margin:14px 0 0;font-family:{!! $serif !!};font-size:17px;font-style:italic;color:{{ $soft }};">Kindly reply on or before {{ $deadline }}.</p>
                        @endif
                    </td>
                </tr>

                {{-- Venues, worded like the save-the-date card --}}
                @if ($first)
                    <tr>
                        <td class="px" align="center" style="padding:32px 44px 0;">
                            <p style="margin:0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;color:{{ $soft }};">{{ $ceremonyLine }}</p>
                            <p style="margin:6px 0 0;font-family:{!! $serif !!};font-size:22px;line-height:1.3;letter-spacing:2px;text-transform:uppercase;font-weight:600;color:{{ $cocoa }};">{{ $first['venue'] }}</p>
                            @if ($first['address'])
                                <p style="margin:3px 0 0;font-family:{!! $serif !!};font-size:16px;line-height:1.5;font-style:italic;color:{{ $soft }};">{{ $first['address'] }}</p>
                            @endif
                            <p style="margin:6px 0 0;font-family:{!! $serif !!};font-size:14px;letter-spacing:3px;font-weight:600;color:{{ $cocoa }};">{{ $first['time'] }}</p>

                            @if ($second)
                                <p style="margin:22px 0 0;font-family:{!! $script !!};font-size:30px;line-height:1.2;color:{{ $caramel }};">{{ $receptionLine }}</p>
                                <p style="margin:2px 0 0;font-family:{!! $serif !!};font-size:22px;line-height:1.3;letter-spacing:2px;text-transform:uppercase;font-weight:600;color:{{ $cocoa }};">{{ $second['venue'] }}</p>
                                @if ($second['address'])
                                    <p style="margin:3px 0 0;font-family:{!! $serif !!};font-size:16px;line-height:1.5;font-style:italic;color:{{ $soft }};">{{ $second['address'] }}</p>
                                @endif
                                <p style="margin:6px 0 0;font-family:{!! $serif !!};font-size:14px;letter-spacing:3px;font-weight:600;color:{{ $cocoa }};">{{ $second['time'] }}</p>
                            @endif

                            @foreach ($more as $event)
                                <p style="margin:20px 0 0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;color:{{ $soft }};">{{ $event['title'] }} · {{ $event['time'] }}</p>
                                <p style="margin:4px 0 0;font-family:{!! $serif !!};font-size:20px;line-height:1.3;color:{{ $cocoa }};">{{ $event['venue'] }}</p>
                                @if ($event['address'])
                                    <p style="margin:3px 0 0;font-family:{!! $serif !!};font-size:15px;font-style:italic;color:{{ $soft }};">{{ $event['address'] }}</p>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endif

                {{-- Attire & motif and the gift guide, as at the end of the website's dress code --}}
                @if ($attire || $giftGuide)
                    <tr>
                        <td class="px" align="center" style="padding:34px 44px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr><td width="72" style="width:72px;border-top:1px solid {{ $line }};font-size:0;line-height:0;">&nbsp;</td></tr>
                            </table>

                            @if ($attire)
                                <p style="margin:22px 0 0;font-family:{!! $script !!};font-size:36px;line-height:1.2;color:{{ $caramel }};">Attire &amp; Motif</p>
                                @if ($attire['colors'])
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:14px auto 0;">
                                        <tr>
                                            @foreach ($attire['colors'] as $color)
                                                @if (! $loop->first)
                                                    <td width="8" style="width:8px;font-size:0;line-height:0;">&nbsp;</td>
                                                @endif
                                                <td width="28" height="28" bgcolor="{{ $color['hex'] }}" style="width:28px;height:28px;border-radius:14px;background:{{ $color['hex'] }};border:1px solid #d9c7ad;font-size:0;line-height:0;">&nbsp;</td>
                                            @endforeach
                                        </tr>
                                    </table>
                                    <p style="margin:10px 0 0;font-family:{!! $serif !!};font-size:15px;line-height:1.5;font-style:italic;color:{{ $soft }};">{{ collect($attire['colors'])->pluck('name')->join(' · ') }}</p>
                                @endif

                                @php
                                    $sponsorsDress = $attire['sponsors'] || $attire['gentlemen'] || $attire['ladies'];
                                    $guestsDress = $attire['guests'] || $attire['guestsNote'];
                                    $dressWidth = $sponsorsDress && $guestsDress ? '50%' : '100%';
                                @endphp
                                @if ($sponsorsDress || $guestsDress)
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
                                        <tr>
                                            @if ($sponsorsDress)
                                                <td class="stack" width="{{ $dressWidth }}" align="center" valign="top" style="padding:12px 10px 0;">
                                                    <p style="margin:0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;color:{{ $rust }};">Principal Sponsors</p>
                                                    @if ($attire['sponsors'])
                                                        <p style="margin:6px 0 0;font-family:{!! $serif !!};font-size:24px;line-height:1.25;color:{{ $ink }};">{{ $attire['sponsors'] }}</p>
                                                    @endif
                                                    @if ($attire['gentlemen'])
                                                        <p style="margin:8px 0 0;font-family:{!! $script !!};font-size:24px;line-height:1.2;color:{{ $caramel }};">Gentlemen</p>
                                                        <p style="margin:0;font-family:{!! $serif !!};font-size:16px;line-height:1.5;color:{{ $soft }};">{{ $attire['gentlemen'] }}</p>
                                                    @endif
                                                    @if ($attire['ladies'])
                                                        <p style="margin:8px 0 0;font-family:{!! $script !!};font-size:24px;line-height:1.2;color:{{ $caramel }};">Ladies</p>
                                                        <p style="margin:0;font-family:{!! $serif !!};font-size:16px;line-height:1.5;color:{{ $soft }};">{{ $attire['ladies'] }}</p>
                                                    @endif
                                                </td>
                                            @endif
                                            @if ($guestsDress)
                                                <td class="stack{{ $sponsorsDress ? ' stack-next' : '' }}" width="{{ $dressWidth }}" align="center" valign="top" style="padding:12px 10px 0;">
                                                    <p style="margin:0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;color:{{ $rust }};">Our Guests</p>
                                                    @if ($attire['guests'])
                                                        <p style="margin:6px 0 0;font-family:{!! $serif !!};font-size:24px;line-height:1.25;color:{{ $ink }};">{{ $attire['guests'] }}</p>
                                                    @endif
                                                    @if ($attire['guestsNote'])
                                                        <p style="margin:8px 0 0;font-family:{!! $serif !!};font-size:16px;line-height:1.6;font-style:italic;color:{{ $soft }};">{{ $attire['guestsNote'] }}</p>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    </table>
                                @endif
                            @endif

                            @if ($giftGuide)
                                @if ($giftGuide['title'])
                                    <p style="margin:{{ $attire ? '28px' : '22px' }} 0 0;font-family:{!! $script !!};font-size:32px;line-height:1.2;color:{{ $caramel }};">{{ $giftGuide['title'] }}</p>
                                @endif
                                <p style="margin:{{ $giftGuide['title'] ? '8px' : ($attire ? '28px' : '22px') }} auto 0;max-width:470px;font-family:{!! $serif !!};font-size:17px;line-height:1.65;font-style:italic;color:{{ $soft }};">{{ $giftGuide['message'] }}</p>
                            @endif
                        </td>
                    </tr>
                @endif

                {{-- Link, signature, corner flowers --}}
                <tr>
                    <td class="px" align="center" style="padding:28px 44px 0;">
                        <p style="margin:0;font-family:{!! $sans !!};font-size:12px;line-height:1.6;color:#8d7a69;">
                            Button not working? Copy this link into your browser:<br>
                            <a href="{{ $link }}" target="_blank" style="color:{{ $rust }};word-break:break-all;">{{ $link }}</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td class="flora-cell" width="170" valign="bottom" style="width:170px;line-height:0;font-size:0;">
                                    @if ($floraBottomLeftSrc)
                                        <img class="flora" src="{{ $floraBottomLeftSrc }}" width="170" height="170" alt="" style="display:block;width:170px;height:170px;border:0;">
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                                <td align="center" valign="middle" style="padding:18px 0 0;">
                                    <p style="margin:0;font-family:{!! $script !!};font-size:28px;line-height:1.2;color:{{ $caramel }};">With love,</p>
                                    <p style="margin:2px 0 0;font-family:{!! $serif !!};font-size:17px;letter-spacing:1px;color:{{ $ink }};">{{ $bride }} &amp; {{ $groom }}</p>
                                    @if ($families)
                                        <p style="margin:2px 0 0;font-family:{!! $sans !!};font-size:11px;letter-spacing:2px;color:{{ $soft }};">{{ $families }}</p>
                                    @endif
                                </td>
                                <td class="flora-cell" width="170" valign="bottom" align="right" style="width:170px;line-height:0;font-size:0;">
                                    @if ($floraBottomSrc)
                                        <img class="flora" src="{{ $floraBottomSrc }}" width="170" height="170" alt="" style="display:block;width:170px;height:170px;border:0;margin-left:auto;">
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
