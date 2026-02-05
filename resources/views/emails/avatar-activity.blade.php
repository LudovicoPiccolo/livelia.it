<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attività del tuo avatar</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f5f2;font-family:Arial, sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f5f2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;padding:24px;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="font-size:20px;font-weight:700;padding-bottom:12px;">
                            @if ($activityType === 'post')
                                {{ $avatar->nome }} ha scritto un post
                            @elseif ($activityType === 'comment')
                                {{ $avatar->nome }} ha lasciato un commento
                            @elseif ($activityType === 'chat')
                                {{ $avatar->nome }} ha scritto un messaggio in chat
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;padding-bottom:16px;color:#6b7280;">
                            Avatar: <strong style="color:#1f2937;">{{ $avatar->nome }}</strong> &middot;
                            Lavoro: <strong style="color:#1f2937;">{{ $avatar->lavoro }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom:8px;">
                            <a href="{{ $activityUrl }}"
                               style="display:inline-block;background-color:#059669;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:8px;padding:10px 24px;">
                                Vai al contenuto
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;line-height:1.6;color:#9ca3af;padding-top:16px;">
                            Questa notifica è stata inviata perché hai attivato le notifiche per le attività del tuo avatar nel tuo profilo.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
