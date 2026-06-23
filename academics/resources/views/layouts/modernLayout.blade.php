@extends('layouts/commonMaster')

@php
/* Display elements */
$contentNavbar = true;
$containerNav = ($containerNav ?? 'container-fluid');
$isNavbar = ($isNavbar ?? true);
$isMenu = ($isMenu ?? true);
$isFlex = ($isFlex ?? false);
$isFooter = ($isFooter ?? false);  // Hide traditional footer in modern layout

/* Content classes */
$container = ($container ?? 'container-fluid');
$pageTitle = ($pageTitle ?? 'Dashboard');
$pageSubtitle = ($pageSubtitle ?? '');
@endphp

@section('layoutContent')
<div class="modern-layout">

  {{-- Modern Sidebar --}}
  @if ($isMenu)
    @include('layouts/sections/menu/modernVerticalMenu')
  @endif

  {{-- Layout Menu Toggle (Required by main.js) --}}
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none" style="display: none;">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="bx bx-menu bx-sm"></i>
    </a>
  </div>

  {{-- Main Content Area --}}
  <div class="modern-main">

    {{-- Modern Top Navigation Bar --}}
    @if ($isNavbar)
    <div class="modern-topbar">
      <div class="modern-topbar-left">
        <button class="modern-icon-button layout-menu-toggle" data-sidebar-toggle title="Toggle Sidebar">
          <i class="bx bx-menu"></i>
        </button>
        <h1 class="modern-topbar-title">{{ $pageTitle }}</h1>
      </div>

      <div class="modern-topbar-right">
        {{-- Search Bar --}}
        <div class="modern-topbar-search">
          <i class="bx bx-search modern-topbar-search-icon"></i>
          <input type="text" placeholder="Search..." aria-label="Search">
        </div>

        {{-- Notifications --}}
        <div class="dropdown">
          <button class="modern-icon-button position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
            <i class="bx bx-bell" style="font-size: 20px;"></i>
            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 500px; overflow-y: auto;">
            <div class="dropdown-header d-flex align-items-center justify-content-between">
              <h6 class="mb-0">Notifications</h6>
              <small class="text-muted" id="notificationCount">0 new</small>
            </div>
            <div class="dropdown-divider"></div>
            <div id="notificationList">
              <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="/suggestions" class="dropdown-item text-center text-primary">
              View All Notifications
            </a>
          </div>
        </div>

        {{-- Messages --}}
        <div class="dropdown">
          <button class="modern-icon-button position-relative" id="messageDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Messages">
            <i class="bx bx-envelope" style="font-size: 20px;"></i>
            <span class="notification-badge" id="messageBadge" style="display: none;">0</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="messageDropdown" style="width: 350px; max-height: 500px; overflow-y: auto;">
            <div class="dropdown-header d-flex align-items-center justify-content-between">
              <h6 class="mb-0">Messages</h6>
              <small class="text-muted" id="messageCount">0 new</small>
            </div>
            <div class="dropdown-divider"></div>
            <div id="messageList">
              <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="/suggestions" class="dropdown-item text-center text-primary">
              View All Messages
            </a>
          </div>
        </div>

        {{-- Theme Toggle --}}
        <button class="modern-icon-button" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark Mode">
          <i class="bx bx-moon" id="themeIcon" style="font-size: 20px;"></i>
        </button>

        {{-- User Menu --}}
        <div class="dropdown">
          <button class="modern-icon-button" data-bs-toggle="dropdown" aria-expanded="false" title="User Menu">
            <div class="modern-user-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
              {{ strtoupper(substr(session('username', 'U'), 0, 2)) }}
            </div>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/profile"><i class="bx bx-user me-2"></i> Profile</a></li>
            <li><a class="dropdown-item" href="/settings"><i class="bx bx-cog me-2"></i> Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/logout"><i class="bx bx-log-out me-2"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
    @endif

    {{-- Content Wrapper --}}
    <div class="modern-content">
      @if (!empty($pageSubtitle))
      <div class="modern-page-header">
        <p class="modern-page-subtitle">{{ $pageSubtitle }}</p>
      </div>
      @endif

      {{-- Main Content --}}
      @yield('content')
    </div>

    {{-- Modern Footer (if needed) --}}
    @if ($isFooter)
    <footer class="modern-footer">
      <div class="modern-footer-content">
        <p class="modern-text-muted mb-0">
          &copy; {{ date('Y') }} OLAM School Management. All rights reserved.
        </p>
      </div>
    </footer>
    @endif

  </div>

</div>

{{-- Notification Badge Styles --}}
<style>
.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #dc3545;
  color: white;
  border-radius: 10px;
  padding: 2px 6px;
  font-size: 10px;
  font-weight: bold;
  min-width: 18px;
  text-align: center;
}

.notification-dropdown {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 1px solid #e0e0e0;
}

.notification-dropdown .dropdown-header h6 {
  font-size: 14px;
  font-weight: 600;
}

.notification-item, .message-item {
  padding: 12px 20px;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s;
  cursor: pointer;
}

.notification-item:hover, .message-item:hover {
  background-color: #f8f9fa;
}

.notification-item:last-child, .message-item:last-child {
  border-bottom: none;
}

