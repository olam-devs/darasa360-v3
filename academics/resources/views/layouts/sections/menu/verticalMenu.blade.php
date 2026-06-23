<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="{{ asset('assets/images/v1_logo.png') }}" alt="Logo" width="40">
      </span>
      <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('variables.templateName') }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
      <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)

      {{-- Uncomment for debugging --}}
      {{--
      @php
        // dd($menu->name, gettype($menu->name));
      @endphp
      --}}

      {{-- menu headers --}}
      @if (isset($menu->menuHeader))
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">{{ is_string($menu->menuHeader) ? __($menu->menuHeader) : json_encode($menu->menuHeader) }}</span>
        </li>
      @else

        {{-- active menu method --}}
        @php
  $activeClass = null;
  $currentPath = request()->path(); // e.g. "admins/dashboard"

  if (isset($menu->url) && trim($menu->url, '/') === $currentPath) {
    // exact match
    $activeClass = 'active';
  } elseif (isset($menu->submenu)) {
    // check inside submenu
    foreach ($menu->submenu as $submenu) {
      if (isset($submenu->url) && trim($submenu->url, '/') === $currentPath) {
        $activeClass = 'active open';
        break;
      }
    }
  }
@endphp


<li class="menu-item {{ $activeClass }}">
  <a href="{{ url($menu->url) }}"
     class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}">
    <i class="{{ $menu->icon }}"></i>
    <div>{{ $menu->name }}</div>
  </a>

  @isset($menu->submenu)
    @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
  @endisset
</li>

      @endif
    @endforeach
  </ul>

  {{-- Sidebar Footer / User Profile --}}
  <div class="menu-footer">
    <div class="user-profile-card">
      <div class="d-flex align-items-center w-100">
        <div class="flex-shrink-0 me-3">
          <div class="avatar avatar-sm">
            @if(session('profile_picture') && file_exists(public_path('uploads/profiles/' . session('profile_picture'))))
              <img
                src="{{ asset('uploads/profiles/' . session('profile_picture')) }}"
                alt="avatar"
                class="rounded-circle"
              >
            @else
              <img
                src="{{ asset('assets/img/avatars/' . (strtolower(session('gender')) === 'female' ? 'female.png' : 'male.png')) }}"
                alt="avatar"
                class="rounded-circle"
              >
            @endif
          </div>
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <h6 class="mb-0 text-truncate small">{{ session('username', 'User') }}</h6>
          <small class="text-muted text-truncate d-block">{{ session('role', 'User') }}</small>
        </div>
        <div class="flex-shrink-0 ms-2">
          <a href="{{ route('logout') }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Logout">
            <i class="bx bx-log-out"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

</aside>

{{-- Menu Footer Styles --}}
<style>
.menu-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1rem;
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  background: inherit;
}

.layout-menu.bg-menu-theme .menu-footer {
  border-top-color: rgba(255, 255, 255, 0.1);
}

.user-profile-card {
  padding: 0.75rem;
  border-radius: 0.375rem;
  background: rgba(0, 0, 0, 0.03);
  transition: all 0.2s;
}

.layout-menu.bg-menu-theme .user-profile-card {
  background: rgba(255, 255, 255, 0.05);
}

.user-profile-card:hover {
  background: rgba(0, 0, 0, 0.05);
}

.layout-menu.bg-menu-theme .user-profile-card:hover {
  background: rgba(255, 255, 255, 0.08);
}

.user-profile-card .avatar {
  width: 38px;
  height: 38px;
}

.user-profile-card h6 {
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.2;
}

.user-profile-card small {
  font-size: 0.75rem;
  line-height: 1.2;
}

.user-profile-card .btn-icon {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Adjust menu inner to account for footer */
.menu-inner {
  height: calc(100% - 100px);
  overflow-y: auto;
  overflow-x: hidden;
}

/* Scrollbar styles */
.menu-inner::-webkit-scrollbar {
  width: 6px;
}

.menu-inner::-webkit-scrollbar-track {
  background: transparent;
}

.menu-inner::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.layout-menu.bg-menu-theme .menu-inner::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
}

.menu-inner::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

.layout-menu.bg-menu-theme .menu-inner::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Hide arrow/chevron on closed menu items with submenus */
.menu-item:not(.open) > .menu-link.menu-toggle::after {
  display: none !important;
}

/* Show arrow/chevron only on open menu items */
.menu-item.open > .menu-link.menu-toggle::after {
  display: block !important;
}
</style>

{{-- Enable click events for desktop devices --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Get the menu instance
  const layoutMenuEl = document.querySelector('#layout-menu');

  if (layoutMenuEl && !window.Helpers.isMobileDevice()) {
    // For desktop devices, manually bind click events since Menu.js only does this for mobile
    layoutMenuEl.addEventListener('click', function(e) {
      // Find if the clicked element is a menu-toggle or within one
      const toggleLink = e.target.classList.contains('menu-toggle')
        ? e.target
        : e.target.closest('.menu-toggle');

      if (toggleLink) {
        e.preventDefault();

        // Get the menu instance and call toggle
        const menu = window.Helpers.mainMenu;
        if (menu) {
          menu.toggle(toggleLink);
        }
      }
    });
  }
});
</script>

