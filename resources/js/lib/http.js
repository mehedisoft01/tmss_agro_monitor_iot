import {ref} from 'vue';
import {useRouter, useRoute} from 'vue-router';
import axios from 'axios';
import {useStore} from 'vuex';

import {useBase, appStore, useValidator} from "@/lib";

export function useHttp() {
    const store = useStore();
    const router = useRouter();
    const route = useRoute();
    const { validate, reset } = useValidator();

    const {can, toaster, closeModal, openModal, handelConfirm, useGetters, assignStore} = {...useBase(), ...appStore()};

    const {formFilter, formObject} = useGetters('formFilter', 'formObject');
    const uploadProgress = ref(0);

    const getRoute = (key = false) => key && route[key] !== undefined ? route[key] : route;
    const routeMeta = (key = false) => route.meta ? (route.meta[key] !== undefined ? route.meta[key] : false) : false;

    const getUrl = () => route.meta?.dataUrl || '';
    const urlGenerate = (customUrl = false) => `${baseUrl}/${customUrl || getUrl()}`;

    const httpReq = async (options = {}) => {
        const {url = '', method = 'get', params = {}, data = {}, callback = false, loader = false, signature = ''} = options;

        if (!url) {
            toaster('error', 'No URL provided and no dataUrl in route meta');
            return;
        }

        if (loader) store.commit('httpRequest', true);

        const response = await axios({method, url, params, data});

        if (loader) store.commit('httpRequest', false);

        const status = parseInt(response.data.status);

        if ([5000, 5001].includes(status)) {
            toaster((response.data.type || 'error'), response.data.message, 'Error');

            if (status === 5001) router.push({path: '/dashboard'});
            return false;
        }
        if (status === 2000) {
            toaster('success', response.data.message, 'Success');
            return response.data.result ?? true;
        }

        if (typeof callback === 'function'){
            callback(response.data.result);
        }

        toaster('info', response.data.message);
        return false;
    };

    const loadConfigurations = async (options = {}) => {
        try {
            const {
                callback = false,
                url = false,
            } = options;

            const retData = await httpReq({
                method: 'post',
                url: url ? urlGenerate(url) : urlGenerate('api/configurations'),
                loader: true,
                signature: 'loadConfigurations'
            });

            if (retData) {
                store.commit('Config', retData);
                store.commit('appConfigs', retData.configs);
                store.commit('authUser', retData.user);
                store.commit('allMenus', retData.menus);
                store.commit('localization', retData.localization);

                if (typeof callback === 'function') {
                    callback(retData)
                }
            }

        } catch (error) {
            store.commit('httpRequest', false);
            toaster('error', error.message);
        }
    };
    const getDependency = async (options = {}, staticData = {}) => {
        const {
            dependency = [],
            callback = false
        } = options;

        const retData = await httpReq({
            method: 'post',
            url: urlGenerate('api/general'),
            data: dependency,
            loader: false
        });
        if (retData) {
            $.each(retData, function (key, value) {
                store.commit("pageDependencies", {key: key, value})
            });
        }
        if (Object.keys(staticData).length > 0) {
            $.each(staticData, function (key, value) {
                store.commit("pageDependencies", {key: key, value})
            });
        }
        if (typeof callback === 'function') {
            callback(retData)
        }
    };

    const getDataList = async (options = {}) => {
        const {url = false, method = 'get', params = {}, callback = false, page = 1, clearList = true} = options;

        store.commit('currentPagination', page || 1);
        // const dataFilter = route.meta?.dataUrl ?? {};
        const dataFilter = {};
        if (clearList){
            store.commit('dataList', {});
        }

        const dataList = await httpReq({
            method,
            url: urlGenerate(url),
            params: Object.assign(formFilter.value, {page}, params, dataFilter),
            loader: true
        });

        if (dataList) {
            store.commit('dataList', dataList);
            if (typeof callback === 'function') callback(dataList);
        }
    };
    const submitForm = async (options = {}) => {
        let {data = {}, modal = false, callback = false, validation = true, url = false, params = {}, method = false, updateId = false, reset = true} = options;

        if (Object.keys(data).length === 0) {
            data = formObject.value;
        }

        const updateIdVal = updateId ? updateId : store.getters.updateId;

        const isValid = validation ? await validate() : true;
        console.log(isValid);
        if (!isValid){
            toaster('warning', 'Please fill all field properly the submit again', 'Validation Failed');
            return false;
        }
        const retData = await httpReq({
            method: method ? method : (parseInt(updateIdVal) ? 'put' : 'post'),
            url: parseInt(updateIdVal) ? `${urlGenerate(url)}/${updateIdVal}` : urlGenerate(url),
            data: data,
            params: params,
            loader: true,
        });
        if (retData) {
            store.commit('updateId', false);
            if (reset) {
                data = {};
            }
            if (modal) closeModal(modal);
            if (typeof callback === 'function') callback(retData);
        }
    };
    const singleData = async (options = {}) => {
        let {id = false, primaryKey = 'id', url = false, method = 'get', dataType = 'edit'} = options;

        const data = await httpReq({
            method: method,
            url: url ? urlGenerate(url) : (dataType === 'edit'
                    ? `${urlGenerate()}/${id}/edit`
                    : `${urlGenerate()}/${id}`),
            loader: true,
            data: { primaryKey: primaryKey }
        });

        return  data ? data : {};
    };
    const editData = async (options = {}) => {
        let {data = {}, id = false, modal = false, primaryKey = 'id', url = false, method = 'get'} = options;

        if (Object.keys(data).length === 0) {
            data = await singleData({
                id: id,
                method: method
            });
        }
        if (modal) {
            openModal({
                modalId: modal, callback: function (retData) {
                    store.commit('formObject', data);
                    store.commit('updateId', data[primaryKey]);
                }
            });
        } else {
            store.commit('formObject', data);
            store.commit('updateId', data[primaryKey]);
        }
    };
    const deleteRecord = async (options = {}) => {
        const isConfirmed = await handelConfirm();
        if (!isConfirmed) return;
        const {
            targetId = null,
            url = null,
            callback = null,
            refresh = false,
            method = 'delete',
            listObject = null,
            selectedKeys = false,
        } = options;

        if (targetId == null && !selectedKeys) {
            toaster('error', 'Target Id not found');
            return;
        }

        const retData = await httpReq({
            url: url ?? `${urlGenerate(url)}/${targetId}`,
            method : method,
            loader: false,
            data : {
                selectedKeys : !selectedKeys ? {} : selectedKeys
            },
        });

        if (!retData) return;

        if (refresh) getDataList(store, {page: 1});

        if (listObject && !refresh) {
            const index = listObject.findIndex(item => item.id === targetId);
            if (index !== -1) listObject.splice(index, 1);
        }

        if (typeof callback === 'function') callback(retData);
    };
    const deleteAllRecords = async (options = {}) => {
        const {
            dataObject = {},
            primaryKey = 'id',
        } = options;
        let deletableIds = [];

        $.each(dataObject, (index, value) =>{
            if (value.checked !== undefined && parseInt(value.checked)){
                deletableIds.push(value[primaryKey]);
            }
        });

        if (deletableIds.length > 0){
            deleteRecord({
                url : `${urlGenerate()}/multiple`,
                selectedKeys : deletableIds,
                method : 'post',
                callback: (retData) =>{
                    getDataList();
                }
            })
        }
    };

    const changeStatus = async (options = {}) => {
        try {
            const {obj = {}, column = false, permissionName = ''} = options;

            if (permissionName !== '' && !can(permissionName)) {
                toast.warning('Not permitted');
                return false
            }

            store.commit('httpRequest', true);
            const dataObject = (typeof obj === 'object') ? obj : {id: obj};

            if (column) {
                dataObject.column = column
            }

            const retData = await httpReq({
                data: dataObject,
                url: `${urlGenerate()}/status`,
                method: 'post',
                loader: false
            });

            store.commit('httpRequest', false);

            if (retData) {
                getDataList({page: 1});
            }
        } catch (error) {
            store.commit('httpRequest', false);
            toaster('error', error.message);
        }
    };
    const uploadFile = async (event, options = {}) => {
        const {imageObject = {}, dataModel = 'file', callback = false, url = false, onlyUrl = true} = options;
        try {
            const input = event.target.files[0];
            const formData = new FormData();
            formData.append("file", input);
            formData.append("only_url", 1);

            if (!onlyUrl) {
                formData.append("only_url", 1)
            }

            const URL = url ? urlGenerate(url) : urlGenerate('api/file_upload');
            store.commit('httpRequest', true);
            store.commit('uploadProgress', 0);

            const config = {
                onUploadProgress: function (progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    store.commit('uploadProgress', percentCompleted)
                }
            };
            const response = await axios.post(URL, formData, config);
            store.commit('httpRequest', false);

            if (parseInt(response.data.status) === 2000) {
                imageObject[dataModel] = response.data.result;

                if (typeof callback === 'function') {
                    callback(response.data)
                }
            } else if (parseInt(response.data.status) === 3000) {
                setTimeout(() => {
                    store.commit('uploadProgress', 0)
                }, 1000);
                toast.error(response.data.message)
            }
        } catch (error) {
            store.commit('httpRequest', false)
        }
    };

    return {
        uploadProgress,
        httpReq,
        getDataList,
        submitForm,
        editData,
        getRoute,
        routeMeta,
        urlGenerate,
        loadConfigurations,
        getDependency,
        deleteRecord,
        changeStatus,
        uploadFile,
        deleteAllRecords
    };
}
