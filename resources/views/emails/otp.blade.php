<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medics - Email Verification</title>
</head>
<body
    style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 40px 0;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7f6;">
    <tr>
        <td align="center">

            <table width="600" cellpadding="0" cellspacing="0" border="0"
                   style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">

                <tr>
                    <td align="center" style="padding: 35px 0; background-color: #2B6F71;">
                        <h1 style="color: #ffffff; margin: 0; font-size: 32px; letter-spacing: 2px; text-transform: uppercase;">
                            Medics</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 40px 40px 30px 40px; color: #333333; line-height: 1.6;">
                        <h2 style="margin-top: 0; color: #2B6F71; font-size: 22px;">Verify Your Email Address</h2>

                        <p style="font-size: 16px; color: #555555; margin-bottom: 30px;">
                            Welcome to Medics! Thank you for registering with us. To complete your registration and
                            activate your account, please enter the following verification code:
                        </p>

                        <div style="text-align: center; margin: 35px 0;">
                                <span
                                    style="display: inline-block; font-size: 36px; font-weight: bold; color: #2B6F71; background-color: #f0f7f7; padding: 15px 40px; border-radius: 8px; letter-spacing: 12px; border: 2px dashed #2B6F71;">
                                    {{ $otp }}
                                </span>
                        </div>

                        <p style="font-size: 14px; color: #777777; margin-bottom: 0; text-align: center;">
                            This code is valid for <strong>15 minutes</strong> only. <br> Please do not share this code
                            with anyone.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center"
                        style="padding: 25px 40px; background-color: #f9f9f9; border-top: 1px solid #eeeeee;">
                        <p style="margin: 0 0 10px 0; font-size: 12px; color: #999999;">
                            If you did not request this verification, please ignore this email or contact support if you
                            have concerns.
                        </p>
                        <p style="margin: 0; font-size: 12px; color: #aaaaaa;">
                            &copy; {{ date('Y') }} Medics Center. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
