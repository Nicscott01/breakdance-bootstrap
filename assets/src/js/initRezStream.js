// Global arrays to store the calendar configurations and track queued calendars
window.rezCalendarQueue = window.rezCalendarQueue || [];
window.rezCalendarQueued = window.rezCalendarQueued || [];

// Function to queue calendars
window.queueRezCalendar = (element_id, config) => {
    window.rezCalendarQueue.push({
        calElements: element_id,
        ...config
    });
};

// Function to trigger the 'rezStreamLoaderReady' event
window.triggerRezStreamLoaderReady = () => {
    const event = new Event('rezStreamLoaderReady');
    document.dispatchEvent(event);
};

// Set up calendar loader readiness
document.addEventListener('DOMContentLoaded', () => {
    if (rezStreamLoader) {
        window.triggerRezStreamLoaderReady();
    } else {
        const interval = setInterval(() => {
            if (rezStreamLoader) {
                clearInterval(interval);
                window.triggerRezStreamLoaderReady();
            }
        }, 100);
    }
});


// Initialize calendars when rezStreamLoader is ready
document.addEventListener('rezStreamLoaderReady', () => {

    const loadCalendars = () => {
        window.rezCalendarQueue.forEach(config => {
            rezStreamLoader.ready(() => rezStreamLoader.addCalendar(config.calElements, config));
            window.rezCalendarQueued.push(window.rezCalendarQueue.shift());
        });
    };

    if (window.rezCalendarQueue.length && !window.rezCalendarQueued.length) {
        rezStreamLoader.load(window.rezCalendarQueue[0]);
        window.rezCalendarQueued.push(window.rezCalendarQueue.shift());
    }
    
    if (window.rezCalendarQueue.length) loadCalendars();
});

// Classes to be removed and added
const classesToRemove = ['ui-button', 'ui-widget', 'ui-state-default', 'ui-corner-all', 'ui-button-text-only'];
const observeElements = ['.rs-sc-nights-wrapper', '.rs-sc-guest-wrapper', '.rs-sc-promo-wrapper', '.rs-sc-arrival-wrapper', '.rs-sc-departure-wrapper', '.rs-sc-rooms-wrapper', '.ui-datepicker'];
const selectClass = 'breakdance-form-field--select';
const textClass = 'breakdance-form-field--text';
const inputClass = 'breakdance-form-field__input';

// Create a MutationObserver to watch for added buttons and other wrappers
const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1) {
                // Check for book buttons
                if (node.matches('.rs-sc-book-button')) {
                    // Remove old classes
                    node.classList.remove(...classesToRemove);

                    // Add the "button-atom" class
                    node.classList.add('button-atom');

                    // Find the closest parent with a data-button-class attribute and add that class if valid
                    const parentWithClass = node.closest('[data-button-class]');
                    if (parentWithClass) {
                        const buttonClass = parentWithClass.getAttribute('data-button-class');
                        if (buttonClass && buttonClass.trim()) { // Check for non-empty string
                            node.classList.add(buttonClass);
                        }
                    }
                }

                // Check for nights, guest, promo, arrival, departure, and rooms wrappers to add appropriate classes
                if (observeElements.some(selector => node.matches(selector))) {
                    if (node.matches('.rs-sc-promo-wrapper, .rs-sc-arrival-wrapper, .rs-sc-departure-wrapper')) {
                        node.classList.add(textClass);
                        node.querySelectorAll('input').forEach(input => {
                            input.classList.add(inputClass);
                        });
                    } else if (node.matches('.rs-sc-nights-wrapper, .rs-sc-guest-wrapper, .rs-sc-rooms-wrapper, .ui-datepicker')) {
                        node.classList.add(selectClass);
                        node.querySelectorAll('select').forEach(select => {
                            select.classList.add(inputClass);
                        });
                    }
                }
            }
        });
    });
});


document.addEventListener('rezStreamLoaderReady', function() {

    // Start observing the document body for child node additions
    observer.observe(document.body, { childList: true, subtree: true });



    // Optionally, add the class to any existing elements on page load
    document.querySelectorAll('.rs-sc-book-button').forEach(button => {
        button.classList.remove(...classesToRemove);
        button.classList.add('button-atom');
        const parentWithClass = button.closest('[data-button-class]');
        if (parentWithClass) {
            const buttonClass = parentWithClass.getAttribute('data-button-class');
            if (buttonClass && buttonClass.trim()) {
                button.classList.add(buttonClass);
            }
        }
    });

    document.querySelectorAll(observeElements.join(',')).forEach(element => {
        if (element.matches('.rs-sc-promo-wrapper, .rs-sc-arrival-wrapper, .rs-sc-departure-wrapper')) {
            element.classList.add(textClass);
            element.querySelectorAll('input').forEach(input => {
                input.classList.add(inputClass);
            });
        } else if (element.matches('.rs-sc-nights-wrapper, .rs-sc-guest-wrapper, .rs-sc-rooms-wrapper, .ui-datepicker')) {
            element.classList.add(selectClass);
            element.querySelectorAll('select').forEach(select => {
                select.classList.add(inputClass);
            });
        }
    });

});