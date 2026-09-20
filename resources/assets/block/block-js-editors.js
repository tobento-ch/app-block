import blockEditors from './../block/block-editors.js';
import editors from './../js-editor/core/editors.js';
import events from './../js-editor/core/events.js';
import notifier from './../js-notifier/notifier.js';

function assignToObject(obj, path, value) {
    const segments = path.split('.');
    let current = obj;

    for (let i = 0; i < segments.length - 1; i++) {
        const segment = segments[i];

        if (!(segment in current)) {
            current[segment] = {};
        }

        current = current[segment];
    }

    current[segments[segments.length - 1]] = value;
}

/**
 * Handle live editing (block.edit)
 */
blockEditors.listen('block.edit', (block, editor) => {
    const editorEl = block.el.querySelector('[data-editor-id]');
    if (!editorEl) return;

    const editorId = editorEl.getAttribute('data-editor-id');
    const field = editorEl.getAttribute('data-editor-field');
    const itemIndex = editorEl.getAttribute('data-editor-item');
    const translatable = editorEl.getAttribute('data-editor-translatable') === '1';
    const locale = block.block.locale;
    const code = editors.get(editorId).code();

    if (itemIndex !== null) {
        if (translatable) {
            assignToObject(block.block, `data.items.${itemIndex}.${field}.${locale}`, code);
        } else {
            assignToObject(block.block, `data.items.${itemIndex}.${field}`, code);
        }
    } else {
        if (translatable) {
            assignToObject(block.block, `${field}.${locale}`, code);
        } else {
            assignToObject(block.block, field, code);
        }
    }
});

/**
 * Register editors when blocks are added or edited
 */
blockEditors.listen('block.added', () => editors.register());
blockEditors.listen('block.edited', () => editors.register());

/**
 * Handle blur event (editor.blur)
 */
events.listen('editor.blur', (e, editor) => {
    const editorEl = editor.el.closest('[data-block-editor-id]');
    const blockEl = editor.el.closest('[data-block-id]');
    if (!editorEl || !blockEl) return;

    const editorId = editorEl.getAttribute('data-block-editor-id');
    const blockId = blockEl.getAttribute('data-block-id');
    const blockEditor = blockEditors.get(editorId);
    const block = blockEditor.getBlock(blockId);
    
    if (!block) return;

    const field = editor.el.getAttribute('data-editor-field');
    const itemIndex = editor.el.getAttribute('data-editor-item');
    const translatable = editor.el.getAttribute('data-editor-translatable') === '1';
    const locale = block.block.locale;
    const code = editor.code();
    
    if (itemIndex !== null) {
        if (translatable) {
            assignToObject(block.block, `data.items.${itemIndex}.${field}.${locale}`, code);
        } else {
            assignToObject(block.block, `data.items.${itemIndex}.${field}`, code);
        }
    } else {
        if (translatable) {
            assignToObject(block.block, `${field}.${locale}`, code);
        } else {
            assignToObject(block.block, field, code);
        }
    }

    fetch(blockEditor.config.updateUrl, {
        method: 'POST',
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            editor: blockEditor.name,
            type: block.block.type,
            block: JSON.stringify(block.block)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status !== 200) {
            notifier.send({
                status: 'error',
                text: data.message
            });
        }
    });
});

export default null;