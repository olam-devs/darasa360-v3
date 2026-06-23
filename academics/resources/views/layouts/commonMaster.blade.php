<!DOCTYPE html>

<html class="light-style layout-menu-fixed" data-theme="light" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{url('/')}}" data-framework="laravel" data-template="vertical-menu-laravel-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title> Darasa360 </title>

  <!-- Theme Loader Script - Must run before page renders -->
  <script>
    (function() {
      // Get saved theme from localStorage
      let savedTheme = localStorage.getItem('theme') || 'light';
      const htmlElement = document.documentElement;
      let effectiveTheme = savedTheme;

      // Handle auto theme based on system preference
      if (savedTheme === 'auto') {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        effectiveTheme = prefersDark ? 'dark' : 'light';
      }

      // Apply theme immediately
      htmlElement.setAttribute('data-theme', effectiveTheme);

      // Update class for compatibility
      if (effectiveTheme === 'dark') {
        htmlElement.classList.remove('light-style');
        htmlElement.classList.add('dark-style');
      }
    })();
  </script>
  <meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon/update4.ico') }}">
  <!-- Include Styles -->
  @include('layouts/sections/styles')

  <!-- Include Scripts for customizer, helper, analytics, config -->
  @include('layouts/sections/scriptsIncludes')
</head>

<body>

  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  <!-- Include Scripts -->
  @include('layouts/sections/scripts')

  <!-- Theme Switcher Global Script -->
  <script>
    // Global theme switcher function
    window.toggleTheme = function() {
      const htmlElement = document.documentElement;
      const currentTheme = htmlElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

      // Update data-theme attribute
      htmlElement.setAttribute('data-theme', newTheme);

      // Update class for compatibility
      if (newTheme === 'dark') {
        htmlElement.classList.remove('light-style');
        htmlElement.classList.add('dark-style');
      } else {
        htmlElement.classList.remove('dark-style');
        htmlElement.classList.add('light-style');
      }

      // Save to localStorage
      localStorage.setItem('theme', newTheme);

      // Dispatch custom event for components that need to react
      window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));

      return newTheme;
    };

    // Get current theme
    window.getCurrentTheme = function() {
      return document.documentElement.getAttribute('data-theme') || 'light';
    };
  </script>

</body>

</html>
