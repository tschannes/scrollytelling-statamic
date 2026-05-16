/**
 * Statamic CP Customizations for Scrollytelling
 * Manages bidirectional state sync between the CP and the Live Preview iframe.
 */

window.PendingVisualEdits = window.PendingVisualEdits || {};

// 1. Clear pending edits on manual CP interaction to prevent accidental overwrites
document.addEventListener('input', (e) => {
    // Only clear if it's a real human interaction, not our programmatic JS updates
    if (e.isTrusted && e.target?.tagName !== 'IFRAME') {
        window.PendingVisualEdits = {};
    }
});

// 2. Axios Interceptor: Injects visual changes directly into the Save payload
const setupInterceptor = (axios) => {
    if (!axios || axios.__visual_interceptor_set) return;

    axios.interceptors.request.use((config) => {
        const payload = config.data;
        if (!payload || typeof payload !== 'object') return config;

        const applyEdits = (data) => {
            if (!data || typeof data !== 'object') return;

            // Handle nested Tiles/Layers (Stories)
            data.add_tiles?.forEach((tile, tIdx) => {
                tile.add_layers?.forEach((layer, lIdx) => {
                    const edit = window.PendingVisualEdits[`${tIdx}_${lIdx}`];
                    if (edit) {
                        if (edit.x !== undefined) layer.layer_x = edit.x;
                        if (edit.y !== undefined) layer.layer_y = edit.y;
                        if (edit.size !== undefined) layer.layer_size = edit.size;
                        if (edit.text !== undefined) layer.layer_text = edit.text;
                    }
                });
            });

            // Handle standalone Layers (Tiles)
            data.add_layers?.forEach((layer, lIdx) => {
                const edit = window.PendingVisualEdits[`none_${lIdx}`];
                if (edit) {
                    if (edit.x !== undefined) layer.layer_x = edit.x;
                    if (edit.y !== undefined) layer.layer_y = edit.y;
                    if (edit.size !== undefined) layer.layer_size = edit.size;
                    if (edit.text !== undefined) layer.layer_text = edit.text;
                }
            });
        };

        applyEdits(payload);
        if (payload.values) applyEdits(payload.values); // Statamic often nests in 'values'

        return config;
    }, (error) => Promise.reject(error));

    axios.__visual_interceptor_set = true;
};

// Setup interceptors on all possible Axios instances
if (window.axios) setupInterceptor(window.axios);
if (window.Statamic && window.Statamic.$axios) setupInterceptor(window.Statamic.$axios);

// 3. UI Helpers
const findCpInput = (selector, fieldName, tile, layer) => {
    const inputs = Array.from(document.querySelectorAll(selector));
    
    // Filter by field name first (e.g., layer_x) to ensure the fallback index is correct
    const matching = inputs.filter(el => {
        const name = el.name || el.getAttribute('name') || '';
        const id = el.id || '';
        return name.includes(fieldName) || id.includes(fieldName);
    });

    const isMatch = (el) => {
        const str = `${el.name || ''} ${el.id || ''} ${el.getAttribute('name') || ''}`;
        const matchLayer = str.includes(`.${layer}.`) || str.includes(`_${layer}_`) || str.includes(`[${layer}]`);
        if (tile !== null) {
            const matchTile = str.includes(`.${tile}.`) || str.includes(`_${tile}_`) || str.includes(`[${tile}]`);
            return matchLayer && matchTile;
        }
        return matchLayer;
    };

    // Find specific match, or fallback to the N-th input of this specific field type
    return matching.find(isMatch) || matching[layer];
};

const updateInputUI = (selector, fieldName, tile, layer, value) => {
    const el = findCpInput(selector, fieldName, tile, layer);
    if (el) {
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
    return el;
};

// 4. Message Listener: Handles updates from the Live Preview iframe
window.addEventListener('message', ({ data }) => {
    if (!data?.action) return;

    const { action, tile, layer, x, y, size, text } = data;
    const key = tile !== null ? `${tile}_${layer}` : `none_${layer}`;

    switch (action) {
        case 'UPDATE_LAYER':
            // Merge coordinates into pending edits, preserving any existing text edits
            window.PendingVisualEdits[key] = { 
                ...window.PendingVisualEdits[key],
                x: Math.round(x), 
                y: Math.round(y), 
                size: Math.round(size) 
            };
            updateInputUI('input[type="range"]', 'layer_x', tile, layer, Math.round(x));
            updateInputUI('input[type="range"]', 'layer_y', tile, layer, Math.round(y));
            updateInputUI('input[type="range"]', 'layer_size', tile, layer, Math.round(size));
            break;

        case 'SYNC_TEXT':
        case 'UPDATE_TEXT':
            // Store text update in global state
            window.PendingVisualEdits[key] = { 
                ...window.PendingVisualEdits[key], 
                text 
            };
            // Only update the CP sidebar UI on blur (UPDATE_TEXT) to avoid layout jitter while typing
            if (action === 'UPDATE_TEXT') {
                updateInputUI('textarea', 'layer_text', tile, layer, text);
            }
            break;

        case 'FOCUS_TEXT':
            const field = findCpInput('textarea', 'layer_text', tile, layer);
            const wrapper = field?.closest('.form-group') || 
                            document.querySelector(`div[data-field-name*="[${tile}][add_layers][${layer}]"]`) ||
                            document.querySelector(`div[data-field-name*="add_layers][${layer}]"]`);
            
            if (wrapper) {
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const originalShadow = wrapper.style.boxShadow;
                wrapper.style.transition = 'box-shadow 0.3s ease';
                wrapper.style.boxShadow = '0 0 0 3px rgba(79, 70, 229, 0.4)';
                setTimeout(() => { if (wrapper) wrapper.style.boxShadow = originalShadow; }, 1500);
                wrapper.querySelector('.ProseMirror, textarea')?.focus();
            }
            break;
    }
});
