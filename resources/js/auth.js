import { detectCloseUpFace, looksLikeFaceFromImage, openCircleCrop } from './circle-crop';

document.addEventListener('DOMContentLoaded', () => {
    initCountryPickers();
    initFaceCapture();
    initIdCardCapture();
    initRegisterDocumentUploads();
    initSelfieCapture();
    initAuthWizard();
});

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function showAuthNotice({ title, message, variant = 'info', okText = 'OK' }) {
    return new Promise((resolve) => {
        const existing = document.querySelector('.auth-notice-backdrop');
        existing?.remove();

        const overlay = document.createElement('div');
        overlay.className = 'auth-notice-backdrop';

        const iconMarkup = variant === 'success'
            ? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7 10 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : variant === 'error'
                ? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.71 3h14.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        overlay.innerHTML = `
            <div class="auth-notice auth-notice--${variant}" role="alertdialog" aria-modal="true" aria-labelledby="auth-notice-title">
                <div class="auth-notice-icon">${iconMarkup}</div>
                <h3 class="auth-notice-title" id="auth-notice-title">${escapeHtml(title)}</h3>
                <p class="auth-notice-copy">${escapeHtml(message)}</p>
                <button type="button" class="btn btn-gold auth-notice-ok">${escapeHtml(okText)}</button>
            </div>
        `;

        const close = () => {
            overlay.remove();
            document.body.classList.remove('auth-notice-open');
            document.removeEventListener('keydown', onKeyDown);
            resolve();
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                close();
            }
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                close();
            }
        });

        overlay.querySelector('.auth-notice-ok')?.addEventListener('click', close);
        document.addEventListener('keydown', onKeyDown);
        document.body.classList.add('auth-notice-open');
        document.body.appendChild(overlay);
        overlay.querySelector('.auth-notice-ok')?.focus();
    });
}

const CHALLENGE_META = {
    blink: { label: 'Blink your eyes slowly 2–3 times' },
    nod: { label: 'Nod your head up and down' },
    shake: { label: 'Gently shake your head left and right' },
};

const LIVENESS_CHALLENGES = [
    CHALLENGE_META.blink,
    CHALLENGE_META.nod,
    CHALLENGE_META.shake,
].map((item, index) => ({ key: ['blink', 'nod', 'shake'][index], label: item.label }));

function isKycToken(value) {
    return typeof value === 'string'
        && value.length >= 32
        && value.length <= 64
        && value !== 'undefined'
        && value !== 'null';
}

async function postFaceVerification(url, body, form) {
    await refreshCsrfToken(form);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || form?.querySelector('input[name="_token"]')?.value;

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
        credentials: 'same-origin',
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const errors = data.errors || {};
        const message = errors.session_token?.[0]
            || errors.face_verification_session?.[0]
            || errors.id_staging_token?.[0]
            || errors.selfie_staging_token?.[0]
            || errors.selfie_image?.[0]
            || errors.challenge?.[0]
            || errors.session?.[0]
            || errors.id_card_image?.[0]
            || errors.id_card_back_image?.[0]
            || data.message
            || 'Face verification failed. Please try again.';
        throw new Error(message);
    }

    return data;
}

async function stageSelfieImage(dataUrl, idStagingToken, form) {
    const compressed = await compressDataUrl(dataUrl, 480, 0.72);

    return postFaceVerification('/selfie-verification/stage', {
        selfie_image: compressed,
        id_staging_token: idStagingToken,
    }, form);
}

async function stageIdCardImage(dataUrl, form, backUrl) {
    const compressed = await compressDataUrl(dataUrl, 720, 0.72);
    const body = {
        id_card_image: compressed,
        id_document_type: form?.querySelector('[data-id-doc-type]')?.value || '',
    };
    if (backUrl) {
        body.id_card_back_image = await compressDataUrl(backUrl, 720, 0.72);
    }

    return postFaceVerification('/id-verification/stage', body, form);
}

function compressDataUrl(dataUrl, maxSize = 480, quality = 0.72) {
    return new Promise((resolve) => {
        if (!dataUrl?.startsWith('data:image')) {
            resolve(dataUrl);
            return;
        }

        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, maxSize / Math.max(img.width, img.height));
            const w = Math.max(1, Math.round(img.width * scale));
            const h = Math.max(1, Math.round(img.height * scale));
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                resolve(dataUrl);
                return;
            }
            ctx.drawImage(img, 0, 0, w, h);
            resolve(canvas.toDataURL('image/jpeg', quality));
        };
        img.onerror = () => resolve(dataUrl);
        img.src = dataUrl;
    });
}

async function refreshCsrfToken(form) {
    try {
        const res = await fetch('/csrf-token', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!res.ok) return false;
        const data = await res.json();
        const token = data?.token;
        if (!token) return false;

        const input = form?.querySelector('input[name="_token"]');
        if (input) input.value = token;

        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);

        return true;
    } catch (_) {
        return false;
    }
}

function initFaceCapture() {
    document.querySelectorAll('[data-face-capture]').forEach((root) => {
        if (root.dataset.faceAuto === '1') {
            initFaceCaptureAuto(root);
        } else {
            initFaceCaptureManual(root);
        }
    });
}

