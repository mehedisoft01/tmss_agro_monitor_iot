import { nextTick } from 'vue';

const select2 = {
    mounted(el, binding) {
        const $el = window.$(el);
        if (!$el.select2) return;

        const parent = window.$(el).closest('.modal');
        const dropdownParent = parent.length ? parent : window.$('body');

        const options = {
            width: '100%',
            allowClear: true,
            placeholder: el.dataset.placeholder || '-- Select --',
            dropdownParent,
            ...(binding.value || {}),
        };

        $el.select2(options);

        $el.on('select2:select select2:unselect', () => {
            const event = new Event('change', { bubbles: true });
            el.dispatchEvent(event);
        });

        nextTick(() => {
            setTimeout(() => {
                if (!el.isConnected) return;

                const currentVal = el.value;
                if (currentVal && $el.find(`option[value="${currentVal}"]`).length) {
                    $el.val(currentVal).trigger('change.select2');
                } else {
                    $el.val(null).trigger('change.select2');
                }
            }, 300);
        });

        const observer = new MutationObserver(() => {
            if (!el.isConnected) return;

            const currentVal = el.value;
            if ($el.data('select2')) $el.select2('destroy');
            $el.select2(options);

            if (currentVal && $el.find(`option[value="${currentVal}"]`).length) {
                $el.val(currentVal).trigger('change.select2');
            } else {
                $el.val(null).trigger('change.select2');
            }
        });
        observer.observe(el, { childList: true, subtree: true });
        el._select2_observer = observer;
    },

    updated(el) {
        const $el = window.$(el);
        nextTick(() => {
            if (!el.isConnected) return;

            const currentVal = el.value;
            if (currentVal && $el.find(`option[value="${currentVal}"]`).length) {
                $el.val(currentVal).trigger('change.select2');
            } else {
                $el.val(null).trigger('change.select2');
            }
        });
    },

    // unmounted(el) {
    //     const $el = window.$(el);
    //     if ($el.data('select2')) $el.select2('destroy');
    //     if (el._select2_observer) el._select2_observer.disconnect();
    // },
    // unmounted(el) {
    //     if (!el) return; // 🛑 guard check
    //
    //     const $el = window.$ ? window.$(el) : null;
    //
    //     if ($el && $el.data('select2')) {
    //         $el.select2('destroy');
    //     }
    //
    //     if (el._select2_observer) {
    //         el._select2_observer.disconnect();
    //         el._select2_observer = null; // clean reference
    //     }
    // }

    unmounted(el) {
        if (!el) return;

        try {
            if (window.$) {
                const $el = window.$(el);
                if ($el?.data('select2')) {
                    $el.select2('destroy');
                }
            }

            if (el?._select2_observer) {
                el._select2_observer.disconnect();
                delete el._select2_observer;
            }
        } catch (e) {
            console.warn('Select2 cleanup error:', e);
        }
    }
};

export default (app) => {
    app.directive('select2', select2);
};
