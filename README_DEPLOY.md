TripKo – Hosting and GitHub Guide

Overview
- Putting this project on GitHub gives you version control and collaboration.
- GitHub DOES NOT run PHP/MySQL. GitHub Pages is static-only (HTML/CSS/JS). Your PHP backend must run on a server that supports PHP.

Quick options to view on your phone
1) Cloudflare Tunnel (fast, free, no router tweaks)
   - Exposes your local Apache (XAMPP) to the internet via HTTPS so your phone can open it.
   - Steps (Windows):
     1. Install cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
     2. Open PowerShell and run a quick tunnel:
        cloudflared tunnel --url http://localhost:80
     3. Copy the https://random-name.trycloudflare.com URL and open it on your phone.
     4. Optional: Point a real domain (e.g., from GitHub Education) to a named Cloudflare Tunnel for a stable URL.

2) Windows Mobile Hotspot (local only)
   - Settings > Network & Internet > Mobile hotspot: On. Share over Wi‑Fi.
   - Connect phone to the hotspot SSID, then open http://192.168.137.1/tripko-system/ (or use your PC’s hotspot IP shown in status).

3) Router LAN (if not isolated)
   - Ensure phone and PC are on the same non-guest SSID and client isolation is off.
   - Open http://<your-pc-lan-ip>/tripko-system/

Where to actually host PHP + MySQL
A) Shared Hosting (cPanel)
- Easiest for PHP apps. Upload the project into public_html/tripko-system, set up a MySQL database, update tripko-backend/config/Database.php, and import your dump.

B) VPS (DigitalOcean via GitHub Education credits)
- Full control (Apache/PHP/MariaDB). Point your domain via DNS.
- Basic steps:
  1. Create Ubuntu droplet
  2. Install Apache, PHP, MariaDB
  3. Clone your GitHub repo into /var/www/html/tripko-system
  4. Update Database.php creds and secure the box

C) Docker on a PaaS (Railway, Render, Fly.io)
- Build an image (Apache+PHP) and deploy. Use managed MySQL (e.g., PlanetScale or Railway MySQL). Map env/secrets.

D) GitHub Pages (frontend only)
- Not suitable for PHP pages. Only viable if you refactor the frontend to a static SPA and point API calls to a hosted backend.

Protecting secrets (important)
- Database.php, mail_config.php, sms_config.php contain secrets. We’ve added:
  - .gitignore to exclude them
  - config/*.example.php templates (copy to real files locally)
- Never commit real passwords or API keys.

Suggest workflow
1. Copy example configs:
   - tripko-backend/config/Database.example.php -> Database.php
   - tripko-backend/config/mail_config.example.php -> mail_config.php
   - tripko-backend/config/sms_config.example.php  -> sms_config.php
   - Fill in real values locally (do NOT commit these real files).
2. Commit and push the rest of the code to GitHub.
3. For mobile testing, use Cloudflare Tunnel or Mobile Hotspot.
4. For public hosting, pick one of the hosting options above.

Git: create and push a repo (PowerShell)
# Inside c:\xampp\htdocs\tripko-system
# 1) Initialize repo
 git init
 git add .
 git commit -m "Initial commit: TripKo"

# 2) Create a repo on GitHub (via website), then link and push
 git branch -M main
 git remote add origin https://github.com/<your-username>/tripko-system.git
 git push -u origin main

Notes
- Keep composer.json/lock committed. On Linux hosts, run: composer install --no-dev --prefer-dist --optimize-autoloader
- Avoid committing the uploads/ directory; it contains user-generated files.
- If you need HTTPS for geolocation, Cloudflare Tunnel or a real host with a certificate will satisfy that.