function initFaceCaptureAuto(root) {
    const form = root.closest('form');
    const video = root.querySelector('[data-face-video]');
    const canvas = root.querySelector('[data-face-canvas]');
    const preview = root.querySelector('[data-face-preview]');
    const sessionInput = root.querySelector('[data-face-session-input]');
    const badge = root.querySelector('[data-face-badge]');
    const autoAction = root.querySelector('[data-face-auto-action]');
    const autoStatus = root.querySelector('[data-face-auto-status]');
    const autoBar = root.querySelector('[data-face-auto-bar]');
    const stepIndicators = root.querySelector('[data-face-step-indicators]');

    let stream = null;
    let sampleCanvas = null;
    let previousFrame = null;
    let livenessTimer = null;
    let sessionToken = sessionInput?.value || null;
    let serverChallenges = [];
    let challengeIndex = 0;
    let challengeFlags = { center: false, blink: false, nod: false, shake: false };
    let sessionStartedAt = null;
    let activated = false;
    let completing = false;
    let runToken = 0;

    const isVerified = () => isKycToken(sessionInput?.value);

    const markVerified = () => {
        root.dataset.faceVerified = '1';
        root.dataset.faceRunning = '0';
        activated = true;
    };

    const safePlayVideo = async (videoEl) => {
        if (!videoEl) return;

        try {
            await videoEl.play();
        } catch (error) {
            const message = String(error?.message || '');
            const interrupted = error?.name === 'AbortError' || message.includes('interrupted');

            if (!interrupted) {
                throw error;
            }

            await new Promise((resolve) => window.setTimeout(resolve, 120));

            try {
                await videoEl.play();
            } catch (retryError) {
                const retryMessage = String(retryError?.message || '');
                if (retryError?.name === 'AbortError' || retryMessage.includes('interrupted')) {
                    return;
                }
                throw retryError;
            }
        }
    };

    const setBadge = (text, isDone = false, visible = true) => {
        if (!badge) return;
        badge.hidden = !visible;
        badge.innerHTML = isDone ? text : `<i></i> ${text}`;
        badge.classList.toggle('is-done', isDone);
        badge.classList.toggle('is-live', !isDone && visible);
    };

    const updateAutoUi = (progress, actionText, statusText) => {
        if (autoBar) autoBar.style.width = `${Math.max(0, Math.min(100, progress))}%`;
        if (autoAction && actionText) autoAction.textContent = actionText;
        if (autoStatus && statusText) autoStatus.textContent = statusText;
    };

    const updateStepIndicators = (index) => {
        stepIndicators?.querySelectorAll('div').forEach((el, i) => {
            el.classList.toggle('is-current', i === index);
            el.classList.toggle('is-done', i < index);
        });
    };

    const stopLiveness = () => {
        if (livenessTimer) {
            clearInterval(livenessTimer);
            livenessTimer = null;
        }
        previousFrame = null;
    };

    const stopCamera = () => {
        stopLiveness();
        if (video) {
            video.pause();
            video.srcObject = null;
        }
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
    };

    const analyzeFramePair = (prev, curr, size) => {
        const rowPrev = new Float32Array(size);
        const rowCurr = new Float32Array(size);
        const colPrev = new Float32Array(size);
        const colCurr = new Float32Array(size);
        let upperDiff = 0;
        let upperCount = 0;
        let totalDiff = 0;

        for (let y = 0; y < size; y += 1) {
            let rp = 0;
            let rc = 0;
            for (let x = 0; x < size; x += 1) {
                const i = y * size + x;
                rp += prev[i];
                rc += curr[i];
                const d = Math.abs(curr[i] - prev[i]);
                totalDiff += d;
                if (y < size * 0.42) {
                    upperDiff += d;
                    upperCount += 1;
                }
            }
            rowPrev[y] = rp / size;
            rowCurr[y] = rc / size;
        }

        for (let x = 0; x < size; x += 1) {
            let cp = 0;
            let cc = 0;
            for (let y = 0; y < size; y += 1) {
                const i = y * size + x;
                cp += prev[i];
                cc += curr[i];
            }
            colPrev[x] = cp / size;
            colCurr[x] = cc / size;
        }

        const bestShift = (prevArr, currArr, maxShift) => {
            let best = 0;
            let bestScore = -Infinity;
            for (let shift = -maxShift; shift <= maxShift; shift += 1) {
                let score = 0;
                let count = 0;
                for (let i = 0; i < size; i += 1) {
                    const j = i + shift;
                    if (j < 0 || j >= size) continue;
                    score -= Math.abs(currArr[i] - prevArr[j]);
                    count += 1;
                }
                if (count > 0) score /= count;
                if (score > bestScore) {
                    bestScore = score;
                    best = shift;
                }
            }
            return best;
        };

        return {
            dy: bestShift(rowPrev, rowCurr, 5),
            dx: bestShift(colPrev, colCurr, 5),
            upperAvg: upperCount ? upperDiff / upperCount : 0,
            totalAvg: totalDiff / (size * size),
        };
    };

    const captureFrameDataUrl = () => {
        if (!video?.videoWidth || !canvas) return null;
        const sourceSize = Math.min(video.videoWidth, video.videoHeight);
        const sourceX = (video.videoWidth - sourceSize) / 2;
        const sourceY = (video.videoHeight - sourceSize) / 2;
        const outputSize = 320;
        canvas.width = outputSize;
        canvas.height = outputSize;
        const ctx = canvas.getContext('2d');
        ctx?.drawImage(video, sourceX, sourceY, sourceSize, sourceSize, 0, 0, outputSize, outputSize);
        return canvas.toDataURL('image/jpeg', 0.85);
    };

    const openCamera = async () => {
        stopCamera();
        await new Promise((resolve) => requestAnimationFrame(resolve));

        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
            audio: false,
        });
        video.srcObject = stream;
        await safePlayVideo(video);
        video.classList.remove('hidden');
        video.classList.add('is-active');
        root.querySelector('.face-capture-media')?.classList.add('is-live');
        preview?.classList.add('hidden');
        root.querySelector('.face-capture-media')?.classList.remove('has-preview');
        setBadge('Face detected', false, true);
    };

    const runCurrentChallenge = () => new Promise((resolve) => {
        const challenge = serverChallenges[challengeIndex];
        if (!challenge) {
            resolve(false);
            return;
        }

        updateStepIndicators(challengeIndex);
        updateAutoUi(
            Math.round((challengeIndex / serverChallenges.length) * 100),
            challenge.label,
            'Follow the instruction…',
        );

        let blinkPulses = 0;
        let blinkCoolDown = 0;
        let nodUp = false;
        let nodDown = false;
        let shakeLeft = false;
        let shakeRight = false;
        let stableCenterTicks = 0;
        previousFrame = null;

        if (!sampleCanvas) sampleCanvas = document.createElement('canvas');
        const sampleSize = 56;
        sampleCanvas.width = sampleSize;
        sampleCanvas.height = sampleSize;
        const ctx = sampleCanvas.getContext('2d', { willReadFrequently: true });

        stopLiveness();
        livenessTimer = setInterval(() => {
            if (!video?.videoWidth || !ctx) return;

            const sourceSize = Math.min(video.videoWidth, video.videoHeight);
            const sourceX = (video.videoWidth - sourceSize) / 2;
            const sourceY = (video.videoHeight - sourceSize) / 2;
            ctx.drawImage(video, sourceX, sourceY, sourceSize, sourceSize, 0, 0, sampleSize, sampleSize);
            const { data } = ctx.getImageData(0, 0, sampleSize, sampleSize);
            const gray = new Float32Array(sampleSize * sampleSize);
            for (let i = 0, p = 0; i < data.length; i += 4, p += 1) {
                gray[p] = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
            }

            if (!previousFrame) {
                previousFrame = gray;
                return;
            }

            const { dx, dy, upperAvg, totalAvg } = analyzeFramePair(previousFrame, gray, sampleSize);
            previousFrame = gray;
            let passed = false;

            if (challenge.key === 'center') {
                if (totalAvg < 10 && Math.abs(dx) <= 1 && Math.abs(dy) <= 1) {
                    stableCenterTicks += 1;
                } else {
                    stableCenterTicks = 0;
                }
                const pct = Math.min(100, Math.round((stableCenterTicks / 12) * 100));
                updateAutoUi(
                    ((challengeIndex + pct / 100) / serverChallenges.length) * 100,
                    challenge.label,
                    pct < 100 ? 'Hold still and look at the camera' : 'Position verified…',
                );
                passed = stableCenterTicks >= 12;
            } else if (challenge.key === 'blink') {
                if (blinkCoolDown > 0) blinkCoolDown -= 1;
                const isPulse = upperAvg > 14 && Math.abs(dx) <= 1 && Math.abs(dy) <= 2 && totalAvg < 28;
                if (isPulse && blinkCoolDown === 0) {
                    blinkPulses += 1;
                    blinkCoolDown = 8;
                }
                passed = blinkPulses >= 2;
                updateAutoUi(
                    ((challengeIndex + Math.min(1, blinkPulses / 2)) / serverChallenges.length) * 100,
                    challenge.label,
                    blinkPulses < 2 ? `Blink detected ${blinkPulses}/2` : 'Blink verified…',
                );
            } else if (challenge.key === 'nod') {
                if (dy <= -2) nodUp = true;
                if (dy >= 2) nodDown = true;
                passed = nodUp && nodDown;
                updateAutoUi(
                    ((challengeIndex + ((nodUp ? 0.5 : 0) + (nodDown ? 0.5 : 0))) / serverChallenges.length) * 100,
                    challenge.label,
                    !nodUp ? 'Nod up' : !nodDown ? 'Now nod down' : 'Nod verified…',
                );
            } else if (challenge.key === 'shake') {
                if (dx <= -2) shakeLeft = true;
                if (dx >= 2) shakeRight = true;
                passed = shakeLeft && shakeRight;
                updateAutoUi(
                    ((challengeIndex + ((shakeLeft ? 0.5 : 0) + (shakeRight ? 0.5 : 0))) / serverChallenges.length) * 100,
                    challenge.label,
                    !shakeLeft ? 'Turn left' : !shakeRight ? 'Now turn right' : 'Turn verified…',
                );
            }

            if (passed) {
                stopLiveness();
                challengeFlags[challenge.key] = true;
                resolve(true);
            }
        }, 140);
    });

    const recordChallenge = async (challengeKey) => {
        await postFaceVerification('/face-verification/challenge', {
            session_token: sessionToken,
            challenge: challengeKey,
        }, form);
    };

    const completeSession = async () => {
        const dataUrl = captureFrameDataUrl();
        if (!dataUrl) throw new Error('Could not capture face image.');

        const compressed = await compressDataUrl(dataUrl, 420, 0.7);
        const durationSeconds = sessionStartedAt
            ? (Date.now() - sessionStartedAt) / 1000
            : 0;

        const result = await postFaceVerification('/face-verification/complete', {
            session_token: sessionToken,
            face_image: compressed,
            metrics: {
                duration_seconds: durationSeconds,
                challenge_flags: challengeFlags,
            },
        }, form);

        if (!isKycToken(result?.session_token)) {
            throw new Error('Live verification did not finish correctly. Please try the live check again.');
        }

        sessionInput.value = result.session_token;
        sessionInput.dispatchEvent(new Event('input', { bubbles: true }));
        sessionInput.dispatchEvent(new Event('change', { bubbles: true }));
        if (preview) {
            preview.src = compressed;
            preview.classList.remove('hidden');
            preview.classList.add('is-active');
        }
        root.querySelector('.face-capture-media')?.classList.add('has-preview');
        root.querySelector('.face-capture-media')?.classList.remove('is-live');
        video?.classList.add('hidden');
        stopCamera();
        root.classList.add('is-liveness-passed');
        markVerified();
        setBadge('Verified ✓', true, true);
        updateAutoUi(100, 'Verification complete', 'Face matched with your ID document.');
        updateStepIndicators(serverChallenges.length);
    };

    const runVerification = async () => {
        if (isVerified()) return;
        if (completing || root.dataset.faceRunning === '1') return;

        const currentRun = ++runToken;
        activated = true;
        completing = true;
        root.dataset.faceRunning = '1';
        challengeFlags = { center: false, blink: false, nod: false, shake: false };

        if (!sessionInput?.value) {
            root.classList.remove('is-liveness-passed');
            root.dataset.faceVerified = '0';
        }

        try {
            await refreshCsrfToken(form);
            updateAutoUi(0, 'Starting session…', 'Connecting to verification service…');

            if (!sessionToken) {
                const startPayload = {
                    purpose: root.dataset.facePurpose || 'registration',
                };

                if (root.dataset.facePurpose === 'registration') {
                    const selfieStagingToken = form?.querySelector('[data-selfie-staging-input]')?.value;
                    if (!isKycToken(selfieStagingToken)) {
                        throw new Error('Please complete selfie verification before the live check.');
                    }
                    startPayload.selfie_staging_token = selfieStagingToken;
                }

                const start = await postFaceVerification('/face-verification/start', startPayload, form);
                sessionToken = start.session_token;
                serverChallenges = start.challenges || [];
            }

            if (!serverChallenges.length) {
                throw new Error('No liveness challenges received.');
            }

            sessionStartedAt = Date.now();
            updateAutoUi(5, 'Opening camera…', 'Please allow camera access.');
            await openCamera();

            for (challengeIndex = 0; challengeIndex < serverChallenges.length; challengeIndex += 1) {
                const ok = await runCurrentChallenge();
                if (!ok) throw new Error('Challenge failed.');
                await recordChallenge(serverChallenges[challengeIndex].key);
            }

            updateAutoUi(92, 'Capturing face…', 'Almost done…');
            await completeSession();
        } catch (error) {
            if (currentRun !== runToken || isVerified()) {
                return;
            }

            const message = String(error?.message || '');
            const interrupted = error?.name === 'AbortError' || message.includes('interrupted');

            activated = false;
            sessionToken = null;
            if (sessionInput) sessionInput.value = '';
            stopCamera();
            root.dataset.faceRunning = '0';
            root.dataset.faceVerified = '0';

            if (interrupted) {
                updateAutoUi(0, 'Camera interrupted', 'Retrying camera…');
                window.setTimeout(() => {
                    if (!isVerified()) runVerification();
                }, 250);
                return;
            }

            updateAutoUi(0, 'Verification failed', message || 'Please try again.');
            showAuthNotice({
                title: 'Face Verification Failed',
                message: message || 'Please try again with good lighting.',
                variant: 'error',
            });
        } finally {
            if (currentRun === runToken) {
                completing = false;
                if (!isVerified()) {
                    root.dataset.faceRunning = '0';
                }
            }
        }
    };

    root.addEventListener('face-capture:activate', () => {
        if (isVerified()) return;
        if (root.dataset.faceRunning === '1' || completing) return;
        sessionToken = null;
        serverChallenges = [];
        runVerification();
    });

    if (isVerified()) {
        root.classList.add('is-liveness-passed');
        markVerified();
        updateAutoUi(100, 'Verified', 'Face already verified.');
        setBadge('Verified ✓', true, true);
    } else if (!root.closest('[data-auth-wizard]')) {
        runVerification();
    }

    window.addEventListener('beforeunload', stopCamera);
}

