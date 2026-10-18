<div class="oc-onb-kyc oc-onb-kyc--selfie" data-selfie-capture>
    <header class="oc-onb-kyc-head">
        <h2>Take a <em>Selfie</em></h2>
        <p>Center your face in the circle (drag to adjust). Cars, animals, and object photos are rejected.</p>
    </header>

    <div class="oc-onb-kyc-panel">
        <div class="oc-onb-selfie-media">
            <video class="oc-onb-selfie-video" data-selfie-video autoplay playsinline muted></video>
            <canvas class="hidden" data-selfie-canvas></canvas>
            <img class="oc-onb-selfie-preview hidden" data-selfie-preview alt="Selfie preview">
            <div class="oc-onb-selfie-frame" aria-hidden="true">
                <span class="oc-onb-selfie-silhouette"></span>
            </div>
        </div>

        <input type="hidden" value="{{ old('selfie_image') }}" data-selfie-input>
        <input type="hidden" name="selfie_staging_token" value="{{ old('selfie_staging_token') }}" data-selfie-staging-input>
        <input type="file" accept="image/*" class="hidden" data-selfie-file>

        <div class="oc-onb-kyc-actions">
            <button type="button" class="oc-onb-btn oc-onb-btn--primary" data-selfie-snap>
                <i class="fa-solid fa-camera"></i> Capture Selfie
            </button>
            <span class="oc-onb-or">or</span>
            <button type="button" class="oc-onb-btn oc-onb-btn--outline" data-selfie-gallery>
                <i class="fa-solid fa-upload"></i> Upload from Gallery
            </button>
            <button type="button" class="oc-onb-btn oc-onb-btn--ghost hidden" data-selfie-retake>Retake</button>
        </div>

        <p class="oc-onb-kyc-status" data-selfie-status>Open camera or upload a clear selfie photo.</p>
    </div>

    <p class="oc-onb-secure"><i class="fa-solid fa-lock"></i> Your biometric data is encrypted and securely stored.</p>
</div>
