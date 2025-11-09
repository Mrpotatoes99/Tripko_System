# Email setup for TripKo (SMTP)

This project sends emails via PHPMailer when SMTP is enabled in `config/mail_config.php`.
To work reliably on Windows/XAMPP, configure environment variables and restart Apache.

## 1) Generate Gmail App Password
- Use a Google account with 2-Step Verification enabled
- Create an App Password (type: Mail)
- Copy the 16-character password

## 2) Set environment variables (Windows PowerShell)
Run the following once, replacing values with your own. Then restart Apache in XAMPP.

```powershell
setx SMTP_ENABLED "true"
setx SMTP_HOST "smtp.gmail.com"
setx SMTP_PORT "587"
setx SMTP_SECURE "tls"

setx SMTP_USERNAME "your-email@gmail.com"
setx SMTP_PASSWORD "your-app-password"

setx FROM_EMAIL "your-email@gmail.com"
setx FROM_NAME "TripKo"

# Optional: enable debug logging temporarily
setx SMTP_DEBUG "true"
# setx SMTP_DEBUG_FILE "C:\xampp\htdocs\tripko-system\tripko-backend\mail_debug.log"
```

Restart Apache from XAMPP Control Panel so PHP can see the updated environment.

## 3) Test sending
You can test via CLI or browser using the provided script:

- CLI:
```powershell
C:\xampp\php\php.exe "c:\xampp\htdocs\tripko-system\tripko-backend\tools\test_mailer.php" your-email@gmail.com
```

- Browser:
```
http://localhost/tripko-system/tripko-backend/tools/test_mailer.php?to=your-email@gmail.com
```

Check the JSON output. If `ok` is false, review `tripko-backend/mail_debug.log` for details and verify the env variables were set.

## Notes
- Do not commit real credentials to the repository.
- When `SMTP_DEBUG` is true, PHPMailer writes SMTP conversation logs to `mail_debug.log`. Turn it off in production.
- The verification API also logs generated codes to `tripko-backend/verification_codes.log` as a development aid.
