import { openCircleCrop } from './circle-crop';

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('avatar');
    const img = document.getElementById('profile-avatar-img');
    const initial = document.getElementById('profile-avatar-initial');
    if (!input) return;

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async () => {
            const raw = typeof reader.result === 'string' ? reader.result : '';
            if (!raw) return;

            const cropped = await openCircleCrop(raw, {
                title: 'Position your photo',
                hint: 'Drag to move your face into the circle, then confirm.',
                outputSize: 512,
                quality: 0.9,
            });

            if (!cropped) {
                input.value = '';
                return;
            }

            const blob = await (await fetch(cropped)).blob();
            const croppedFile = new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
            const dt = new DataTransfer();
            dt.items.add(croppedFile);
            input.files = dt.files;

            if (img) {
                img.src = cropped;
                img.classList.remove('is-hidden');
            }
            initial?.classList.add('is-hidden');

            document.getElementById('profile-edit-trigger')?.click();
            const panel = document.getElementById('profile-edit-panel');
            if (panel) {
                panel.hidden = false;
                panel.classList.add('is-open');
            }
        };
        reader.readAsDataURL(file);
    });
});
