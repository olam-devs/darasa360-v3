{{-- Modern Sidebar Navigation --}}
<aside id="layout-menu" class="modern-sidebar" data-modern-sidebar>

  {{-- Sidebar Header --}}
  <div class="modern-sidebar-header">
    <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none">
      <img src="{{ asset('assets/images/v1_logo.png') }}" alt="Logo" class="modern-sidebar-logo">
      <h1 class="modern-sidebar-title">{{ config('variables.templateName') }}</h1>
    </a>
  </div>

  {{-- Sidebar Navigation --}}
  <nav class="modern-sidebar-nav">
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
    <div class="modern-menu-section">
      @foreach ($menuData[0]->menu as $menu)

        {{-- Menu Section Header --}}
        @if (isset($menu->menuHeader))
          @if (!$loop->first)
            </div>
            <div class="modern-menu-section">
          @endif
          <h6 class="modern-menu-section-title">{{ __($menu->menuHeader) }}</h6>

        @else
          {{-- Menu Item --}}
          @php
            $activeClass = '';
            $currentPath = request()->path();

            // Check if current menu item is active
            if (isset($menu->url) && trim($menu->url, '/') === $currentPath) {
              $activeClass = 'active';
            } elseif (isset($menu->submenu)) {
              foreach ($menu->submenu as $submenu) {
                if (isset($submenu->url) && trim($submenu->url, '/') === $currentPath) {
                  $activeClass = 'active';
                  break;
                }
              }
            }

            // Extract icon class for modern icons
            $iconClass = $menu->icon ?? 'bx bx-circle';
            // Remove 'menu-icon tf-icons' prefix if present
            $iconClass = str_replace(['menu-icon', 'tf-icons'], '', $iconClass);
            $iconClass = trim($iconClass);
          @endphp

          <div class="modern-menu-item">
            <a href="{{ url($menu->url) }}"
               class="modern-menu-link {{ $activeClass }}"
               title="{{ $menu->name }}">
              <span class="modern-menu-icon">
                <i class="{{ $iconClass }}"></i>
              </span>
              <span class="modern-menu-text">{{ $menu->name }}</span>

              {{-- Badge for notifications (if needed) --}}
              @if (isset($menu->badge))
                <span class="modern-menu-badge">{{ $menu->badge }}</span>
              @endif
            </a>

            {{-- Submenu (if exists) --}}
            @if (isset($menu->submenu))
              <div class="modern-submenu {{ $activeClass ? 'open' : '' }}">
                @foreach ($menu->submenu as $submenu)
                  @php
                    $submenuActive = '';
                    if (isset($submenu->url) && trim($submenu->url, '/') === $currentPath) {
                      $submenuActive = 'active';
                    }

                    $submenuIconClass = $submenu->icon ?? 'bx bx-circle';
                    $submenuIconClass = str_replace(['menu-icon', 'tf-icons'], '', $submenuIconClass);
                    $submenuIconClass = trim($submenuIconClass);
                  @endphp

                  <a href="{{ url($submenu->url) }}"
                     class="modern-menu-link modern-submenu-link {{ $submenuActive }}"
                     title="{{ $submenu->name }}">
                    <span class="modern-menu-icon">
                      <i class="{{ $submenuIconClass }}"></i>
                    </span>
                    <span class="modern-menu-text">{{ $submenu->name }}</span>
                  </a>
                @endforeach
              </div>
            @endif
          </div>

        @endif
      @endforeach
    </div>
    </ul>
  </nav>

  {{-- Sidebar Footer / User Profile --}}
  <div class="modern-sidebar-footer">
    <div class="modern-user-profile">
      <div class="modern-user-avatar">
        @if(session('profile_picture') && file_exists(public_path('uploads/profiles/' . session('profile_picture'))))
          <img
            src="{{ asset('uploads/profiles/' . session('profile_picture')) }}"
            alt="avatar"
            style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
          >
        @else
          {{ strtoupper(substr(session('username', 'U'), 0, 2)) }}
        @endif
      </div>
      <div class="modern-user-info">
        <p class="modern-user-name">{{ session('username', 'User') }}</p>
        <p class="modern-user-role">{{ session('role', 'User') }}</p>
      </div>
      <button class="modern-icon-button" onclick="event.stopPropagation(); window.location.href='/logout'" title="Logout">
        <i class="bx bx-log-out"></i>
      </button>
    </div>
  </div>

</aside>

{{-- Submenu Styles (if not in main CSS) --}}
<style>
.modern-submenu {
  display: none;
  padding-left: var(--spacing-md);
  margin-top: 4px;
}

.modern-submenu.open {
  display: block;
}

.modern-submenu-link {
  font-size: 0.875rem;
  padding: 8px var(--spacing-md);
}

.modern-submenu-link .modern-menu-icon {
  font-size: 16px;
  opacity: 0.7;
}
</style>

{{-- Sidebar Toggle Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.querySelector('[data-modern-sidebar]');
  const toggleBtn = document.querySelector('[data-sidebar-toggle]');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      // Save state to localStorage
      localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    });
  }

  // Restore sidebar state
  const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
  if (isCollapsed && sidebar) {
    sidebar.classList.add('collapsed');
  }

  // Handle submenu toggles
  const menuLinks = document.querySelectorAll('.modern-menu-link');
  menuLinks.forEach(link => {
    if (link.nextElementSibling && link.nextElementSibling.classList.contains('modern-submenu')) {
      link.addEventListener('click', function(e) {
        // Always prevent navigation for menu items with submenus
        e.preventDefault();

        const submenu = this.nextElementSibling;

        // Close all other submenus
        document.querySelectorAll('.modern-submenu').forEach(sm => {
          if (sm !== submenu) sm.classList.remove('open');
        });

        // Toggle this submenu
        submenu.classList.toggle('open');
      });
    }
  });
});
</script>
