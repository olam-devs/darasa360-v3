<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .verification-card {
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .card-header {
            background: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid #f0f0f0;
        }

        .card-body {
            padding: 40px 30px;
        }

        .verification-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .verification-icon.success {
            color: #28a745;
        }

        .verification-icon.error {
            color: #dc3545;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-weight: bold;
            color: #6c757d;
        }

        .info-value {
            font-weight: 600;
            color: #212529;
        }

        .verification-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 20px;
        }

        .verification-badge.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .verification-badge.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .back-button {
            margin-top: 30px;
            text-align: center;
        }

        .security-note {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class='bx bx-shield-quarter'></i>
                    Report Card Verification
                </h4>
            </div>

            <div class="card-body text-center">
                @if($valid)
                    {{-- Verified Successfully --}}
                    <div class="verification-icon success">
                        <i class='bx bx-check-circle'></i>
                    </div>

                    <h3 class="mb-3 text-success fw-bold">✓ Verified Authentic</h3>
                    <p class="text-muted mb-4">This report card is genuine and issued by the school.</p>

                    <div class="verification-badge success">
                        <i class='bx bx-check-double'></i> OFFICIAL DOCUMENT
                    </div>

                    <div class="mt-4 text-start">
                        <h5 class="mb-3">Report Card Details:</h5>

                        <div class="info-row">
                            <span class="info-label">Student Name:</span>
                            <span class="info-value">{{ $student->first_name }} {{ $student->last_name }}</span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Registration Number:</span>
                            <span class="info-value">{{ $student->registration_no }}</span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Class:</span>
                            <span class="info-value">{{ $configuration->class->name ?? 'N/A' }}</span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Term:</span>
                            <span class="info-value">{{ $configuration->term }}</span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Academic Year:</span>
                            <span class="info-value">{{ $configuration->academic_year }}</span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Report Status:</span>
                            <span class="info-value">
                                @if($reportCard->status === 'published')
                                    <span class="badge bg-success">Published</span>
                                @elseif($reportCard->status === 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @else
                                    <span class="badge bg-info">{{ ucfirst($reportCard->status) }}</span>
                                @endif
                            </span>
                        </div>

                        @if($reportCard->published_at)
                        <div class="info-row">
                            <span class="info-label">Published Date:</span>
                            <span class="info-value">{{ $reportCard->published_at->format('d F Y') }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="security-note">
                        <i class='bx bx-info-circle'></i>
                        <strong>Security Notice:</strong> This verification confirms that the report card is authentic and was generated by the school's official system. The QR code is unique to this document.
                    </div>

                @else
                    {{-- Verification Failed --}}
                    <div class="verification-icon error">
                        <i class='bx bx-error-circle'></i>
                    </div>

                    <h3 class="mb-3 text-danger fw-bold">✗ Verification Failed</h3>
                    <p class="text-muted mb-4">{{ $message ?? 'This report card could not be verified. It may be forged or the verification link is invalid.' }}</p>

                    <div class="verification-badge error">
                        <i class='bx bx-x-circle'></i> INVALID DOCUMENT
                    </div>

                    <div class="alert alert-danger mt-4 text-start" role="alert">
                        <h5 class="alert-heading">
                            <i class='bx bx-error'></i> Warning!
                        </h5>
                        <p class="mb-0">This report card appears to be invalid or tampered with. Please contact the school administration to verify the authenticity of this document.</p>
                    </div>

                    <div class="security-note">
                        <i class='bx bx-shield-x'></i>
                        <strong>Possible Reasons:</strong>
                        <ul class="text-start mt-2 mb-0">
                            <li>The report card may be forged</li>
                            <li>The QR code or verification link is damaged</li>
                            <li>The report card has been revoked by the school</li>
                            <li>The verification link has expired</li>
                        </ul>
                    </div>
                @endif

                <div class="back-button">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg">
                        <i class='bx bx-arrow-back'></i> Go Back
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <p class="text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                <i class='bx bx-lock-alt'></i> Secured Verification System
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
