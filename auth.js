// User data storage (temporary - replace with actual API calls)
const users = [];

/**
 * Register a new user
 * @param {Object} userData - User registration data
 * @returns {Object} Registration result
 */
function register(userData) {
    // Check if username already exists
    const usernameExists = users.some(user => user.username === userData.username);
    if (usernameExists) {
        return { success: false, message: 'Username already exists' };
    }

    // Check if email already exists
    const emailExists = users.some(user => user.email === userData.email);
    if (emailExists) {
        return { success: false, message: 'Email already registered' };
    }

    // Add new user
    users.push({
        ...userData,
        id: Date.now().toString(),
        createdAt: new Date().toISOString()
    });

    return { success: true, message: 'Registration successful' };
}

/**
 * Login user
 * @param {string} username - Username or email
 * @param {string} password - User password
 * @returns {Object|null} User data if successful, null otherwise
 */
function login(username, password) {
    // If no users exist, allow login for any credentials (for testing)
    if (users.length === 0) {
        const testUser = { username, password, id: Date.now().toString() };
        sessionStorage.setItem('currentUser', JSON.stringify(testUser));
        return testUser;
    }
    // Allow login if username and password match, ignore status or other checks
    const user = users.find(u => 
        (u.username === username || u.email === username) && 
        u.password === password
    );
    if (user) {
        sessionStorage.setItem('currentUser', JSON.stringify(user));
        return user;
    }
    return null;
}

/**
 * Check if user is logged in
 * @returns {boolean} True if user is logged in
 */
function isLoggedIn() {
    return !!sessionStorage.getItem('currentUser');
}

/**
 * Get current user data
 * @returns {Object|null} Current user data or null
 */
function getCurrentUser() {
    const user = sessionStorage.getItem('currentUser');
    return user ? JSON.parse(user) : null;
}

/**
 * Logout current user
 */
function logout() {
    sessionStorage.removeItem('currentUser');
    window.location.href = 'login.html';
}

/**
 * Handle Google login (placeholder for actual implementation)
 */
function handleGoogleLogin() {
    alert('Google login will be implemented after hosting setup');
}

/**
 * Handle Google registration (placeholder for actual implementation)
 */
function handleGoogleRegister() {
    alert('Google registration will be implemented after hosting setup');
}