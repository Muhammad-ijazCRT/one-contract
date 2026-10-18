@php
    $hasToast = session()->has('success') || session()->has('error') || session()->has('info') || $errors->any();
@endphp

@if ($hasToast)
    <div class="dash-toast-stack" aria-live="polite" aria-atomic="true">
        @if (session('success'))
            <div class="dash-toast dash-toast--success" data-toast role="status">
                <span class="dash-toast-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                <div class="dash-toast-body">
                    <strong>Success</strong>
                    <p>{{ session('success') }}</p>
                </div>
                <button type="button" class="dash-toast-close" data-toast-close aria-label="Close notification">×</button>
            </div>
        @endif

        @if (session('error'))
            <div class="dash-toast dash-toast--error" data-toast role="alert">
                <span class="dash-toast-icon" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                <div class="dash-toast-body">
                    <strong>Error</strong>
                    <p>{{ session('error') }}</p>
                </div>
                <button type="button" class="dash-toast-close" data-toast-close aria-label="Close notification">×</button>
            </div>
        @endif

        @if (session('info'))
            <div class="dash-toast dash-toast--info" data-toast role="status">
                <span class="dash-toast-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
                <div class="dash-toast-body">
                    <strong>Notice</strong>
                    <p>{{ session('info') }}</p>
                </div>
                <button type="button" class="dash-toast-close" data-toast-close aria-label="Close notification">×</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="dash-toast dash-toast--error" data-toast role="alert">
                <span class="dash-toast-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div class="dash-toast-body">
                    <strong>Please check your input</strong>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
                <button type="button" class="dash-toast-close" data-toast-close aria-label="Close notification">×</button>
            </div>
        @endif
    </div>
@endif

@once
    @push('scripts')
        <script>
            (() => {
                const toasts = document.querySelectorAll('[data-toast]');

                toasts.forEach((toast) => {
                    const closeBtn = toast.querySelector('[data-toast-close]');

                    const dismiss = () => {
                        toast.style.opacity = '0';
                        toast.style.transform = 'translateX(24px)';
                        toast.style.transition = 'opacity 180ms ease, transform 180ms ease';
                        window.setTimeout(() => toast.remove(), 180);
                    };

                    if (closeBtn) {
                        closeBtn.addEventListener('click', dismiss);
                    }

                    window.setTimeout(dismiss, 4500);
                });
            })();
        </script>
    @endpush
@endonce
