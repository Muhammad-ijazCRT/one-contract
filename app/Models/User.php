<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletCurrency;
use App\Models\SupportTicket;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'country_id',
        'face_image_path',
        'selfie_image_path',
        'id_card_image_path',
        'id_card_back_image_path',
        'id_document_type',
        'passport_image_path',
        'license_image_path',
        'license_back_image_path',
        'face_verified_at',
        'id_card_verified_at',
        'password',
        'google_id',
        'auth_method',
        'role',
        'member_id',
        'referral_code',
        'referred_by',
        'phone',
        'avatar',
        'status',
        'main_wallet_balance',
        'ngn_wallet_balance',
        'preferred_wallet_currency',
        'two_factor_secret',
        'two_factor_enabled',
        'kyc_status',
        'kyc_reviewed_at',
        'kyc_review_note',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'face_verified_at' => 'datetime',
            'id_card_verified_at' => 'datetime',
            'kyc_reviewed_at' => 'datetime',
            'kyc_status' => \App\Enums\KycStatus::class,
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'two_factor_enabled' => 'boolean',
            'main_wallet_balance' => 'decimal:8',
            'ngn_wallet_balance' => 'decimal:8',
        ];
    }

    public function isKycApproved(): bool
    {
        return ($this->kyc_status ?? \App\Enums\KycStatus::Pending)->isApproved();
    }

    public function isKycPending(): bool
    {
        return ($this->kyc_status ?? \App\Enums\KycStatus::Pending) === \App\Enums\KycStatus::Pending;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function sendEmailVerificationNotification(): void
    {
        app(\App\Services\EmailVerificationService::class)->send($this);
    }

    public function preferredWalletCurrency(): WalletCurrency
    {
        $fromSession = session('preferred_wallet_currency');
        $fromDb = $this->preferred_wallet_currency ?? null;

        return WalletCurrency::tryNormalize($fromSession)
            ?? WalletCurrency::tryNormalize($fromDb)
            ?? WalletCurrency::Usdt;
    }

    public function setPreferredWalletCurrency(WalletCurrency $currency): void
    {
        session(['preferred_wallet_currency' => $currency->value]);

        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'preferred_wallet_currency')) {
            $this->forceFill(['preferred_wallet_currency' => $currency->value])->save();
        }
    }

    public function hasGoogleLinked(): bool
    {
        return filled($this->google_id);
    }

    public function usesGoogleAuth(): bool
    {
        return $this->auth_method === 'google' && $this->hasGoogleLinked();
    }

    public function hasTwoFactorConnected(): bool
    {
        return filled($this->two_factor_secret);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && $this->hasTwoFactorConnected();
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function pendingDeposits(): HasMany
    {
        return $this->deposits()->where('status', DepositStatus::Pending);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function faceVerificationSessions(): HasMany
    {
        return $this->hasMany(FaceVerificationSession::class)->latest();
    }

    public function dojahChecks(): HasMany
    {
        return $this->hasMany(DojahCheck::class)->latest();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(UserWallet::class);
    }

    public function clubInvestments(): HasMany
    {
        return $this->hasMany(ClubInvestment::class);
    }

    public function clubCapitalRequests(): HasMany
    {
        return $this->hasMany(ClubCapitalRequest::class);
    }

    public function savingsPlans(): HasMany
    {
        return $this->hasMany(SavingsPlan::class);
    }

    public function lendingPlans(): HasMany
    {
        return $this->hasMany(LendingPlan::class);
    }

    public function termsAcceptances(): HasMany
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    public function activeSavingsPlan(): ?SavingsPlan
    {
        $statuses = [
            \App\Enums\SavingsPlanStatus::Active,
            \App\Enums\SavingsPlanStatus::PendingEarlyReturn,
            \App\Enums\SavingsPlanStatus::Matured,
        ];

        foreach ($statuses as $status) {
            $plan = $this->savingsPlans()
                ->where('status', $status)
                ->latest('id')
                ->first();

            if ($plan) {
                return $plan;
            }
        }

        return null;
    }

    public function mainWalletBalance(): float
    {
        return (float) $this->main_wallet_balance;
    }

    public function totalActiveClubInvestment(): float
    {
        return (float) $this->clubInvestments()
            ->where('status', \App\Enums\ClubInvestmentStatus::Active)
            ->sum('amount');
    }

    public function hasAcceptedTerms(\App\Enums\TermsDocument $document): bool
    {
        return $this->termsAcceptances()
            ->where('document_key', $document)
            ->exists();
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function activeDeposit(): ?Deposit
    {
        return $this->deposits()
            ->where('status', DepositStatus::Active)
            ->latest('approved_at')
            ->first();
    }

    public function activePrincipal(): float
    {
        return (float) $this->deposits()
            ->where('status', DepositStatus::Active)
            ->sum('amount');
    }

    public function hasActiveDeposits(): bool
    {
        return $this->deposits()
            ->where('status', DepositStatus::Active)
            ->exists();
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        $path = str_replace('\\', '/', $this->avatar);

        // If it's already an absolute URL, return as-is
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        // Normalize leading slashes
        $path = ltrim($path, '/');

        // If stored as "storage/..." return asset directly
        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        // If file exists on the public disk, return the public storage URL
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        // No valid avatar found
        return null;
    }

    public function faceImageUrl(): ?string
    {
        return $this->publicStorageUrl($this->face_image_path);
    }

    public function idCardImageUrl(): ?string
    {
        return $this->publicStorageUrl($this->id_card_image_path);
    }

    public function selfieImageUrl(): ?string
    {
        return $this->publicStorageUrl($this->selfie_image_path);
    }

    /** ID and selfie images must exist before KYC can be approved. */
    public function hasCompleteKycDocuments(): bool
    {
        return $this->kycFileExists($this->id_card_image_path)
            && $this->kycFileExists($this->selfie_image_path)
            && $this->kycFileExists($this->face_image_path);
    }

    public function kycImagePath(string $type): ?string
    {
        return match ($type) {
            'face' => $this->face_image_path,
            'selfie' => $this->selfie_image_path ?: $this->face_image_path,
            'id' => $this->id_card_image_path,
            'id_back' => $this->id_card_back_image_path,
            'passport' => $this->passport_image_path
                ?: ($this->id_document_type === 'passport' ? $this->id_card_image_path : null),
            'license' => $this->license_image_path
                ?: ($this->id_document_type === 'license' ? $this->id_card_image_path : null),
            'license_back' => $this->license_back_image_path
                ?: ($this->id_document_type === 'license' ? $this->id_card_back_image_path : null),
            default => null,
        };
    }

    public function faceImageAdminUrl(): ?string
    {
        return $this->kycAdminUrl('face', $this->face_image_path);
    }

    public function idCardImageAdminUrl(): ?string
    {
        return $this->kycAdminUrl('id', $this->id_card_image_path);
    }

    public function idCardBackImageAdminUrl(): ?string
    {
        return $this->kycAdminUrl('id_back', $this->id_card_back_image_path);
    }

    public function passportImageAdminUrl(): ?string
    {
        return $this->kycAdminUrl('passport', $this->passport_image_path);
    }

    public function licenseImageAdminUrl(): ?string
    {
        return $this->kycAdminUrl('license', $this->license_image_path);
    }

    public function licenseBackImageAdminUrl(): ?string
    {
        return $this->kycAdminUrl('license_back', $this->license_back_image_path);
    }

    public function selfieImageAdminUrl(): ?string
    {
        if ($this->selfie_image_path) {
            return $this->kycAdminUrl('selfie', $this->selfie_image_path);
        }

        return $this->faceImageAdminUrl();
    }

    /**
     * @return array{id_front: ?string, id_back: ?string, passport: ?string, license_front: ?string, license_back: ?string}
     */
    public function kycDocumentAdminUrls(): array
    {
        $idFront = $this->idCardImageAdminUrl();
        $idBack = $this->idCardBackImageAdminUrl();
        $passport = $this->passportImageAdminUrl();
        $licenseFront = $this->licenseImageAdminUrl();
        $licenseBack = $this->licenseBackImageAdminUrl();
        $type = (string) $this->id_document_type;

        if (! $passport && $type === 'passport' && $this->id_card_image_path) {
            $passport = $this->idCardImageAdminUrl();
            $idFront = null;
            $idBack = null;
        }

        if (! $licenseFront && $type === 'license' && $this->id_card_image_path) {
            $licenseFront = $this->idCardImageAdminUrl();
            $licenseBack = $this->idCardBackImageAdminUrl();
            $idFront = null;
            $idBack = null;
        }

        return [
            'id_front' => $idFront,
            'id_back' => $idBack,
            'passport' => $passport,
            'license_front' => $licenseFront,
            'license_back' => $licenseBack,
        ];
    }

    private function kycAdminUrl(string $type, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return route('admin.users.kyc.image', ['user' => $this, 'type' => $type]);
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = str_replace('\\', '/', ltrim($path, '/'));

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private function kycFileExists(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        return Storage::disk('public')->exists($normalized);
    }
}
