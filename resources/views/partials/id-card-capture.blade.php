@php
    $variant = $variant ?? 'compact';
    $showHeader = $showHeader ?? true;
@endphp
<div class="form-group id-card-capture-group">
    <div class="kyc-modern-card id-card-capture id-card-capture-{{ $variant === 'square' ? 'square' : 'compact' }}" data-id-card-capture>
        @if($showHeader)
            <div class="kyc-modern-top">
                <button type="button" class="kyc-modern-back" aria-label="Back" data-wizard-back>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
                </button>

                <div class="kyc-modern-title-wrap">
                    <h3 class="kyc-modern-title">Document Capture</h3>
                    <div class="kyc-modern-stepper kyc-modern-stepper--labels" aria-label="Document capture steps">
                        <span class="is-active">1</span>
                        <i></i>
                        <span>2</span>
                        <i></i>
                        <span>3</span>
                        <small class="is-active">ID Card</small>
                        <small>Selfie</small>
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
            <h4>Capture <em>Front</em> of ID Card</h4>
            <p>Position your ID card inside the frame</p>
        </div>

        <div class="id-card-capture-media {{ $variant === 'square' ? 'is-square' : '' }}">
            <video class="id-card-capture-video" data-id-card-video autoplay playsinline muted></video>
            <canvas class="hidden" data-id-card-canvas></canvas>
            <img class="id-card-capture-preview hidden" data-id-card-preview alt="ID card preview">

            <div class="id-card-capture-corners" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="id-card-capture-frame" aria-hidden="true"></div>
            <img class="id-card-guide" src="{{ asset('images/kyc/id-guide.svg') }}" alt="" data-id-card-guide>

            <div class="kyc-capture-badge is-live" data-id-card-badge>
                <i></i> Auto-detect enabled
            </div>

            <div class="kyc-media-tip">
                <img src="{{ asset('images/kyc/sun.svg') }}" alt="" width="14" height="14">
                <span>Good lighting helps us verify you faster</span>
            </div>
        </div>

        <div class="kyc-modern-info-grid kyc-modern-info-grid--doc">
            <div>
                <img src="{{ asset('images/kyc/sun.svg') }}" alt="" width="20" height="20">
                <div>
                    <strong>Use good lighting</strong>
                    <span>Avoid glare and shadows</span>
                </div>
            </div>
            <div>
                <img src="{{ asset('images/kyc/frame.svg') }}" alt="" width="20" height="20">
                <div>
                    <strong>Place ID inside</strong>
                    <span>the frame; all corners visible</span>
                </div>
            </div>
            <div>
                <img src="{{ asset('images/kyc/hand.svg') }}" alt="" width="20" height="20">
                <div>
                    <strong>Hold steady</strong>
                    <span>Tap capture. Retake anytime</span>
                </div>
            </div>
        </div>

        <div class="id-card-capture-side">
            <input type="hidden" value="{{ old('id_card_image') }}" data-id-card-input>
            <input type="hidden" name="id_staging_token" value="{{ old('id_staging_token') }}" data-id-staging-input>
            <input type="file" accept="image/*" class="hidden" data-id-card-file>

            <div class="id-card-capture-actions kyc-doc-actions">
                <button type="button" class="kyc-doc-side-btn" data-id-card-gallery>
                    <img src="{{ asset('images/kyc/gallery.svg') }}" alt="" width="20" height="20">
                    <span>Gallery</span>
                </button>

                <button type="button" class="kyc-capture-main" data-id-card-snap aria-label="Capture ID Card">
                    <img src="{{ asset('images/kyc/camera.svg') }}" alt="" width="22" height="22">
                </button>

                <button type="button" class="kyc-doc-side-btn kyc-doc-flash" disabled>
                    <img src="{{ asset('images/kyc/flash.svg') }}" alt="" width="18" height="18">
                    <span>Flash Auto</span>
                </button>
                <button type="button" class="hidden" data-id-card-start aria-hidden="true"></button>

                <button type="button" class="btn btn-outline btn-sm hidden kyc-doc-retake" data-id-card-retake>
                    Retake
                </button>
            </div>

            <div class="kyc-accept-note">
                <img src="{{ asset('images/kyc/shield.svg') }}" alt="" width="34" height="34">
                <div>
                    <strong>We accept all government <em>issued ID cards</em></strong>
                    <span>National ID, Driver's License, Passport, Voter's Card, etc.</span>
                </div>
            </div>

            <p class="auth-field-hint" data-id-card-status>{{ $hint ?? 'Open camera or Gallery, align your ID in the guide, then Capture.' }}</p>
        </div>
    </div>
</div>
