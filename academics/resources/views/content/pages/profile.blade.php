@extends('layouts.modernLayout', [
    'pageTitle' => 'My Profile'
])

@section('title', 'Profile')

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

<div class="row">
  {{-- Profile Overview Card --}}
  <div class="col-lg-4 mb-4">
    <div class="card">
      <div class="card-body text-center">
        <div class="profile-picture-wrapper mb-3">
          <div class="avatar avatar-xl mx-auto">
            @if(!empty($user->profile_picture) && file_exists(public_path('uploads/profiles/' . $user->profile_picture)))
              <img src="{{ asset('uploads/profiles/' . $user->profile_picture) }}" alt="Profile" class="rounded-circle" id="profileImage">
            @else
              <img src="{{ asset('assets/img/avatars/' . (strtolower($user->gender ?? session('gender', 'male')) === 'female' ? 'female.png' : 'male.png')) }}"
                   alt="Profile" class="rounded-circle" id="profileImage">
            @endif
          </div>
          <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#uploadPictureModal">
            <i class="bx bx-camera"></i> Change Photo
          </button>
        </div>

        <h4 class="mb-1">{{ $user->username ?? 'User' }}</h4>
        <p class="text-muted mb-1">{{ ucfirst($user->role_name ?? $user->class_name ?? session('role', 'User')) }}</p>
        <p class="text-muted small">{{ $user->registration_no ?? session('registration_no', 'N/A') }}</p>

        <hr class="my-3">

        <div class="d-flex justify-content-around text-center">
          <div>
            <h6 class="mb-0">{{ !empty($user->email) ? 'Verified' : 'Not Set' }}</h6>
            <small class="text-muted">Email</small>
          </div>
          <div>
            <h6 class="mb-0">{{ !empty($user->phone) ? 'Added' : 'Not Set' }}</h6>
            <small class="text-muted">Phone</small>
          </div>
        </div>
      </div>
    </div>

    {{-- Quick Stats Card --}}
    <div class="card mt-4">
      <div class="card-body">
        <h6 class="card-title mb-3">
          <i class="bx bx-info-circle me-2"></i>Account Information
        </h6>
        <div class="mb-2">
          <small class="text-muted">Member Since</small>
          <p class="mb-0">{{ !empty($user->created_at) ? date('M d, Y', strtotime($user->created_at)) : 'N/A' }}</p>
        </div>
        <div class="mb-2">
          <small class="text-muted">Last Updated</small>
          <p class="mb-0">{{ !empty($user->updated_at) ? date('M d, Y', strtotime($user->updated_at)) : 'N/A' }}</p>
        </div>
        <div>
          <small class="text-muted">Account Status</small>
          <p class="mb-0">
            <span class="badge bg-success">Active</span>
          </p>
        </div>
      </div>
    </div>
  </div>

  {{-- Profile Edit Forms --}}
  <div class="col-lg-8">
    {{-- Personal Information Card --}}
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
          <i class="bx bx-user me-2"></i>Personal Information
        </h5>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('profile.update') }}">
          @csrf
          @method('PUT')

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="username">Full Name</label>
              <input type="text" class="form-control" id="username" name="username"
                     value="{{ old('username', $user->username ?? '') }}" required>
              @error('username')
                <small class="text-danger">{{ $message }}</small>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="registration_no">Registration Number</label>
              <input type="text" class="form-control" id="registration_no"
                     value="{{ $user->registration_no ?? 'N/A' }}" disabled>
              <small class="text-muted">Registration number cannot be changed</small>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="email">Email Address</label>
              <input type="email" class="form-control" id="email" name="email"
                     value="{{ old('email', $user->email ?? '') }}">
              @error('email')
                <small class="text-danger">{{ $message }}</small>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="phone">Phone Number</label>
              <input type="text" class="form-control" id="phone" name="phone"
                     value="{{ old('phone', $user->phone ?? '') }}">
              @error('phone')
                <small class="text-danger">{{ $message }}</small>
              @enderror
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $user->address ?? '') }}</textarea>
            @error('address')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="gender">Gender</label>
              <input type="text" class="form-control" id="gender"
                     value="{{ ucfirst($user->gender ?? session('gender', 'Not Set')) }}" disabled>
            </div>
            @if(isset($userType) && $userType === 'student')
            <div class="col-md-6">
              <label class="form-label" for="class">Class</label>
              <input type="text" class="form-control" id="class"
                     value="{{ $user->class_name ?? 'Not Assigned' }}" disabled>
            </div>
            @else
            <div class="col-md-6">
              <label class="form-label" for="role">Role</label>
              <input type="text" class="form-control" id="role"
                     value="{{ ucfirst($user->role_name ?? session('role', 'Not Set')) }}" disabled>
            </div>
            @endif
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i>Save Changes
            </button>
            <button type="reset" class="btn btn-outline-secondary">
              <i class="bx bx-reset me-2"></i>Reset
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Change Password Card --}}
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bx bx-lock-alt me-2"></i>Change Password
        </h5>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('profile.password.change') }}">
          @csrf

          <div class="alert alert-info">
            <i class="bx bx-info-circle me-2"></i>
            For security reasons, please use a strong password with at least 6 characters.
          </div>

          <div class="mb-3">
            <label class="form-label" for="current_password">Current Password</label>
            <input type="password" class="form-control" id="current_password"
                   name="current_password" required>
            @error('current_password')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label" for="new_password">New Password</label>
            <input type="password" class="form-control" id="new_password"
                   name="new_password" required>
            @error('new_password')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
            <input type="password" class="form-control" id="new_password_confirmation"
                   name="new_password_confirmation" required>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bx bx-key me-2"></i>Update Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Upload Picture Modal --}}
<div class="modal fade" id="uploadPictureModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bx bx-image-add me-2"></i>Upload Profile Picture
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="uploadPictureForm" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label">Choose Image</label>
            <input type="file" class="form-control" id="profile_picture"
                   name="profile_picture" accept="image/*" required>
            <small class="text-muted">Maximum file size: 2MB. Accepted formats: JPG, PNG, JPEG</small>
          </div>
          <div id="imagePreview" class="text-center mb-3" style="display: none;">
            <img src="" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="uploadBtn">
          <i class="bx bx-upload me-2"></i>Upload
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.avatar-xl {
  width: 120px;
  height: 120px;
}

.avatar-xl img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-picture-wrapper {
  position: relative;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Image preview
  const pictureInput = document.getElementById('profile_picture');
  const imagePreview = document.getElementById('imagePreview');

  if (pictureInput) {
    pictureInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          imagePreview.querySelector('img').src = e.target.result;
          imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Upload button
  const uploadBtn = document.getElementById('uploadBtn');
  if (uploadBtn) {
    uploadBtn.addEventListener('click', function() {
      const formData = new FormData(document.getElementById('uploadPictureForm'));

      uploadBtn.disabled = true;
      uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';

      fetch('{{ route("profile.picture.upload") }}', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('profileImage').src = data.image_url;
          bootstrap.Modal.getInstance(document.getElementById('uploadPictureModal')).hide();

          // Show success message
          const alert = document.createElement('div');
          alert.className = 'alert alert-success alert-dismissible fade show';
          alert.innerHTML = `
            <i class="bx bx-check-circle me-2"></i>${data.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          `;
          const contentArea = document.querySelector('.modern-content') || document.querySelector('.content');
          if (contentArea) {
            contentArea.insertBefore(alert, contentArea.firstChild);
          }

          setTimeout(() => alert.remove(), 5000);
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        alert('Upload failed: ' + error);
      })
      .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="bx bx-upload me-2"></i>Upload';
      });
    });
  }
});
</script>

@endsection
