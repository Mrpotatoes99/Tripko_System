<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup Form - TripKo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#00a6b8', brand: '#0f766e' } } } };</script>
    <link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/SignUp_LogIn_Form.css?v=20251103">
    <link rel="stylesheet" href="/tripko-system/tripko-frontend/file_css/responsive.css?v=20251103">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .error-message, .success-message {margin:12px 0;padding:10px 14px;border-radius:4px;font-size:14px}
        .error-message {background:#ffe5e5;color:#7d1212;border:1px solid #f3b1b1}
        .success-message {background:#e6f9ed;color:#0f5e2b;border:1px solid #b0ebc9}
        .resend-box button {background:#1d72b8;color:#fff;border:none;padding:6px 14px;border-radius:4px;cursor:pointer}
        .resend-box {margin:10px 0}
    </style>
</head>
<body>
<!-- Animated Background Slideshow -->
<div class="bg-slideshow">
    <div class="bg-slide" style="background-image: url('https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=1920&q=80');"></div>
    <div class="bg-slide" style="background-image: url('https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=1920&q=80');"></div>
    <div class="bg-slide" style="background-image: url('https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?w=1920&q=80');"></div>
    <div class="bg-overlay"></div>
</div>

<?php
  $error = $_GET['error'] ?? '';
  $success = $_GET['success'] ?? '';
  $prefill = $_GET['email'] ?? '';
?>
<div class="auth-container">
    <div class="form-box login">
        <?php if ($error): ?>
            <div class="error-message">
                <?php
                switch($error){
                    case 'invalid': echo 'Invalid username / email or password'; break;
                    case 'notfound': echo 'Account not found'; break;
                    case 'system': echo 'System error. Try again.'; break;
                    case 'empty': echo 'Please fill in all fields.'; break;
                    case 'inactive': echo 'Account inactive.'; break;
                    case 'unverified': echo 'Email not verified yet.'; break;
                    case 'session': echo 'Session expired. Please login again.'; break;
                    case 'timeout': echo 'Session timed out. Please login again.'; break;
                    case 'no_town': echo 'Tourism officer account has no town assigned. Contact admin to assign one.'; break;
                    default: echo 'An error occurred.';
                }
                ?>
            </div>
        <?php endif; ?>
        <?php if ($success==='pending'): ?>
            <div class="success-message">Account created. Please check your Gmail for the verification link.</div>
        <?php elseif ($success==='registered'): ?>
            <div class="success-message">Registration completed successfully! You can now login with your credentials.</div>
        <?php endif; ?>
        <form action="../../tripko-backend/login.php" method="POST">
            <h1>Login</h1>
            <div class="input-box">
                <input type="text" name="username" placeholder="Username or Email" required minlength="3" maxlength="100" value="<?php echo htmlspecialchars($prefill); ?>">
                <i class='bx bxs-user'></i>
            </div>
            <div class="input-box password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required minlength="6">
                <i id="togglePassword" class='bx bxs-hide eye-icon' aria-hidden="true"></i>
            </div>
            <?php if ($error==='unverified' && $prefill): ?>
            <div class="resend-box">
                <button type="button" id="resendBtn">Resend Verification Email</button>
                <div id="resendStatus" style="font-size:12px;color:#555;margin-top:4px;"></div>
            </div>
            <?php endif; ?>
            <div class="forgot-link">
                <a href="#" id="forgotPasswordLink">Forgot Password?</a>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
    <div class="form-box register">
        <!-- Step 1: Contact Verification (Email or Phone) -->
        <form id="emailVerificationForm" style="display: block;">
            <h1>Sign Up - Step 1</h1>
            <div style="display:flex; gap:8px; margin-bottom:10px;">
                <button type="button" id="useEmailBtn" class="btn" style="flex:1;">Use Email</button>
                <button type="button" id="usePhoneBtn" class="btn" style="flex:1; background:#444;">Use Phone</button>
            </div>
            <div id="emailSection">
                <p style="font-size: 14px; color: #666; margin-bottom: 12px;">Enter your Gmail address to receive a verification code</p>
                <div class="input-box">
                    <input type="email" id="signup_email" placeholder="Gmail Address" maxlength="120"
                           pattern="^[A-Za-z0-9._%+-]+@gmail\\.com$" title="Must be a valid Gmail address">
                    <i class='bx bxs-envelope'></i>
                </div>
            </div>
            <div id="phoneSection" style="display:none;">
                <p style="font-size: 14px; color: #666; margin-bottom: 12px;">Enter your phone (PH) to receive a verification code</p>
                <div class="input-box">
                    <input type="tel" id="signup_phone" placeholder="09XXXXXXXXX or +639XXXXXXXXX" maxlength="20"
                           title="Enter a valid PH mobile number">
                    <i class='bx bxs-phone'></i>
                </div>
            </div>
            <button type="button" id="sendCodeBtn" class="btn">Send Verification Code</button>
            <div id="emailVerificationStatus" style="margin-top: 10px; font-size: 14px;"></div>
        </form>

        <!-- Step 2: Complete Registration -->
        <form id="completeRegistrationForm" style="display: none;" novalidate>
            <h1>Sign Up - Step 2</h1>
            <p id="step2WhereText" style="font-size: 14px; color: #666; margin-bottom: 12px;">Enter the 6-digit code sent to your email</p>
            <div class="input-box" style="display:flex; gap:12px; align-items:center;">
                <div style="position:relative; flex:1; min-width:0;">
                    <input type="text" id="verification_code" placeholder="6-Digit Code" required maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code"
                           title="Enter the 6-digit code"
                           style="background:#fff;color:#111;letter-spacing:4px;font-weight:700;text-align:center;font-size:20px;height:48px;border:1px solid #ccc;border-radius:8px;">
                </div>
                <button type="button" id="verifyCodeBtn" class="btn" style="white-space:nowrap;width:auto;flex:0 0 auto;padding:12px 16px;min-width:140px;">Verify Code</button>
            </div>
            <div id="codeVerifyHint" style="font-size:12px;color:#555;margin:6px 0 8px;">Verify your code to continue</div>
            <div style="display:flex; gap:12px; align-items:center; justify-content:flex-end; margin-bottom:14px;">
                <button type="button" id="resendCodeBtn" class="btn" style="width:auto;flex:0 0 auto;padding:8px 12px;min-width:120px;background:#6c757d;">Resend Code</button>
                <span id="resendCountdown" style="font-size:12px;color:#666;display:none;">Resend in 60s</span>
            </div>
            <div id="passwordFields" style="display:none;">
                <div class="input-box">
                    <input type="text" id="final_username" placeholder="Username" required minlength="3" maxlength="50"
                           pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscore">
                    <i class='bx bxs-user'></i>
                </div>
                <div class="input-box password-wrapper">
                    <input type="password" id="final_password" placeholder="Password" required minlength="6"
                           pattern="(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{6,}"
                           title="Must contain at least one number, one uppercase and lowercase letter, and at least 6 characters">
                    <i id="toggleFinalPassword" class='bx bxs-hide eye-icon' aria-hidden="true"></i>
                </div>
                <div class="input-box password-wrapper">
                    <input type="password" id="final_confirm_password" placeholder="Confirm Password" required minlength="6">
                    <i id="toggleFinalConfirmPassword" class='bx bxs-hide eye-icon' aria-hidden="true"></i>
                </div>
                <button type="button" id="completeRegistrationBtn" class="btn">Complete Registration</button>
            </div>
            <button type="button" id="backToEmailBtn" class="btn" style="background: #666; margin-top: 10px;">Back</button>
            <div id="registrationStatus" style="margin-top: 10px; font-size: 14px;"></div>
        </form>
    </div>
    
    <!-- Toggle Panel -->
    <div class="toggle-box">
        <div class="toggle-content">
            <h1 class="toggle-title">Welcome to TripKo</h1>
            <p class="toggle-description">Your gateway to exploring Pangasinan's beautiful destinations</p>
            <button class="toggle-btn register-btn" type="button">Create Account</button>
        </div>
    </div>
 </div>

<script>
// Two-step registration process (email or phone)
let currentEmail = '';
let currentPhone = '';
let currentMethod = 'email'; // 'email' | 'phone'

// Make the 6-digit code input clearly visible and numeric-only
const codeInput = document.getElementById('verification_code');
if (codeInput) {
    codeInput.addEventListener('input', () => {
        // Keep only digits and limit to 6
        const cleaned = codeInput.value.replace(/\D/g, '').slice(0,6);
        if (codeInput.value !== cleaned) codeInput.value = cleaned;
    });
}

// Resend code logic with cooldown (Step 2) - use unique variable names to avoid collision with login resendBtn
const resendCodeBtnEl = document.getElementById('resendCodeBtn');
const resendCountdownEl = document.getElementById('resendCountdown');
function startResendCooldown(seconds){
    let remain = seconds;
    if (!resendCodeBtnEl || !resendCountdownEl) return;
    resendCodeBtnEl.style.display = 'none';
    resendCountdownEl.style.display = 'inline';
    resendCountdownEl.textContent = `Resend in ${remain}s`;
    const t = setInterval(()=>{
        remain -= 1;
        if (remain <= 0){
            clearInterval(t);
            resendCountdownEl.style.display = 'none';
            resendCodeBtnEl.style.display = 'inline-block';
            return;
        }
        resendCountdownEl.textContent = `Resend in ${remain}s`;
    }, 1000);
}

if (resendCodeBtnEl){
    resendCodeBtnEl.addEventListener('click', ()=>{
        const statusEl = document.getElementById('registrationStatus');
        const isEmail = (currentMethod === 'email');
        const contactValue = isEmail ? currentEmail : currentPhone;
        if (!contactValue) { statusEl.innerHTML = '<span style="color:#d93025;">Missing contact. Go back and enter your email/phone.</span>'; return; }
        resendCodeBtnEl.disabled = true; resendCodeBtnEl.textContent = 'Sending...';
        const url = isEmail ? '../../tripko-backend/send_verification_code.php' : '../../tripko-backend/send_sms_code.php';
        const body = isEmail ? ('email=' + encodeURIComponent(contactValue)) : ('phone=' + encodeURIComponent(contactValue));
        fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
            .then(r=>{ if(!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
            .then(data=>{
                if (data.ok){
                    statusEl.innerHTML = '<span style="color:#0f5132;">Code re-sent. Check your ' + (isEmail?'email':'SMS') + ' inbox.</span>';
                    startResendCooldown(60);
                } else {
                    let msg = 'Failed to resend code';
                    switch(data.error){
                        case 'rate_limit': msg = 'Too many attempts. Try again later.'; break;
                        case 'email_exists': msg = 'Email already registered.'; break;
                        case 'phone_exists': msg = 'Phone already registered.'; break;
                        default: if (data.error) msg = `Error: ${data.error}`;
                    }
                    statusEl.innerHTML = '<span style="color:#d93025;">' + msg + '</span>';
                }
            })
            .catch(err=>{
                statusEl.innerHTML = '<span style="color:#d93025;">Network error while resending code</span>';
            })
            .finally(()=>{ resendCodeBtnEl.disabled = false; resendCodeBtnEl.textContent = 'Resend Code'; });
    });
}

// Toggle between email and phone modes
const useEmailBtn = document.getElementById('useEmailBtn');
const usePhoneBtn = document.getElementById('usePhoneBtn');
const emailSection = document.getElementById('emailSection');
const phoneSection = document.getElementById('phoneSection');

function setMethod(m){
    currentMethod = m;
    if (m==='email'){
        emailSection.style.display='block';
        phoneSection.style.display='none';
        useEmailBtn.style.background='';
        usePhoneBtn.style.background='#444';
    } else {
        emailSection.style.display='none';
        phoneSection.style.display='block';
        useEmailBtn.style.background='#444';
        usePhoneBtn.style.background='';
    }
}
useEmailBtn.addEventListener('click', ()=> setMethod('email'));
usePhoneBtn.addEventListener('click', ()=> setMethod('phone'));

// Step 1: Send verification code
document.getElementById('sendCodeBtn').addEventListener('click', function() {
    const statusEl = document.getElementById('emailVerificationStatus');
    const isEmail = (currentMethod === 'email');
    let contactValue = '';
    if (isEmail){
        const email = document.getElementById('signup_email').value.trim();
        if (!email){ statusEl.innerHTML = '<span style="color:#d93025;">Please enter your Gmail address</span>'; return; }
        if (!/^[A-Za-z0-9._%+-]+@gmail\.com$/.test(email)) { statusEl.innerHTML = '<span style="color:#d93025;">Please enter a valid Gmail address</span>'; return; }
        contactValue = email;
    } else {
        const phone = document.getElementById('signup_phone').value.trim();
        if (!phone){ statusEl.innerHTML = '<span style="color:#d93025;">Please enter your phone number</span>'; return; }
        // Lightweight client check for PH mobile
        if (!/^(\+?63|0)9\d{9}$/.test(phone.replace(/\s|-/g,''))){ statusEl.innerHTML = '<span style="color:#d93025;">Enter a valid PH mobile (09XXXXXXXXX or +639XXXXXXXXX)</span>'; return; }
        contactValue = phone;
    }

    this.disabled = true; this.textContent = 'Sending...';
    statusEl.innerHTML = '<span style="color:#1976d2;">Testing connection...</span>';

    fetch('../../tripko-backend/setup_database.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'setup=1' })
    .then(r=>{ if(!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`); return r.json(); })
    .then(setup=>{ if(!setup.ok) throw new Error('Database setup failed: '+(setup.message||'')); statusEl.innerHTML = '<span style="color:#1976d2;">Sending verification code...</span>';
        const url = isEmail ? '../../tripko-backend/send_verification_code.php' : '../../tripko-backend/send_sms_code.php';
        const body = isEmail ? ('email=' + encodeURIComponent(contactValue)) : ('phone=' + encodeURIComponent(contactValue));
        return fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
    })
    .then(r=>{ if(!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`); return r.json(); })
    .then(data=>{
        if(data.ok){
            if (isEmail) { currentEmail = contactValue; } else { currentPhone = contactValue; }
            let message = isEmail ? 'Code sent! Check your email inbox.' : 'Code sent! Check your SMS inbox.';
            statusEl.innerHTML = '<span style="color:#0f5132;">' + message + '</span>';
            setTimeout(()=>{ 
                document.getElementById('emailVerificationForm').style.display='none'; 
                document.getElementById('completeRegistrationForm').style.display='block';
                const whereText = document.getElementById('step2WhereText');
                if (whereText) { whereText.textContent = isEmail ? 'Enter the 6-digit code sent to your email' : 'Enter the 6-digit code sent to your phone'; }
            }, 1200);
        } else {
            let errorMsg = 'Failed to send code';
            switch(data.error){
                case 'email_exists': errorMsg = 'Email already registered'; break;
                case 'gmail_only': errorMsg = 'Only Gmail addresses allowed'; break;
                case 'rate_limit': errorMsg = 'Too many attempts. Try again later.'; break;
                case 'email_failed': errorMsg = 'Failed to send email. Try again.'; break;
                case 'phone_exists': errorMsg = 'Phone already registered'; break;
                case 'bad_phone': errorMsg = 'Invalid phone number'; break;
                case 'sms_not_configured': errorMsg = 'SMS is not configured. Please contact support.'; break;
                case 'sms_failed': errorMsg = 'Failed to send SMS. Please try again later.'; break;
                default: errorMsg = `Error: ${data.error}`;
            }
            statusEl.innerHTML = '<span style="color:#d93025;">' + errorMsg + '</span>';
        }
    })
    .catch(err=>{ let msg = err.message; if (msg.includes('Unexpected token')) msg = 'Backend returned HTML instead of JSON (PHP error)'; statusEl.innerHTML = '<span style="color:#d93025;">Network error: '+msg+'</span>'; })
    .finally(()=>{ this.disabled = false; this.textContent = 'Send Verification Code'; });
});

// Step 2a: Verify code only (gate password fields)
document.getElementById('verifyCodeBtn').addEventListener('click', function(){
    const code = document.getElementById('verification_code').value.trim();
    const statusEl = document.getElementById('registrationStatus');
    const hint = document.getElementById('codeVerifyHint');
    if (!/^\d{6}$/.test(code)){
        statusEl.innerHTML = '<span style="color:#d93025;">Please enter a valid 6-digit code</span>';
        return;
    }
    this.disabled = true; this.textContent = 'Verifying...';
    const url = (currentMethod==='email') ? '../../tripko-backend/check_verification_code.php' : '../../tripko-backend/check_sms_code.php';
    const body = (currentMethod==='email') ? (`email=${encodeURIComponent(currentEmail)}&code=${encodeURIComponent(code)}`) : (`phone=${encodeURIComponent(currentPhone)}&code=${encodeURIComponent(code)}`);
    fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body }).then(r=>r.json()).then(data=>{
        if(data.ok){
            hint.textContent = 'Code verified. You can now set your username and password.';
            hint.style.color = '#0f5132';
            document.getElementById('passwordFields').style.display = 'block';
            statusEl.innerHTML = '';
        } else {
            let msg = 'Invalid or expired code';
            if (data.error==='email_exists') msg = 'Email already registered';
            if (data.error==='phone_exists') msg = 'Phone already registered';
            statusEl.innerHTML = `<span style="color:#d93025;">${msg}</span>`;
        }
    }).catch(()=>{
        statusEl.innerHTML = '<span style="color:#d93025;">Network error while verifying code</span>';
    }).finally(()=>{ this.disabled = false; this.textContent = 'Verify Code'; });
});

// Step 2b: Complete registration
document.getElementById('completeRegistrationBtn').addEventListener('click', function() {
    const code = document.getElementById('verification_code').value.trim();
    const username = document.getElementById('final_username').value.trim();
    const password = document.getElementById('final_password').value;
    const confirmPassword = document.getElementById('final_confirm_password').value;
    const statusEl = document.getElementById('registrationStatus');
    
    // Validation
    if (!code || !username || !password || !confirmPassword) {
        statusEl.innerHTML = '<span style="color: #d93025;">Please fill in all fields</span>';
        return;
    }
    
    if (!/^\d{6}$/.test(code)) {
        statusEl.innerHTML = '<span style="color: #d93025;">Please enter a valid 6-digit code</span>';
        return;
    }
    
    if (password !== confirmPassword) {
        statusEl.innerHTML = '<span style="color: #d93025;">Passwords do not match</span>';
        return;
    }
    
    if (password.length < 6) {
        statusEl.innerHTML = '<span style="color: #d93025;">Password must be at least 6 characters</span>';
        return;
    }
    
    if (!/^[a-zA-Z0-9_]{3,50}$/.test(username)) {
        statusEl.innerHTML = '<span style="color: #d93025;">Username can only contain letters, numbers, and underscore (3-50 chars)</span>';
        return;
    }
    
    this.disabled = true;
    this.textContent = 'Creating Account...';
    statusEl.innerHTML = '<span style="color: #1976d2;">Creating your account...</span>';
    
        const finalUrl = (currentMethod==='email') ? '../../tripko-backend/verify_registration_code.php' : '../../tripko-backend/verify_sms_registration_code.php';
        const body = (currentMethod==='email')
            ? `email=${encodeURIComponent(currentEmail)}&code=${encodeURIComponent(code)}&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            : `phone=${encodeURIComponent(currentPhone)}&code=${encodeURIComponent(code)}&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;
        fetch(finalUrl, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            statusEl.innerHTML = '<span style="color: #0f5132;">Account created successfully!</span>';
            setTimeout(() => {
                window.location.href = 'SignUp_LogIn_Form.php?success=registered';
            }, 1500);
        } else {
            let errorMsg = 'Registration failed';
            switch(data.error) {
                case 'invalid_or_expired_code': errorMsg = 'Invalid or expired code'; break;
                case 'username_exists': errorMsg = 'Username already taken'; break;
                case 'email_exists': errorMsg = 'Email already registered'; break;
                case 'weak_password': errorMsg = 'Password too weak'; break;
                case 'invalid_username': errorMsg = 'Invalid username format'; break;
            }
            statusEl.innerHTML = '<span style="color: #d93025;">' + errorMsg + '</span>';
        }
    })
    .catch(err => {
        statusEl.innerHTML = '<span style="color: #d93025;">Network error. Please try again.</span>';
    })
    .finally(() => {
        this.disabled = false;
        this.textContent = 'Complete Registration';
    });
});

// Back to email step
document.getElementById('backToEmailBtn').addEventListener('click', function() {
    document.getElementById('completeRegistrationForm').style.display = 'none';
    document.getElementById('emailVerificationForm').style.display = 'block';
    document.getElementById('registrationStatus').innerHTML = '';
});

// Handle resend for login verification (existing functionality)
const resendBtn = document.getElementById('resendBtn');
if (resendBtn) {
  resendBtn.addEventListener('click', () => {
    const statusEl = document.getElementById('resendStatus');
    statusEl.textContent = 'Sending...';
    const emailVal = "<?php echo htmlspecialchars($prefill); ?>";
    fetch('../../tripko-backend/resend_verification.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'email=' + encodeURIComponent(emailVal)
    }).then(r=>r.json()).then(data=>{
      if (data.ok && !data.already) statusEl.textContent='Verification email sent.';
      else if (data.already) statusEl.textContent='Already verified.';
      else if (data.error==='rate') statusEl.textContent='Rate limit reached. Try again later.';
      else statusEl.textContent='Failed. Try again.';
    }).catch(()=> statusEl.textContent='Network error.');
  });
}
</script>
<script>
// Forgot Password modal
(function(){
    const link = document.getElementById('forgotPasswordLink');
    if (!link) return;

    // Create modal lazily to avoid cluttering initial DOM
    let modal;
    function buildModal(){
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'forgotModal';
        modal.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1000;">
                <div style="background:#fff;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.18);width:min(92vw,480px);padding:20px 20px 16px;border:1px solid #e5e7eb;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <h3 style="margin:0;color:#10323c;font-size:1.1rem;font-weight:800;">Reset your password</h3>
                        <button type="button" id="fpClose" aria-label="Close" style="background:transparent;border:none;font-size:22px;line-height:1;cursor:pointer;color:#6b7280">×</button>
                    </div>
                    <p style="margin:0 0 10px 0;color:#365057;font-size:.95rem;">Enter the email associated with your account. If it exists, well email a 6-digit reset code.</p>
                    <form id="forgotForm">
                        <div style="display:grid;gap:10px;">
                            <input type="email" name="email" id="fpEmail" placeholder="you@example.com" required style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;width:100%" />
                            <div id="fpStep1" style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                                <button type="button" id="fpCancel" class="btn" style="background:#6b7280;">Cancel</button>
                                <button type="submit" id="fpSubmit" class="btn">Send code</button>
                            </div>
                            <div id="fpStep2" style="display:none;">
                                <div style="display:grid;gap:10px;">
                                    <input type="text" id="fpCode" placeholder="6-digit code" maxlength="6" inputmode="numeric" style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;width:100%" />
                                    <input type="password" id="fpNew" placeholder="New password" style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;width:100%" />
                                    <input type="password" id="fpConfirm" placeholder="Confirm password" style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;width:100%" />
                                    <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                                        <button type="button" id="fpBack" class="btn" style="background:#6b7280;">Back</button>
                                        <button type="button" id="fpVerify" class="btn">Reset Password</button>
                                    </div>
                                </div>
                            </div>
                            <div id="fpStatus" style="min-height:20px;font-size:.9rem;"></div>
                        </div>
                    </form>
                </div>
            </div>`;
        document.body.appendChild(modal);
        const close = ()=>{ if (modal && modal.parentNode) modal.parentNode.removeChild(modal); modal = null; }
        modal.querySelector('#fpClose').addEventListener('click', close);
        modal.querySelector('#fpCancel').addEventListener('click', close);
        modal.addEventListener('click',(e)=>{ if(e.target===modal) close(); });

        const form = modal.querySelector('#forgotForm');
        form.addEventListener('submit', async (e)=>{
            e.preventDefault();
            const email = modal.querySelector('#fpEmail').value.trim();
            const status = modal.querySelector('#fpStatus');
            const btn = modal.querySelector('#fpSubmit');
            if (!email) { status.textContent='Please enter your email'; status.style.color='#b91c1c'; return; }
            btn.disabled=true; btn.style.opacity='.6'; status.textContent='Sending...'; status.style.color='#10323c';
            try {
                const res = await fetch('/tripko-system/tripko-backend/request_password_reset_code.php', {
                    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'email='+encodeURIComponent(email)
                });
                await res.json().catch(()=>({ok:true}));
                status.textContent='If that email exists, well send a reset code shortly.';
                status.style.color='#0f766e';
                // Move to step 2
                document.getElementById('fpStep1').style.display='none';
                document.getElementById('fpStep2').style.display='block';
            } catch(err){
                status.textContent='Network error. Please try again.'; status.style.color='#b91c1c';
            } finally {
                btn.disabled=false; btn.style.opacity='';
            }
        });
        // Back
        modal.querySelector('#fpBack').addEventListener('click', ()=>{
            document.getElementById('fpStep2').style.display='none';
            document.getElementById('fpStep1').style.display='flex';
            modal.querySelector('#fpStatus').textContent='';
        });
        // Verify and reset
        modal.querySelector('#fpVerify').addEventListener('click', async ()=>{
            const email = modal.querySelector('#fpEmail').value.trim();
            const code = modal.querySelector('#fpCode').value.trim();
            const newp = modal.querySelector('#fpNew').value;
            const conf = modal.querySelector('#fpConfirm').value;
            const status = modal.querySelector('#fpStatus');
            if (!/^\d{6}$/.test(code)) { status.textContent='Enter the 6-digit code'; status.style.color='#b91c1c'; return; }
            if (!newp || !conf) { status.textContent='Enter your new password twice'; status.style.color='#b91c1c'; return; }
            if (newp !== conf) { status.textContent='Passwords do not match'; status.style.color='#b91c1c'; return; }
            status.textContent='Resetting password...'; status.style.color='#10323c';
            try{
                const body = new URLSearchParams({ email, code, new_password:newp, confirm_password:conf }).toString();
                const res = await fetch('/tripko-system/tripko-backend/verify_password_reset_code.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
                const data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.error||'Reset failed');
                status.textContent='Password reset. You can now log in.'; status.style.color='#0f766e';
                setTimeout(()=>{ if(modal){ modal.querySelector('#fpClose').click(); } }, 1400);
            } catch(err){ status.textContent='Error: '+err.message; status.style.color='#b91c1c'; }
        });
        return modal;
    }
    link.addEventListener('click', (e)=>{ e.preventDefault(); buildModal(); });
})();
</script>
<script src="/tripko-system/tripko-frontend/file_js/mobile-viewport-fix.js?v=20251103"></script>
<script src="/tripko-system/tripko-frontend/file_js/SignUp_LogIn_Form.js?v=20251103"></script>
</body>
</html>
