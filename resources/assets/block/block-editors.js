import modals from './../modal/modals.js';
import notifier from './../js-notifier/notifier.js';
import button from './../crud/button.js';

const blockEditors = (function(window, document) {
    'use strict';
    
    let idCounter = 0;

    function uniqueId(prefix) {
        const id = ++idCounter + '';
        return prefix ? prefix + id : id;
    }
    
    function toDotNotation(string) {
        return string.replaceAll('[]', '').replaceAll('[', '.').replaceAll(']', '');
    }
    
    function toInputName(string) {
        const segments = string.split('.');
        let name = segments[0];

        delete segments[0];

        segments.forEach(segment => {
            name += '['+segment+']';
        });

        return name;
    }
    
    class Eventer {
        constructor() {
            this.listeners = {};
        }
        listen(eventName, listener) {
            if (typeof this.listeners[eventName] === 'undefined') {
                this.listeners[eventName] = [];
            }

            this.listeners[eventName].push(listener);
        }
        fire(eventName, parameters) {
            if (typeof this.listeners[eventName] === 'object') {
                this.listeners[eventName].forEach(listener => {
                    if (typeof listener === 'function') {
                        if (parameters instanceof Array) {
                            listener(...parameters);
                        } else if (parameters instanceof Object) {
                            listener(parameters);
                        }
                    }
                });
            }

            return parameters;
        }
    }
    
    class Toolbar {
        constructor(el, block) {
            this.el = el;
            this.block = block;
        }
        isOpen() {
            return this.block.el.classList.contains('active');
        }
        open() {
            this.el.classList.add('active');
            this.block.el.classList.add('active');
            this.positioning();
            editors.setCurrentBlock(this.block);
        }
        close() {
            this.el.classList.remove('active');
            this.block.el.classList.remove('active');
        }
        positioning() {
            const blockRect = this.block.el.getBoundingClientRect();
            const rect = this.el.getBoundingClientRect();
            
            this.el.style.left = ((blockRect.width/2)-(rect.width/2))+'px';
            
            if (blockRect.top < 100) {
                this.el.style.top = (blockRect.height+5)+'px';
            } else {
                this.el.style.top = -(rect.height+5)+'px';
            }
        }
    }
    
    class Block {
        constructor(id, editorId, el, block) {
            this.id = id;
            this.editorId = editorId;
            this.el = el;
            this.block = block;
            this.toolbar = null;
            this.el.setAttribute('data-block-id', this.id);
            
            // listeners:
            this.el.addEventListener('click', (e) => {
                
                if (! this.getToolbar().isOpen()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                modals.get('block-editor-'+this.editorId).close();
                editors.closeToolbars();
                this.getToolbar().open();
                
                const el = e.target.closest('[data-block-action]');
                
                if (el && el.getAttribute('data-block-action') === 'edit') {
                    modals.get('block-editor-'+this.editorId).close();
                    this.edit();
                    e.stopPropagation();
                }
                
                if (el && el.getAttribute('data-block-action') === 'delete') {
                    modals.get('block-editor-'+this.editorId).close();
                    this.delete();
                    e.stopPropagation();
                }
                
                if (el && el.getAttribute('data-block-action') === 'new') {
                    const editor = editors.get(this.editorId);
                    editor.newBlock(e, el);
                    e.stopPropagation();
                }
                
                if (el && el.getAttribute('data-block-action') === 'move-up') {
                    this.moveUp();
                }
                
                if (el && el.getAttribute('data-block-action') === 'move-down') {
                    this.moveDown();
                }
            });
        
            this.el.addEventListener('mouseenter', (e) => {
                this.el.classList.add('highlight');
            });
            
            this.el.addEventListener('mouseleave', (e) => {
                this.el.classList.remove('highlight');
            });
        }
        moveUp() {
            if (this.el.previousElementSibling) {
                this.el.parentNode.insertBefore(this.el, this.el.previousElementSibling);
                editors.get(this.editorId).reorderBlocks();
            }
        }
        moveDown() {
            if (this.el.nextElementSibling && this.el.nextElementSibling.hasAttribute('data-block')) {
                this.el.parentNode.insertBefore(this.el.nextElementSibling, this.el);
                editors.get(this.editorId).reorderBlocks();
            }
        }
        getToolbar() {
            if (! this.toolbar) {
                this.toolbar = new Toolbar(this.el.querySelector('[data-block-section="toolbar"]'), this);
            }
            
            return this.toolbar;
        }
        edit() {
            this.el.classList.add('active');
            modals.get('block-editor-blocks-'+this.editorId).close();
            
            const editor = editors.get(this.editorId);
            const modal = modals.get('block-editor-'+this.editorId);
            
            document.body.append(modal.modalEl);
            modal.modalEl.querySelector('.modal-body').innerHTML = '';
            modal.block = this;
            
            editor.fire('block.edit', [this, editor]);
            
            modals.get('block-editor-'+this.editorId).listen('close', (modal) => {
                modal.modalEl.querySelector('.modal-body').innerHTML = '';
                modal.listeners = {};
            });
            
            fetch(editor.config.editUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({editor: editor.name, block: this.block})
            })
            .then(response => response.text())
            .then(string => {
                const modalBody = modal.modalEl.querySelector('.modal-body');
                modalBody.innerHTML = string;
                modals.register();
            });
            
            modal.open();
        }
        save(saved = null) {
            const editor = editors.get(this.editorId);
            const modal = modals.get('block-editor-'+this.editorId);
            const modalBody = modal.modalEl.querySelector('.modal-body');
            const form = modal.modalEl.querySelector('form');
            const formData = form ? new FormData(form) : new FormData();
            
            formData.append('editor', editor.name);
            formData.append('type', this.block.type);
            formData.append('block', JSON.stringify(this.block));
            
            fetch(editor.config.updateUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    const msgEl = modal.modalEl.querySelector('[data-field-error]');
                    
                    if (msgEl) {
                        msgEl.remove();
                    }
                                        
                    const doc = (new DOMParser()).parseFromString(data.html, 'text/html');
                    const blockEl = doc.querySelector('[data-block]');
                    this.el.innerHTML = blockEl.innerHTML;
                    this.block = data.block;
                    this.toolbar = null;

                    editor.fire('block.edited', [this, editor]);

                    if (saved) {
                        saved(this, editor);
                    }
                } else {
                    data.messages.forEach(message => {
                        if (message.key === null) {
                            let msgEl = document.createElement('div');
                            msgEl.setAttribute('data-field-error', '');
                            msgEl.classList.add('form-message', 'error', 'mt-xs');
                            modal.modalEl.querySelector('.modal-body').appendChild(msgEl);
                            msgEl.textContent = message.message;
                            return;
                        }
                        
                        const field = modal.modalEl.querySelector('[name^="'+toInputName(message.key)+'"]');

                        if (field) {
                            let msgEl = modal.modalEl.querySelector('[data-field-error]');
                            
                            if (msgEl === null) {
                                msgEl = document.createElement('div');
                                msgEl.setAttribute('data-field-error', '');
                                msgEl.classList.add('form-message', 'error', 'mt-xs');
                                
                                if (field.getAttribute('type') === 'checkbox' || field.getAttribute('type') === 'radio') {
                                    field.parentNode.parentNode.appendChild(msgEl);
                                } else {
                                    field.parentNode.appendChild(msgEl);
                                }
                            }
                            
                            msgEl.textContent = message.message;
                        }
                    });
                }
            });
        }
        delete() {
            const editor = editors.get(this.editorId);
            
            fetch(editor.config.deleteUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({editor: editor.name, block: this.block})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    editor.deleteBlock(this);
                    this.el.remove();
                } else {
                    notifier.send({
                        status: 'error',
                        text: data.message,
                    });
                }
            });
        }
    }

    class Editor {
        constructor(el, config, events) {
            this.el = el;
            this.name = config.name;
            this.id = config.id;
            this.config = config;
            this.events = events;
            this.blocks = {};
            this.currentBlock = null;
            this.newBlockEl = this.el.querySelector('[data-block-editor-section="new"]');
            
            this.el.setAttribute('data-block-editor-id', this.id);
            
            this.registerBlocks();
            
            this.updateBlocksInputField();
            
            if (Object.keys(this.blocks).length === 0) {
                this.newBlockEl.classList.add('active');
            }

            this.el.addEventListener('focusout', (event) => {
                this.updateBlocksInputField();
                this.fire('focusout', [event, this]);
            });

            this.el.addEventListener('click', (e) => {
                const el = e.target.closest('[data-block-action]');
                
                if (el && el.getAttribute('data-block-action') === 'new') {
                    editors.closeToolbars();
                    this.newBlock(e, el);
                }
            });
            
            const modal = modals.get('block-editor-blocks-'+this.id);
            
            modal.modalEl.addEventListener('click', (e) => {
                const el = e.target.closest('[data-block-action]');
                
                if (el && el.getAttribute('data-block-action') === 'add') {
                    const modal = modals.get('block-editor-blocks-'+this.id);
                    const block = JSON.parse(el.getAttribute('data-block'));
                    this.addBlock(modal.blockEl.parentNode.parentNode, block);
                    modal.close();
                    editors.closeToolbars();
                }
            });
            
            const modalEdit = modals.get('block-editor-'+this.id);
            this.liveEdit(modalEdit);
            
            this.searchBlocks();
        }
        liveEdit(modal) {
            let globalTimeout = null;
            
            ['keyup', 'change'].forEach(evt => {
                modal.modalEl.addEventListener(evt, e => {
                    const el = e.target.closest('[data-field]');
                    const block = modal.block;
                    
                    if (el === null) {
                        return;
                    }

                    if (e.type === 'keyup') {
                        if (globalTimeout != null) {
                            clearTimeout(globalTimeout);
                        }
                        
                        globalTimeout = setTimeout(() => {
                            globalTimeout = null;
                            block.save();
                        }, 200);
                    } else {
                        if (e.target.tagName.toLowerCase() === 'input' && e.target.getAttribute('type') === 'file') {
                            block.save((block, editor) => {
                                block.edit();
                            });
                            e.target.setAttribute('readonly', 'readonly');
                        } else {
                            block.save();
                        }
                    }
                });
            });
            
            // supporting move up and down for items:
            modal.modalEl.addEventListener('click', e => {
                const el = e.target.closest('[data-items-action*="move"]');
                const block = modal.block;

                if (el === null) {
                    return;
                }

                setTimeout(() => {
                    block.save();
                }, 200);
            });
            
            // supporting items delete:
            modal.modalEl.addEventListener('click', e => {
                const el = e.target.closest('[data-items-action="delete"]');
                const block = modal.block;

                if (el === null) {
                    return;
                }

                setTimeout(() => {
                    block.save();
                }, 200);
            });
            
            // supporting move up and down for files:
            modal.modalEl.addEventListener('click', e => {
                const el = e.target.closest('[data-action*="move"]');
                const block = modal.block;

                if (el === null) {
                    return;
                }

                setTimeout(() => {
                    block.save((block, editor) => {
                        block.edit();
                    });
                }, 200);
            });
            
            // supporting delete files:
            modal.modalEl.addEventListener('click', e => {
                const el = e.target.closest('[data-action*="delete"]');
                const block = modal.block;

                if (el === null) {
                    return;
                }

                setTimeout(() => {
                    block.save((block, editor) => {
                        block.edit();
                    });
                }, 200);
            });
        }
        updateBlocksInputField() {
            if (typeof this.config['storeBlocksToInput'] !== 'string') {
                return;
            }
            
            const inputEl = document.querySelector('input[name="'+this.config['storeBlocksToInput']+'"]');
            if (inputEl) {
                inputEl.value = JSON.stringify(this.getBlocksData());
            }
        }
        getBlock(id) {
            return this.blocks[id];
        }
        hasBlock(id) {
            return (typeof this.blocks[id] === 'undefined') ? false : true;
        }
        getCurrentBlock() {
            return this.currentBlock;
        }
        getBlocksData() {
            const data = [];
            Object.entries(this.blocks).forEach(([id, block]) => {
                data.push(block.block);
            });
            return data;
        }
        listen(eventName, callback) {
            this.events.listen(eventName, callback);
        }
        fire(eventName, parameters) {
            this.events.fire(eventName, parameters);
        }
        registerBlocks() {
            const blocks = this.el.querySelectorAll('[data-block]');
            
            blocks.forEach(el => {
                this.registerBlock(el);
            });
        }
        registerBlock(el) {
            const block = JSON.parse(el.getAttribute('data-block'));
            const blockId = uniqueId();
            
            return this.blocks[blockId] = new Block(blockId, this.id, el, block);
        }
        newBlock(event, el) {
            modals.get('block-editor-'+this.id).close();
            
            const modal = modals.get('block-editor-blocks-'+this.id);
            
            modal.listen('open', (modal) => {
                modal.modalEl.querySelectorAll('[data-block]').forEach(block => {
                    block.classList.remove('display-none');
                });
                
                modal.blockEl = el;
                setTimeout(() => {
                    const input = modal.modalEl.querySelector('input[name="blocks_search"]');
                    
                    if (input) {
                        input.value = '';
                        input.focus();
                    }
                }, 50);
            });
            
            modal.open();
        }
        searchBlocks() {
            const modal = modals.get('block-editor-blocks-'+this.id);
            const input = modal.modalEl.querySelector('input[name="blocks_search"]');
            
            if (!input) { return; }
            
            input.addEventListener('keyup', (e) => {
                const searchTerm = input.value.toLowerCase();
                
                modal.modalEl.querySelectorAll('[data-block]').forEach(el => {
                    const contentEl = el.querySelector('[data-block-search]');
                    const txtValue = contentEl.textContent || contentEl.innerText;
                    
                    if (txtValue.toLowerCase().indexOf(searchTerm) !== -1) {
                        el.classList.remove('display-none');
                    } else {
                        el.classList.add('display-none');
                    }
                });
            });
        }
        addBlock(el, block = {}) {
            fetch(this.config.storeUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({editor: this.name, block: block})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 200) {
                    const doc = (new DOMParser()).parseFromString(data.html, 'text/html');
                    const blockEl = doc.querySelector('[data-block]');
                    
                    this.newBlockEl.classList.remove('active');
                    
                    if (el.hasAttribute('data-block')) {
                        el.after(blockEl);
                    } else {
                        el.appendChild(blockEl);
                    }
                    
                    const block = this.registerBlock(blockEl);
                    this.fire('block.added', [block, this]);
                    block.edit();
                } else {
                    notifier.send({
                        status: 'error',
                        text: data.message,
                    });
                }
            });
        }
        reorderBlocks() {
            const blocks = [];
            let i = 1;
            
            this.el.querySelectorAll('[data-block-id]').forEach(el => {
                const block = this.blocks[el.getAttribute('data-block-id')];
                block.block.sortorder = i++;
                blocks.push(block.block);
            });
            
            fetch(this.config.reorderUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({editor: this.name, blocks: blocks})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status !== 200) {
                    notifier.send({
                        status: 'error',
                        text: data.message,
                    });
                } else {
                    this.fire('blocks.reordered', [blocks, this]);
                }
            });
        }        
        deleteBlock(block) {
            delete this.blocks[block.id];
            
            // add new btn if no blocks
            if (Object.keys(this.blocks).length === 0) {
                this.newBlockEl.classList.add('active');
            }
            
            this.fire('block.deleted', [block, this]);
        }
    }
    
    class Editors {
        constructor() {
            this.editors = {};
            this.events = new Eventer();
            this.currentBlock = null;
            
            this.events.listen('block.edited', (block, editor) => {
                editor.updateBlocksInputField();
            });
            
            this.events.listen('block.added', (block, editor) => {
                editor.reorderBlocks();
            });
            
            this.events.listen('block.deleted', (block, editor) => {
                editor.updateBlocksInputField();
            });
            
            this.events.listen('blocks.reordered', (blocks, editor) => {
                editor.updateBlocksInputField();
            });
        }
        register() {
            document.querySelectorAll('[data-block-editor]').forEach(el => {
                let config = {};
                
                try {
                    config = JSON.parse(el.getAttribute('data-block-editor'));
                } catch (e) {
                    // ingore
                }
                
                el.removeAttribute('data-block-editor');
                this.create(el, config);
            });
        }
        create(el, config = {}) {
            if (!el) {
                return;
            }
            
            if (typeof config['id'] === 'undefined') {
                return;
            }

            if (! this.has(config['id'])) {
                this.editors[config['id']] = new Editor(el, config, this.events);
            }
            
            return this.editors[config['id']];
        }        
        set(id, obj) {
            this.editors[id] = obj;
            return obj;
        }
        get(id) {
            return this.editors[id];
        }
        delete(id) {
            delete this.editors[id];
        }
        has(id) {
            return (typeof this.editors[id] === 'undefined') ? false : true;
        }
        current() {
            return this.editors[this.currentBlock.editorId];
        }
        setCurrentBlock(block) {
            this.currentBlock = block;
            const editor = this.editors[this.currentBlock.editorId];
            editor.currentBlock = block;
        }
        all() {
            return this.editors;
        }
        closeToolbars() {
            Object.entries(editors.all()).forEach(([id, editor]) => {
                Object.entries(editor.blocks).forEach(([id, block]) => {
                    block.getToolbar().close();
                });
            });
        }
        listen(eventName, callback) {
            this.events.listen(eventName, callback);
        }
        fire(eventName, parameters) {
            this.events.fire(eventName, parameters);
        }
    }
    
    document.addEventListener('DOMContentLoaded', (e) => {
        editors.register();
        
        button.listen('dom.updated', () => {
            editors.editors = {};
            editors.register();
        });
    });
    
    const editors = new Editors();
    return editors;
})(window, document);

export default blockEditors;