<?php
// Shared admin header
// Usage: include __DIR__ . '/_header.php'; placed inside <body>
?>
<style>
  .admin-navbar {
    background: rgba(26, 26, 26, 0.8);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--md-sys-color-outline);
    padding: 16px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
  }
  .admin-navbar-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .admin-brand {
    font-family: 'Garamond', serif;
    font-size: 24px;
    color: var(--md-sys-color-on-surface);
    text-decoration: none;
    font-weight: 600;
  }
  .admin-nav-links {
    display: flex;
    gap: 32px;
    align-items: center;
  }
  .admin-nav-link {
    color: var(--muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .admin-nav-link:hover, .admin-nav-link.active {
    color: var(--accent);
  }
  .admin-logout {
    background: rgba(248, 113, 113, 0.1);
    color: #f87171;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
  }
  .admin-logout:hover {
    background: rgba(248, 113, 113, 0.2);
  }
</style>

<nav class="admin-navbar">
  <div class="admin-navbar-container">
    <a href="dashboard.php" class="admin-brand">MeyDa Admin</a>
    
    <div class="admin-nav-links">
      <?php $cur = basename($_SERVER['PHP_SELF']); ?>
      <a href="dashboard.php" class="admin-nav-link <?php echo $cur === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
      <a href="products.php" class="admin-nav-link <?php echo $cur === 'products.php' ? 'active' : ''; ?>">Products</a>
      <a href="categories.php" class="admin-nav-link <?php echo $cur === 'categories.php' ? 'active' : ''; ?>">Categories</a>
      <a href="reports.php" class="admin-nav-link <?php echo $cur === 'reports.php' ? 'active' : ''; ?>">Reports</a>
      <a href="transactions.php" class="admin-nav-link <?php echo $cur === 'transactions.php' ? 'active' : ''; ?>">Transactions</a>
      <a href="../login.php?logout=1" class="admin-logout" style="display: flex; align-items: center; gap: 8px;" onclick="return handleLogout(event)">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
        </svg>
        Logout
      </a>
    </div>
  </div>
</nav>

<!-- Custom Admin Confirmation Modal -->
<div id="adminModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#f7f0f0" d="M2.725 21q-.275 0-.5-.137t-.35-.363t-.137-.488t.137-.512l9.25-16q.15-.25.388-.375T12 3t.488.125t.387.375l9.25 16q.15.25.138.513t-.138.487t-.35.363t-.5.137zM12 18q.425 0 .713-.288T13 17t-.288-.712T12 16t-.712.288T11 17t.288.713T12 18m0-3q.425 0 .713-.288T13 14v-3q0-.425-.288-.712T12 10t-.712.288T11 11v3q0 .425.288.713T12 15"/></svg>
    </div>
    <h3 class="admin-modal-title" id="adminModalTitle">Are you sure?</h3>
    <p class="admin-modal-message" id="adminModalMessage">This action cannot be undone.</p>
    <div class="admin-modal-actions">
      <button class="admin-btn admin-btn-secondary admin-modal-btn" id="adminModalCancel">Cancel</button>
      <button class="admin-btn admin-btn-primary admin-modal-btn" id="adminModalConfirm">Confirm</button>
    </div>
  </div>
</div>

<script>
let modalCallback = null;

function adminConfirm(options, callback) {
    const modal = document.getElementById('adminModal');
    const title = document.getElementById('adminModalTitle');
    const message = document.getElementById('adminModalMessage');
    const confirmBtn = document.getElementById('adminModalConfirm');
    
    title.textContent = options.title || 'Are you sure?';
    message.textContent = options.message || 'This action cannot be undone.';
    confirmBtn.textContent = options.confirmText || 'Confirm';
    
    // Set theme color if provided (e.g., for logout use primary, for delete use danger)
    confirmBtn.className = 'admin-btn admin-modal-btn ' + (options.confirmClass || 'admin-btn-primary');
    
    modalCallback = callback;
    modal.classList.add('active');
}

// Close modal handlers
document.getElementById('adminModalCancel').onclick = () => {
    document.getElementById('adminModal').classList.remove('active');
    modalCallback = null;
};

document.getElementById('adminModalConfirm').onclick = () => {
    document.getElementById('adminModal').classList.remove('active');
    if (modalCallback) modalCallback();
    modalCallback = null;
};

// Global Logout Handler
function handleLogout(e) {
    e.preventDefault();
    const logoutUrl = e.currentTarget.href;
    adminConfirm({
        title: 'Sign Out',
        message: 'Are you sure you want to log out of the admin portal?',
        confirmText: 'Logout',
        confirmClass: 'admin-btn-primary'
    }, () => {
        window.location.href = logoutUrl;
    });
    return false;
}
</script>
