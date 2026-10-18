<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject }}</title>
    <!--[if mso]>
    <xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    <![endif]-->
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f3f5f4;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;color:#f3f5f4;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">{{ $intro }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f3f5f4" style="width:100%;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <!--[if mso]>
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td>
                <![endif]-->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;table-layout:fixed;border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
                    <tr>
                        <td bgcolor="#123c31" style="padding:28px 28px 24px;background-color:#123c31;border-top:4px solid #34d399;">
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:32px;font-weight:bold;color:#ffffff;word-break:break-word;">{{ $appName }}</p>
                            <p style="margin:6px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#cde4da;">Tu espacio para avanzar.</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding:32px 28px 12px;background-color:#ffffff;">
                            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:18px;letter-spacing:1px;font-weight:bold;color:#047857;">{{ $eyebrow }}</p>
                            <h1 style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:30px;line-height:38px;font-weight:bold;color:#17352d;">{{ $title }}</h1>
                            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:25px;color:#253c34;word-break:break-word;">Hola{{ filled($name) ? ', '.$name : '' }}:</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:25px;color:#4b5c55;">{{ $intro }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding:12px 28px 24px;background-color:#ffffff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f0f7f3" style="width:100%;table-layout:fixed;border-collapse:collapse;border-left:3px solid #047857;mso-table-lspace:0pt;mso-table-rspace:0pt;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:18px;letter-spacing:1px;font-weight:bold;color:#496456;">TU CUENTA</p>
                                        <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#17352d;word-break:break-all;">{{ $email }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding:0 28px 12px;background-color:#ffffff;">
                            <!--[if mso]>
                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $actionUrl }}" style="height:50px;v-text-anchor:middle;width:250px;" arcsize="12%" stroke="f" fillcolor="#047857">
                                <w:anchorlock/>
                                <center style="font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;">{{ $actionLabel }}</center>
                            </v:roundrect>
                            <![endif]-->
                            <!--[if !mso]><!-->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;">
                                <tr>
                                    <td align="center" bgcolor="#047857" style="background-color:#047857;border:1px solid #047857;border-radius:6px;">
                                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:15px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:20px;font-weight:bold;text-align:center;text-decoration:none;color:#ffffff;border-radius:6px;">
                                            {{ $actionLabel }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <!--<![endif]-->
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding:0 28px 28px;background-color:#ffffff;">
                            <p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:21px;color:#5b6b63;">{{ $expiry }}</p>
                            <p style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:23px;color:#4b5c55;">{{ $nextStep }}</p>
                            <p style="margin:0;padding-top:20px;border-top:1px solid #e0e8e3;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:22px;color:#5b6b63;">{{ $security }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#f8faf9" style="padding:22px 28px;background-color:#f8faf9;border-top:1px solid #e0e8e3;">
                            <p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#5b6b63;">Si el botón no funciona, copia y pega este enlace completo en tu navegador:</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;word-break:break-all;overflow-wrap:anywhere;"><a href="{{ $actionUrl }}" style="color:#047857;text-decoration:underline;word-break:break-all;">{{ $actionUrl }}</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 12px 0;">
                            <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#5b6b63;">{{ $appName }} · Correo automático de acceso y seguridad.</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#5b6b63;">No compartas este enlace ni tu contraseña.</p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
