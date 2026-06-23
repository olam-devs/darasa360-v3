@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
$containerNav = $containerNav ?? 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
@endif
@if(isset($navbarDetached) && $navbarDetached == '')
<nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="{{$containerNav}}">
    @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
      <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
        <a href="{{url('/')}}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">
            <img src="{{ asset('assets/images/v1_logo.png') }}" alt="Logo" width="40">
          </span>
          <span class="app-brand-text demo menu-text fw-bold text-heading">{{config('variables.templateName')}}</span>
        </a>
      </div>
      @endif

      <!-- ! Not required for layout-without-menu -->
      @if(!isset($navbarHideToggle))
      <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
          <i class="bx bx-menu bx-md"></i>
        </a>
      </div>
      @endif

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->

        <!-- /Search -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">

          <!-- Notifications -->
          <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown" id="navbarNotifications">
              <i class="bx bx-bell bx-md"></i>
              <span class="badge rounded-pill bg-danger badge-notifications" id="navNotificationBadge" style="display: none;">0</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 500px; overflow-y: auto;">
              <li class="dropdown-header d-flex align-items-center justify-content-between px-3 py-2">
                <h6 class="mb-0">Notifications</h6>
                <small class="text-muted" id="navNotificationCount">0 new</small>
              </li>
              <li><hr class="dropdown-divider m-0"></li>
              <li><div id="navNotificationList" class="px-0">
                <div class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
              </div></li>
              <li><hr class="dropdown-divider m-0"></li>
              <li><a href="/suggestions" class="dropdown-item text-center text-primary py-2">View All Notifications</a></li>
            </ul>
          </li>

          <!-- Messages -->
          <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown" id="navbarMessages">
              <i class="bx bx-envelope bx-md"></i>
              <span class="badge rounded-pill bg-danger badge-notifications" id="navMessageBadge" style="display: none;">0</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 500px; overflow-y: auto;">
              <li class="dropdown-header d-flex align-items-center justify-content-between px-3 py-2">
                <h6 class="mb-0">Messages</h6>
                <small class="text-muted" id="navMessageCount">0 new</small>
              </li>
              <li><hr class="dropdown-divider m-0"></li>
              <li><div id="navMessageList" class="px-0">
                <div class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
              </div></li>
              <li><hr class="dropdown-divider m-0"></li>
              <li><a href="/suggestions" class="dropdown-item text-center text-primary py-2">View All Messages</a></li>
            </ul>
          </li>

          <!-- Theme Switcher -->
          <li class="nav-item me-3">
            <a class="nav-link hide-arrow" href="javascript:void(0);" id="themeSwitcher" title="Toggle Dark Mode">
              <i class="bx bx-moon bx-md" id="themeIcon"></i>
            </a>
          </li>

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                @if(session('profile_picture') && file_exists(public_path('uploads/profiles/' . session('profile_picture'))))
                  <img src="{{ asset('uploads/profiles/' . session('profile_picture')) }}"
                       alt class="w-px-40 h-auto rounded-circle">
                @else
                  <img src="{{ asset('assets/img/avatars/' . (strtolower(session('gender')) === 'female' ? 'female.png' : 'male.png')) }}"
                       alt class="w-px-40 h-auto rounded-circle">
                @endif
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                <a class="dropdown-item" href="javascript:void(0);">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar avatar-online">
                        @if(session('profile_picture') && file_exists(public_path('uploads/profiles/' . session('profile_picture'))))
                          <img
                            src="{{ asset('uploads/profiles/' . session('profile_picture')) }}"
                            alt="avatar"
                            class="w-px-40 h-auto rounded-circle"
                          >
                        @else
                          <img
                            src="{{ asset('assets/img/avatars/' . (strtolower(session('gender')) === 'female' ? 'female.png' : 'male.png')) }}"
                            alt="avatar"
                            class="w-px-40 h-auto rounded-circle"
                          >
                        @endif
                      </div>

                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0">{{ session('name', 'Unknown') }}</h6>
                      <small class="text-muted">Reg:{{ ucfirst(session('registration_no', 'N/A')) }}</small><br>
                      <small class="text-muted">Role:{{ ucfirst(session('role', 'User')) }}</small>
                    </div>


                  </div>
                </a>
              </li>

              {{--  --}}


              <li>
                <div class="dropdown-divider my-1"></div>
              </li>

              <li>
                <div class="dropdown-divider my-1"></div>
              </li>
              <li>
                <a class="dropdown-item" href="{{ route('logout') }}">
                  <i class="bx bx-log-out bx-md me-3"></i><span>Log Out</span>
                </a>


              </li>
            </ul>
          </li>
          <!--/ User -->
        </ul>
      </div>

      @if(!isset($navbarDetached))
    </div>
    @endif
  </nav>
  <!-- / Navbar -->

  {{-- Notification Styles for Regular Navbar --}}
  <style>
  /* Theme Switcher Styles */
  #themeSwitcher {
    cursor: pointer;
    transition: all 0.3s ease;
  }

  #themeSwitcher:hover {
    transform: scale(1.1);
  }

  #themeIcon {
    transition: transform 0.3s ease;
  }

  #themeSwitcher:hover #themeIcon {
    transform: rotate(20deg);
  }

  /* Smooth theme transition */
  html {
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  .badge-notifications {
    position: absolute;
    top: 8px;
    right: 0;
    font-size: 9px;
    padding: 2px 5px;
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

  {{-- Notification JavaScript for Regular Navbar --}}
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Check if we're using regular navbar
    if (!document.getElementById('navNotificationBadge')) return;

    // Fetch notifications
    function fetchNavNotifications() {
      fetch('/api/notifications')
        .then(response => response.json())
        .then(data => {
          updateNavNotificationUI(data);
        })
        .catch(error => {
          console.error('Error fetching notifications:', error);
          const list = document.getElementById('navNotificationList');
          if (list) {
            list.innerHTML = `
              <div class="empty-state">
                <i class="bx bx-error-circle"></i>
                <p>Failed to load</p>
              </div>
            `;
          }
        });
    }

    // Fetch messages
    function fetchNavMessages() {
      fetch('/api/messages')
        .then(response => response.json())
        .then(data => {
          updateNavMessageUI(data);
        })
        .catch(error => {
          console.error('Error fetching messages:', error);
          const list = document.getElementById('navMessageList');
          if (list) {
            list.innerHTML = `
              <div class="empty-state">
                <i class="bx bx-error-circle"></i>
                <p>Failed to load</p>
              </div>
            `;
          }
        });
    }

    // Update notification UI
    function updateNavNotificationUI(data) {
      const badge = document.getElementById('navNotificationBadge');
      const count = document.getElementById('navNotificationCount');
      const list = document.getElementById('navNotificationList');

      if (!badge || !count || !list) return;

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
    function updateNavMessageUI(data) {
      const badge = document.getElementById('navMessageBadge');
      const count = document.getElementById('navMessageCount');
      const list = document.getElementById('navMessageList');

      if (!badge || !count || !list) return;

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
    const notifDropdown = document.getElementById('navbarNotifications');
    const msgDropdown = document.getElementById('navbarMessages');

    if (notifDropdown) {
      notifDropdown.addEventListener('click', fetchNavNotifications);
    }

    if (msgDropdown) {
      msgDropdown.addEventListener('click', fetchNavMessages);
    }

    // Initial fetch
    fetchNavNotifications();
    fetchNavMessages();

    // Auto-refresh every 60 seconds
    setInterval(fetchNavNotifications, 60000);
    setInterval(fetchNavMessages, 60000);
  });
  </script>

  {{-- Theme Switcher JavaScript --}}
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const themeSwitcher = document.getElementById('themeSwitcher');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    // Check for saved theme preference or default to 'light'
    const currentTheme = localStorage.getItem('theme') || 'light';

    // Apply the current theme on page load
    function applyTheme(theme) {
      if (theme === 'dark') {
        htmlElement.classList.add('dark-style');
        htmlElement.setAttribute('data-theme', 'dark');
        themeIcon.classList.remove('bx-moon');
        themeIcon.classList.add('bx-sun');
      } else {
        htmlElement.classList.remove('dark-style');
        htmlElement.setAttribute('data-theme', 'light');
        themeIcon.classList.remove('bx-sun');
        themeIcon.classList.add('bx-moon');
      }
    }

    // Apply saved theme
    applyTheme(currentTheme);

    // Toggle theme on button click
    if (themeSwitcher) {
      themeSwitcher.addEventListener('click', function() {
        const isDark = htmlElement.classList.contains('dark-style');
        const newTheme = isDark ? 'light' : 'dark';

        // Apply new theme
        applyTheme(newTheme);

        // Save preference
        localStorage.setItem('theme', newTheme);
      });
    }
  });
  </script>
