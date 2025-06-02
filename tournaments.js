// Tournament data (temporary - replace with API calls)
let tournaments = [];
let userRegistrations = [];

/**
 * Fetch tournaments from API
 */
async function fetchTournaments() {
    try {
        // In a real app, this would be an actual API call
        // const response = await fetch('/api/tournaments');
        // tournaments = await response.json();
        
        // Mock data
        tournaments = [
            {
                id: 1,
                title: "Free Fire Solo Tournament",
                type: "Solo",
                date: new Date(Date.now() + 86400000).toISOString(),
                players: 100,
                filled: 45,
                prize: 5000,
                entry: 0,
                status: "upcoming",
                roomId: "123456",
                roomPassword: "ff123"
            },
            {
                id: 2,
                title: "Duo Challenge",
                type: "Duo",
                date: new Date(Date.now() + 172800000).toISOString(),
                players: 50,
                filled: 22,
                prize: 10000,
                entry: 50,
                status: "upcoming",
                roomId: "654321",
                roomPassword: "duo456"
            }
        ];

        // If user is logged in, fetch their registrations
        if (isLoggedIn()) {
            // const response = await fetch('/api/user/registrations');
            // userRegistrations = await response.json();
            userRegistrations = [1]; // Mock data
        }

        renderTournaments();
    } catch (error) {
        console.error('Error fetching tournaments:', error);
        showEmptyState("Failed to load tournaments. Please try again later.");
    }
}

/**
 * Render tournaments based on filter
 * @param {string} filter - Filter type ('all', 'solo', 'duo', 'squad', 'my-matches')
 */
function renderTournaments(filter = 'all') {
    const tournamentsList = document.getElementById('tournamentsList');
    if (!tournamentsList) return;
    
    tournamentsList.innerHTML = '';
    
    let filteredTournaments = [];
    
    if (filter === 'my-matches') {
        filteredTournaments = tournaments.filter(t => userRegistrations.includes(t.id));
        if (filteredTournaments.length === 0) {
            showEmptyState("You haven't joined any tournaments yet.");
            return;
        }
    } else if (filter === 'solo' || filter === 'duo' || filter === 'squad') {
        filteredTournaments = tournaments.filter(t => t.type.toLowerCase() === filter);
        if (filteredTournaments.length === 0) {
            showEmptyState(`No ${filter} tournaments available.`);
            return;
        }
    } else {
        filteredTournaments = [...tournaments];
        if (filteredTournaments.length === 0) {
            showEmptyState("No tournaments available at the moment.");
            return;
        }
    }
    
    filteredTournaments.forEach(tournament => {
        const isRegistered = userRegistrations.includes(tournament.id);
        const tournamentCard = createTournamentCard(tournament, isRegistered);
        tournamentsList.appendChild(tournamentCard);
    });
}

/**
 * Create tournament card HTML element
 * @param {Object} tournament - Tournament data
 * @param {boolean} isRegistered - Whether user is registered
 * @returns {HTMLElement} Tournament card element
 */
function createTournamentCard(tournament, isRegistered) {
    const card = document.createElement('div');
    card.className = 'tournament-card';
    
    // Status class
    let statusClass = '';
    let statusText = '';
    if (tournament.status === 'upcoming') {
        statusClass = 'status-upcoming';
        statusText = 'Upcoming';
    } else if (tournament.status === 'live') {
        statusClass = 'status-live';
        statusText = 'Live';
    } else {
        statusClass = 'status-completed';
        statusText = 'Completed';
    }
    
    // Button text and class
    let btnText = 'Join Now';
    let btnClass = 'btn-primary';
    if (isRegistered) {
        btnText = 'Join Match';
        btnClass = 'btn-secondary';
    }
    if (tournament.status === 'completed') {
        btnText = 'Completed';
        btnClass = 'btn-disabled';
    }
    
    card.innerHTML = `
        <div class="tournament-banner">
            <span class="tournament-badge">${tournament.type}</span>
            <span class="tournament-status ${statusClass}">${statusText}</span>
        </div>
        <div class="tournament-content">
            <h3 class="tournament-title">${tournament.title}</h3>
            <div class="tournament-info">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>${formatDate(tournament.date)}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <span>${tournament.players} ${tournament.type === 'Solo' ? 'Players' : tournament.type + 's'}</span>
                </div>
            </div>
            <div class="prize-pool">Prize Pool: ₹${tournament.prize.toLocaleString()}</div>
            <div class="progress-container">
                <div class="progress-text">
                    <span>${tournament.filled}% Filled</span>
                    <span>${Math.round(tournament.players * tournament.filled / 100)}/${tournament.players} Slots</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${tournament.filled}%;"></div>
                </div>
            </div>
            <div class="tournament-footer">
                <div class="entry-fee">Entry: ${tournament.entry === 0 ? 'Free' : '₹' + tournament.entry}</div>
                <button class="btn ${btnClass}" data-id="${tournament.id}" 
                    ${tournament.status === 'completed' ? 'disabled' : ''}
                    data-room="${tournament.roomId}" 
                    data-password="${tournament.roomPassword}"
                    data-start="${formatDate(tournament.date)}">
                    ${btnText}
                </button>
            </div>
        </div>
    `;
    
    return card;
}