function initFaceCaptureManual(root) {
        const video = root.querySelector('[data-face-video]');
        const canvas = root.querySelector('[data-face-canvas]');
        const preview = root.querySelector('[data-face-preview]');
        const input = root.querySelector('[data-face-input]');
        const startBtn = root.querySelector('[data-face-start]');
        const snapBtn = root.querySelector('[data-face-snap]');
        const retakeBtn = root.querySelector('[data-face-retake]');
        const badge = root.querySelector('[data-face-badge]');
        const livenessEnabled = root.dataset.faceLiveness === '1';
        const livenessBox = root.querySelector('[data-face-liveness-box]');
        const livenessAction = root.querySelector('[data-face-liveness-action]');
        const livenessStatus = root.querySelector('[data-face-liveness-status]');
        const livenessBar = root.querySelector('[data-face-liveness-bar]');
        let stream = null;
        let livenessPassed = !livenessEnabled;
        let livenessTimer = null;
        let sampleCanvas = null;
        let previousFrame = null;
        let challenge = null;
        let progress = 0;
        let blinkPulses = 0;
        let blinkCoolDown = 0;
        let nodUp = false;
        let nodDown = false;
        let shakeLeft = false;
        let shakeRight = false;
        let lastDy = 0;
        let lastDx = 0;

        const setSnapEnabled = (enabled) => {
            if (!snapBtn) return;
            snapBtn.disabled = !enabled;
        };

        const setBadge = (text, isDone = false, visible = true) => {
            if (!badge) return;
            badge.hidden = !visible;
            badge.innerHTML = isDone ? text : `<i></i> ${text}`;
            badge.classList.toggle('is-done', isDone);
            badge.classList.toggle('is-live', !isDone && visible);
        };

        const showCapturedUi = (captured) => {
            retakeBtn?.classList.toggle('hidden', !captured);
            snapBtn?.classList.toggle('hidden', captured);
            startBtn?.classList.toggle('hidden', captured);
            if (captured) {
                setBadge('Captured ✓', true, true);
            } else if (stream) {
                setBadge('Face detected', false, true);
            } else {
                setBadge('Face detected', false, false);
            }
        };

        const stopLiveness = () => {
            if (livenessTimer) {
                clearInterval(livenessTimer);
                livenessTimer = null;
            }
            previousFrame = null;
        };

        const stopCamera = () => {
            stopLiveness();
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
        };

        const resetLivenessUi = () => {
            if (!livenessEnabled) return;
            livenessPassed = false;
            setSnapEnabled(false);
            livenessBox?.removeAttribute('hidden');
            root.classList.remove('is-liveness-passed');
            if (livenessBar) livenessBar.style.width = '0%';
            if (livenessStatus) livenessStatus.textContent = 'Waiting for camera…';
            if (livenessAction) livenessAction.textContent = 'Open camera to begin';
        };

        const markLivenessPassed = () => {
            livenessPassed = true;
            stopLiveness();
            root.classList.add('is-liveness-passed');
            if (livenessBar) livenessBar.style.width = '100%';
            if (livenessStatus) livenessStatus.textContent = 'Liveness verified — capture your face now.';
            setSnapEnabled(true);
        };

        const updateProgress = (value, statusText) => {
            progress = Math.max(0, Math.min(100, value));
            if (livenessBar) livenessBar.style.width = `${progress}%`;
            if (livenessStatus && statusText) livenessStatus.textContent = statusText;
        };

        const analyzeFramePair = (prev, curr, size) => {
            const rowPrev = new Float32Array(size);
            const rowCurr = new Float32Array(size);
            const colPrev = new Float32Array(size);
            const colCurr = new Float32Array(size);
            let upperDiff = 0;
            let upperCount = 0;
            let totalDiff = 0;

            for (let y = 0; y < size; y += 1) {
                let rp = 0;
                let rc = 0;
                for (let x = 0; x < size; x += 1) {
                    const i = y * size + x;
                    rp += prev[i];
                    rc += curr[i];
                    const d = Math.abs(curr[i] - prev[i]);
                    totalDiff += d;
                    if (y < size * 0.42) {
                        upperDiff += d;
                        upperCount += 1;
                    }
                }
                rowPrev[y] = rp / size;
                rowCurr[y] = rc / size;
            }

            for (let x = 0; x < size; x += 1) {
                let cp = 0;
                let cc = 0;
                for (let y = 0; y < size; y += 1) {
                    const i = y * size + x;
                    cp += prev[i];
                    cc += curr[i];
                }
                colPrev[x] = cp / size;
                colCurr[x] = cc / size;
            }

            const bestShift = (prevArr, currArr, maxShift) => {
                let best = 0;
                let bestScore = -Infinity;
                for (let shift = -maxShift; shift <= maxShift; shift += 1) {
                    let score = 0;
                    let count = 0;
                    for (let i = 0; i < size; i += 1) {
                        const j = i + shift;
                        if (j < 0 || j >= size) continue;
                        score -= Math.abs(currArr[i] - prevArr[j]);
                        count += 1;
                    }
                    if (count > 0) score /= count;
                    if (score > bestScore) {
                        bestScore = score;
                        best = shift;
                    }
                }
                return best;
            };

            return {
                dy: bestShift(rowPrev, rowCurr, 5),
                dx: bestShift(colPrev, colCurr, 5),
                upperAvg: upperCount ? upperDiff / upperCount : 0,
                totalAvg: totalDiff / (size * size),
            };
        };

        const startLiveness = () => {
            if (!livenessEnabled || !video) return;

            stopLiveness();
            livenessPassed = false;
            setSnapEnabled(false);
            previousFrame = null;
            progress = 0;
            blinkPulses = 0;
            blinkCoolDown = 0;
            nodUp = false;
            nodDown = false;
            shakeLeft = false;
            shakeRight = false;
            lastDy = 0;
            lastDx = 0;
            challenge = LIVENESS_CHALLENGES[Math.floor(Math.random() * LIVENESS_CHALLENGES.length)];

            livenessBox?.removeAttribute('hidden');
            root.classList.remove('is-liveness-passed');
            if (livenessAction) livenessAction.textContent = challenge.label;
            updateProgress(0, 'Follow the instruction carefully…');

            if (!sampleCanvas) {
                sampleCanvas = document.createElement('canvas');
            }

            const sampleSize = 56;
            sampleCanvas.width = sampleSize;
            sampleCanvas.height = sampleSize;
            const ctx = sampleCanvas.getContext('2d', { willReadFrequently: true });

            livenessTimer = setInterval(() => {
                if (!video.videoWidth || !video.videoHeight || !ctx || !challenge) return;

                const sourceSize = Math.min(video.videoWidth, video.videoHeight);
                const sourceX = (video.videoWidth - sourceSize) / 2;
                const sourceY = (video.videoHeight - sourceSize) / 2;

                ctx.drawImage(
                    video,
                    sourceX,
                    sourceY,
                    sourceSize,
                    sourceSize,
                    0,
                    0,
                    sampleSize,
                    sampleSize
                );

                const { data } = ctx.getImageData(0, 0, sampleSize, sampleSize);
                const gray = new Float32Array(sampleSize * sampleSize);

                for (let i = 0, p = 0; i < data.length; i += 4, p += 1) {
                    gray[p] = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
                }

                if (previousFrame) {
                    const motion = analyzeFramePair(previousFrame, gray, sampleSize);
                    const { dx, dy, upperAvg, totalAvg } = motion;

                    if (challenge.key === 'blink') {
                        // Eyes region pulse: strong upper change, little head travel
                        if (blinkCoolDown > 0) blinkCoolDown -= 1;

                        const isPulse = upperAvg > 14 && Math.abs(dx) <= 1 && Math.abs(dy) <= 2 && totalAvg < 28;
                        if (isPulse && blinkCoolDown === 0) {
                            blinkPulses += 1;
                            blinkCoolDown = 8; // ~1.1s gap between blinks
                        }

                        const pct = Math.min(100, Math.round((blinkPulses / 2) * 100));
                        updateProgress(
                            pct,
                            blinkPulses < 2
                                ? `Blink detected ${blinkPulses}/2 — keep blinking slowly`
                                : 'Blink verified…'
                        );

                        if (blinkPulses >= 2) markLivenessPassed();
                    } else if (challenge.key === 'nod') {
                        // Require clear up AND down head movement
                        if (dy <= -2) nodUp = true;
                        if (dy >= 2) nodDown = true;

                        // Ignore mostly horizontal movement
                        if (Math.abs(dx) > Math.abs(dy) + 1 && Math.abs(dx) >= 2) {
                            updateProgress(progress, 'Nod up and down — not side to side');
                        } else {
                            const done = (nodUp ? 1 : 0) + (nodDown ? 1 : 0);
                            updateProgress(
                                done * 50,
                                !nodUp && !nodDown
                                    ? 'Nod your head up, then down'
                                    : nodUp && !nodDown
                                        ? 'Good — now nod down'
                                        : !nodUp && nodDown
                                            ? 'Good — now nod up'
                                            : 'Nod verified…'
                            );
                            if (nodUp && nodDown) markLivenessPassed();
                        }

                        lastDy = dy;
                    } else if (challenge.key === 'shake') {
                        if (dx <= -2) shakeLeft = true;
                        if (dx >= 2) shakeRight = true;

                        if (Math.abs(dy) > Math.abs(dx) + 1 && Math.abs(dy) >= 2) {
                            updateProgress(progress, 'Shake left and right — not up/down');
                        } else {
                            const done = (shakeLeft ? 1 : 0) + (shakeRight ? 1 : 0);
                            updateProgress(
                                done * 50,
                                !shakeLeft && !shakeRight
                                    ? 'Turn head left, then right'
                                    : shakeLeft && !shakeRight
                                        ? 'Good — now turn right'
                                        : !shakeLeft && shakeRight
                                            ? 'Good — now turn left'
                                            : 'Shake verified…'
                            );
                            if (shakeLeft && shakeRight) markLivenessPassed();
                        }

                        lastDx = dx;
                    }
                }

                previousFrame = gray;
            }, 140);
        };

        if (livenessEnabled) {
            resetLivenessUi();
        } else {
            setSnapEnabled(true);
        }

        startBtn?.addEventListener('click', async () => {
            try {
                // Keep session/CSRF alive while camera permissions open
                refreshCsrfToken(root.closest('form'));

                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                    audio: false,
                });
                video.srcObject = stream;
                await video.play();
                video.classList.remove('hidden');
                video.classList.add('is-active');
                root.querySelector('.face-capture-media')?.classList.add('is-live');
                preview?.classList.add('hidden');
                preview?.classList.remove('is-active');
                root.querySelector('.face-capture-media')?.classList.remove('has-preview');
                input.value = '';
                setBadge('Face detected', false, true);
                startLiveness();
            } catch (error) {
                showAuthNotice({
                    title: 'Camera Access Denied',
                    message: 'Camera access was denied. Please check your browser permissions.',
                    variant: 'error',
                });
            }
        });

        snapBtn?.addEventListener('click', () => {
            if (!video || !canvas || !input) return;

            if (livenessEnabled && !livenessPassed) {
                showAuthNotice({
                    title: 'Complete Liveness First',
                    message: challenge?.label
                        ? `Please complete this action first: ${challenge.label}`
                        : 'Please complete the liveness action before capturing your face.',
                    variant: 'info',
                });
                return;
            }

            if (!video.videoWidth || !video.videoHeight) {
                showAuthNotice({
                    title: 'Open Camera First',
                    message: 'Please open the camera first.',
                    variant: 'info',
                });
                return;
            }

            const sourceSize = Math.min(video.videoWidth, video.videoHeight);
            const sourceX = (video.videoWidth - sourceSize) / 2;
            const sourceY = (video.videoHeight - sourceSize) / 2;
            const outputSize = 320;

            canvas.width = outputSize;
            canvas.height = outputSize;
            const ctx = canvas.getContext('2d');
            ctx?.drawImage(
                video,
                sourceX,
                sourceY,
                sourceSize,
                sourceSize,
                0,
                0,
                outputSize,
                outputSize
            );

            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            input.value = dataUrl;
            if (preview) {
                preview.src = dataUrl;
                preview.classList.remove('hidden');
                preview.classList.add('is-active');
            }
            root.querySelector('.face-capture-media')?.classList.add('has-preview');
            root.querySelector('.face-capture-media')?.classList.remove('is-live');
            video.classList.add('hidden');
            video.classList.remove('is-active');
            stopCamera();

            if (livenessStatus) {
                livenessStatus.textContent = 'Face captured successfully.';
            }

            showCapturedUi(true);
            refreshCsrfToken(root.closest('form'));
        });

        retakeBtn?.addEventListener('click', () => {
            input.value = '';
            preview?.classList.add('hidden');
            preview?.classList.remove('is-active');
            if (preview) preview.removeAttribute('src');
            showCapturedUi(false);
            resetLivenessUi();
            startBtn?.click();
        });

        if (input?.value?.startsWith('data:image') && preview) {
            preview.src = input.value;
            preview.classList.remove('hidden');
            preview.classList.add('is-active');
            root.querySelector('.face-capture-media')?.classList.add('has-preview');
            video?.classList.add('hidden');
            showCapturedUi(true);
            if (livenessEnabled) {
                livenessPassed = true;
                setSnapEnabled(true);
                livenessBox?.removeAttribute('hidden');
                root.classList.add('is-liveness-passed');
                if (livenessBar) livenessBar.style.width = '100%';
                if (livenessStatus) livenessStatus.textContent = 'Face already captured.';
                if (livenessAction) livenessAction.textContent = 'Ready';
            }
        } else {
            showCapturedUi(false);
        }

        window.addEventListener('beforeunload', stopCamera);
}

