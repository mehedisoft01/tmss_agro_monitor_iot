import {computed, nextTick, watch} from 'vue'
import {useToast} from 'vue-toast-notification';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import {appStore, useValidator} from "@/lib";
import {useStore} from 'vuex';
import {printPage} from "../plugins/print_div.js";

import {useI18n} from 'vue-i18n'
import {ref} from 'vue'

export function useBase() {
    const toast = useToast();
    const store = useStore();
    const {t} = useI18n();

    let {formObject, assignStore, resetValidation, Permissions} = {...appStore(), ...useValidator()};

    const toaster = (type = 'success', message = false, title = false) => {
        if (!message) return;

        const capitalize = str => str.charAt(0).toUpperCase() + str.slice(1);
        title = title || capitalize(type);

        const formatMessage = (msg, t) => t ? `<strong>${t}</strong><br><span>${msg}</span>` : `<span>${msg}</span>`;

        const map = {
            success: msg => toast.success(formatMessage(msg, title)),
            error: msg => toast.error(formatMessage(msg, title)),
            info: msg => toast.info(formatMessage(msg, title)),
            warning: msg => toast.warning(formatMessage(msg, title)),
            default: msg => toast(formatMessage(msg, title)),
        };

        (map[type] || map.default)(message);
    };

    const _l = (key, fallback = '') => {
        if (!key) return fallback;
        return t(key) || fallback;
    };

    const can = (permission) => {
        if (!permission) return false;
        if (Permissions.value === undefined || Permissions.value.length === 0) return false;
        return Permissions.value.includes(permission);
    };

    const getImage = (imagePath = null, alternative = false) => {
        if (imagePath !== undefined && imagePath !== '' && imagePath !== null) {
            return `${uploadPath}/${imagePath}`;
        }
        if (alternative) {
            return `${publicPath}/${alternative}`;
        }
    };
    const openFile = (url, title = '', customWidth = false, customHeight = false) => {
        const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
        const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;

        const width = parseFloat(window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width);
        const height = parseInt(window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height);

        const w = !customWidth ? width - 250 : customWidth;
        const h = !customHeight ? height - 100 : customHeight;

        const systemZoom = width / window.screen.availWidth;
        const left = (width - w) / 2 / systemZoom + dualScreenLeft;
        const top = (height - h) / 2 / systemZoom + dualScreenTop;
        const newWindow = window.open(url, title, `scrollbars=yes,width=${w / systemZoom},height=${h / systemZoom},top=${top},left=${left}`);

        if (window.focus) {
            newWindow.focus();
        }
    };

    const openModal = (options = {}) => {
        let {
            modalId = 'fromModal',
            defaultObject = {},
            callback = false,
            resetForm = true
        } = options;

        resetValidation();

        if (resetForm) {
            assignStore('formObject', {});
            assignStore('updateId', null);
        }

        if (Object.keys(defaultObject).length > 0) {
            store.commit('resetForm', defaultObject);
        }

        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal with id "${modalId}" not found`);
            return;
        }

        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: true,
            focus: true
        });
        bsModal.show();

        const firstInput = modal.querySelector('input, textarea, select');
        if (firstInput) {
            firstInput.focus();
        } else {
            modal.focus();
        }

        if (typeof callback === 'function') {
            callback({success: true, modalId, formObject});
        }
    };
    const closeModal = (modalId = 'fromModal') => {
        const modal = document.getElementById(modalId);
        const bsModal = bootstrap.Modal.getInstance(modal);
        bsModal.hide();
        document.querySelector('body').focus();
    };

    const handelConfirm = async (options = {}) => {
        const {
            title = "Are you sure?",
            message = "Are you sure to delete this ??",
            callback = false
        } = options;

        const result = await Swal.fire({
            title: title,
            text: message,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: '<i class="fadeIn animated bx bx-check-circle"></i> Yes',
            cancelButtonText: '<i class="fadeIn animated bx bx-window-close"></i> No',
        });

        if (typeof callback === 'function' && result.isConfirmed) {
            callback(true);
        }
        return result.isConfirmed;
    };
    const handleSelectAll = (event, dataList) => {
        if (dataList !== undefined) {
            $.each(dataList, (index, item) => {
                item.checked = event.target.checked ? 1 : 0;
                $.each(item.submenus, (sIndex, sItem) => {
                    sItem.checked = event.target.checked ? 1 : 0;
                })
            });
        }
    };

    const statusBadge = (status, activeText = 'active', inActiveText = 'inactive') => {
        const isActive = parseInt(status);
        const badgeClass = isActive ? "bg-success" : "bg-warning";
        const label = isActive ? _l(activeText) : _l(inActiveText);

        return `<span class="badge rounded-pill p-2 text-uppercase px-3 ${badgeClass}">
            <i class="bx bxs-circle me-1"></i>
            <span>${label}</span>
        </span>`;
    };

    const clickFile = (inputId) => {
        $(`#${inputId}`).click();
    };
    const copiedItem = ref(null);
    const copyText = async (inputText) => {
        try {
            if (!inputText) return "N/A";
            await navigator.clipboard.writeText(inputText);
            copiedItem.value = inputText

            setTimeout(() => {
                copiedItem.value = null
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text: ', err);
            return `<span class="badge rounded-pill p-2 text-uppercase px-3">Failed</span>`;
        }
    };

    const dateFormat = (inputDate) => {
        if (!inputDate) return "N/A";
        const date = new Date(inputDate);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${day}-${month}-${year}`;
    };
    const dateTimeFormat = (inputDate) => {
        if (!inputDate) return "N/A";
        const date = new Date(inputDate);
        return date.toLocaleString();
    };
    const characterLimit = (inputText, limit) => {
        if (typeof inputText !== "string") return "";
        return inputText.length > limit
            ? inputText.slice(0, limit) + "..."
            : inputText;
    };

    const printData = (div_id = 'printDiv') => {
        printPage('#' + div_id)
    };
    const formatMonthYear = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        const options = {month: 'long', year: 'numeric'};
        return date.toLocaleDateString('en-US', options).replace(' ', '-');
    }

    const addRow = (object, pushEr) => {
        if (typeof object === 'object') {
            object.push(pushEr);
        }
    };
    const deleteRow = (object, index) => {
        object.splice(index, 1);
    };

    const showData = (dataArray, fieldName, retBoolean = false) => {
        if ((dataArray !== null && dataArray !== undefined)
            && (dataArray[fieldName] !== undefined && dataArray[fieldName] !== null)) {
            return dataArray[fieldName];
        } else {
            return retBoolean ? false : '-';
        }
    };
    const fileIcon = (fileName = null) => {
        const iconMap = {
            // Image files
            'png': (f) => getImage(f.path),
            'jpg': (f) => getImage(f.path),
            'gif': (f) => getImage(f.path),

            // PDF
            'pdf': () => getImage(null, 'images/icon/pdf.png'),

            // HTML
            'html': () => getImage(null, 'images/icon/html.png'),

            // Documents
            'doc': () => getImage(null, 'images/icon/doc.png'),
            'docx': () => getImage(null, 'images/icon/doc.png'),
            'odt': () => getImage(null, 'images/icon/doc.png'),

            // Spreadsheets
            'xls': () => getImage(null, 'images/icon/xlsx.png'),
            'xlsx': () => getImage(null, 'images/icon/xlsx.png'),
            'ods': () => getImage(null, 'images/icon/xlsx.png'),
            'csv': () => getImage(null, 'images/icon/xlsx.png')
        };

        const filename = fileName || '';
        const extension = filename.split('.').pop().toLowerCase();

        if (iconMap[extension]) {
            return iconMap[extension](fileName);
        }

        return getImage(null, 'images/icon/file_image.png');
    };


    return {
        showData,
        _l,
        getImage,
        printData,
        addRow,
        deleteRow,
        formatMonthYear,
        openFile,
        toaster,
        can,
        openModal,
        closeModal,
        handelConfirm,
        handleSelectAll,
        statusBadge,
        clickFile,
        copyText,
        copiedItem,
        dateFormat,
        dateTimeFormat,
        characterLimit,
        fileIcon
    };
}
