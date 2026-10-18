@extends('layouts.app')

@section('title', $notice['title'].' — One-Contract™')
@section('meta_description', $notice['meta'])

@push('styles')
    @vite(['resources/css/legal-page.css'])
@endpush

@section('content')
<main class="legal-page">
    <section class="legal-hero">
        <div class="legal-hero-bg"></div>
        <div class="container legal-hero-inner">
            <span class="section-tag">{{ $notice['tag'] }}</span>
            <h1 class="legal-hero-title">{{ $notice['title'] }}</h1>
            <p class="legal-hero-desc">Important information for all One-Contract™ users and participants.</p>
        </div>
    </section>

    <section class="legal-main">
        <div class="container legal-grid">
            <aside class="legal-sidebar" aria-label="{{ $sidebarTitle }}">
                <h2>{{ $sidebarTitle }}</h2>
                <nav class="legal-nav">
                    @foreach ($notices as $key => $item)
                        <a
                            href="{{ route('legal.show', $key) }}"
                            class="legal-nav-link {{ $key === $slug ? 'active' : '' }}"
                        >
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <article class="legal-content">
                <div class="legal-content-card">
                    <h2>{{ $notice['title'] }}</h2>
                    @foreach ($notice['paragraphs'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </article>
        </div>
    </section>
</main>
@endsection
