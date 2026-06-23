@extends('layouts/contentNavbarLayout')

@section('title', 'System Logs')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Activity Logs</h5>
        <div>
          <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
            <i class='bx bx-refresh'></i> Refresh
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Browser</th>
                <th>OS</th>
                <th>Device</th>
                <th>Location</th>
                <th>IP Address</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              @forelse($logs ?? [] as $log)
              <tr>
                <td class="text-nowrap">
                  <small>{{ $log->logged_at ? \Carbon\Carbon::parse($log->logged_at)->format('Y-m-d H:i:s') : ($log->created_at ?? 'N/A') }}</small>
                </td>
                <td>
                  <div class="text-truncate" style="max-width: 150px;">
                    <strong>{{ $log->user_name ?? 'System' }}</strong>
                    @if($log->role)
                      <br><small class="text-muted">{{ ucfirst(str_replace('_', ' ', $log->role)) }}</small>
                    @endif
                  </div>
                </td>
                <td>
                  <span class="badge
                    @if($log->level === 'error') bg-danger
                    @elseif($log->level === 'warning') bg-warning
                    @elseif($log->level === 'info') bg-info
                    @else bg-secondary
                    @endif">
                    {{ strtoupper($log->level ?? 'N/A') }}
                  </span>
                  <br><small class="text-muted">{{ $log->method ?? 'N/A' }}</small>
                </td>
                <td>
                  @if($log->browser)
                    <i class='bx
                      @if(str_contains(strtolower($log->browser), 'chrome')) bx-chrome text-warning
                      @elseif(str_contains(strtolower($log->browser), 'firefox')) bx-firefox text-danger
                      @elseif(str_contains(strtolower($log->browser), 'safari')) bx-compass text-info
                      @elseif(str_contains(strtolower($log->browser), 'edge')) bx-browser text-primary
                      @else bx-globe
                      @endif'></i>
                    {{ $log->browser }}
                    @if($log->browser_version)
                      <br><small class="text-muted">v{{ $log->browser_version }}</small>
                    @endif
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>
                  @if($log->platform)
                    <i class='bx
                      @if(str_contains(strtolower($log->platform), 'windows')) bx-windows text-primary
                      @elseif(str_contains(strtolower($log->platform), 'mac')) bx-apple text-secondary
                      @elseif(str_contains(strtolower($log->platform), 'linux')) bx-terminal text-success
                      @elseif(str_contains(strtolower($log->platform), 'android')) bx-android text-success
                      @elseif(str_contains(strtolower($log->platform), 'ios')) bx-apple text-secondary
                      @else bx-desktop
                      @endif'></i>
                    {{ $log->platform }}
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>
                  @if($log->device_type)
                    <i class='bx
                      @if($log->device_type === 'mobile') bx-mobile-alt
                      @elseif($log->device_type === 'tablet') bx-tablet
                      @elseif($log->device_type === 'desktop') bx-desktop
                      @else bx-devices
                      @endif'></i>
                    {{ ucfirst($log->device_type) }}
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>
                  @if($log->city || $log->country)
                    <i class='bx bx-map'></i>
                    {{ $log->city }}{{ $log->city && $log->country ? ', ' : '' }}{{ $log->country }}
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td><code>{{ $log->ip ?? 'N/A' }}</code></td>
                <td>
                  <button class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#logDetail{{ $log->id }}">
                    <i class='bx bx-info-circle'></i>
                  </button>
                </td>
              </tr>

              <!-- Log Detail Modal -->
              <div class="modal fade" id="logDetail{{ $log->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Log Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <dl class="row">
                        <dt class="col-sm-3">Timestamp</dt>
                        <dd class="col-sm-9">{{ $log->logged_at ?? $log->created_at }}</dd>

                        <dt class="col-sm-3">User</dt>
                        <dd class="col-sm-9">{{ $log->user_name ?? 'System' }} (ID: {{ $log->user_id ?? 'N/A' }})</dd>

                        <dt class="col-sm-3">Registration No</dt>
                        <dd class="col-sm-9">{{ $log->registration_no ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Role</dt>
                        <dd class="col-sm-9">{{ $log->role ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">School</dt>
                        <dd class="col-sm-9">{{ $log->school_name ?? 'N/A' }} ({{ $log->school_code ?? 'N/A' }})</dd>

                        <dt class="col-sm-3">URL</dt>
                        <dd class="col-sm-9"><code>{{ $log->url ?? 'N/A' }}</code></dd>

                        <dt class="col-sm-3">Message</dt>
                        <dd class="col-sm-9">{{ $log->message ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Browser</dt>
                        <dd class="col-sm-9">{{ $log->browser ?? 'N/A' }} {{ $log->browser_version ?? '' }}</dd>

                        <dt class="col-sm-3">Operating System</dt>
                        <dd class="col-sm-9">{{ $log->platform ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Device Type</dt>
                        <dd class="col-sm-9">{{ $log->device_type ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Location</dt>
                        <dd class="col-sm-9">{{ $log->city ?? 'N/A' }}, {{ $log->country ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">IP Address</dt>
                        <dd class="col-sm-9"><code>{{ $log->ip ?? 'N/A' }}</code></dd>

                        <dt class="col-sm-3">User Agent</dt>
                        <dd class="col-sm-9"><small>{{ $log->user_agent ?? 'N/A' }}</small></dd>

                        @if($log->context)
                        <dt class="col-sm-3">Context</dt>
                        <dd class="col-sm-9">
                          <pre class="bg-light p-2 rounded">{{ json_encode(json_decode($log->context), JSON_PRETTY_PRINT) }}</pre>
                        </dd>
                        @endif
                      </dl>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>
              @empty
              <tr>
                <td colspan="9" class="text-center">
                  <p class="mb-0">No logs available</p>
                  <small class="text-muted">Activity logs will appear here</small>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h6>How to Use Logging in Your Code</h6>
        <pre class="bg-light p-3 rounded"><code>use App\Services\LoggingService;

// Log an action
LoggingService::log(
    level: 'info',        // info, warning, error
    method: 'POST',       // HTTP method
    url: '/schools/create',
    message: 'School created successfully',
    context: ['school_id' => $school->id],
    userId: auth()->id()  // optional
);</code></pre>
      </div>
    </div>
  </div>
</div>

@endsection
