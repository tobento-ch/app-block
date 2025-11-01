import modals from './../modal/modals.js';
import button from './../crud/button.js';
import blockEditors from './../block/block-editors.js';

const fieldSingleOptions = (function(window, document) {
    'use strict';
    
    function dotPathToObj(pathStr, value) {
        return pathStr
            .split('.')
            .reverse()
            .reduce((acc, cv, index) => ({
                [cv]: index === 1 && value ? {[acc]: value} : acc
            }));
    }
    
    const options = {
        listeners: {},
        modals: [],
        defaultScriptLoaded: false,
        register: function() {
            options.defaultScriptLoaded = Array.from(document.getElementsByTagName('script')).some(script =>
                script.src && script.src.endsWith('crud/field-single-options.js')
            );
            
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const actionEl = e.target.closest('.modal-block [data-single-options-action]');

                if (actionEl) {
                    options.handleClickAction(e, actionEl);
                }
            });
            
            let globalTimeout = null;
            
            document.addEventListener('keyup', (e) => {
                const actionEl = e.target.closest('[data-single-options-action]');

                if (actionEl && actionEl.getAttribute('data-single-options-action') === 'search') {
                    if (globalTimeout != null) {
                        clearTimeout(globalTimeout);
                    }

                    globalTimeout = setTimeout(() => {
                        globalTimeout = null;
                        options.search(e, actionEl);
                    }, 200);
                }
            });
        },
        handleClickAction: function(e, actionEl) {
            const optionsEl = e.target.closest('[data-options]');
            
            if (!optionsEl) {
                return;
            }
            
            const displayModal = optionsEl.getAttribute('data-display-modal');
            const selectedEl = optionsEl.querySelector('[data-selected]');
            const unselectedEl = optionsEl.querySelector('[data-unselected]');
            const dropdownEl = optionsEl.querySelector('[data-dropdown]');
            const fieldEl = e.target.closest('[data-field]');
            
            options.modals.push(fieldEl.getAttribute('data-field'));
            
            if (!options.defaultScriptLoaded) {
                switch (actionEl.getAttribute('data-single-options-action')) {
                    case 'open-dropdown':
                        if (displayModal) {
                            modals.get(fieldEl.getAttribute('data-field')).open();
                            optionsEl.querySelector('[data-single-options-action="search"]').focus();
                            return;
                        }

                        const closeDropdown = (e) => {
                            if (!e.target.closest('.crud-select-input-ctn')) {
                                dropdownEl.classList.remove('active');
                                document.removeEventListener('click', closeDropdown);
                            }
                        }

                        dropdownEl.classList.toggle('active');
                        optionsEl.querySelector('[data-single-options-action="search"]').focus();
                        document.addEventListener('click', closeDropdown);
                        break;
                    case 'add':
                        const fieldInputEl = optionsEl.querySelector('[data-field-input]');
                        fieldInputEl.value = actionEl.getAttribute('data-value');

                        // dispatch event for live.js
                        fieldInputEl.dispatchEvent(new Event('change', { bubbles: true }));

                        const selected = actionEl.cloneNode(true);
                        selected.removeAttribute('data-single-options-action');
                        selectedEl.innerHTML = selected.outerHTML;

                        if (displayModal) {
                            modals.get(fieldEl.getAttribute('data-field')).close();
                        } else {
                            dropdownEl.classList.remove('active');
                        }
                        options.fire('option.added', [selectedEl, actionEl, optionsEl]);
                        break;
                    case 'remove':
                        const fiEl = optionsEl.querySelector('[data-field-input]');
                        fiEl.value = '';
                        selectedEl.innerHTML = '';
                        options.fire('option.removed', [selectedEl, actionEl, optionsEl]);

                        // dispatch event for live.js
                        fiEl.dispatchEvent(new Event('change', { bubbles: true }));

                        break;
                }
            }
        },
        search: function(e, actionEl) {
            const optionsEl = e.target.closest('[data-options]');
            
            if (!optionsEl || !blockEditors.hasCurrent()) {
                return;
            }
            
            e.preventDefault();
            
            const fieldName = optionsEl.getAttribute('data-options');
            const editor = blockEditors.current();
            const block = editor.getCurrentBlock();
            
            fetch(editor.config.editUrl, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    "options-search": dotPathToObj(fieldName, e.target.value),
                    editor: editor.name,
                    block: {id: block.block.id, type: block.block.type}
                })
            }).then(response => {
                return response.text();
            }).then(string => {
                const selector = '[data-unselected="'+fieldName+'"]';
                const doc = (new DOMParser()).parseFromString(string, 'text/html');
                const newEl = doc.querySelector(selector);
                const oldEl = document.querySelector(selector);
                
                if (newEl && oldEl) {
                    oldEl.parentNode.replaceChild(newEl, oldEl);
                    options.removeDublicate(optionsEl);
                }
            });
        },
        removeDublicate: function(optionsEl) {
            const selectedValue = optionsEl.querySelector('[data-field-input]').value;
            const unselectedEl = optionsEl.querySelector('[data-unselected]');

            unselectedEl.querySelectorAll('[data-single-options-action="add"]').forEach(el => {
                if (el.getAttribute('data-value') === selectedValue) {
                    el.closest('[data-single-options-action]').remove();
                }
            });
        },
        listen: function(eventName, callback) {
            if (typeof this.listeners[eventName] === 'undefined') {
                this.listeners[eventName] = [];
            }
            
            this.listeners[eventName].push(callback);
        },
        fire: function(eventName, parameters) {
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
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        options.register();
        
        button.listen('dom.updated', () => {
            options.modals.forEach(id => {
                delete modals.items[id];
            });
            modals.register();
        });
        
        blockEditors.listen('block.edit', (block, editor) => {
            options.modals.forEach(id => {
                delete modals.items[id];
            });
            modals.register();
        });
    });
    
    return options;
    
})(window, document);

export default fieldSingleOptions;