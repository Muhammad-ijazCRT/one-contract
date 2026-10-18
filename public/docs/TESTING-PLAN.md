# One-Contract — Testing Plan (Stage 2 → 4)

**Use with:** `docs/STAGE-2-4-WORK-SET.md`  
**Rule:** Stage N critical fails → do not start Stage N+1 feature work.  
**Environments:** Local → Staging → Production (UAT on staging).

---

## 0. Setup (every test cycle)

| Step | Action |
|------|--------|
| 0.1 | Fresh login as normal user + second user (chat/leaderboard) |
| 0.2 | Admin login ready (withdraw/deposit/chat mod) |
| 0.3 | Test wallets funded with sandbox crypto |
| 0.4 | Hard refresh after each frontend deploy (`Ctrl+F5`) |
| 0.5 | Record build/version date in test sheet |

**Roles**

| Role | Needed for |
|------|------------|
| User A | Primary flows |
| User B | Chat peer, leaderboard competition |
| Admin | Fiat/crypto approve, message delete |

---

## 1. Regression pack (run every stage)

| ID | Case | Steps | Pass |
|----|------|-------|------|
| REG-01 | Login / logout | Valid + invalid password | Session correct |
| REG-02 | Dashboard loads | Open home | No JS errors; balances show |
| REG-03 | Crypto deposit | Create NOWPayments invoice | Redirect / pending / credit on IPN |
| REG-04 | Crypto withdraw | Request + OTP/KYC | Pending → admin complete; balance correct |
| REG-05 | Prediction | Place bet, wait tick | Debit/credit; history |
| REG-06 | Azubogu | Place bet, wait tick | Debit/credit; history |
| REG-07 | Savings / lending / clubs | Open + one light action | No 500; UI ok |
| REG-08 | Referrals | Open network | Levels show; CTA still works |
| REG-09 | Notifications | Open list | Mark read works |
| REG-10 | Support ticket | Create ticket | Admin can see (chat must not break this) |
| REG-11 | Mobile smoke | 375px width key pages | Usable, no overlap |

---

## 2. Stage 2 — Leaderboard / Chat / New game

### 2.A Leaderboard

| ID | Priority | Case | Steps | Pass criteria |
|----|----------|------|-------|----------------|
| L1 | Critical | CTA target | Dashboard → View Leaderboard | Opens leaderboard route, **not** referrals |
| L2 | Critical | Ranking data | User A wins bets; refresh | A rises in wins/volume list |
| L3 | Critical | Own rank | Open page as User A | “Your rank” card matches list |
| L4 | High | Periods | Switch Weekly / Monthly / All | Lists change or empty-state clear |
| L5 | High | Referrals tab | User with referrals | Referrer rank correct |
| L6 | Medium | Empty state | New user zero activity | Friendly empty, no crash |
| L7 | Medium | Sidebar | Click Leaderboard nav | Same page |
| L8 | Medium | Mobile | Phone width | Table/cards readable |

### 2.B Chat panel

| ID | Priority | Case | Steps | Pass criteria |
|----|----------|------|-------|----------------|
| C1 | Critical | Open panel | Open chat from UI | Panel/page loads; no layout break |
| C2 | Critical | Send message | User A sends text | Appears in own history |
| C3 | Critical | Receive | User B opens chat | Sees A’s message (≤ poll interval) |
| C4 | Critical | Auth | Logout → open chat URL | Redirect login |
| C5 | High | Rate limit | Spam many messages | Blocked with clear error |
| C6 | High | Length limit | Very long message | Rejected or truncated safely |
| C7 | High | Support intact | Open Support | Tickets still work; “Coming Soon” replaced or linked |
| C8 | Medium | XSS | Send `<script>` | Rendered escaped, not executed |
| C9 | Medium | Mobile | Open chat on phone | Usable keyboard + list |

### 2.C New game (when built)

| ID | Priority | Case | Steps | Pass criteria |
|----|----------|------|-------|----------------|
| G1 | Critical | Picker | Open game picker | 3rd game visible with Prediction/Azubogu |
| G2 | Critical | Place bet | Bet valid amount | Balance debit immediate |
| G3 | Critical | Round settle | Wait tick/result | Correct payout; transaction logged |
| G4 | Critical | Insufficient funds | Bet > balance | Rejected; no debit |
| G5 | High | Cancel | Cancel if allowed | Refund per rules |
| G6 | High | History | Open history | Bets listed with status |
| G7 | High | Fairness | Check seed/proof if any | Matches documented fair flow |
| G8 | Medium | Concurrent | Two users same round | Both recorded; no double-credit bug |
| G9 | Medium | Mobile | Play on phone | Controls usable |

