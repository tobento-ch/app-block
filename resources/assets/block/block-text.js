import blockEditors from './../block/block-editors.js';
import editors from './../js-editor/core/editors.js';
import events from './../js-editor/core/events.js';
import notifier from './../js-notifier/notifier.js';

blockEditors.listen('block.edit', (block, editor) => {
    const editorEl = block.el.querySelector('[data-editor-id]');
    
    if (editorEl) {
        const editorId = editorEl.getAttribute('data-editor-id');
        block.block.translation[block.block.locale] = editors.get(editorId).code();
    }
});

blockEditors.listen('block.added', (block, editor) => {
    editors.register();
});

blockEditors.listen('block.edited', (block, editor) => {
    editors.register();
});

events.listen('editor.blur', (e, editor) => {
    const editorEl = editor.el.closest('[data-block-editor-id]');
    const blockEl = editor.el.closest('[data-block-id]');
    
    if (editorEl && blockEl) {
        const editorId = editorEl.getAttribute('data-block-editor-id');
        const blockId = blockEl.getAttribute('data-block-id');
        const blockEditor = blockEditors.get(editorId);
        const block = blockEditor.getBlock(blockId);

        // FIX: block may not exist yet
        if (!block) {
            return;
        }
        
        block.block.translation[block.block.locale] = editor.code();
        
        fetch(blockEditor.config.updateUrl, {
            method: 'POST',
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({editor: blockEditor.name, type: block.block.type, block: JSON.stringify(block.block)})
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 200) {
                notifier.send({
                    status: 'error',
                    text: data.message,
                });
            }
        });
    }
});

const blockText = null;

export default blockText;