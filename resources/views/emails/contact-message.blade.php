<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuovo messaggio</title>
</head>
<body style="margin:0;padding:0;background-color:#f6f5f2;font-family:Arial, sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f5f2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;padding:24px;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="font-size:20px;font-weight:700;padding-bottom:12px;">
                            Nuovo messaggio dal sito Livelia
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;padding-bottom:16px;">
                            Hai ricevuto un nuovo messaggio dal form contatti.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;line-height:1.6;color:#6b7280;padding-bottom:12px;">
                            <strong>Nome:</strong> {{ $name }}<br>
                            <strong>Email:</strong> {{ $email }}
                            @if (! empty($postId))
                                <br><strong>Post segnalato:</strong> #{{ $postId }}
                            @endif
                            @if (! empty($commentId))
                                <br><strong>Commento segnalato:</strong> #{{ $commentId }}
                            @endif
                            @if (! empty($chatId))
                                <br><strong>Messaggio chat segnalato:</strong> #{{ $chatId }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;line-height:1.6;color:#1f2937;background-color:#f9fafb;border-radius:12px;padding:16px;">
                            {!! nl2br(e($messageBody)) !!}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
