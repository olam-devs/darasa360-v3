@extends('layouts.modernLayout', [
    'pageTitle' => 'Settings'
])

@section('title', 'Settings')

@section('content')

@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="bx bx-cog me-2"></i>Application Settings
    </h5>
  </div>
  <div class="card-body">
    {{-- Settings Navigation Tabs --}}
    <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="notifications-tab" data-bs-toggle="tab"
                data-bs-target="#notifications" type="button" role="tab">
          <i class="bx bx-bell me-2"></i>Notifications
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab"
                data-bs-target="#appearance" type="button" role="tab">
          <i class="bx bx-palette me-2"></i>Appearance
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="privacy-tab" data-bs-toggle="tab"
                data-bs-target="#privacy" type="button" role="tab">
          <i class="bx bx-shield me-2"></i>Privacy
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="about-tab" data-bs-toggle="tab"
                data-bs-target="#about" type="button" role="tab">
          <i class="bx bx-info-circle me-2"></i>About
        </button>
      </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
      {{-- Notifications Tab --}}
      <div class="tab-pane fade show active" id="notifications" role="tabpanel">
        <h6 class="mb-3">Notification Preferences</h6>
        <p class="text-muted">Manage how you receive notifications and updates</p>

        <form method="POST" action="{{ route('settings.notifications.update') }}">
          @csrf

          <div class="card border mb-3">
            <div class="card-body">
              <h6 class="card-title">Email Notifications</h6>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="email_notifications"
                       name="email_notifications" {{ $preferences['email_notifications'] ? 'checked' : '' }}>
                <label class="form-check-label" for="email_notifications">
                  Receive email notifications
                </label>
              </div>
              <small class="text-muted">Get updates and important information via email</small>
            </div>
          </div>

          <div class="card border mb-3">
            <div class="card-body">
              <h6 class="card-title">SMS Notifications</h6>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="sms_notifications"
                       name="sms_notifications" {{ $preferences['sms_notifications'] ? 'checked' : '' }}>
                <label class="form-check-label" for="sms_notifications">
                  Receive SMS notifications
                </label>
              </div>
              <small class="text-muted">Get urgent updates via SMS on your registered phone number</small>
            </div>
          </div>

          <div class="card border mb-3">
            <div class="card-body">
              <h6 class="card-title">Academic Notifications</h6>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="result_notifications"
                       name="result_notifications" {{ $preferences['result_notifications'] ? 'checked' : '' }}>
                <label class="form-check-label" for="result_notifications">
                  Results and grades notifications
                </label>
              </div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="announcement_notifications"
                       name="announcement_notifications" {{ $preferences['announcement_notifications'] ? 'checked' : '' }}>
                <label class="form-check-label" for="announcement_notifications">
                  School announcements
                </label>
              </div>
              <small class="text-muted">Stay updated on academic matters and school events</small>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-2"></i>Save Notification Settings
          </button>
        </form>
      </div>

      {{-- Appearance Tab --}}
      <div class="tab-pane fade" id="appearance" role="tabpanel">
        <h6 class="mb-3">Appearance Settings</h6>
        <p class="text-muted">Customize how the application looks</p>

        <form method="POST" action="{{ route('settings.appearance.update') }}">
          @csrf

          <div class="mb-4">
            <label class="form-label">Theme</label>
            <div class="row g-3">
              <div class="col-md-4">
                <div class="form-check card card-body theme-card {{ $preferences['theme'] === 'light' ? 'border-primary' : '' }}">
                  <input class="form-check-input" type="radio" name="theme" id="theme_light"
                         value="light" {{ $preferences['theme'] === 'light' ? 'checked' : '' }}>
                  <label class="form-check-label d-flex align-items-center" for="theme_light">
                    <i class="bx bx-sun bx-md me-2 text-warning"></i>
                    <div>
                      <div class="fw-bold">Light Mode</div>
                      <small class="text-muted">Bright and clear interface</small>
                    </div>
                  </label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check card card-body theme-card {{ $preferences['theme'] === 'dark' ? 'border-primary' : '' }}">
                  <input class="form-check-input" type="radio" name="theme" id="theme_dark"
                         value="dark" {{ $preferences['theme'] === 'dark' ? 'checked' : '' }}>
                  <label class="form-check-label d-flex align-items-center" for="theme_dark">
                    <i class="bx bx-moon bx-md me-2 text-primary"></i>
                    <div>
                      <div class="fw-bold">Dark Mode</div>
                      <small class="text-muted">Easy on the eyes</small>
                    </div>
                  </label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check card card-body theme-card {{ $preferences['theme'] === 'auto' ? 'border-primary' : '' }}">
                  <input class="form-check-input" type="radio" name="theme" id="theme_auto"
                         value="auto" {{ $preferences['theme'] === 'auto' ? 'checked' : '' }}>
                  <label class="form-check-label d-flex align-items-center" for="theme_auto">
                    <i class="bx bx-laptop bx-md me-2 text-info"></i>
                    <div>
                      <div class="fw-bold">Auto</div>
                      <small class="text-muted">Match system preference</small>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label" for="language">Language</label>
            <select class="form-select" id="language" name="language">
              <option value="en" {{ $preferences['language'] === 'en' ? 'selected' : '' }}>English</option>
              <option value="sw" {{ $preferences['language'] === 'sw' ? 'selected' : '' }}>Kiswahili</option>
            </select>
            <small class="text-muted">Choose your preferred language for the interface</small>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-2"></i>Save Appearance Settings
          </button>
        </form>
      </div>

      {{-- Privacy Tab --}}
      <div class="tab-pane fade" id="privacy" role="tabpanel">
        <h6 class="mb-3">Privacy Settings</h6>
        <p class="text-muted">Control your privacy and data sharing preferences</p>

        <form method="POST" action="{{ route('settings.privacy.update') }}">
          @csrf

          <div class="card border mb-3">
            <div class="card-body">
              <h6 class="card-title">Profile Visibility</h6>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="show_email"
                       name="show_email" {{ session('pref_show_email', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="show_email">
                  Show my email address to other users
                </label>
              </div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="show_phone"
                       name="show_phone" {{ session('pref_show_phone', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="show_phone">
                  Show my phone number to other users
                </label>
              </div>
            </div>
          </div>

          <div class="card border mb-3">
            <div class="card-body">
              <h6 class="card-title">Communication</h6>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="allow_messages"
                       name="allow_messages" {{ session('pref_allow_messages', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="allow_messages">
                  Allow others to send me messages
                </label>
              </div>
              <small class="text-muted">Control who can contact you through the system</small>
            </div>
          </div>

          <div class="alert alert-info">
            <i class="bx bx-info-circle me-2"></i>
            Your personal information is secure and will only be shared according to your preferences.
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-2"></i>Save Privacy Settings
          </button>
        </form>
      </div>

      {{-- About Tab --}}
      <div class="tab-pane fade" id="about" role="tabpanel">
        <h6 class="mb-3">About OLAM School Management System</h6>

        <div class="card border mb-3">
          <div class="card-body text-center">
            <img src="{{ asset('assets/images/v1_logo.png') }}" alt="Logo" style="max-width: 100px;" class="mb-3">
            <h5>OLAM School Management System</h5>
            <p class="text-muted">Version 2.0.0</p>
          </div>
        </div>

        <div class="card border mb-3">
          <div class="card-body">
            <h6 class="card-title">System Information</h6>
            <div class="row">
              <div class="col-md-6 mb-2">
                <small class="text-muted">School Name</small>
                <p class="mb-0">{{ session('school_name', 'N/A') }}</p>
              </div>
              <div class="col-md-6 mb-2">
                <small class="text-muted">Your Role</small>
                <p class="mb-0">{{ ucfirst(session('role', 'User')) }}</p>
              </div>
              <div class="col-md-6 mb-2">
                <small class="text-muted">Registration Number</small>
                <p class="mb-0">{{ session('registration_no', 'N/A') }}</p>
              </div>
              <div class="col-md-6 mb-2">
                <small class="text-muted">Session Status</small>
                <p class="mb-0"><span class="badge bg-success">Active</span></p>
              </div>
            </div>
          </div>
        </div>

        <div class="card border mb-3">
          <div class="card-body">
            <h6 class="card-title">Features</h6>
            <ul class="list-unstyled mb-0">
              <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Student & Staff Management</li>
              <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Academic Records & Results</li>
              <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Attendance Tracking</li>
              <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Communication Tools</li>
              <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Report Card Generation</li>
              <li class="mb-2"><i class="bx bx-check text-success me-2"></i>SMS & Email Notifications</li>
            </ul>
          </div>
        </div>

        <div class="card border">
          <div class="card-body">
            <h6 class="card-title">Support</h6>
            <p class="mb-2">Need help? Contact our support team:</p>
            <p class="mb-1"><i class="bx bx-envelope me-2"></i>support@olam.com</p>
            <p class="mb-0"><i class="bx bx-phone me-2"></i>+254 700 000 000</p>
          </div>
        </div>

        <div class="text-center mt-4">
          <small class="text-muted">
            &copy; {{ date('Y') }} OLAM School Management System. All rights reserved.
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.theme-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.theme-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.theme-card.border-primary {
  border-width: 2px !important;
}

.nav-tabs .nav-link {
  color: #6c757d;
}

.nav-tabs .nav-link.active {
  font-weight: 600;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Theme card click handling with instant preview
  const themeCards = document.querySelectorAll('.theme-card');
  const themeRadios = document.querySelectorAll('input[name="theme"]');

  // Set initial state based on current theme
  const currentTheme = window.getCurrentTheme();
  updateThemeCardBorders(currentTheme);

  themeCards.forEach(card => {
    card.addEventListener('click', function() {
      const radio = this.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
        const themeValue = radio.value;

        // Update border
        updateThemeCardBorders(themeValue);

        // Apply theme instantly
        applyTheme(themeValue);
      }
    });
  });

  // Also listen to radio button changes
  themeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      updateThemeCardBorders(this.value);
      applyTheme(this.value);
    });
  });

  function updateThemeCardBorders(themeValue) {
    themeCards.forEach(c => c.classList.remove('border-primary'));
    const selectedCard = document.querySelector(`#theme_${themeValue}`)?.closest('.theme-card');
    if (selectedCard) {
      selectedCard.classList.add('border-primary');
    }
  }

  function applyTheme(themeValue) {
    const htmlElement = document.documentElement;

    if (themeValue === 'auto') {
      // Check system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const newTheme = prefersDark ? 'dark' : 'light';
      setTheme(newTheme);
      localStorage.setItem('theme', 'auto'); // Save the preference
      localStorage.setItem('effectiveTheme', newTheme); // Save what's actually applied
    } else {
      setTheme(themeValue);
      localStorage.setItem('theme', themeValue);
      localStorage.setItem('effectiveTheme', themeValue);
    }
  }

  function setTheme(theme) {
    const htmlElement = document.documentElement;

    // Update data-theme attribute
    htmlElement.setAttribute('data-theme', theme);

    // Update class
    if (theme === 'dark') {
      htmlElement.classList.remove('light-style');
      htmlElement.classList.add('dark-style');
    } else {
      htmlElement.classList.remove('dark-style');
      htmlElement.classList.add('light-style');
    }

    // Dispatch event
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: theme } }));
  }

  // Listen for system theme changes if auto is selected
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const savedPreference = localStorage.getItem('theme');
    if (savedPreference === 'auto') {
      const newTheme = e.matches ? 'dark' : 'light';
      setTheme(newTheme);
      localStorage.setItem('effectiveTheme', newTheme);
    }
  });
});
</script>

@endsection
