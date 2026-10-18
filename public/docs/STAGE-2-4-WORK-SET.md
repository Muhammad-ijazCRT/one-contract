# One-Contract — Full Work Set (Stage 2 → 4)

**Purpose:** Sara kaam packages, tasks, files, acceptance, blockers.  
**Related:** `docs/TESTING-PLAN.md`  
**Stage 1 note:** UI + games polish continues in parallel until client sign-off.

---

## Overview

| Stage | Package | Status | Can start coding now? |
|-------|---------|--------|------------------------|
| 2.1 | Leaderboard | Not started | Yes (default ranks: wins + volume) |
| 2.2 | Chat panel | Not started | Yes (start as global lobby + DM later) |
| 2.3 | New game | Blocked | No — wait for client game brief |
| 3.1 | Fiat on-ramp | Blocked | No — wait for provider choice |
| 3.2 | Fiat off-ramp | Blocked | No — wait for provider choice |
| 3.3 | Crypto rails keep | Exists | Regression only |
| 4.1–4.4 | Graphics standard | Partial | Yes — after Stage 1 mock sign-off |

**Recommended delivery order**

1. Stage 2.1 Leaderboard  
2. Stage 2.2 Chat (MVP)  
3. Stage 4 graphics pass (dashboard → landing → games)  
4. Stage 2.3 New game (after brief)  
5. Stage 3 fiat on/off ramp (after provider)

---

## STAGE 2 — Social + New Game

### Package 2.1 — Leaderboard

| Task | Detail | Touch points |
|------|--------|----------------|
| 2.1.1 | Ranking service (wins, wagered volume, referrals count) | New `LeaderboardService` |
| 2.1.2 | Periods: All-time / Weekly / Monthly | Query scopes + cache |
| 2.1.3 | User page: top 50 + my rank card | `LeaderboardController`, `user/leaderboard/index.blade.php` |
| 2.1.4 | Route `user.leaderboard.index` | `routes/web.php` |
| 2.1.5 | Dashboard CTA → leaderboard (not referrals) | `dashboard/index.blade.php` |
| 2.1.6 | Sidebar nav item | `user/partials/sidebar.blade.php` |
| 2.1.7 | Optional: admin read-only top users | Admin route later |

**Acceptance**
- CTA opens leaderboard.
- Rankings match DB bets/referrals.
- Own rank always visible when logged in.
- Mobile usable.

**Default rank rules (until client confirms)**
1. Primary: total game wins (Prediction + Azubogu)  
2. Tie-break: total wagered volume  
3. Secondary tab: top referrers (level-1 count)

---

### Package 2.2 — Chat panel

| Task | Detail | Touch points |
|------|--------|----------------|
| 2.2.1 | Migrations: `chat_rooms`, `chat_messages` | models + migrations |
| 2.2.2 | Global lobby room (auto-join all users) | seeder / boot |
| 2.2.3 | API: list messages, send message (rate limit) | `ChatController` |
| 2.2.4 | UI panel (dashboard drawer or `/chat`) | Blade + JS poll (MVP) |
| 2.2.5 | Replace Support “Live Chat Coming Soon” link | `support/index` |
| 2.2.6 | Moderation: mute word filter + admin delete | admin later / basic filter |
| 2.2.7 | (Phase B) Direct messages | after MVP |

**Acceptance**
- Auth required.
- Message send/receive within ~5s (polling MVP).
- Empty/spam protected (length + rate limit).
- Does not break support tickets.

**Realtime note:** MVP = short polling. Pusher/Reverb = Phase B if client wants live.

---

### Package 2.3 — New game (BLOCKED)

| Task | Detail | Touch points |
|------|--------|----------------|
| 2.3.1 | Receive client game description | Client |
| 2.3.2 | Spec: rules, odds, round length, fair seed | Doc |
| 2.3.3 | Clone Prediction/Azubogu stack | Models, Service, Controller, tick command |
| 2.3.4 | Vite JS/CSS + Blade | `resources/js`, `css`, views |
| 2.3.5 | Dashboard game picker 3rd option | dashboard modal |
| 2.3.6 | Admin controls if needed | admin routes |

**Acceptance**
- Same wallet debit/credit safety as existing games.
- Provably fair (or documented RNG).
- History + cancel rules consistent.
- Appears in game picker.

---

## STAGE 3 — On-ramp / Off-ramp

### Keep (do not break)

| Existing | Path |
|----------|------|
| Crypto deposit (NOWPayments) | `DepositService`, `NowPaymentsController` |
| Crypto withdraw (manual + OTP/KYC) | `WithdrawalService`, admin complete |

### Package 3.1 — Fiat on-ramp (BLOCKED on provider)

| Task | Detail |
|------|--------|
| 3.1.1 | Client picks provider (MoonPay / Transak / Stripe / other) |
| 3.1.2 | Env + config adapter |
| 3.1.3 | Create fiat payment → webhook credit Main Wallet |
| 3.1.4 | Deposit UI: Crypto \| Fiat tabs |
| 3.1.5 | Admin visibility of fiat deposits |

### Package 3.2 — Fiat off-ramp (BLOCKED on provider)

| Task | Detail |
|------|--------|
| 3.2.1 | Bank/card payout fields + validation |
| 3.2.2 | Hold balance on request |
| 3.2.3 | Admin approve / reject / complete |
| 3.2.4 | Withdraw UI: Crypto \| Fiat tabs |
| 3.2.5 | Fee + min amount from `PlatformSettings` |

**Acceptance**
- Fiat success credits once (idempotent webhook).
- Fail/cancel never credits.
- Crypto paths unchanged.
- Admin can reconcile.

---

## STAGE 4 — Graphics to standard

| Task | Detail |
|------|--------|
| 4.1 | Dashboard: lock `images/dashboard/v3`, delete unused old duplicates |
| 4.2 | Landing + clubs + deposit heroes: replace/compress, consistent black/gold |
| 4.3 | Games art pack: Prediction, Azubogu, (+ new game) match dashboard |
| 4.4 | Remove `--mock` CSS/class names after client visual sign-off |
| 4.5 | `npm run build` + hard-refresh QA on desktop + mobile |

**Acceptance**
- No broken images.
- Visual match signed mockups.
- Acceptable load time (optimize large PNGs).

---

## Effort snapshot (estimate)

| Package | Effort |
|---------|--------|
| 2.1 Leaderboard | 2–4 days |
| 2.2 Chat MVP | 4–7 days |
| 2.3 New game | 1–2 weeks after brief |
| 3.1–3.2 Fiat ramps | 1–3 weeks after provider |
| 4 Graphics | 3–7 days (parallel with polish) |

---

## Client blockers (must answer)

1. Leaderboard: confirm default ranks or send custom formula.  
2. Chat: global only, DM only, or both? Moderation level?  
3. New game: full description (rules, theme, round time).  
4. Fiat provider + countries/currencies.  
5. Stage 4: final mockups / brand pack sign-off.

---

## Definition of done (per package)

- [ ] Code merged  
- [ ] `docs/TESTING-PLAN.md` cases for that package = Pass  
- [ ] Regression pack = Pass  
- [ ] Client UAT signed for that package  
