/**
 * Circular face/avatar crop: drag to position, +/- zoom, confirm.
 * @param {string} sourceDataUrl
 * @param {{ title?: string, hint?: string, outputSize?: number, quality?: number }} [options]
 * @returns {Promise<string|null>} cropped JPEG data URL, or null if cancelled
 */
export function openCircleCrop(sourceDataUrl, options = {}) {
    const title = options.title || 'Position your face';
    const hint = options.hint || 'Drag to move. Use + / − to zoom. Keep your face inside the circle.';
    const outputSize = options.outputSize || 480;
    const quality = options.quality ?? 0.85;

    return new Promise((resolve) => {
        const existing = document.querySelector('.oc-circle-crop-backdrop');
        existing?.remove();

        const backdrop = document.createElement('div');
        backdrop.className = 'oc-circle-crop-backdrop';
        backdrop.innerHTML = `
            <div class="oc-circle-crop" role="dialog" aria-modal="true" aria-labelledby="oc-circle-crop-title">
                <h3 id="oc-circle-crop-title">${escapeHtml(title)}</h3>
                <p class="oc-circle-crop-hint">${escapeHtml(hint)}</p>
                <div class="oc-circle-crop-stage" data-crop-stage>
                    <img class="oc-circle-crop-img" data-crop-img alt="" draggable="false">
                    <div class="oc-circle-crop-mask" aria-hidden="true"></div>
                </div>
                <div class="oc-circle-crop-zoom">
                    <button type="button" data-crop-zoom-out aria-label="Zoom out">−</button>
                    <input type="range" min="1" max="3" step="0.01" value="1" data-crop-zoom>
                    <button type="button" data-crop-zoom-in aria-label="Zoom in">+</button>
                </div>
                <div class="oc-circle-crop-actions">
                    <button type="button" class="oc-circle-crop-btn oc-circle-crop-btn--ghost" data-crop-cancel>Cancel</button>
                    <button type="button" class="oc-circle-crop-btn oc-circle-crop-btn--primary" data-crop-confirm>Use this crop</button>
                </div>
            </div>
        `;
        document.body.appendChild(backdrop);
        document.body.classList.add('oc-circle-crop-open');

        const img = backdrop.querySelector('[data-crop-img]');
        const stage = backdrop.querySelector('[data-crop-stage]');
        const zoomInput = backdrop.querySelector('[data-crop-zoom]');
        let scale = 1;
        let offsetX = 0;
        let offsetY = 0;
        let dragging = false;
        let lastX = 0;
        let lastY = 0;
        let baseW = 0;
        let baseH = 0;
        let settled = false;

        const finish = (value) => {
            if (settled) return;
            settled = true;
            document.body.classList.remove('oc-circle-crop-open');
            backdrop.remove();
            resolve(value);
        };

        const applyTransform = () => {
            if (!img) return;
            img.style.width = `${baseW * scale}px`;
            img.style.height = `${baseH * scale}px`;
            img.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
        };

        const fitImage = () => {
            const rect = stage.getBoundingClientRect();
            const stageSize = Math.min(rect.width, rect.height);
            const nw = img.naturalWidth || 1;
            const nh = img.naturalHeight || 1;
            const cover = Math.max(stageSize / nw, stageSize / nh);
            baseW = nw * cover;
            baseH = nh * cover;
            scale = 1;
            offsetX = (stageSize - baseW) / 2;
            offsetY = (stageSize - baseH) / 2;
            if (zoomInput) zoomInput.value = '1';
            applyTransform();
        };

        img.onload = () => fitImage();
        img.src = sourceDataUrl;

        const onPointerDown = (e) => {
            dragging = true;
            lastX = e.clientX ?? e.touches?.[0]?.clientX ?? 0;
            lastY = e.clientY ?? e.touches?.[0]?.clientY ?? 0;
            stage.setPointerCapture?.(e.pointerId);
        };
        const onPointerMove = (e) => {
            if (!dragging) return;
            const x = e.clientX ?? e.touches?.[0]?.clientX ?? lastX;
            const y = e.clientY ?? e.touches?.[0]?.clientY ?? lastY;
            offsetX += x - lastX;
            offsetY += y - lastY;
            lastX = x;
            lastY = y;
            applyTransform();
        };
        const onPointerUp = () => {
            dragging = false;
        };

        stage.addEventListener('pointerdown', onPointerDown);
        stage.addEventListener('pointermove', onPointerMove);
        stage.addEventListener('pointerup', onPointerUp);
        stage.addEventListener('pointercancel', onPointerUp);

        const setZoom = (next) => {
            const clamped = Math.min(3, Math.max(1, next));
            const rect = stage.getBoundingClientRect();
            const cx = rect.width / 2;
            const cy = rect.height / 2;
            const relX = (cx - offsetX) / (baseW * scale);
            const relY = (cy - offsetY) / (baseH * scale);
            scale = clamped;
            offsetX = cx - relX * baseW * scale;
            offsetY = cy - relY * baseH * scale;
            if (zoomInput) zoomInput.value = String(scale);
            applyTransform();
        };

        zoomInput?.addEventListener('input', () => setZoom(Number(zoomInput.value) || 1));
        backdrop.querySelector('[data-crop-zoom-in]')?.addEventListener('click', () => setZoom(scale + 0.1));
        backdrop.querySelector('[data-crop-zoom-out]')?.addEventListener('click', () => setZoom(scale - 0.1));

        backdrop.querySelector('[data-crop-cancel]')?.addEventListener('click', () => finish(null));
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) finish(null);
        });

        backdrop.querySelector('[data-crop-confirm]')?.addEventListener('click', () => {
            const rect = stage.getBoundingClientRect();
            const stageSize = Math.min(rect.width, rect.height);
            const canvas = document.createElement('canvas');
            canvas.width = outputSize;
            canvas.height = outputSize;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                finish(null);
                return;
            }
            ctx.fillStyle = '#111';
            ctx.fillRect(0, 0, outputSize, outputSize);
            const ratio = outputSize / stageSize;
            ctx.drawImage(
                img,
                offsetX * ratio,
                offsetY * ratio,
                baseW * scale * ratio,
                baseH * scale * ratio,
            );
            finish(canvas.toDataURL('image/jpeg', quality));
        });
    });
}