.notification-item .notification-title,
.message-item .message-sender {
  font-weight: 600;
  font-size: 13px;
  color: #333;
  margin-bottom: 4px;
}

.notification-item .notification-message,
.message-item .message-content {
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
  line-height: 1.4;
}

.notification-item .notification-time,
.message-item .message-time {
  font-size: 11px;
  color: #999;
}

.notification-type-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  margin-left: 8px;
}

.notification-type-general { background: #e3f2fd; color: #1976d2; }
.notification-type-ticket { background: #fff3e0; color: #f57c00; }
.notification-type-event { background: #f3e5f5; color: #7b1fa2; }
.notification-type-report_card { background: #e8f5e9; color: #388e3c; }

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #999;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}
</style>

{{-- Notification and Message JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Fetch notifications
  function fetchNotifications() {
    fetch('/api/notifications')
      .then(response => response.json())
      .then(data => {
        updateNotificationUI(data);
      })
      .catch(error => {
        console.error('Error fetching notifications:', error);
        document.getElementById('notificationList').innerHTML = `
          <div class="empty-state">
            <i class="bx bx-error-circle"></i>
            <p>Failed to load notifications</p>
          </div>
        `;
      });
  }

  // Fetch messages
  function fetchMessages() {
    fetch('/api/messages')
      .then(response => response.json())
      .then(data => {
        updateMessageUI(data);
      })
      .catch(error => {
        console.error('Error fetching messages:', error);
        document.getElementById('messageList').innerHTML = `
          <div class="empty-state">
            <i class="bx bx-error-circle"></i>
            <p>Failed to load messages</p>
          </div>
        `;
      });
  }

  // Update notification UI
  function updateNotificationUI(data) {
    const badge = document.getElementById('notificationBadge');
    const count = document.getElementById('notificationCount');
    const list = document.getElementById('notificationList');

    // Update badge
    if (data.unreadCount > 0) {
      badge.textContent = data.unreadCount;
      badge.style.display = 'block';
    } else {
      badge.style.display = 'none';
    }

    // Update count
    count.textContent = `${data.unreadCount} new`;

    // Update list
    if (data.notifications && data.notifications.length > 0) {
      list.innerHTML = data.notifications.map(notification => `
        <div class="notification-item">
          <div class="notification-title">
            ${notification.title}
            <span class="notification-type-badge notification-type-${notification.type}">
              ${notification.type}
            </span>
          </div>
          <div class="notification-message">${notification.message}</div>
          <div class="notification-time">${notification.time}</div>
        </div>
      `).join('');
    } else {
      list.innerHTML = `
        <div class="empty-state">
          <i class="bx bx-bell-off"></i>
          <p>No notifications</p>
        </div>
      `;
    }
  }

  // Update message UI
  function updateMessageUI(data) {
    const badge = document.getElementById('messageBadge');
    const count = document.getElementById('messageCount');
    const list = document.getElementById('messageList');

    // Update badge
    if (data.unreadCount > 0) {
      badge.textContent = data.unreadCount;
      badge.style.display = 'block';
    } else {
      badge.style.display = 'none';
    }

    // Update count
    count.textContent = `${data.unreadCount} new`;

    // Update list
    if (data.messages && data.messages.length > 0) {
      list.innerHTML = data.messages.map(message => `
        <div class="message-item" onclick="window.location.href='/suggestions'">
          <div class="message-sender">
            ${message.sender}
            <small class="text-muted">(${message.senderReg})</small>
            ${message.hasReply ? '<i class="bx bx-reply ms-2 text-primary"></i>' : ''}
          </div>
          <div class="message-content">${message.content}</div>
          <div class="message-time">${message.time}</div>
        </div>
      `).join('');
    } else {
      list.innerHTML = `
        <div class="empty-state">
          <i class="bx bx-envelope-open"></i>
          <p>No messages</p>
        </div>
      `;
    }
  }

  // Fetch data when dropdowns are opened
  document.getElementById('notificationDropdown')?.addEventListener('shown.bs.dropdown', fetchNotifications);
  document.getElementById('messageDropdown')?.addEventListener('shown.bs.dropdown', fetchMessages);

  // Initial fetch
  fetchNotifications();
  fetchMessages();

  // Auto-refresh every 60 seconds
  setInterval(fetchNotifications, 60000);
  setInterval(fetchMessages, 60000);

  // Theme toggle icon updater
  function updateThemeIcon() {
    const currentTheme = window.getCurrentTheme();
    const themeIcon = document.getElementById('themeIcon');
    const themeToggle = document.getElementById('themeToggle');

    if (themeIcon) {
      if (currentTheme === 'dark') {
        themeIcon.className = 'bx bx-sun';
        themeToggle.title = 'Switch to Light Mode';
      } else {
        themeIcon.className = 'bx bx-moon';
        themeToggle.title = 'Switch to Dark Mode';
      }
    }
  }

  // Update icon on page load
  updateThemeIcon();

  // Listen for theme changes
  window.addEventListener('themeChanged', updateThemeIcon);
});
</script>

@endsection

@push('page-style')
{{-- Additional styles will be added via styles.blade.php --}}
@endpush