/**
 * Registration only gates unreadable or unusably small images. Whether the
 * image is the selected document is a manual KYC-review decision.
 */
function analyzeIdCardContent(canvas) {
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx || !canvas.width || !canvas.height) {
        return { ok: false, reason: 'Could not read the captured image. Please try again.' };
    }

    if (Math.min(canvas.width, canvas.height) < 160) {
        return { ok: false, reason: 'Image resolution is too low to review. Please upload a clearer image.' };
    }

    return { ok: true };
}

function analyzeIdCardDataUrl(dataUrl) {
    return new Promise((resolve) => {
        if (!dataUrl?.startsWith('data:image')) {
            resolve({ ok: false, reason: 'Please capture your ID card photo before continuing.' });
            return;
        }

        const img = new Image();
        img.onload = () => {
            const checkCanvas = document.createElement('canvas');
            checkCanvas.width = img.naturalWidth || img.width;
            checkCanvas.height = img.naturalHeight || img.height;
            const ctx = checkCanvas.getContext('2d');
            if (!ctx) {
                resolve({ ok: false, reason: 'Could not analyze the captured image. Please try again.' });
                return;
            }
            ctx.drawImage(img, 0, 0);
            resolve(analyzeIdCardContent(checkCanvas));
        };
        img.onerror = () => {
            resolve({ ok: false, reason: 'Could not read the captured image. Please capture again.' });
        };
        img.src = dataUrl;
    });
}

function markIdCardValid(input, root, valid) {
    if (input) {
        input.dataset.idValid = valid ? '1' : '0';
    }
    root?.classList.toggle('is-id-valid', Boolean(valid));
    root?.classList.toggle('is-id-invalid', !valid && Boolean(input?.value));
}

