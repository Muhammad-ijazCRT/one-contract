{{--
  @var string $title
  @var string $action
  @var string $checkboxLabel
  @var array<int, string> $points
  @var string|null $documentUrl
  @var string|null $documentLabel
--}}
<div class="panel dual-panel terms-gate-panel">
    <div class="panel-header">
        <h3>{{ $title }}</h3>
        <span class="dual-summary-chip">Required</span>
    </div>
    <p class="terms-gate-intro">Please review and accept these terms before continuing. This step is required once per account.</p>
    <ul class="terms-gate-list">
        @foreach ($points as $point)
            <li>{{ $point }}</li>
        @endforeach
    </ul>
    @if (! empty($documentUrl))
        <p class="terms-gate-doc">
            Full document:
            <a href="{{ $documentUrl }}" target="_blank" rel="noopener noreferrer">
                {{ $documentLabel ?? 'Read Terms & Conditions' }}
            </a>
        </p>
    @endif
    <form method="POST" action="{{ $action }}" class="terms-gate-form dash-form">
        @csrf
        <label class="checkbox-label">
            <input type="checkbox" name="terms" value="1" required>
            <span class="checkbox-mark"></span>
            <span class="checkbox-text">
                {!! $checkboxLabel !!}
            </span>
        </label>
        <button type="submit" class="btn btn-gold">Accept &amp; Continue</button>
    </form>
</div>
