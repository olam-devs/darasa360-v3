@extends('layouts/blankLayout')

@section('title', '')

@section('page-style')
@vite([
  'resources/assets/vendor/scss/pages/page-auth.scss'
])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
      <!-- Login -->
      <div class="card px-sm-6 px-0">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center">
            <a href="{{url('/')}}" class="app-brand-link gap-2">
              <span class="app-brand-logo demo">
                <img src="{{ asset('assets/images/v1_logo.png') }}" alt="Logo" width="50">
              </span>

              <span class="app-brand-text demo text-heading fw-bold">{{config('variables.templateName')}}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1">Welcome Back 👋</h4>
          <p class="mb-6">Please sign-in to your account</p>

          <!-- Display validation errors -->
          @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          <form id="formAuthentication" class="mb-6" action="{{ url('/login') }}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="registration_no" class="form-label">Registration Number</label>
              <input type="text"
                     class="form-control @error('registration_no') is-invalid @enderror"
                     id="registration_no"
                     name="registration_no"
                     value="{{ old('registration_no') }}"
                     placeholder="Enter your registration number"
                     required autofocus>
              @error('registration_no')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password">Password</label>
              <div class="input-group input-group-merge">
                <input type="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password"
                       required
                       placeholder="••••••••••••" />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
              @error('password')
              <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
            </div>
          </form>

          {{-- <p class="text-center">
            <span>New on our platform?</span>
            <a href="{{url('auth/register-basic')}}">
              <span>Create an account</span>
            </a>
          </p> --}}
        </div>
      </div>
    </div>
    <!-- /Login -->
  </div>
</div>
@endsection
