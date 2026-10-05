// Logout Confirmation Modal
function showLogoutModal(logoutUrl) {
    // Create modal HTML
    const modalHTML = `
        <div id="logoutModal" class="logout-modal">
            <div class="logout-modal-content">
                <div class="logout-modal-icon">⚠️</div>
                <h3>Confirm Logout</h3>
                <p>Are you sure you want to log out?</p>
                <div class="logout-modal-buttons">
                    <button onclick="confirmLogout('${logoutUrl}')" class="btn btn-danger">Yes, Logout</button>
                    <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    document.getElementById('logoutModal').style.display = 'block';
    
    // Close on outside click
    document.getElementById('logoutModal').onclick = function(event) {
        if (event.target.id === 'logoutModal') {
            closeLogoutModal();
        }
    };
}

function confirmLogout(logoutUrl) {
    window.location.href = logoutUrl;
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
        modal.remove();
    }
}

// Handle logout links
document.addEventListener('DOMContentLoaded', function() {
    // Find all logout links
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutModal(this.href);
        });
    });
});
