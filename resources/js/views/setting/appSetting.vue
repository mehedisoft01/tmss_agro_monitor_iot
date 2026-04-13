
<script setup>
    import {generalLayout,fromModal,pageTop, fileUpload } from '@/components';

    import {ref, onMounted, reactive} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';


    const {getDependency, submitForm, editData, deleteRecord, uploadFile} = {...useHttp()};
    const {_l, formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId, clickFile} = {
        ...useBase(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };
    const {getDataList, httpReq} = useHttp();
    const confTypes = {
        text : 'Text',
        textarea : 'TextArea',
        select : 'Select',
        file : 'File',
        encoded : 'Encoded',
        youtube : 'Youtube',
    };
    const getObjKeyName = (key) => {
        const roles = ['salesman_role', 'division_manager_role', 'district_manager_role', 'financial_manager_role','batter_go_admin_role'];
        if (roles.includes(key)) {
            return 'roles'
        }
        return key
    };


    onMounted(() => {
        getDataList();

        getDependency({ dependency: ['roles'] }, {
            app_details : [{name : 'Name', value : 1}, {name : 'Name', value : 1}]
        });
    });
</script>

<template>
    <generalLayout>
        <template v-slot:pageTop>
            <pageTop :listPage="false"></pageTop>
        </template>
        <form @submit.prevent="submitForm({data:dataList, method : 'put', updateId : 1})">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <template v-for="(settingGroup, group) in dataList">
                            <div class="row">
                                <h6 class="col-md-12">{{_l(group)}}: <a class="pointer" @click="openModal({
                            defaultObject:{key : '',type : 'text',setting_type : group, value : '', is_visible : '1' }
                            })"><i class="bx bxs-plus-square"></i></a> </h6>
                                <hr>
                            </div>
                            <template v-for="setting in settingGroup">
                                <div class="row mt-2">
                                    <label :for="setting.key" class="col-md-3">{{_l(setting.key)}}: </label>
                                    <div class="col-md-9">
                                        <template v-if="['text','number'].includes(setting.type)">
                                            <input :id="setting.key" v-model="setting.value" class="form-control" :type="setting.type">
                                        </template>
                                        <template v-if="setting.type == 'textarea'">
                                            <textarea :id="setting.key" v-model="setting.value" rows="2" class="form-control"></textarea>
                                        </template>
                                        <template v-if="setting.type == 'date'">
                                            <datepicker :id="setting.key" :value="setting.value" v-model="setting.value" class="form-control"></datepicker>
                                        </template>
                                        <template v-if="setting.type == 'select'">
                                            <select :id="setting.key" v-model="setting.value" class="form-control">
                                                <option value="">Select</option>
                                                <template v-for="(item, index) in pageDependencies[getObjKeyName(setting.key)]">
                                                    <option :value="item.id"> {{ item.display_name}}</option>
                                                </template>
                                            </select>
                                        </template>
                                        <template v-if="setting.type === 'file'">
                                            <fileUpload :object="setting" :column="'value'" />
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </template>
                        <div class="row mt-2">
                            <div class="col-md-12 text-end">
                                <button class="btn btn-sm btn-success" type="submit">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <fromModal @submit="submitForm({
            modal: 'fromModal',
            callback: function (retData) {
                Object.assign(formObject, {});
                getDataList();
            }
        })">
            <div class="row mt-2">
                <label class="col-md-3">{{_l('key')}}</label>
                <div class="col-md-9">
                    <input class="form-control" type="text" v-model="formObject.key">
                </div>
            </div>
            <div class="row mt-2">
                <label class="col-md-3">{{_l('type')}}</label>
                <div class="col-md-9">
                    <select class="form-control" v-model="formObject.type">
                        <option v-for="(config, cIndex) in confTypes" :value="cIndex">{{config}}</option>
                    </select>
                </div>
            </div>
            <div class="row mt-2">
                <label class="col-md-3">{{_l('setting_type')}}</label>
                <div class="col-md-9">
                    <input class="form-control" type="text" v-model="formObject.setting_type">
                </div>
            </div>
            <div class="row mt-2">
                <label class="col-md-3">{{_l('value')}}</label>
                <div class="col-md-9">
                    <input class="form-control" type="text" v-model="formObject.value">
                </div>
            </div>
            <div class="row mt-2">
                <label class="col-md-3">{{_l('visibility')}}</label>
                <div class="col-md-9">
                    <select class="form-control" v-model="formObject.is_visible">
                        <option value="1">Visible</option>
                        <option value="0">Hidden</option>
                    </select>
                </div>
            </div>
        </fromModal>
    </generalLayout>
</template>

