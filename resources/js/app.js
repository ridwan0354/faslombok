import './bootstrap';
import Alpine from 'alpinejs';
import { Html5Qrcode, Html5QrcodeScanner } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;
window.Html5QrcodeScanner = Html5QrcodeScanner;

// Offline Drafts & Synchronization Storage
window.FasOffline = {
    STORAGE_KEY: 'fas_judge_drafts_v1',

    getDrafts() {
        try {
            const raw = localStorage.getItem(this.STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            console.error('Failed to get drafts from localStorage', e);
            return [];
        }
    },

    saveDraft(draft) {
        const drafts = this.getDrafts();
        const existingIdx = drafts.findIndex(d => d.client_uuid === draft.client_uuid);
        if (existingIdx >= 0) {
            drafts[existingIdx] = draft;
        } else {
            drafts.push(draft);
        }
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(drafts));
        window.dispatchEvent(new CustomEvent('fas:drafts-updated', { detail: drafts }));
    },

    removeDraft(clientUuid) {
        const drafts = this.getDrafts().filter(d => d.client_uuid !== clientUuid);
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(drafts));
        window.dispatchEvent(new CustomEvent('fas:drafts-updated', { detail: drafts }));
    },

    count() {
        return this.getDrafts().length;
    },

    async syncAll(csrfToken) {
        const drafts = this.getDrafts();
        if (drafts.length === 0) return { success: true, count: 0 };

        try {
            const response = await fetch('/api/scores/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ drafts }),
            });

            const result = await response.json();
            if (result.success && result.synced_uuids) {
                // remove successfully synced drafts
                let currentDrafts = this.getDrafts();
                currentDrafts = currentDrafts.filter(d => !result.synced_uuids.includes(d.client_uuid));
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(currentDrafts));
                window.dispatchEvent(new CustomEvent('fas:drafts-updated', { detail: currentDrafts }));
            }
            return result;
        } catch (err) {
            console.error('Offline sync failed', err);
            return { success: false, message: err.message };
        }
    }
};

Alpine.start();
