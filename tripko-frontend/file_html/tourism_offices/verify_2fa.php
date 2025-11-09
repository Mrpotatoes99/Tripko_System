<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['2fa_pending']['user_id'])) {
  header('Location: /tripko-system/tripko-frontend/file_html/SignUp_LogIn_Form.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verify Login - TripKo</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f6f8fa;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 28px -12px rgba(16,50,60,.2);padding:24px;max-width:420px;width:100%}
    h1{margin:0 0 8px 0;color:#10323c;font-size:1.25rem}
    .muted{color:#6b7280;margin:0 0 10px 0;font-size:.95rem}
    input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1.05rem;letter-spacing:4px;text-align:center}
    .btn{background:#00a6b8;color:#fff;border:none;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer;width:100%}
    .hint{font-size:.9rem;color:#365057;margin-top:10px}
    .err{color:#b91c1c;font-weight:600;margin:6px 0}
    .ok{color:#0f766e;font-weight:600;margin:6px 0}
  </style>
  <script>
    async function submitCode(e){
      e.preventDefault();
      const code = document.getElementById('code').value.trim();
      const status = document.getElementById('status');
      const btn = document.getElementById('btn');
      if (!/^\d{6}$/.test(code)){ status.textContent='Enter the 6-digit code'; status.className='err'; return; }
      btn.disabled=true; btn.style.opacity='.6'; status.textContent='Verifying...'; status.className='hint';
      try{
        const res = await fetch('/tripko-system/tripko-backend/verify_2fa.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'code='+encodeURIComponent(code)});
        const data = await res.json();
        if(!res.ok || !data.ok){
          let msg = 'Invalid or expired code';
          if (data && data.error==='system') msg = 'Server error, try again';
          status.textContent=msg; status.className='err';
        } else {
          status.textContent='Verified! Redirecting...'; status.className='ok';
          setTimeout(()=>{ window.location.href='/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php'; }, 900);
        }
      }catch(err){
        status.textContent='Network error'; status.className='err';
      } finally{
        btn.disabled=false; btn.style.opacity='';
      }
    }
  </script>
  </head>
<body>
  <div class="card">
    <h1>Verify your login</h1>
    <p class="muted">We sent a 6-digit code to your email. Enter it to continue.</p>
    <form onsubmit="submitCode(event)">
      <input id="code" autocomplete="one-time-code" inputmode="numeric" maxlength="6" placeholder="••••••" />
      <div id="status" class="hint"></div>
      <div style="margin-top:12px"><button id="btn" class="btn" type="submit"><i class="fa-solid fa-key"></i> Verify</button></div>
    </form>
  </div>
</body>
</html>