@php
    $variant = $variant ?? 'compact';
    $showBack = $showBack ?? true;
    $showHeader = $showHeader ?? true;
    $purpose = $purpose ?? ($variant === 'circle' ? 'registration' : null);
    $auto = $auto ?? ($variant === 'circle');
@endphp
<div class="form-group face-capture-group">
    <div
        class="kyc-modern-card face-capture face-capture-{{ $variant === 'circle' ? 'circle' : 'compact' }}{{ $auto ? ' is-auto' : '' }}"
        data-face-capture
        data-face-liveness="{{ $variant === 'circle' ? '1' : '0' }}"
        @if($auto) data-face-auto="1" data-face-purpose="{{ $purpose }}" @endif
    >
        @if($showHeader)
            <div class="kyc-modern-top">
                @if($showBack)
                    <button type="button" class="kyc-modern-back" aria-label="Back" data-wizard-back>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                @else
                    <span class="kyc-modern-back kyc-modern-back--placeholder" aria-hidden="true"></span>
                @endif
                <div class="kyc-modern-title-wrap">
                    <h3 class="kyc-modern-title">Liveness Check</h3>
                    <div class="kyc-modern-stepper kyc-modern-stepper--labels">
                        <span class="is-done">✓</span>
                        <i></i>
                        <span class="is-active">2</span>
                        <i></i>
                        <span>3</span>
                        <small>ID Card</small>
                        <small class="is-active">Liveness</small>
                        <small>Complete</small>
                    </div>
                </div>
                <div class="kyc-modern-secure">
                    <img src="{{ asset('images/kyc/lock-secure.svg') }}" alt="" width="28" height="28">
                    <div>
                        <strong>Secure &amp; Private</strong>
                        <small>Your data is protected</small>
                    </div>
                </div>
            </div>
        @endif

        <div class="kyc-modern-copy">
            <h4>Let's confirm you're <em>real</em></h4>
            <p>{{ $auto ? 'Camera opens automatically — follow the prompts' : 'Follow the simple steps below' }}</p>
        </div>

        <div class="kyc-live-stage">
            <div class="kyc-live-hints">
                <div class="kyc-live-hint-card">
                    <img src="{{ asset('images/kyc/sun.svg') }}" alt="" width="18" height="18">
                    <strong>Good lighting</strong>
                    <span>Use clear light</span>
                </div>
                <div class="kyc-live-hint-card">
                    <img src="{{ asset('images/kyc/frame.svg') }}" alt="" width="18" height="18">
                    <strong>Center face</strong>
                    <span>Keep face aligned</span>
                </div>
            </div>

            <div class="face-capture-media {{ $variant === 'circle' ? 'is-circle' : '' }}">
                <video class="face-capture-video hidden" data-face-video autoplay playsinline muted></video>
                <canvas class="hidden" data-face-canvas></canvas>
                <img class="face-capture-preview hidden" data-face-preview alt="">
                <div class="face-capture-placeholder" aria-hidden="true">
                    <svg viewBox="0 0 280 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="140" cy="140" r="128" fill="#0b1220" stroke="#A3FF2E" stroke-opacity=".22" stroke-width="2"/>
                        <ellipse cx="140" cy="118" rx="48" ry="58" fill="#1e293b"/>
                        <path d="M78 228c14-30 36-48 62-48s48 18 62 48" fill="#1e293b"/>
                        <ellipse cx="140" cy="118" rx="62" ry="74" stroke="#e2e8f0" stroke-opacity=".75" stroke-dasharray="5 5" stroke-width="2"/>
                        <path d="M96 230c12-24 26-38 44-38s32 14 44 38" stroke="#e2e8f0" stroke-opacity=".75" stroke-dasharray="5 5" stroke-width="2"/>
                    </svg>
                </div>
                <div class="face-capture-ring" aria-hidden="true"></div>
                <div class="face-capture-corners" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                <div class="kyc-capture-badge is-live" data-face-badge hidden><i></i> Face detected</div>
            </div>
        </div>

        <div class="face-liveness-box" data-face-liveness-box @if($auto) hidden @endif>
            <p class="face-liveness-title">Liveness check</p>
            <p class="face-liveness-action" data-face-liveness-action>{{ $auto ? 'Starting camera…' : 'Open camera to begin' }}</p>
            <div class="face-liveness-bar"><span data-face-liveness-bar></span></div>
            <p class="face-liveness-status" data-face-liveness-status>Waiting…</p>
        </div>

        @if($auto)
            <div class="face-liveness-box" data-face-auto-status-box>
                <p class="face-liveness-title">Liveness check</p>
                <p class="face-liveness-action" data-face-auto-action>Preparing verification…</p>
                <div class="face-liveness-bar"><span data-face-auto-bar></span></div>
                <p class="face-liveness-status" data-face-auto-status>Please allow camera access when prompted.</p>
            </div>
        @endif

        <p class="kyc-liveness-heading">LIVENESS STEPS</p>
        <div class="kyc-liveness-steps" data-face-step-indicators>
            <div class="is-current">
                <span class="kyc-step-icon" aria-hidden="true">
                    <img src="{{ asset('images/kyc/face-look.svg') }}" alt="" width="16" height="16">
                </span>
                <b>1</b>
                <small>Look at camera</small>
            </div>
            <div>
                <span class="kyc-step-icon" aria-hidden="true">
                    <img src="{{ asset('images/kyc/face-right.svg') }}" alt="" width="16" height="16">
                </span>
                <b>2</b>
                <small>Turn right</small>
            </div>
            <div>
                <span class="kyc-step-icon" aria-hidden="true">
                    <img src="{{ asset('images/kyc/face-left.svg') }}" alt="" width="16" height="16">
                </span>
                <b>3</b>
                <small>Turn left</small>
            </div>
            <div>
                <span class="kyc-step-icon" aria-hidden="true">
                    <img src="{{ asset('images/kyc/face-blink.svg') }}" alt="" width="16" height="16">
                </span>
                <b>4</b>
                <small>Blink eyes</small>
            </div>
        </div>

        <div class="face-capture-side">
            <input type="hidden" name="face_verification_session" value="{{ old('face_verification_session') }}" data-face-session-input>
            @unless($auto)
                <input type="hidden" name="face_image" value="{{ old('face_image') }}" data-face-input>
            @endunless
            @unless($auto)
                <div class="face-capture-actions">
                    <button type="button" class="btn btn-gold btn-sm" data-face-start>
                        <span class="capture-btn-label-full">Start Liveness Check</span>
                        <span class="capture-btn-label-short">Start</span>
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" data-face-snap disabled>
                        <span class="capture-btn-label-full">Capture Face</span>
                        <span class="capture-btn-label-short">Capture</span>
                    </button>
                    <button type="button" class="btn btn-outline btn-sm hidden" data-face-retake>
                        <span class="capture-btn-label-full">Retake</span>
                        <span class="capture-btn-label-short">Retake</span>
                    </button>
                </div>
            @endunless
            <div class="kyc-accept-note">
                <img src="{{ asset('images/kyc/shield.svg') }}" alt="" width="34" height="34">
                <div>
                    <strong>This helps us keep your account safe and secure</strong>
                    <span>Verification is decided on our servers — not by the browser alone.</span>
                </div>
            </div>
            <p class="auth-field-hint" data-face-hint>{{ $hint ?? ($auto ? 'Follow the prompts — your face is captured automatically when liveness passes.' : 'Use the camera to capture your face.') }}</p>
            @error('face_verification_session')<span class="field-error">{{ $message }}</span>@enderror
        </div>
    </div>
</div>
