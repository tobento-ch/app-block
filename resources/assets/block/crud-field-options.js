import blockEditors from './../block/block-editors.js';

const fieldOptions = (function(window, document) {
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
        register: function() {
            // we add click event globally as not to loose listeners on update DOM
            document.addEventListener('click', (e) => {
                const actionEl = e.target.closest('[data-options-action]');

                if (actionEl) {
                    options.handleClickAction(e, actionEl);
                }
            });
            
            let globalTimeout = null;
            
            document.addEventListener('keyup', (e) => {
                const actionEl = e.target.closest('[data-options-action]');

                if (actionEl && actionEl.getAttribute('data-options-action') === 'search') {
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
            
            const selectedEl = optionsEl.querySelector('[data-selected]');
            const unselectedEl = optionsEl.querySelector('[data-unselected]');
            
            switch (actionEl.getAttribute('data-options-action')) {
                case 'add':
                    e.preventDefault();
                    actionEl.setAttribute('data-options-action', 'remove');
                    setTimeout(() => {
                        actionEl.querySelector('input[type="checkbox"]').checked = true;
                        selectedEl.appendChild(actionEl);
                    }, 10);
                    break;
                case 'remove':
                    e.preventDefault();
                    actionEl.setAttribute('data-options-action', 'add');
                    setTimeout(() => {
                        actionEl.querySelector('input[type="checkbox"]').checked = false;
                        unselectedEl.prepend(actionEl);
                    }, 10);
                    break;
            }
            
            const editor = blockEditors.current();
            editor.getCurrentBlock().save();
        },
        search: function(e, actionEl) {
            const optionsEl = e.target.closest('[data-options]');
            
            if (!optionsEl) {
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
                    search: dotPathToObj(fieldName, e.target.value),
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
            const selectedEl = optionsEl.querySelector('[data-selected]');
            const unselectedEl = optionsEl.querySelector('[data-unselected]');
            const selectedValues = [];
            
            selectedEl.querySelectorAll('input').forEach(el => {
                selectedValues.push(el.value);
            });
            
            unselectedEl.querySelectorAll('input').forEach(el => {
                if (selectedValues.indexOf(el.value) !== -1) {
                    el.closest('[data-options-action]').remove();
                }
            });
        }
    };
    
    document.addEventListener('DOMContentLoaded', (e) => {
        options.register();
    });
    
    return options;
    
})(window, document);

export default fieldOptions;