function initRegisterDocumentUploads() {
    const panel = document.querySelector('[data-reg-doc-panel]');
    if (!panel) return;

    const picker = panel.querySelector('[data-doc-picker]');
    const typeInput = panel.querySelector('[data-id-doc-type]');
    const frontInput = panel.querySelector('[data-id-card-input]');
    const backInput = panel.querySelector('[data-id-card-back-input]');
    const maxBytes = 5 * 1024 * 1024;
    const docFlow = ['national_id', 'passport', 'license'];
    const docStore = {
        national_id: { front: '', back: '' },
        passport: { front: '', back: '' },
        license: { front: '', back: '' },
    };

    const screens = [...panel.querySelectorAll('[data-doc-upload]')];

    const saveCurrentDoc = () => {
        const kind = typeInput?.value;
        if (!kind || !docStore[kind]) return;
        docStore[kind].front = frontInput?.value || '';
        docStore[kind].back = kind === 'passport' ? '' : (backInput?.value || '');
    };

    const applyDocToInputs = (kind) => {
        const data = docStore[kind] || { front: '', back: '' };
        if (frontInput) frontInput.value = data.front || '';
        if (backInput) backInput.value = kind === 'passport' ? '' : (data.back || '');
        const screen = screens.find((item) => item.dataset.docUpload === kind);
        screen?.querySelectorAll('[data-doc-slot]').forEach((slot) => {
            const side = slot.dataset.docSlot;
            setSlotPreview(slot, side === 'front' ? data.front : data.back);
        });
    };

    const isDocComplete = (kind) => {
        const data = docStore[kind];
        if (!data?.front?.startsWith('data:image')) return false;
        if (kind !== 'passport' && !data.back?.startsWith('data:image')) return false;
        return true;
    };

    const findCompleteDoc = () => {
        saveCurrentDoc();
        for (let i = docFlow.length - 1; i >= 0; i -= 1) {
            if (isDocComplete(docFlow[i])) return docFlow[i];
        }
        return null;
    };

    const keepOnly = (kind) => {
        Object.keys(docStore).forEach((key) => {
            if (key === kind) return;
            docStore[key].front = '';
            docStore[key].back = '';
        });
    };

    const showPicker = () => {
        saveCurrentDoc();
        const selected = typeInput?.value || '';
        const restore = (selected && isDocComplete(selected)) ? selected : findCompleteDoc();
        picker.hidden = false;
        screens.forEach((screen) => {
            screen.hidden = true;
        });
        if (restore) {
            if (typeInput) typeInput.value = restore;
            applyDocToInputs(restore);
            return;
        }
        if (typeInput) typeInput.value = '';
        if (frontInput) frontInput.value = '';
        if (backInput) backInput.value = '';
    };

    const showScreen = (kind) => {
        saveCurrentDoc();
        picker.hidden = Boolean(kind);
        screens.forEach((screen) => {
            screen.hidden = screen.dataset.docUpload !== kind;
        });
        if (typeInput && kind) typeInput.value = kind;
        applyDocToInputs(kind);
    };

    panel.__ocStartDocFlow = () => {
        const kind = typeInput?.value;
        if (kind && docStore[kind]) {
            showScreen(kind);
            return;
        }
        showPicker();
    };

    panel.__ocBackToPicker = () => {
        const uploadOpen = screens.some((screen) => !screen.hidden);
        if (!uploadOpen) return false;
        showPicker();
        return true;
    };

    panel.__ocPrepareBestDoc = () => {
        saveCurrentDoc();
        const selected = typeInput?.value || '';
        const kind = (selected && isDocComplete(selected)) ? selected : findCompleteDoc();
        if (!kind) return false;
        if (typeInput) typeInput.value = kind;
        applyDocToInputs(kind);
        return true;
    };

    const setSlotPreview = (slot, dataUrl) => {
        const empty = slot.querySelector('.oc-idup-drop-empty');
        const previewWrap = slot.querySelector('.oc-idup-drop-preview');
        const img = slot.querySelector('[data-doc-preview]');
        if (dataUrl) {
            if (img) img.src = dataUrl;
            empty.hidden = true;
            previewWrap.hidden = false;
        } else {
            if (img) img.removeAttribute('src');
            empty.hidden = false;
            previewWrap.hidden = true;
        }
    };

    const setSlotError = (slot, message) => {
        const error = slot.querySelector('[data-doc-error]');
        if (!error) return;
        error.hidden = !message;
        error.textContent = message || '';
    };

    const readFileAsDataUrl = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Could not read that file.'));
        reader.readAsDataURL(file);
    });

    const applyFile = async (slot, file) => {
        const side = slot.dataset.docSlot;
        setSlotError(slot, '');
        if (!file) return;

        if (file.type === 'application/pdf') {
            setSlotError(slot, 'Please upload a JPG or PNG photo of the document, not a PDF file.');
            return;
        }

        if (!/^image\/(jpeg|jpg|png|webp)$/i.test(file.type)) {
            setSlotError(slot, 'Only JPG or PNG photos of the real document are accepted.');
            return;
        }

        if (file.size > maxBytes) {
            setSlotError(slot, 'File is larger than 5MB. Please upload a smaller image.');
            return;
        }

        const dataUrl = await readFileAsDataUrl(file);
        const analysis = await analyzeIdCardDataUrl(dataUrl);
        const kind = typeInput?.value;
        if (!analysis.ok) {
            if (side === 'front' && frontInput) frontInput.value = '';
            if (side === 'back' && backInput) backInput.value = '';
            if (kind && docStore[kind]) {
                if (side === 'front') docStore[kind].front = '';
                if (side === 'back') docStore[kind].back = '';
            }
            setSlotPreview(slot, '');
            setSlotError(slot, analysis.reason);
            showAuthNotice({
                title: 'Document Not Accepted',
                message: analysis.reason,
                variant: 'error',
            });
            return;
        }

        if (side === 'front' && frontInput) frontInput.value = dataUrl;
        if (side === 'back' && backInput) backInput.value = dataUrl;
        if (kind && docStore[kind]) {
            if (side === 'front') docStore[kind].front = dataUrl;
            if (side === 'back') docStore[kind].back = dataUrl;
            keepOnly(kind);
            const stagingInput = panel.querySelector('[data-id-staging-input]');
            if (stagingInput) stagingInput.value = '';
        }
        setSlotPreview(slot, dataUrl);
    };

    panel.querySelectorAll('[data-doc-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            showScreen(btn.dataset.docOpen);
        });
    });

    panel.querySelectorAll('[data-doc-change]').forEach((btn) => {
        btn.addEventListener('click', () => {
            showPicker();
        });
    });

    screens.forEach((screen) => {
        screen.querySelectorAll('[data-doc-slot]').forEach((slot) => {
            const fileInput = slot.querySelector('[data-doc-file]');
            const drop = slot.querySelector('[data-doc-drop]');

            drop?.addEventListener('click', (event) => {
                if (event.target.closest('[data-doc-replace]')) {
                    event.preventDefault();
                    fileInput?.click();
                    return;
                }
                fileInput?.click();
            });

            drop?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    fileInput?.click();
                }
            });

            ['dragenter', 'dragover'].forEach((name) => {
                drop?.addEventListener(name, (event) => {
                    event.preventDefault();
                    drop.classList.add('is-drag');
                });
            });
            ['dragleave', 'drop'].forEach((name) => {
                drop?.addEventListener(name, (event) => {
                    event.preventDefault();
                    drop.classList.remove('is-drag');
                });
            });
            drop?.addEventListener('drop', (event) => {
                const file = event.dataTransfer?.files?.[0];
                if (file) applyFile(slot, file);
            });

            fileInput?.addEventListener('change', () => {
                const file = fileInput.files?.[0];
                applyFile(slot, file);
                fileInput.value = '';
            });
        });
    });

    if (typeInput?.value) {
        showScreen(typeInput.value);
    }
}

