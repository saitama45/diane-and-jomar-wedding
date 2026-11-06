@php
    // Email clients only honour inline styles and tables. No external fonts or stylesheets (remote resources
    // count against deliverability): the stacks use Apple's script font where present, else Georgia and Helvetica.
    $photo = $imagePath ? $message->embed($imagePath) : $imageUrl;
    $serif = "'Cormorant Garamond', Georgia, 'Times New Roman', serif";
    $script = "'Pinyon Script', 'Snell Roundhand', Georgia, serif";
    $sans = "'Jost', 'Helvetica Neue', Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title }}</title>
    <style>
        body { margin: 0; padding: 0; background: #ecdcc6; -webkit-text-size-adjust: 100%; }
        a { color: #8e3b1f; }
        @media (max-width: 620px) {
            .card { width: 100% !important; }
            .px { padding-left: 22px !important; padding-right: 22px !important; }
            .names { font-size: 46px !important; }
            .day { font-size: 46px !important; }
            .side { width: 88px !important; letter-spacing: 2px !important; }
            .stack { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#ecdcc6;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">{{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ecdcc6;">
    <tr>
        <td align="center" style="padding:36px 12px;">
            <table role="presentation" class="card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:#fbf5ec;box-shadow:0 18px 40px -24px rgba(58,42,32,0.45);">
                <tr>
                    <td style="padding:12px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #dfbd8a;">

                            {{-- Names --}}
                            <tr>
                                <td class="px" align="center" style="padding:46px 48px 0;">
                                    <p style="margin:0;font-family:{!! $sans !!};font-size:11px;letter-spacing:4px;text-transform:uppercase;color:#b9873f;">{{ $kicker }}</p>
                                    <p class="names" style="margin:14px 0 0;font-family:{!! $script !!};font-size:60px;line-height:1.15;font-weight:400;color:#3a2a20;">{{ $bride }} <span style="color:#c2714f;">&amp;</span> {{ $groom }}</p>
                                    @if ($inviteLine)
                                        <p style="margin:16px auto 0;max-width:420px;font-family:{!! $serif !!};font-size:19px;line-height:1.55;font-style:italic;color:#7b6453;">{{ $inviteLine }}</p>
                                    @endif
                                </td>
                            </tr>

                            {{-- Photo --}}
                            @if ($photo)
                                <tr>
                                    <td class="px" align="center" style="padding:32px 48px 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="background:#ffffff;box-shadow:0 14px 30px -20px rgba(58,42,32,0.55);">
                                            <tr>
                                                <td style="padding:8px;">
                                                    <img src="{{ $photo }}" width="472" alt="{{ $bride }} &amp; {{ $groom }}" style="display:block;width:100%;max-width:472px;height:auto;border:0;outline:none;text-decoration:none;">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endif

                            {{-- Date --}}
                            <tr>
                                <td class="px" align="center" style="padding:36px 48px 0;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr>
                                            <td class="side" width="120" align="center" style="border-top:1px solid #dfbd8a;border-bottom:1px solid #dfbd8a;padding:10px 0;font-family:{!! $sans !!};font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#3a2a20;">{{ $weekday }}</td>
                                            <td align="center" style="padding:0 20px;">
                                                <p style="margin:0;font-family:{!! $sans !!};font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#b9873f;">{{ $month }}</p>
                                                <p class="day" style="margin:4px 0;font-family:{!! $serif !!};font-size:60px;line-height:1;color:#3a2a20;">{{ $day }}</p>
                                                <p style="margin:0;font-family:{!! $sans !!};font-size:11px;letter-spacing:3px;color:#b9873f;">{{ $year }}</p>
                                            </td>
                                            <td class="side" width="120" align="center" style="border-top:1px solid #dfbd8a;border-bottom:1px solid #dfbd8a;padding:10px 0;font-family:{!! $sans !!};font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#3a2a20;">{{ $time }}</td>
                                        </tr>
                                    </table>
                                    @if ($city)
                                        <p style="margin:18px 0 0;font-family:{!! $sans !!};font-size:11px;letter-spacing:4px;text-transform:uppercase;color:#7b6453;">{{ $city }}</p>
                                    @endif
                                </td>
                            </tr>

                            {{-- Ornament --}}
                            <tr>
                                <td align="center" style="padding:34px 0 0;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr>
                                            <td width="70" valign="middle"><div style="height:1px;line-height:1px;font-size:1px;background:#dfbd8a;">&nbsp;</div></td>
                                            <td valign="middle" style="padding:0 12px;font-family:Georgia,serif;font-size:15px;line-height:1;color:#b9873f;">&#10022;</td>
                                            <td width="70" valign="middle"><div style="height:1px;line-height:1px;font-size:1px;background:#dfbd8a;">&nbsp;</div></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            {{-- Personal note --}}
                            <tr>
                                <td class="px" align="center" style="padding:28px 56px 0;">
                                    <p style="margin:0;font-family:{!! $serif !!};font-size:25px;line-height:1.3;color:#3a2a20;">Dear {{ $guest }},</p>
                                    @if ($note)
                                        <p style="margin:14px 0 0;font-family:{!! $serif !!};font-size:18px;line-height:1.65;color:#7b6453;">{!! nl2br(e($note)) !!}</p>
                                    @endif
                                </td>
                            </tr>

                            {{-- Reserved seats --}}
                            <tr>
                                <td align="center" style="padding:28px 48px 0;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="border:1px dashed #b9873f;">
                                        <tr>
                                            <td align="center" style="padding:14px 24px;">
                                                <p style="margin:0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;white-space:nowrap;color:#b9873f;">Reserved in your honor</p>
                                                <p style="margin:4px 0 0;font-family:{!! $serif !!};font-size:30px;line-height:1.2;color:#8e3b1f;">{{ $seatsLabel }}</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            {{-- Call to action --}}
                            <tr>
                                <td class="px" align="center" style="padding:32px 48px 0;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                        <tr>
                                            <td align="center" bgcolor="#3a2a20" style="background:#3a2a20;">
                                                <a href="{{ $link }}" target="_blank" style="display:inline-block;padding:17px 40px;font-family:{!! $sans !!};font-size:12px;letter-spacing:4px;text-transform:uppercase;color:#f5ede1;text-decoration:none;">Open your invitation</a>
                                            </td>
                                        </tr>
                                    </table>
                                    @if ($deadline)
                                        <p style="margin:16px 0 0;font-family:{!! $serif !!};font-size:17px;font-style:italic;color:#7b6453;">Kindly reply on or before {{ $deadline }}.</p>
                                    @endif
                                </td>
                            </tr>

                            {{-- Venues --}}
                            @if (count($events))
                                <tr>
                                    <td class="px" style="padding:30px 36px 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                @foreach ($events as $event)
                                                    <td class="stack" width="{{ floor(100 / count($events)) }}%" valign="top" align="center" style="padding:14px 10px;">
                                                        <p style="margin:0;font-family:{!! $sans !!};font-size:10px;letter-spacing:4px;text-transform:uppercase;color:#b9873f;">{{ $event['title'] }} · {{ $event['time'] }}</p>
                                                        <p style="margin:6px 0 0;font-family:{!! $serif !!};font-size:22px;line-height:1.3;color:#3a2a20;">{{ $event['venue'] }}</p>
                                                        @if ($event['address'])
                                                            <p style="margin:4px 0 0;font-family:{!! $sans !!};font-size:13px;line-height:1.5;color:#7b6453;">{{ $event['address'] }}</p>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endif

                            {{-- Footer --}}
                            <tr>
                                <td class="px" align="center" style="padding:30px 48px 44px;">
                                    @if ($hashtag)
                                        <p style="margin:0;font-family:{!! $sans !!};font-size:12px;letter-spacing:3px;color:#b9873f;">{{ $hashtag }}</p>
                                    @endif
                                    <p style="margin:18px 0 0;font-family:{!! $sans !!};font-size:12px;line-height:1.6;color:#8d7a69;">
                                        Button not working? Copy this link into your browser:<br>
                                        <a href="{{ $link }}" target="_blank" style="color:#8e3b1f;word-break:break-all;">{{ $link }}</a>
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>

            <p style="margin:20px 0 0;font-family:{!! $sans !!};font-size:11px;letter-spacing:1px;color:#8d7a69;">
                With love, {{ $bride }} &amp; {{ $groom }}@if ($families) · {{ $families }}@endif
            </p>
        </td>
    </tr>
</table>
</body>
</html>