/**
 * Show empty state message
 * @param {string} message - Message to display
 */
function showEmptyState(message) {
    const tournamentsList = document.getElementById('tournamentsList');
    if (!tournamentsList) return;
    
    tournamentsList.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-trophy"></i>
            <h3>${message}</h3>
            <p>Check back later for new tournaments or view other categories.</p>
        </div>
    `;
}

/**
 * Format date to readable string
 * @param {string} dateString - ISO date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Initialize tournaments page
 */
function initTournamentsPage() {
    // Set up tab click handlers
    const tabs = document.querySelectorAll('.tab-item');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.textContent.toLowerCase().replace(' ', '-');
            renderTournaments(filter);
        });
    });
    
    // Set up filter dropdowns
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            document.querySelectorAll('.filter-dropdown').forEach(d => {
                if (d !== dropdown) d.classList.remove('show');
            });
            dropdown.classList.toggle('show');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-group')) {
            document.querySelectorAll('.filter-dropdown').forEach(d => {
                d.classList.remove('show');
            });
        }
    });
    
    // Set up filter options
    document.querySelectorAll('.filter-option').forEach(option => {
        option.addEventListener('click', function() {
            this.parentElement.querySelectorAll('.filter-option').forEach(opt => {
                opt.classList.remove('active');
            });
            this.classList.add('active');
            this.closest('.filter-dropdown').classList.remove('show');
        });
    });
    
    // Set up join tournament handlers
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-primary') && !e.target.disabled) {
            const tournamentId = parseInt(e.target.getAttribute('data-id'));
            if (!userRegistrations.includes(tournamentId)) {
                userRegistrations.push(tournamentId);
                e.target.textContent = 'Join Match';
                e.target.classList.remove('btn-primary');
                e.target.classList.add('btn-secondary');
                alert('You have successfully registered for this tournament!');
            }
        }
        else if (e.target.classList.contains('btn-secondary') && !e.target.disabled) {
            const joinModal = document.getElementById('joinModal');
            if (joinModal) {
                document.getElementById('roomId').textContent = e.target.getAttribute('data-room');
                document.getElementById('roomPassword').textContent = e.target.getAttribute('data-password');
                document.getElementById('startTime').textContent = e.target.getAttribute('data-start');
                
                joinModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
    });
    
    // Set up modal close button
    const closeModalBtn = document.querySelector('.close-modal');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            document.getElementById('joinModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }
    
    // Set up copy credentials button
    const copyBtn = document.getElementById('copyCredentials');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const roomId = document.getElementById('roomId').textContent;
            const password = document.getElementById('roomPassword').textContent;
            const text = `Room ID: ${roomId}\nPassword: ${password}`;
            
            navigator.clipboard.writeText(text).then(function() {
                alert('Credentials copied to clipboard!');
                document.getElementById('joinModal').classList.remove('active');
                document.body.style.overflow = 'auto';
            }, function() {
                alert('Failed to copy credentials');
            });
        });
    }
    
    // Close modal when clicking outside
    const joinModal = document.getElementById('joinModal');
    if (joinModal) {
        joinModal.addEventListener('click', function(e) {
            if (e.target === joinModal) {
                joinModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Fetch and render tournaments
    fetchTournaments();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('tournamentsList')) {
        initTournamentsPage();
    }
});