function initIdCardCapture() {
    document.querySelectorAll('[data-id-card-video]').forEach((video) => {
        const root = video.closest('[data-id-card-capture]');
        if (!root) return;
        const canvas = root.querySelector('[data-id-card-canvas]');
        const preview = root.querySelector('[data-id-card-preview]');
        const input = root.querySelector('[data-id-card-input]');
        const startBtn = root.querySelector('[data-id-card-start]');
        const snapBtn = root.querySelector('[data-id-card-snap]');
        const retakeBtn = root.querySelector('[data-id-card-retake]');
        const galleryBtn = root.querySelector('[data-id-card-gallery]');
        const fileInput = root.querySelector('[data-id-card-file]');
        const statusHint = root.querySelector('[data-id-card-status]');
        const badge = root.querySelector('[data-id-card-badge]');
        const guide = root.querySelector('[data-id-card-guide]');
        const flashBtn = root.querySelector('.kyc-doc-flash');
        const preferSquare = root.classList.contains('id-card-capture-square');
        let stream = null;

        const stopCamera = () => {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
        };

        const setStatus = (message, isError = false) => {
            if (!statusHint) return;
            statusHint.textContent = message;
            statusHint.classList.toggle('is-error', isError);
        };

        const setBadge = (text, done = false) => {
            if (!badge) return;
            badge.innerHTML = done ? text : `<i></i> ${text}`;
            badge.classList.toggle('is-done', done);
            badge.classList.toggle('is-live', !done);
        };

        const showCapturedUi = (captured) => {
            retakeBtn?.classList.toggle('hidden', !captured);
            snapBtn?.classList.toggle('hidden', captured);
            galleryBtn?.classList.toggle('hidden', captured);
            flashBtn?.classList.toggle('hidden', captured);
            guide?.classList.toggle('hidden', captured || Boolean(stream));
            root.classList.toggle('is-camera-on', Boolean(stream) && !captured);
            if (captured) {
                setBadge('Captured ✓', true);
            } else if (stream) {
                setBadge('Auto-detect enabled', false);
            } else {
                setBadge('Auto-detect enabled', false);
            }
        };

        const openCamera = async () => {
            const constraints = [
                {
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1280 },
                        height: { ideal: 1280 },
                    },
                    audio: false,
                },
                { video: { facingMode: 'user' }, audio: false },
                { video: true, audio: false },
            ];

            let lastError = null;
            for (const constraint of constraints) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia(constraint);
                    break;
                } catch (error) {
                    lastError = error;
                }
            }

            if (!stream) {
                throw lastError ?? new Error('Camera unavailable');
            }

            video.srcObject = stream;
            await video.play();
            video.classList.remove('hidden');
            video.classList.add('is-active');
            preview?.classList.add('hidden');
            preview?.classList.remove('is-active');
            guide?.classList.add('hidden');
            input.value = '';
            markIdCardValid(input, root, false);
            showCapturedUi(false);
            setStatus('Show your ID card clearly in the camera, then tap Capture.');
        };

        startBtn?.addEventListener('click', async () => {
            try {
                await openCamera();
            } catch (error) {
                showAuthNotice({
                    title: 'Camera Access Denied',
                    message: 'Camera access was denied. Please check your browser permissions.',
                    variant: 'error',
                });
            }
        });

        galleryBtn?.addEventListener('click', () => {
            fileInput?.click();
        });

        fileInput?.addEventListener('change', async () => {
            const file = fileInput.files?.[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                showAuthNotice({
                    title: 'Invalid File',
                    message: 'Please choose an image file of your ID card.',
                    variant: 'error',
                });
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                const dataUrl = String(reader.result || '');
                if (!dataUrl.startsWith('data:image')) {
                    showAuthNotice({
                        title: 'Invalid Image',
                        message: 'Could not read that image. Please try another photo.',
                        variant: 'error',
                    });
                    return;
                }

                const img = new Image();
                img.onload = () => {
                    const checkCanvas = document.createElement('canvas');
                    checkCanvas.width = img.naturalWidth || img.width;
                    checkCanvas.height = img.naturalHeight || img.height;
                    checkCanvas.getContext('2d')?.drawImage(img, 0, 0);
                    const analysis = analyzeIdCardContent(checkCanvas);

                    if (!analysis.ok) {
                        input.value = '';
                        markIdCardValid(input, root, false);
                        setStatus(analysis.reason, true);
                        showAuthNotice({
                            title: 'ID Card Not Detected',
                            message: analysis.reason,
                            variant: 'error',
                        });
                        return;
                    }

                    stopCamera();
                    input.value = dataUrl;
                    markIdCardValid(input, root, true);
                    if (preview) {
                        preview.src = dataUrl;
                        preview.classList.remove('hidden');
                        preview.classList.add('is-active');
                    }
                    video?.classList.add('hidden');
                    video?.classList.remove('is-active');
                    showCapturedUi(true);
                    setStatus('ID card details detected. You can continue or Retake.');
                };
                img.src = dataUrl;
            };
            reader.readAsDataURL(file);
            fileInput.value = '';
        });

        snapBtn?.addEventListener('click', async () => {
            if (!video || !canvas || !input) return;

            if (!stream || !video.videoWidth || !video.videoHeight) {
                try {
                    await openCamera();
                    setStatus('Camera ready. Align your ID, then tap Capture again.');
                } catch (error) {
                    showAuthNotice({
                        title: 'Camera Access Denied',
                        message: 'Camera access was denied. Please check your browser permissions.',
                        variant: 'error',
                    });
                }
                return;
            }

            if (preferSquare) {
                const sourceSize = Math.min(video.videoWidth, video.videoHeight);
                const sourceX = (video.videoWidth - sourceSize) / 2;
                const sourceY = (video.videoHeight - sourceSize) / 2;
                const outputSize = Math.min(960, sourceSize);

                canvas.width = outputSize;
                canvas.height = outputSize;
                canvas.getContext('2d')?.drawImage(
                    video,
                    sourceX,
                    sourceY,
                    sourceSize,
                    sourceSize,
                    0,
                    0,
                    outputSize,
                    outputSize
                );
            } else {
                const maxWidth = 1280;
                let width = video.videoWidth;
                let height = video.videoHeight;

                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d')?.drawImage(video, 0, 0, width, height);
            }

            const analysis = analyzeIdCardContent(canvas);
            if (!analysis.ok) {
                input.value = '';
                markIdCardValid(input, root, false);
                setStatus(analysis.reason, true);
                showAuthNotice({
                    title: 'ID Card Not Detected',
                    message: analysis.reason,
                    variant: 'error',
                });
                return;
            }

            const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
            input.value = dataUrl;
            markIdCardValid(input, root, true);

            if (preview) {
                preview.src = dataUrl;
                preview.classList.remove('hidden');
                preview.classList.add('is-active');
            }

            video.classList.add('hidden');
            video.classList.remove('is-active');
            stopCamera();
            showCapturedUi(true);
            setStatus('ID card details detected. You can continue or Retake.');
        });

        retakeBtn?.addEventListener('click', async () => {
            input.value = '';
            markIdCardValid(input, root, false);
            preview?.classList.add('hidden');
            preview?.classList.remove('is-active');
            if (preview) preview.removeAttribute('src');
            showCapturedUi(false);
            setStatus('Retake: align your ID and capture again.');
            try {
                await openCamera();
            } catch (error) {
                setStatus('Allow camera access, or use Gallery to upload your ID.');
            }
        });

        if (input?.value?.startsWith('data:image') && preview) {
            const img = new Image();
            img.onload = () => {
                const checkCanvas = document.createElement('canvas');
                checkCanvas.width = img.naturalWidth || img.width;
                checkCanvas.height = img.naturalHeight || img.height;
                checkCanvas.getContext('2d')?.drawImage(img, 0, 0);
                const analysis = analyzeIdCardContent(checkCanvas);

                if (!analysis.ok) {
                    input.value = '';
                    markIdCardValid(input, root, false);
                    preview.classList.add('hidden');
                    preview.classList.remove('is-active');
                    showCapturedUi(false);
                    setStatus(analysis.reason, true);
                    return;
                }

                preview.src = input.value;
                preview.classList.remove('hidden');
                preview.classList.add('is-active');
                video?.classList.add('hidden');
                markIdCardValid(input, root, true);
                showCapturedUi(true);
                setStatus('ID card details detected. You can continue or Retake.');
            };
            img.src = input.value;
        } else {
            markIdCardValid(input, root, false);
            showCapturedUi(false);
        }

        window.addEventListener('beforeunload', stopCamera);
    });
}

function initSelfieCapture() {
    document.querySelectorAll('[data-selfie-capture]').forEach((root) => {
        const video = root.querySelector('[data-selfie-video]');
        const canvas = root.querySelector('[data-selfie-canvas]');
        const preview = root.querySelector('[data-selfie-preview]');
        const input = root.querySelector('[data-selfie-input]');
        const stagingInput = root.querySelector('[data-selfie-staging-input]');
        const snapBtn = root.querySelector('[data-selfie-snap]');
        const retakeBtn = root.querySelector('[data-selfie-retake]');
        const galleryBtn = root.querySelector('[data-selfie-gallery]');
        const fileInput = root.querySelector('[data-selfie-file]');
        const status = root.querySelector('[data-selfie-status]');
        let stream = null;

        const stopCamera = () => {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
        };

        const setStatus = (text, variant = '') => {
            if (!status) return;
            status.textContent = text;
            status.classList.remove('is-error', 'is-success');
            if (variant) status.classList.add(`is-${variant}`);
        };

        const showPreview = (dataUrl) => {
            if (preview) {
                preview.src = dataUrl;
                preview.classList.remove('hidden');
            }
            video?.classList.add('hidden');
            retakeBtn?.classList.remove('hidden');
        };

        const openCamera = async () => {
            stopCamera();
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                    audio: false,
                });
                if (video) {
                    video.srcObject = stream;
                    video.classList.remove('hidden');
                    await video.play();
                }
                preview?.classList.add('hidden');
            } catch (_) {
                setStatus('Camera access denied. Upload a selfie from gallery instead.', 'error');
            }
        };

        const captureFrame = () => {
            if (!video?.videoWidth || !canvas) return null;
            const size = Math.min(video.videoWidth, video.videoHeight);
            const sx = (video.videoWidth - size) / 2;
            const sy = (video.videoHeight - size) / 2;
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');
            ctx?.drawImage(video, sx, sy, size, size, 0, 0, size, size);
            return canvas.toDataURL('image/jpeg', 0.82);
        };

        const rejectNonFace = (message) => {
            if (input) input.value = '';
            if (stagingInput) stagingInput.value = '';
            preview?.classList.add('hidden');
            retakeBtn?.classList.add('hidden');
            video?.classList.remove('hidden');
            setStatus(message, 'error');
            showAuthNotice({
                title: 'Face required',
                message,
                variant: 'error',
            });
        };

        const applyCapture = async (dataUrl, { allowCrop = false } = {}) => {
            let finalUrl = dataUrl;

            if (allowCrop) {
                const cropped = await openCircleCrop(dataUrl, {
                    title: 'Position your face in the circle',
                    hint: 'Drag to move, zoom in/out, then confirm. Only a clear face will pass verification.',
                    outputSize: 480,
                    quality: 0.85,
                });
                if (!cropped) {
                    setStatus('Crop cancelled. Capture or upload again.');
                    return;
                }
                finalUrl = cropped;
            }

            const probe = new Image();
            const loaded = await new Promise((resolve) => {
                probe.onload = () => resolve(true);
                probe.onerror = () => resolve(false);
                probe.src = finalUrl;
            });
            if (!loaded) {
                rejectNonFace('Could not read that image. Upload a clear selfie of your face.');
                return;
            }

            const closeUp = await detectCloseUpFace(probe);
            const heuristic = looksLikeFaceFromImage(probe);
            if (closeUp !== true && !heuristic) {
                rejectNonFace('No close-up face detected. Fill the circle with your face — animals, cars, and landscapes are not allowed.');
                return;
            }

            const form = root.closest('form');
            const idToken = form?.querySelector('[data-id-staging-input]')?.value;
            setStatus('Checking face against your ID…');

            if (isKycToken(idToken) && form) {
                try {
                    const staged = await stageSelfieImage(finalUrl, idToken, form);
                    if (stagingInput) stagingInput.value = staged.selfie_staging_token;
                    if (input) input.value = finalUrl;
                    showPreview(finalUrl);
                    stopCamera();
                    const score = staged.liveness_probability != null
                        ? ` (${Math.round(Number(staged.liveness_probability))}%)`
                        : '';
                    setStatus(
                        staged.liveness_passed
                            ? `Face verified. Live person check passed${score}.`
                            : 'Face verified. Continue when ready or retake.',
                        'success',
                    );
                    return;
                } catch (error) {
                    rejectNonFace(error.message || 'No human face detected. Use a clear selfie of your own face.');
                    return;
                }
            }

            if (input) input.value = finalUrl;
            if (stagingInput) stagingInput.value = '';
            showPreview(finalUrl);
            stopCamera();
            setStatus('Face detected. Continue when ready or retake.', 'success');
        };

        snapBtn?.addEventListener('click', async () => {
            if (!stream) await openCamera();
            const dataUrl = captureFrame();
            if (!dataUrl) {
                setStatus('Could not capture selfie. Try again.', 'error');
                return;
            }
            await applyCapture(dataUrl, { allowCrop: true });
        });

        retakeBtn?.addEventListener('click', async () => {
            if (input) input.value = '';
            if (stagingInput) stagingInput.value = '';
            retakeBtn.classList.add('hidden');
            preview?.classList.add('hidden');
            setStatus('Open camera or upload a clear selfie photo.');
            await openCamera();
        });

        galleryBtn?.addEventListener('click', () => fileInput?.click());

        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async () => {
                if (typeof reader.result === 'string') {
                    await applyCapture(reader.result, { allowCrop: true });
                }
            };
            reader.readAsDataURL(file);
            fileInput.value = '';
        });

        if (input?.value?.startsWith('data:image')) {
            showPreview(input.value);
            setStatus('Selfie ready. Continue when ready or retake.', 'success');
        }

        window.addEventListener('beforeunload', stopCamera);
    });
}

