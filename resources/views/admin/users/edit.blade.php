@extends('layouts.admin')

@section('title', 'Edit User â€” Admin')
@section('page_title', 'Edit User')

@section('content')
<div class="dash-page">
    @if ($errors->any())
        <div class="dash-alert dash-alert-error">
            <p>Please fix the highlighted fields and try again.</p>
        </div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <h3>Edit {{ $user->name }}</h3>
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-dark btn-sm">View</a>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-edit-form">
            @csrf
            @method('PUT')
            <div class="admin-edit-grid">
                <div class="dash-field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="dash-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="dash-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected(old('status', $user->status->value) === 'active')>Active</option>
                        <option value="suspended" @selected(old('status', $user->status->value) === 'suspended')>Suspended</option>
                    </select>
                    @error('status')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
