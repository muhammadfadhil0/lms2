    let usernameTimeout;
    let usernameAvailable = false;
    let passwordMatch = false;
    
    // Password visibility toggle
    document.getElementById('toggle-password').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            passwordInput.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        }
    });
    
    // Confirm password visibility toggle
    document.getElementById('toggle-confirm-password').addEventListener('click', function() {
        const confirmPasswordInput = document.getElementById('confirm_password');
        const eyeOpenConfirm = document.getElementById('eye-open-confirm');
        const eyeClosedConfirm = document.getElementById('eye-closed-confirm');
        
        if (confirmPasswordInput.type === 'password') {
            confirmPasswordInput.type = 'text';
            eyeOpenConfirm.style.display = 'none';
            eyeClosedConfirm.style.display = 'block';
        } else {
            confirmPasswordInput.type = 'password';
            eyeOpenConfirm.style.display = 'block';
            eyeClosedConfirm.style.display = 'none';
        }
    });
    
    // Password confirmation validation
    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const statusDiv = document.getElementById('password-status');
        const checkIcon = document.getElementById('check-icon-password');
        const xIcon = document.getElementById('x-icon-password');
        const statusText = document.getElementById('password-status-text');
        const registerBtn = document.getElementById('register-btn');
        
        if (confirmPassword.length === 0) {
            statusDiv.style.display = 'none';
            passwordMatch = false;
            return;
        }
        
        statusDiv.style.display = 'flex';
        
        if (password === confirmPassword) {
            checkIcon.style.display = 'block';
            xIcon.style.display = 'none';
            statusText.textContent = 'Password sesuai';
            statusText.className = 'text-green-500';
            passwordMatch = true;
        } else {
            checkIcon.style.display = 'none';
            xIcon.style.display = 'block';
            statusText.textContent = 'Password tidak sesuai';
            statusText.className = 'text-red-500';
            passwordMatch = false;
        }
        
        updateRegisterButton();
    }
    
    // Update register button state
    function updateRegisterButton() {
        const registerBtn = document.getElementById('register-btn');
        const username = document.getElementById('username').value.trim();
        
        if (username.length >= 3 && !usernameAvailable) {
            registerBtn.disabled = true;
        } else if (!passwordMatch && document.getElementById('confirm_password').value.length > 0) {
            registerBtn.disabled = true;
        } else {
            registerBtn.disabled = false;
        }
    }
    
    // Add event listeners for password validation
    document.getElementById('password').addEventListener('input', checkPasswordMatch);
    document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
    
    document.getElementById('username').addEventListener('input', function() {
        const username = this.value.trim();
        const statusDiv = document.getElementById('username-status');
        const loadingIcon = document.getElementById('loading-icon');
        const checkIcon = document.getElementById('check-icon');
        const xIcon = document.getElementById('x-icon');
        const statusText = document.getElementById('status-text');
        
        // Clear previous timeout
        clearTimeout(usernameTimeout);
        
        // Hide all icons and status
        loadingIcon.style.display = 'none';
        checkIcon.style.display = 'none';
        xIcon.style.display = 'none';
        statusDiv.style.display = 'none';
        usernameAvailable = false;
        
        if (username.length < 3) {
            updateRegisterButton();
            return;
        }
        
        // Show loading state immediately
        statusDiv.style.display = 'flex';
        loadingIcon.style.display = 'block';
        statusText.textContent = 'Memeriksa ketersediaan username...';
        statusText.className = 'text-gray-500';
        
        // Set timeout for 1 second delay
        usernameTimeout = setTimeout(() => {
            fetch('../logic/check-username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username)
            })
            .then(response => response.json())
            .then(data => {
                loadingIcon.style.display = 'none';
                
                if (data.status === 'available') {
                    checkIcon.style.display = 'block';
                    statusText.textContent = 'Username tersedia';
                    statusText.className = 'text-green-500';
                    usernameAvailable = true;
                } else if (data.status === 'unavailable') {
                    xIcon.style.display = 'block';
                    statusText.textContent = 'Username tidak tersedia';
                    statusText.className = 'text-red-500';
                    usernameAvailable = false;
                } else {
                    statusDiv.style.display = 'none';
                }
                
                updateRegisterButton();
            })
            .catch(error => {
                loadingIcon.style.display = 'none';
                statusDiv.style.display = 'none';
                console.error('Error:', error);
                updateRegisterButton();
            });
        }, 1000);
    });
    
    // Prevent form submission if conditions are not met
    document.querySelector('form').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        
        if (username.length >= 3 && !usernameAvailable) {
            e.preventDefault();
            alert('Silakan pilih username yang tersedia');
            return;
        }
        
        if (!passwordMatch && document.getElementById('confirm_password').value.length > 0) {
            e.preventDefault();
            alert('Pastikan konfirmasi password sesuai');
            return;
        }
    });