/**
 * Require a close-up face filling the frame. Tiny faces in a camel/group photo fail.
 * @returns {Promise<boolean|null>}
 */
export async function detectCloseUpFace(source) {
    if (typeof window.FaceDetector !== 'function') {
        return null;
    }

    try {
        const detector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 5 });
        const faces = await detector.detect(source);
        const w = source.naturalWidth || source.videoWidth || source.width || 0;
        const h = source.naturalHeight || source.videoHeight || source.height || 0;
        if (!w || !h) return null;
        if (!Array.isArray(faces) || faces.length === 0) {
            return null;
        }

        const large = faces.some((face) => {
            const box = face.boundingBox || {};
            const bw = Number(box.width) || 0;
            const bh = Number(box.height) || 0;
            const bx = Number(box.x) || 0;
            const by = Number(box.y) || 0;
            const areaRatio = (bw * bh) / (w * h);
            const cx = bx + bw / 2;
            const cy = by + bh / 2;
            const centered = Math.abs(cx - w / 2) <= w * 0.28 && Math.abs(cy - h / 2) <= h * 0.28;
            return areaRatio >= 0.12 && bw >= w * 0.28 && bh >= h * 0.28 && centered;
        });

        return large;
    } catch (_) {
        return null;
    }
}

/**
 * Client-side close-up face gate. Landscapes, animals, and objects fail.
 * @param {HTMLImageElement|HTMLCanvasElement} source
 * @returns {boolean}
 */