function initAuthWizard() {
    document.querySelectorAll('[data-auth-wizard]').forEach((form) => {
        const shell = form.closest('.oc-register-page, .auth-card, .oc-onb-card');
        const panels = Array.from(form.querySelectorAll('[data-wizard-step]'));
        const indicators = Array.from(shell?.querySelectorAll('[data-step-indicator]') || []);
        const extras = Array.from(shell?.querySelectorAll('[data-wizard-extra-step]') || []);
        const mode = form.dataset.wizardMode || 'login';
        const maxStep = mode === 'register' ? 4 : 3;
        let current = 1;

        const indicatorForWizardStep = (step) => {
            if (step === 1) return 1;
            if (step >= 2 && step <= 4) return 2;
            return 3;
        };

        const updateVerifyHubState = () => {
            if (mode !== 'register') return;
            const idDone = isKycToken(form.querySelector('[data-id-staging-input]')?.value);
            const selfieDone = isKycToken(form.querySelector('[data-selfie-staging-input]')?.value);

            ['id', 'selfie'].forEach((key) => {
                const done = (key === 'id' && idDone) || (key === 'selfie' && selfieDone);
                const badge = form.querySelector(`[data-verify-done="${key}"]`);
                if (badge) badge.hidden = !done;
                const card = form.querySelector(`[data-verify-card="${key}"]`);
                card?.classList.toggle('is-done', done);
            });

            const hubBtn = form.querySelector('#create-account-hub-btn');
            if (hubBtn) hubBtn.disabled = !(idDone && selfieDone);
        };

        const toggleRegisterChrome = (step) => {
            const page = form.closest('.oc-register-page');
            const head = page?.querySelector('[data-register-head]');
            const stepper = page?.querySelector('[data-auth-steps]');
            const divider = page?.querySelector('[data-register-divider]');
            const extraBlock = page?.querySelector('[data-wizard-extra-step]');

            if (!page) return;

            head?.toggleAttribute('hidden', step !== 1);
            stepper?.toggleAttribute('hidden', step >= 3);
            divider?.toggleAttribute('hidden', step !== 1);
            extraBlock?.toggleAttribute('hidden', step !== 1);
            page.classList.toggle('is-verify-hub', step === 2);
            page.classList.toggle('is-verify-detail', step >= 3);
        };

        const startDocFlowIfNeeded = (step) => {
            if (mode === 'register' && step === 3) {
                form.querySelector('[data-reg-doc-panel]')?.__ocStartDocFlow?.();
            }
        };

        const showStep = (step) => {
            current = step;
            if (shell) {
                shell.dataset.currentStep = String(step);
            }
            form.dataset.currentStep = String(step);
            startDocFlowIfNeeded(step);

            panels.forEach((panel) => {
                const panelStep = Number(panel.dataset.wizardStep);
                const active = panelStep === step;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });

            const mappedIndicator = indicatorForWizardStep(step);
            indicators.forEach((item) => {
                const itemStep = Number(item.dataset.stepIndicator);
                const isActive = itemStep === mappedIndicator;
                const isDone = itemStep < mappedIndicator;
                item.classList.toggle('is-active', isActive);
                item.classList.toggle('is-done', isDone);
                const num = item.querySelector('.auth-step-num, .oc-onb-step-dot');
                if (num) {
                    num.textContent = isDone ? '✓' : String(itemStep);
                }
            });

            extras.forEach((extra) => {
                const extraStep = Number(extra.dataset.wizardExtraStep);
                extra.hidden = extraStep !== step;
            });

            if (mode === 'register') {
                toggleRegisterChrome(step);
            }

            if (step === 2) {
                updateVerifyHubState();
            }
        };

        const validateStep = (step) => {
            if (step === 1) {
                const email = form.querySelector('#email');
                const password = form.querySelector('#password');
                const country = form.querySelector('[data-country-value], input[name="country"]');
                const name = form.querySelector('#name');
                const passwordConfirm = form.querySelector('#password_confirmation');
                const terms = form.querySelector('#terms');

                if (mode === 'register') {
                    if (!name?.value?.trim()) {
                        showAuthNotice({ title: 'Name Required', message: 'Please enter your full name.', variant: 'error' });
                        name?.focus();
                        return false;
                    }
                }

                if (!email?.value?.trim()) {
                    showAuthNotice({ title: 'Email Required', message: 'Please enter your email address.', variant: 'error' });
                    email?.focus();
                    return false;
                }

                if (email && !email.checkValidity()) {
                    showAuthNotice({ title: 'Invalid Email', message: 'Please enter a valid email address.', variant: 'error' });
                    email.focus();
                    return false;
                }

                if (!country?.value) {
                    showAuthNotice({ title: 'Country Required', message: 'Please select your country ID.', variant: 'error' });
                    return false;
                }

                if (!password?.value) {
                    showAuthNotice({ title: 'Password Required', message: 'Please enter your password.', variant: 'error' });
                    password?.focus();
                    return false;
                }

                if (mode === 'register') {
                    if (password.value.length < 8) {
                        showAuthNotice({
                            title: 'Weak Password',
                            message: 'Password must be at least 8 characters.',
                            variant: 'error',
                        });
                        password.focus();
                        return false;
                    }

                    if (password.value !== passwordConfirm?.value) {
                        showAuthNotice({
                            title: 'Password Mismatch',
                            message: 'Password confirmation does not match.',
                            variant: 'error',
                        });
                        passwordConfirm?.focus();
                        return false;
                    }

                    if (!terms?.checked) {
                        const termsError = document.getElementById('terms-error');
                        if (termsError) {
                            termsError.hidden = false;
                            termsError.textContent = 'You must read and accept the User Agreement before creating an account.';
                        }
                        showAuthNotice({
                            title: 'Agreement Required',
                            message: 'Please accept the User Agreement to continue.',
                            variant: 'error',
                        });
                        terms?.focus();
                        return false;
                    }
                }

                return true;
            }

            if (step === 2) {
                const idDone = isKycToken(form.querySelector('[data-id-staging-input]')?.value);
                const selfieDone = isKycToken(form.querySelector('[data-selfie-staging-input]')?.value);

                if (!idDone || !selfieDone) {
                    showAuthNotice({
                        title: 'Verification Incomplete',
                        message: 'Please finish ID upload and selfie capture, then create your account.',
                        variant: 'error',
                    });
                    return false;
                }

                return true;
            }

            if (step === 3) {
                const idRoot = form.querySelector('[data-id-card-capture]');
                const idInput = form.querySelector('[data-id-card-input]');
                const backInput = form.querySelector('[data-id-card-back-input]');
                const typeInput = form.querySelector('[data-id-doc-type]');
                const stagingInput = form.querySelector('[data-id-staging-input]');
                const hasFront = idInput?.value?.startsWith('data:image');
                const needsBack = typeInput?.value && typeInput.value !== 'passport';
                const hasBack = backInput?.value?.startsWith('data:image');
                const hasStaging = isKycToken(stagingInput?.value);

                if (hasStaging && hasFront && (!needsBack || hasBack)) {
                    markIdCardValid(idInput, idRoot, true);
                    return true;
                }

                if (!typeInput?.value) {
                    showAuthNotice({
                        title: 'Document Type Required',
                        message: 'Choose one document: International Passport, Driver’s License, or National ID Card.',
                        variant: 'error',
                    });
                    return false;
                }

                if (!hasFront || (needsBack && !hasBack)) {
                    markIdCardValid(idInput, idRoot, false);
                    showAuthNotice({
                        title: needsBack ? 'Front and Back Required' : 'Passport Photo Page Required',
                        message: needsBack
                            ? 'Upload both sides of the document. It will be submitted for manual review.'
                            : 'Upload the passport photo page. It will be submitted for manual review.',
                        variant: 'error',
                    });
                    return false;
                }

                const checks = [analyzeIdCardDataUrl(idInput.value)];
                if (needsBack) {
                    checks.push(analyzeIdCardDataUrl(backInput.value));
                }

                return Promise.all(checks).then(async ([frontAnalysis, backAnalysis]) => {
                    if (!frontAnalysis.ok || (needsBack && !backAnalysis?.ok)) {
                        if (stagingInput) stagingInput.value = '';
                        markIdCardValid(idInput, idRoot, false);
                        showAuthNotice({
                            title: 'Document Not Accepted',
                            message: frontAnalysis.ok ? (backAnalysis?.reason || 'Document not accepted.') : frontAnalysis.reason,
                            variant: 'error',
                        });
                        return false;
                    }

                    try {
                        const staged = await stageIdCardImage(idInput.value, form, needsBack ? backInput.value : null);
                        if (stagingInput) {
                            stagingInput.value = staged.id_staging_token;
                        }
                    } catch (error) {
                        showAuthNotice({
                            title: 'ID Upload Failed',
                            message: error.message || 'Could not save your ID document. Please try again.',
                            variant: 'error',
                        });
                        return false;
                    }

                    markIdCardValid(idInput, idRoot, true);
                    return true;
                });
            }

            if (step === 4) {
                const selfieInput = form.querySelector('[data-selfie-input]');
                const selfieStagingInput = form.querySelector('[data-selfie-staging-input]');
                const idStagingInput = form.querySelector('[data-id-staging-input]');
                const hasImage = selfieInput?.value?.startsWith('data:image');
                const hasStaging = isKycToken(selfieStagingInput?.value);

                if (hasStaging) {
                    return true;
                }

                if (!hasImage) {
                    showAuthNotice({
                        title: 'Selfie Required',
                        message: 'Please capture or upload a clear selfie before continuing.',
                        variant: 'error',
                    });
                    return false;
                }

                const idToken = idStagingInput?.value;
                if (!isKycToken(idToken)) {
                    showAuthNotice({
                        title: 'ID Session Expired',
                        message: 'Please go back and capture your ID document again.',
                        variant: 'error',
                    });
                    return false;
                }

                return stageSelfieImage(selfieInput.value, idToken, form).then((staged) => {
                    if (selfieStagingInput) {
                        selfieStagingInput.value = staged.selfie_staging_token;
                    }
                    const selfieStatus = form.querySelector('[data-selfie-status]');
                    if (selfieStatus && staged.liveness_passed) {
                        const score = staged.liveness_probability != null ? ` (${Math.round(Number(staged.liveness_probability))}%)` : '';
                        selfieStatus.textContent = `Selfie saved. Live person check passed${score}.`;
                    }
                    return true;
                }).catch((error) => {
                    showAuthNotice({
                        title: 'Selfie Verification Failed',
                        message: error.message || 'Could not verify your selfie. Please try again.',
                        variant: 'error',
                    });
                    return false;
                });
            }

            return true;
        };

        form.querySelectorAll('[data-wizard-next]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                btn.disabled = true;
                try {
                    if (mode === 'register' && current === 3) {
                        const staged = await Promise.resolve(validateStep(3));
                        if (!staged) return;
                        updateVerifyHubState();
                        showStep(2);
                        return;
                    }

                    const result = await Promise.resolve(validateStep(current));
                    if (!result) return;
                    if (mode === 'register' && current === 4) {
                        updateVerifyHubState();
                        showStep(2);
                        return;
                    }
                    showStep(Math.min(maxStep, current + 1));
                } finally {
                    btn.disabled = false;
                }
            });
        });

        form.querySelectorAll('[data-wizard-goto]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = Number(btn.dataset.wizardGoto);
                if (target >= 1 && target <= maxStep) {
                    showStep(target);
                }
            });
        });

        shell?.querySelector('[data-register-back]')?.addEventListener('click', () => {
            if (current === 1) {
                window.location.href = shell?.querySelector('.oc-onb-brand-link')?.href || '/';
                return;
            }
            if (current === 3 && form.querySelector('[data-reg-doc-panel]')?.__ocBackToPicker?.()) {
                return;
            }
            if (current >= 3) {
                showStep(2);
                return;
            }
            showStep(Math.max(1, current - 1));
        });

        form.querySelectorAll('[data-id-card-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                showStep(3);
            });
        });

        form.querySelectorAll('[data-wizard-back]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (mode === 'register' && current === 3 && form.querySelector('[data-reg-doc-panel]')?.__ocBackToPicker?.()) {
                    return;
                }
                if (mode === 'register' && current >= 3) {
                    showStep(2);
                    return;
                }
                showStep(Math.max(1, current - 1));
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitBtn = form.querySelector('#create-account-btn, #create-account-hub-btn');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const step1Ok = await Promise.resolve(validateStep(1));
                if (!step1Ok) {
                    showStep(1);
                    return;
                }

                const step2Ok = await Promise.resolve(validateStep(2));
                if (!step2Ok) {
                    showStep(2);
                    return;
                }

                form.querySelector('[data-reg-doc-panel]')?.__ocPrepareBestDoc?.();
                const step3Ok = await Promise.resolve(validateStep(3));
                if (!step3Ok) {
                    showStep(3);
                    return;
                }

                const step4Ok = await Promise.resolve(validateStep(4));
                if (!step4Ok) {
                    showStep(4);
                    return;
                }

                await refreshCsrfToken(form);

                form.submit();
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        // Keep session alive while user is on the multi-step register form
        const keepAlive = window.setInterval(() => {
            refreshCsrfToken(form);
        }, 4 * 60 * 1000);
        window.addEventListener('beforeunload', () => window.clearInterval(keepAlive));

        const terms = form.querySelector('#terms');
        const termsError = document.getElementById('terms-error');
        terms?.addEventListener('change', () => {
            if (terms.checked && termsError) {
                termsError.hidden = true;
            }
        });

        // After server validation errors, jump to the relevant step
        const errorText = Array.from(shell?.querySelectorAll('.auth-alert-error p, .oc-onb-alert--error p') || [])
            .map((p) => p.textContent?.toLowerCase() || '')
            .join(' ');

        let startStep = 1;
        const accountFieldError = errorText && (
            errorText.includes('password')
            || errorText.includes('email')
            || errorText.includes('country')
            || errorText.includes('name')
            || errorText.includes('agreement')
        );

        if (errorText.includes('selfie') || errorText.includes('face') || errorText.includes('liveness') || errorText.includes('live')) {
            startStep = 4;
        } else if (errorText.includes('id card') || errorText.includes('document')) {
            startStep = 3;
        } else if (accountFieldError) {
            startStep = 1;
        } else if (
            form.querySelector('[data-id-card-input]')?.value?.startsWith('data:image')
            && !form.querySelector('[data-selfie-input]')?.value?.startsWith('data:image')
        ) {
            startStep = 3;
        }

        showStep(startStep);
    });
}

