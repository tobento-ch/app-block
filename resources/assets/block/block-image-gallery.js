import modals from './../modal/modals.js';

const blockImageGallery = (function(window, document) {
    'use strict';

    let idCounter = 0;

    function uniqueId(prefix) {
        const id = ++idCounter + '';
        return prefix ? prefix + id : id;
    }
    
    class Gallery {
        constructor(el, config) {
            el.setAttribute('data-image-gallery', JSON.stringify(config));
            this.el = el;
            this.id = config.id;
            this.images = {};

            const modal = modals.get('block-image-gallery');
            const expandEl = modal.modalEl.querySelector('[data-gallery="expand"]');
            
            if (expandEl) {
                expandEl.addEventListener('click', (e) => {
                    modal.modalEl.querySelector('.modal-content').classList.toggle('modal-full');
                });
            }
            
            document.body.append(modal.modalEl);
            
            this.create();
        }
        create() {
            let id = 1;
            this.el.querySelectorAll('[data-gallery="open"]').forEach(el => {
                el.setAttribute('data-image-id', id);
                id++;
            });
            
            const templateEl = this.el.querySelector('[data-images]').content.cloneNode(true);
            
            id = 1;
            
            templateEl.querySelectorAll('[data-image]').forEach(el => {
                el.setAttribute('data-image-id', id);
                this.images[id] = {id: id, el: el};
                id++;
            });
        }
        open(event) {
            const el = event.target.closest('[data-gallery="open"]');
            let id = 0;

            if (el && el.hasAttribute('data-image-id')) {
                id = el.getAttribute('data-image-id');
            }
            
            const modal = modals.get('block-image-gallery');

            this.display(id, modal);
        }
        display(id, modal) {
            if (! this.hasImage(id)) {
                return;
            }

            const displayEl = modal.modalEl.querySelector('[data-display="images"]');
            
            displayEl.innerHTML = '';
            modal.modalEl.querySelector('.modal-body').scrollTop = 0;
            
            this.reorderImages(id).forEach(img => {
                displayEl.appendChild(img.el);
            });
            
            modal.open();
        }
        reorderImages(currentId) {
            const ordered = [];
            const images = Object.values(this.images);
            const imgCount = images.length;
            
            for (let i = currentId-1; i < imgCount; i++) {
                ordered.push(this.images[i+1]);
            }
            
            for (let i = 1; i < currentId; i++) {
                ordered.push(this.images[i]);
            }
            
            return ordered;
        }
        hasImage(id) {
            return (typeof this.images[id] === 'undefined') ? false : true;
        }
    }
    
    class Galleries {
        constructor() {
            this.galleries = {};
        }
        handleClickEvent(e, el) {
            let config = {};
            try {
                config = JSON.parse(el.getAttribute('data-image-gallery'));
            } catch (e) {
                // ingore
            }
            if (typeof config['id'] !== 'undefined' && this.has(config['id'])) {
                this.get(config['id']).open(e);
            } else {
                this.create(el, config).open(e);
            }
        }
        create(el, config = {}) {
            if (!el) {
                return;
            }
            
            if (typeof config['id'] === 'undefined') {
                config['id'] = uniqueId();
            }
            
            if (! this.has(config['id'])) {
                this.galleries[config['id']] = new Gallery(el, config);
            }
            
            return this.galleries[config['id']];
        }
        get(id) {
            return this.galleries[id];
        }
        delete(id) {
            delete this.galleries[id];
        }
        has(id) {
            return (typeof this.galleries[id] === 'undefined') ? false : true;
        }
        all() {
            return this.galleries;
        }
    }

    document.addEventListener('DOMContentLoaded', (e) => {
        // we add click event globally as not to loose listeners on update DOM.
        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-image-gallery]');

            if (el) {
                galleries.handleClickEvent(e, el);
            }
        });
    });    

    const galleries = new Galleries();
    return galleries;
})(window, document);

export default blockImageGallery;