export function looksLikeFaceFromImage(source) {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) return false;
    ctx.drawImage(source, 0, 0, size, size);
    const pixels = ctx.getImageData(0, 0, size, size).data;
    const cx = (size - 1) / 2;
    const cy = (size - 1) / 2;
    const innerR = size * 0.32;
    const outerR = size * 0.48;
    const skyBand = Math.round(size * 0.20);
    const corner = Math.round(size * 0.12);
    let centerSkin = 0;
    let centerTotal = 0;
    let outerSkin = 0;
    let outerTotal = 0;
    let lumaSum = 0;
    let lumaSq = 0;
    let count = 0;
    let nature = 0;
    let inner = 0;
    let skyTop = 0;
    let skyTopTotal = 0;
    let cornerSkin = 0;
    let cornerTotal = 0;

    const lumaAt = (x, y) => {
        const i = (y * size + x) * 4;
        return pixels[i] * 0.299 + pixels[i + 1] * 0.587 + pixels[i + 2] * 0.114;
    };
    const isSkin = (r, g, b) => {
        const y = 0.299 * r + 0.587 * g + 0.114 * b;
        const cb = 128 + (-0.168736 * r) - (0.331264 * g) + (0.5 * b);
        const cr = 128 + (0.5 * r) - (0.418688 * g) - (0.081312 * b);
        return y > 35 && y < 245 && cb >= 70 && cb <= 145 && cr >= 125 && cr <= 185 && r > b - 25;
    };

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const i = (y * size + x) * 4;
            const r = pixels[i];
            const g = pixels[i + 1];
            const b = pixels[i + 2];
            const luma = r * 0.299 + g * 0.587 + b * 0.114;
            lumaSum += luma;
            lumaSq += luma * luma;
            count++;
            const dx = (x - cx) / innerR;
            const dy = (y - cy) / innerR;
            const distInner = Math.sqrt(dx * dx + dy * dy);
            const dxo = (x - cx) / outerR;
            const dyo = (y - cy) / outerR;
            const distOuter = Math.sqrt(dxo * dxo + dyo * dyo);
            const skin = isSkin(r, g, b);
            const isSky = b > 135 && b > r + 18 && b > g + 8;
            if (y < skyBand) {
                skyTopTotal++;
                if (isSky) skyTop++;
            }
            const inCorner = (x < corner || x >= size - corner) && (y < corner || y >= size - corner);
            if (inCorner) {
                cornerTotal++;
                if (skin) cornerSkin++;
            }
            if (distInner <= 1) {
                centerTotal++;
                inner++;
                if (skin) centerSkin++;
                if (isSky || (g > r + 22 && g > b + 10)) nature++;
            } else if (distOuter <= 1.15) {
                outerTotal++;
                if (skin) outerSkin++;
            }
        }
    }

    const centerRatio = centerTotal ? centerSkin / centerTotal : 0;
    const outerRatio = outerTotal ? outerSkin / outerTotal : 0;
    const skyRatio = skyTopTotal ? skyTop / skyTopTotal : 0;
    const cornerRatio = cornerTotal ? cornerSkin / cornerTotal : 0;
    const mean = count ? lumaSum / count : 0;
    const variance = count ? Math.max(0, lumaSq / count - mean * mean) : 0;
    const natureRatio = inner ? nature / inner : 1;
    const left = regionDarkDelta(lumaAt, size, 0.22, 0.22, 0.24, 0.24);
    const right = regionDarkDelta(lumaAt, size, 0.54, 0.22, 0.24, 0.24);
    const eyes = left >= 12 && right >= 12;
    const filledFace = centerRatio >= 0.32 && eyes;

    return skyRatio < 0.18
        && natureRatio < 0.28
        && eyes
        && centerRatio >= 0.12
        && (
            filledFace
            || (centerRatio >= outerRatio - 0.06 && cornerRatio < 0.50)
        )
        && variance >= 140
        && variance <= 8000
        && mean >= 30
        && mean <= 235;
}

