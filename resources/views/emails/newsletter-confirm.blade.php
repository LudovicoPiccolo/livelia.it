<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conferma iscrizione</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f5f2;font-family:Arial, sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f5f2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;padding:24px;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="font-size:20px;font-weight:700;padding-bottom:12px;">
                            Conferma la tua iscrizione a Livelia
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;padding-bottom:16px;">
                            Clicca sul pulsante qui sotto per confermare l'iscrizione alla newsletter.
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom:16px;">
                            <a href="{{ $confirmationUrl }}" style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;font-size:14px;">
                                Clicca qui per confermare l'iscrizione
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;line-height:1.6;color:#6b7280;">
                            Se non hai richiesto l'iscrizione, puoi ignorare questa mail.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
