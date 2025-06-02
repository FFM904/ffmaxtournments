/**
 * Initialize dashboard page
 */
function initDashboard() {
    // Toggle sidebar on mobile
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }
    
    // Set current user info
    const currentUser = getCurrentUser();
    if (currentUser) {
        const userAvatar = document.querySelector('.user-avatar');
        const userName = document.querySelector('.user-name');
        
        if (userAvatar) {
            userAvatar.textContent = currentUser.username.charAt(0).toUpperCase();
        }
        
        if (userName) {
            userName.textContent = currentUser.username;
        }
    }
    
    // Load dashboard stats
    loadDashboardStats();
}

/**
 * Load dashboard statistics
 */
async function loadDashboardStats() {
    try {
        // In a real app, these would be API calls
        // const response = await fetch('/api/user/stats');
        // const stats = await response.json();
        
        // Mock data
        const stats = {
            balance: 0,
            activeTournaments: 0,
            totalWins: 0,
            totalEarnings: 0
        };
        
        // Update card values
        document.querySelector('.card-value:nth-child(1)').textContent = `₹${stats.balance}`;
        document.querySelector('.card-value:nth-child(2)').textContent = stats.activeTournaments;
        document.querySelector('.card-value:nth-child(3)').textContent = stats.totalWins;
        document.querySelector('.card-value:nth-child(4)').textContent = `₹${stats.totalEarnings}`;
        
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.main-content')) {
        initDashboard();
    }
});