function initCountryPickers() {
    document.querySelectorAll('[data-country-picker]').forEach((root) => {
        const toggle = root.querySelector('[data-country-toggle]');
        const menu = root.querySelector('[data-country-menu]');
        const search = root.querySelector('[data-country-search]');
        const valueInput = root.querySelector('[data-country-value]');
        const label = root.querySelector('[data-country-label]');
        const options = Array.from(root.querySelectorAll('[data-country-option]'));
        const isPhonePicker = root.dataset.countryPickerVariant === 'phone';

        const setPhoneLabel = (option) => {
            if (!label || !isPhonePicker || !option) return;
            const flag = option.dataset.countryFlag || '';
            const dial = option.dataset.countryDial || '';
            if (!dial) {
                label.textContent = option.textContent?.trim() || '';
                return;
            }
            label.innerHTML = `<span class="oc-phone-flag" aria-hidden="true">${flag}</span><span class="oc-phone-code">+${dial}</span>`;
        };

        const close = () => {
            menu?.classList.add('hidden');
            root.classList.remove('is-open');
            toggle?.setAttribute('aria-expanded', 'false');
        };

        const open = () => {
            document.querySelectorAll('[data-country-picker].is-open').forEach((other) => {
                if (other === root) return;
                other.classList.remove('is-open');
                other.querySelector('[data-country-menu]')?.classList.add('hidden');
                other.querySelector('[data-country-toggle]')?.setAttribute('aria-expanded', 'false');
            });

            menu?.classList.remove('hidden');
            root.classList.add('is-open');
            toggle?.setAttribute('aria-expanded', 'true');
            search?.focus();
        };

        toggle?.addEventListener('click', () => {
            if (menu?.classList.contains('hidden')) {
                open();
            } else {
                close();
            }
        });

        search?.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();

            options.forEach((option) => {
                const name = option.dataset.countryName || '';
                const code = option.dataset.countryCode || '';
                const dial = option.dataset.countryDial || '';
                const match = name.includes(query) || code.includes(query) || dial.includes(query.replace(/^\+/, ''));
                option.classList.toggle('hidden', !match);
            });
        });

        menu?.addEventListener('mousedown', (event) => {
            event.stopPropagation();
        });

        options.forEach((option) => {
            option.addEventListener('mousedown', (event) => {
                event.preventDefault();
                event.stopPropagation();
            });

            option.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const value = option.dataset.countryValueItem || '';
                if (valueInput) {
                    valueInput.value = value;
                    valueInput.dispatchEvent(new Event('input', { bubbles: true }));
                    valueInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (label) {
                    if (isPhonePicker) {
                        setPhoneLabel(option);
                    } else {
                        label.textContent = option.textContent?.trim() || value;
                    }
                }
                if (search) search.value = '';
                options.forEach((item) => item.classList.remove('hidden'));
                close();
            });
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });

        const initialValue = valueInput?.value;
        if (initialValue) {
            const selected = options.find((option) => option.dataset.countryValueItem === initialValue);
            if (selected && label) {
                if (isPhonePicker) {
                    setPhoneLabel(selected);
                } else {
                    label.textContent = selected.textContent?.trim() || initialValue;
                }
            }
        }
    });
}
