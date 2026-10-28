import TomSelect from 'tom-select/base';
import dropdownInput from 'tom-select/plugins/dropdown_input/plugin.js';

// Search box sits at the top of the open list, so the field itself always shows the clean selection.
TomSelect.define('dropdown_input', dropdownInput);

/*
 * Any <select> with a long list gets type-to-search (Tom Select on top of the native select,
 * so form posts, validation and Alpine x-model keep working).
 * Force it with data-searchable, opt out with data-searchable="false".
 */
const MIN_OPTIONS = 8;

const wants = (el) => {
    if (el.multiple || el.tomselect) return false;
    const flag = el.dataset.searchable;
    if (flag === 'false') return false;
    if (flag !== undefined) return true;
    return [...el.options].filter((o) => o.value !== '').length > MIN_OPTIONS;
};

const enhance = (el) => {
    // Classes on the select are copied to the Tom Select wrapper; keep layout ones (widths), drop the input styling.
    const small = el.classList.contains('input-sm');
    el.classList.remove('input', 'input-sm', 'appearance-none', 'pr-10');
    el.style.backgroundImage = '';

    const placeholder = el.querySelector('option[value=""]')?.textContent.trim();
    new TomSelect(el, {
        plugins: ['dropdown_input'],
        maxOptions: null,
        // An empty option on an optional field ("— Unassigned —") must stay selectable.
        allowEmptyOption: !el.required,
        placeholder: placeholder || 'Type to search…',
        dropdownParent: 'body',
        wrapperClass: 'ts-wrapper' + (small ? ' ts-sm' : ''),
        render: { no_results: () => '<div class="no-results">No matches</div>' },
    });
};

const init = () => document.querySelectorAll('select').forEach((el) => wants(el) && enhance(el));

// Alpine can change a select's value from code (e.g. picking a customer fills in their collector).
// Keep the search box in step without re-triggering change events.
const sync = () => document.querySelectorAll('select.tomselected').forEach((el) => {
    const ts = el.tomselect;
    if (ts && ts.getValue() !== el.value) ts.setValue(el.value, true);
});
['change', 'input', 'click'].forEach((type) => document.addEventListener(type, () => setTimeout(sync), true));

document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(init));
