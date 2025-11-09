const container = document.querySelector('.auth-container');
const registerBtn = document.querySelector('.register-btn');
const toggleBox = document.querySelector('.toggle-box');
const toggleTitle = document.querySelector('.toggle-title');
const toggleDescription = document.querySelector('.toggle-description');
const toggleBtn = document.querySelector('.toggle-btn');

if (registerBtn && container && toggleBox) {
    registerBtn.addEventListener('click', () => {
        const isActive = container.classList.contains('active');
        
        if (isActive) {
            // Switch to Login
            container.classList.remove('active');
            toggleTitle.textContent = 'Welcome to TripKo';
            toggleDescription.textContent = "Your gateway to exploring Pangasinan's beautiful destinations";
            toggleBtn.textContent = 'Create Account';
            toggleBtn.className = 'toggle-btn register-btn';
        } else {
            // Switch to Register
            container.classList.add('active');
            toggleTitle.textContent = 'Welcome Back!';
            toggleDescription.textContent = 'Sign in to continue your journey';
            toggleBtn.textContent = 'Sign In';
            toggleBtn.className = 'toggle-btn login-btn';
        }
    });
}

// Password visibility toggles
function setupPasswordToggle(iconId, inputId) {
    const icon = document.getElementById(iconId);
    const input = document.getElementById(inputId);
    if (!icon || !input) return;
    icon.addEventListener('click', (e) => {
        e.preventDefault();
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        // Toggle icon between hide/show (uses boxicons solid variants)
        if (isHidden) {
            icon.classList.remove('bxs-hide');
            icon.classList.add('bxs-show');
        } else {
            icon.classList.remove('bxs-show');
            icon.classList.add('bxs-hide');
        }
        // Keep focus on the input for better UX
        input.focus({ preventScroll: true });
    });
}

setupPasswordToggle('togglePassword', 'password');
setupPasswordToggle('toggleFinalPassword', 'final_password');
setupPasswordToggle('toggleFinalConfirmPassword', 'final_confirm_password');