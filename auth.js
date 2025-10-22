// Logout functionality
async function logoutUser() {
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=logout'
        });
        
        const data = await response.json();
        
        // Clear local storage regardless of server response
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        localStorage.removeItem('cart');
        
        // Update UI
        updateUserInterface();
        
        // Show success message
        alert('You have been logged out successfully.');
        
        // Redirect to home page
        window.location.href = 'index.html';
        
    } catch (error) {
        console.error('Logout error:', error);
        // Still clear local storage even if there's an error
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        localStorage.removeItem('cart');
        updateUserInterface();
        window.location.href = 'index.html';
    }
}

// Update UI based on authentication status
function updateUserInterface() {
    const userBtn = document.getElementById('userBtn');
    const authToken = localStorage.getItem('auth_token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (userBtn) {
        if (authToken && user.name) {
            // User is logged in
            userBtn.innerHTML = '<i class="fas fa-user-check"></i>';
            userBtn.title = `Logged in as ${user.name}`;
            
            // Update click handler for logout
            userBtn.onclick = function() {
                showUserMenu(user.name);
            };
        } else {
            // User is not logged in
            userBtn.innerHTML = '<i class="fas fa-user"></i>';
            userBtn.title = 'Login';
            userBtn.onclick = function() {
                document.getElementById('loginModal').style.display = 'flex';
            };
        }
    }
    
    // Update cart count
    updateCartCount();
}

// Show user menu when clicking on user icon
function showUserMenu(userName) {
    // Remove existing menu if any
    const existingMenu = document.querySelector('.user-menu');
    if (existingMenu) {
        existingMenu.remove();
    }
    
    // Create user menu
    const userMenu = document.createElement('div');
    userMenu.className = 'user-menu';
    userMenu.innerHTML = `
        <div class="user-menu-content">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>${userName}</span>
            </div>
            <div class="user-menu-items">
                <a href="profile.html" class="user-menu-item">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="bookings.html" class="user-menu-item">
                    <i class="fas fa-calendar-alt"></i> My Bookings
                </a>
                <button class="user-menu-item logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    `;
    
    // Add styles for user menu
    if (!document.querySelector('#user-menu-styles')) {
        const styles = document.createElement('style');
        styles.id = 'user-menu-styles';
        styles.textContent = `
            .user-menu {
                position: absolute;
                top: 70px;
                right: 20px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                z-index: 1001;
                min-width: 200px;
                animation: fadeIn 0.3s ease;
            }
            
            .user-menu-content {
                padding: 15px;
            }
            
            .user-info {
                display: flex;
                align-items: center;
                padding-bottom: 10px;
                margin-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            
            .user-info i {
                font-size: 1.2rem;
                color: var(--dark-green);
                margin-right: 10px;
            }
            
            .user-info span {
                font-weight: 500;
                color: var(--dark-gray);
            }
            
            .user-menu-items {
                display: flex;
                flex-direction: column;
            }
            
            .user-menu-item {
                padding: 10px;
                text-decoration: none;
                color: var(--dark-gray);
                border: none;
                background: none;
                text-align: left;
                cursor: pointer;
                border-radius: 5px;
                transition: var(--transition);
                display: flex;
                align-items: center;
            }
            
            .user-menu-item:hover {
                background-color: var(--light-gray);
                color: var(--dark-green);
            }
            
            .user-menu-item i {
                margin-right: 8px;
                width: 16px;
            }
            
            .logout-btn {
                color: #e74c3c;
            }
            
            .logout-btn:hover {
                background-color: #ffeaea;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Add event listener for logout button
    userMenu.querySelector('.logout-btn').addEventListener('click', logoutUser);
    
    // Add to page
    document.body.appendChild(userMenu);
    
    // Close menu when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!userMenu.contains(e.target) && e.target !== userBtn) {
                userMenu.remove();
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 100);
}