export function looksLikeIdPortraitFromImage(source, strict = false) {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) return false;
    ctx.drawImage(source, 0, 0, size, size);
    const pixels = ctx.getImageData(0, 0, size, size).data;
    const cx = (size - 1) / 2;
    const cy = (size - 1) / 2;
    const innerR = size * 0.34;
    let centerSkin = 0;
    let centerTotal = 0;
    let outerSkin = 0;
    let outerTotal = 0;
    let lumaSum = 0;
    let lumaSq = 0;
    let count = 0;

    const isSkin = (r, g, b, y) => {
        const cb = 128 + (-0.168736 * r) - (0.331264 * g) + (0.5 * b);
        const cr = 128 + (0.5 * r) - (0.418688 * g) - (0.081312 * b);
        return y > 35 && y < 245 && cb >= 70 && cb <= 145 && cr >= 125 && cr <= 185 && r > b - 25;
    };

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const i = (y * size + x) * 4;
            const r = pixels[i];
            const g = pixels[i + 1];
            const b = pixels[i + 2];
            const luma = r * 0.299 + g * 0.587 + b * 0.114;
            lumaSum += luma;
            lumaSq += luma * luma;
            count++;
            const dx = (x - cx) / innerR;
            const dy = (y - cy) / innerR;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const skin = isSkin(r, g, b, luma);
            if (dist <= 1) {
                centerTotal++;
                if (skin) centerSkin++;
            } else {
                outerTotal++;
                if (skin) outerSkin++;
            }
        }
    }

    const centerRatio = centerTotal ? centerSkin / centerTotal : 0;
    const outerRatio = outerTotal ? outerSkin / outerTotal : 0;
    const mean = count ? lumaSum / count : 0;
    const variance = count ? Math.max(0, lumaSq / count - mean * mean) : 0;

    const minCenter = strict ? 0.20 : 0.10;
    const centerLead = strict ? 0.08 : -0.02;

    return centerRatio >= minCenter
        && centerRatio >= outerRatio + centerLead
        && variance >= 80
        && mean >= 28
        && mean <= (strict ? 190 : 235);
}

export function canvasHasIdPortrait(canvas, strict = false) {
    const crops = [
        [0.03, 0.16, 0.38, 0.78],
        [0.04, 0.20, 0.34, 0.70],
        [0.62, 0.10, 0.97, 0.70],
        [0.66, 0.12, 0.98, 0.62],
        [0.58, 0.16, 0.94, 0.80],
        [0.02, 0.08, 0.42, 0.88],
        [0.54, 0.08, 0.98, 0.88],
    ];
    for (const [x0, y0, x1, y1] of crops) {
        const crop = document.createElement('canvas');
        crop.width = 96;
        crop.height = 96;
        const ctx = crop.getContext('2d');
        if (!ctx) continue;
        const w = canvas.width;
        const h = canvas.height;
        ctx.drawImage(canvas, x0 * w, y0 * h, (x1 - x0) * w, (y1 - y0) * h, 0, 0, 96, 96);
        if (looksLikeIdPortraitFromImage(crop, strict)) return true;
    }
    return false;
}

function regionDarkDelta(lumaAt, size, xRatio, yRatio, wRatio, hRatio) {
    const x0 = Math.round(size * xRatio);
    const y0 = Math.round(size * yRatio);
    const w = Math.max(1, Math.round(size * wRatio));
    const h = Math.max(1, Math.round(size * hRatio));
    const values = [];
    for (let y = y0; y < y0 + h && y < size; y++) {
        for (let x = x0; x < x0 + w && x < size; x++) {
            values.push(lumaAt(x, y));
        }
    }
    if (values.length < 8) return 0;
    const mean = values.reduce((a, b) => a + b, 0) / values.length;
    values.sort((a, b) => a - b);
    const darkN = Math.max(1, Math.floor(values.length * 0.12));
    const dark = values.slice(0, darkN).reduce((a, b) => a + b, 0) / darkN;
    return mean - dark;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}
