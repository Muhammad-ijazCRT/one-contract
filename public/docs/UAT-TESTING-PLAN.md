# One-Contract — UAT Testing Plan (after Stage 2–4 build)

Run these after: `php artisan migrate` + hard refresh.

## 0. Before you start

1. `php artisan migrate`
2. Login as **User A**, prepare **User B**, and **Admin**
3. Fund User A main wallet (crypto deposit or admin credit)
4. Hard refresh browser (`Ctrl+F5`)

---

## 1. Regression (must still work)

| # | Test | Pass? |
|---|------|-------|
| R1 | Login / logout | |
| R2 | Dashboard loads | |
| R3 | Crypto deposit (NOWPayments) | |
| R4 | Crypto withdraw + OTP | |
| R5 | Prediction bet | |
| R6 | Azubogu bet | |
| R7 | Savings / lending / clubs open | |
| R8 | Referrals page | |

---

## 2. Stage 2 — Leaderboard

| # | Steps | Expected | Pass? |
|---|-------|----------|-------|
| L1 | Dashboard → **View Leaderboard!** | Opens `/dashboard/leaderboard` (not referrals) | |
| L2 | Sidebar → Leaderboard | Same page | |
| L3 | Games tab · All / Week / Month | Lists change or empty state | |
| L4 | Play Prediction/Azubogu/Dice then refresh | Wins/volume update | |
| L5 | Referrals tab | L1 referral ranks show | |
| L6 | Your rank card | Shows your position or — | |

## 3. Stage 2 — Chat (inside games)

See section **4b** below. Sidebar Chat link removed.

## 4. Stage 2 — New game (placeholder only)

| # | Steps | Expected | Pass? |
|---|-------|----------|-------|
| G1 | Open `/dashboard/coming-soon-game` | Coming Soon page (no betting) | |
| G2 | Dashboard game picker | Only Prediction + Azubogu | |

## 4b. In-game chat

| # | Steps | Expected | Pass? |
|---|-------|----------|-------|
| C1 | Open Prediction | Chat panel at bottom of game | |
| C2 | User A sends in Prediction | Message shows | |
| C3 | User B on Prediction | Sees message (~4s) | |
| C4 | Azubogu chat | Separate room from Prediction | |
| C5 | Sidebar | No Chat / Lucky Dice links | |

## 5. Stage 3 — Fiat on/off ramp

| # | Steps | Expected | Pass? |
|---|-------|----------|-------|
| F1 | Sidebar → Fiat Ramp | On-ramp + Off-ramp forms | |
| F2 | Submit on-ramp with bank + reference | Pending in your list | |
| F3 | Admin → Fiat Ramp → **Credit** | User balance increases once | |
| F4 | Submit off-ramp | Balance held (decreases); pending | |
| F5 | Admin → **Complete** | Status completed; funds stay out | |
| F6 | New off-ramp → Admin **Reject** | Balance refunded | |
| F7 | Crypto Deposit / Withdraw pages | Still work (unchanged) | |

## 6. Stage 4 — Graphics smoke

| # | Steps | Expected | Pass? |
|---|-------|----------|-------|
| A1 | Dashboard sections | Images load (promo, features, market, trophy, dice) | |
| A2 | Leaderboard / Chat / Dice / Fiat | Dark gold UI readable | |
| A3 | Mobile width | Nav + pages usable | |

---

## Sign-off

| Area | Tester | Date | Result |
|------|--------|------|--------|
| Leaderboard | | | Pass / Fail |
| Chat | | | Pass / Fail |
| Lucky Dice | | | N/A — replaced by Coming Soon page |
| In-game chat | | | Pass / Fail |
| Fiat ramp | | | Pass / Fail |
| Regression | | | Pass / Fail |

**Notes:** New game = Coming Soon placeholder until client brief. Chat is inside Prediction & Azubogu (not sidebar). Fiat is manual bank on/off ramp until a card provider is chosen.
