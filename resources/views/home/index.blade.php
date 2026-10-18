@extends('layouts.app')

@section('title', 'ONE CONTRACT™ — A Smarter Way to Play')

@section('meta_description', 'Unique games. Real rewards. A next generation iGaming platform built for you.')

@push('styles')
    <link href="https://fonts.bunny.net/css?family=poppins:800" rel="stylesheet">
    @vite(['resources/css/home-onboarding-landing.css'])
@endpush

@section('content')
    {{-- New onboarding-style landing (active) --}}
  @include('home.sections.onboarding-landing')

    {{-- Previous full landing — kept for future use
    <main class="home-landing hl-page">
        @include('home.sections.hero')
        @include('home.sections.services')
        @include('home.sections.stats')
        @include('home.sections.clubs')
        @include('home.sections.trust')
        @include('home.sections.join')
        @include('home.sections.how-it-works')
        @include('home.sections.faq')
        @include('home.sections.cta')
    </main>
    --}}
@endsection
