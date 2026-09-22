<nav class="navbar">
  <div class="navbar-inner">
    <?php $is_profile_page = basename($_SERVER['PHP_SELF']) === 'profile.php'; ?>
       <?php $is_index_page = basename($_SERVER['PHP_SELF']) === 'index.php'; ?>
    <div class="navbar-brand">
      <div class="brand-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8z"/>
        </svg>
      </div>
      <span class="brand-name">System Management</span>
    </div>
    <div class="navbar-actions">
      <?php if ($is_index_page): ?>
      <div class="admin-profile">
        <a href="profile.php" class="admin-link" title="Admin Profile">
          <div class="admin-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <span class="admin-name">
            <?php 
              $admin_name = $admin->get_by_id($_SESSION['id_admin']);
              echo htmlspecialchars($admin_name->username);
            ?>
          </span>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($is_profile_page): ?>
        <a href="index.php" class="btn btn-ghost">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
          Back
        </a>
      <?php endif; ?>

      <div class="divider"></div>

      <button class="btn btn-icon" onclick="toggleTheme()" title="Toggle theme">
        <svg id="icon-sun" style="display:none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="5"/>
          <path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
        </svg>
        <svg id="icon-moon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
        </svg>
      </button>

      <?php if ($is_index_page): ?>
        <button class="btn btn-primary" onclick="openModal()">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Add user
        </button>
      </button>
 <?php endif; ?>

      <button class="btn btn-secondary" onclick="window.location.href='?logout'">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Logout
      </button>
    </div>
  </div>
</nav>
