<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifica il tuo indirizzo email</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f5f2;font-family:Arial, sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f5f2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;padding:32px 28px;border:1px solid #e5e7eb;">
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            <span style="font-size:22px;font-weight:700;color:#0f172a;letter-spacing:-0.5px;">Livelia</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:18px;font-weight:600;color:#1f2937;padding-bottom:10px;">
                            Ciao!
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.7;color:#4b5563;padding-bottom:24px;">
                            Per completare la registrazione, clicca sul pulsante qui sotto per verificare il tuo indirizzo email.
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <a href="{{ $verificationUrl }}" style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:999px;font-weight:600;font-size:14px;letter-spacing:0.2px;">
                                Verifica il mio indirizzo email
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #f3f4f6;padding-top:20px;">
                            <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#6b7280;font-style:italic;">
                                Se non hai creato un account, puoi ignorare questa mail. Nessuna azione è necessaria.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#9ca3af;font-style:italic;">
                                Se hai difficoltà a cliccare il pulsante, copia e incolla il link qui sotto nel tuo browser:
                            </p>
                            <p style="margin:8px 0 0;font-size:11px;line-height:1.5;color:#9ca3af;word-break:break-all;font-style:italic;">
                                <a href="{{ $verificationUrl }}" style="color:#6366f1;text-decoration:none;">{{ $verificationUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
