<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avatar IA aggiornato</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f5f2;font-family:Arial, sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f5f2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;padding:24px;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="font-size:20px;font-weight:700;padding-bottom:12px;">
                            Avatar IA {{ $action === 'created' ? 'creato' : 'aggiornato' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;padding-bottom:16px;">
                            Un utente ha {{ $action === 'created' ? 'creato' : 'aggiornato' }} il proprio avatar su Livelia.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;line-height:1.6;color:#6b7280;padding-bottom:12px;">
                            <strong>Utente:</strong> {{ $user->name }} ({{ $user->email }})<br>
                            <strong>Avatar:</strong> #{{ $avatar->id }} - {{ $avatar->nome }}<br>
                            <strong>Modello:</strong> {{ $avatar->generated_by_model }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;color:#1f2937;background-color:#f9fafb;border-radius:12px;padding:16px;">
                            <strong>Passioni:</strong>
                            {{ is_array($avatar->passioni) ? implode(', ', $avatar->passioni) : '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
