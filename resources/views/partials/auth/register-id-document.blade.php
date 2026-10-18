@php
    $idDocs = [
        'national_id' => [
            'title' => 'Upload Your National ID',
            'lead' => 'Upload clear images of the front and back of your ID card.',
            'front_title' => 'National ID Card — Front',
            'front_icon' => 'fa-regular fa-id-badge',
            'back_title' => 'National ID Card — Back',
            'back_icon' => 'fa-regular fa-address-card',
            'sides' => ['front', 'back'],
        ],
        'passport' => [
            'title' => 'Upload Your Passport',
            'lead' => 'Upload a clear photo of the passport page with your details.',
            'front_title' => 'Passport Photo Page',
            'front_icon' => 'fa-solid fa-globe',
            'back_title' => '',
            'back_icon' => '',
            'sides' => ['front'],
        ],
        'license' => [
            'title' => "Upload Your Driver's License",
            'lead' => "Upload clear images of the front and back of your license.",
            'front_title' => "Driver's License — Front",
            'front_icon' => 'fa-regular fa-id-badge',
            'back_title' => "Driver's License — Back",
            'back_icon' => 'fa-regular fa-address-card',
            'sides' => ['front', 'back'],
        ],
    ];
@endphp

<div class="oc-reg-doc oc-idup" data-reg-doc-panel data-id-card-capture>
    <input type="hidden" name="id_document_type" value="{{ old('id_document_type') }}" data-id-doc-type>
    <input type="hidden" value="{{ old('id_card_image') }}" data-id-card-input>
    <input type="hidden" value="{{ old('id_card_back_image') }}" data-id-card-back-input>
    <input type="hidden" name="id_staging_token" value="{{ old('id_staging_token') }}" data-id-staging-input>

    <div class="oc-idup-picker" data-doc-picker>
        <header class="oc-idup-hero">
            <div class="oc-idup-shield" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
            <h2>Choose one ID</h2>
            <p>National ID, passport, or driver's license.</p>
        </header>

        <div class="oc-reg-doc-types">
            <button type="button" class="oc-reg-doc-type" data-doc-open="national_id">
                <i class="fa-solid fa-id-card"></i>
                <span><strong>National ID Card</strong><small>Front and back</small></span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <button type="button" class="oc-reg-doc-type" data-doc-open="passport">
                <i class="fa-solid fa-passport"></i>
                <span><strong>Passport</strong><small>Photo page</small></span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <button type="button" class="oc-reg-doc-type" data-doc-open="license">
                <i class="fa-solid fa-address-card"></i>
                <span><strong>Driver's License</strong><small>Front and back</small></span>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

    </div>

    @foreach ($idDocs as $kind => $doc)
        <div class="oc-idup-screen" data-doc-upload="{{ $kind }}" hidden>
            <header class="oc-idup-hero">
                <div class="oc-idup-shield" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
                <h2>{{ $doc['title'] }}</h2>
                <p>{{ $doc['lead'] }}</p>
            </header>

            <div class="oc-idup-grid {{ count($doc['sides']) === 1 ? 'is-single' : '' }}">
                @foreach ($doc['sides'] as $side)
                    <article class="oc-idup-card" data-doc-slot="{{ $side }}">
                        <div class="oc-idup-card-head">
                            <span class="oc-idup-card-ico" aria-hidden="true"><i class="{{ $doc[$side.'_icon'] }}"></i></span>
                            <div>
                                <strong>{{ $doc[$side.'_title'] }}</strong>
                            </div>
                        </div>

                        <div class="oc-idup-drop" data-doc-drop tabindex="0" role="button">
                            <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" hidden data-doc-file>
                            <div class="oc-idup-drop-empty">
                                <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                                <strong>Click to browse or drag &amp; drop</strong>
                                <small>Upload an image (JPG, PNG or PDF, max 5MB)</small>
                                <em>Choose File</em>
                            </div>
                            <div class="oc-idup-drop-preview" hidden>
                                <img alt="" data-doc-preview>
                                <button type="button" class="oc-idup-replace" data-doc-replace>Replace file</button>
                            </div>
                        </div>
                        <p class="oc-idup-slot-error" data-doc-error hidden></p>
                    </article>
                @endforeach
            </div>

            <button type="button" class="oc-idup-continue" data-wizard-next>
                Continue <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
            <button type="button" class="oc-idup-switch" data-doc-change>Choose a different document</button>
        </div>
    @endforeach
</div>