**Stage 2 exit:** All Critical Pass + Regression pack Pass.

---

## 3. Stage 3 — On-ramp / Off-ramp

### 3.A Fiat on-ramp

| ID | Priority | Case | Steps | Pass criteria |
|----|----------|------|-------|----------------|
| O1 | Critical | Happy path | Start fiat buy → provider success | Main wallet credited **once** |
| O2 | Critical | Cancel | Cancel at provider | No credit |
| O3 | Critical | Fail / decline | Fail payment | No credit; error shown |
| O4 | Critical | Replay webhook | Send same success webhook twice | Still one credit (idempotent) |
| O5 | Critical | Crypto regression | REG-03 | Unchanged |
| O6 | High | Min amount | Below min | Blocked |
| O7 | High | UI tabs | Deposit Crypto \| Fiat | Both clear |
| O8 | Medium | Admin list | Admin opens fiat deposits | Status visible |

### 3.B Fiat off-ramp

| ID | Priority | Case | Steps | Pass criteria |
|----|----------|------|-------|----------------|
| F1 | Critical | Request | Submit fiat withdraw | Balance held/reduced; pending |
| F2 | Critical | Admin complete | Admin completes | Status completed; payout recorded |
| F3 | Critical | Admin reject | Admin rejects | Balance restored |
| F4 | Critical | Crypto regression | REG-04 | Unchanged |
| F5 | High | Invalid bank data | Bad IBAN/account | Validation error |
| F6 | High | OTP/KYC | If required for fiat | Same security gates as product rules |
| F7 | Medium | Fees | Apply fee setting | Fee deducted correctly |

### 3.C Security

| ID | Priority | Case | Pass criteria |
|----|----------|------|----------------|
| S1 | Critical | Bad webhook signature | Rejected; no credit |
| S2 | Critical | Auth on deposit/withdraw | Guest blocked |
| S3 | High | CSRF on forms | Invalid token rejected |

**Stage 3 exit:** All Critical Pass + Regression pack Pass.

---

## 4. Stage 4 — Graphics

| ID | Priority | Case | Steps | Pass criteria |
|----|----------|------|-------|----------------|
| A1 | Critical | Dashboard | Walk home sections | No 404 images; match signed mock |
| A2 | Critical | Landing | Home public page | Heroes sharp, not stretched |
| A3 | Critical | Games | Prediction + Azubogu (+ new) | Art consistent with brand |
| A4 | High | Deposit/clubs/referrals | Open pages | Icons/heroes load |
| A5 | High | Performance | Network tab large assets | Heavy PNGs optimized / acceptable LCP |
| A6 | High | Contrast | Dark theme | Text readable on gold/neon |
| A7 | Medium | Cache | New deploy + hard refresh | New assets appear |
| A8 | Medium | Mobile | Same pages phone | No overflow from images |
| A9 | Medium | No mock naming | Inspect classes | `--mock` removed after sign-off |

**Stage 4 exit:** Critical/High Pass + client visual UAT signed.

---

## 5. UAT sign-off sheet (client)

| Package | Tester | Date | Result (Pass/Fail) | Notes |
|---------|--------|------|--------------------|-------|
| 2.1 Leaderboard | | | | |
| 2.2 Chat | | | | |
| 2.3 New game | | | | |
| 3.1 Fiat on-ramp | | | | |
| 3.2 Fiat off-ramp | | | | |
| 4 Graphics | | | | |
| Full regression | | | | |

---

## 6. Bug severity (for reporting)

| Severity | Meaning | Example |
|----------|---------|---------|
| S1 Blocker | Money wrong / security | Double credit, XSS |
| S2 Critical | Feature unusable | Chat cannot send |
| S3 Major | Wrong UX / data display | Rank wrong by 1 |
| S4 Minor | Polish | Spacing, typo |

**Release rule:** No open S1/S2 on staging before production.
