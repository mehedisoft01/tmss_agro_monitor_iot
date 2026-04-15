<script setup>
import {dataTable,fromModal,tableTop} from '@/components';

import {ref, onMounted} from 'vue';
import {useStore} from 'vuex';
const store = useStore();
import {useBase, useHttp, appStore} from '@/lib';

const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
const {_l,can,formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId,statusBadge,characterLimit,getImage,changeStatus,handleSelectAll,deleteAllRecords} = {
    ...useBase(),
    ...useHttp(),
    ...appStore(),
    ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
};

const tableHeaders = ref(["#", "device_id", "device_name", "farmer_type", "device_location", "device_lat", "device_long", "actions"]);
const {getDataList, httpReq,urlGenerate} = useHttp();

onMounted(() => {
    getDataList();
});
</script>


<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultAddButton="false"></tableTop>
        </template>

        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td class="fw-medium">{{index+1}}</td>
                <td>{{item.device_id}}</td>
                <td>{{item.device_name}}</td>
                <td>
                    <span v-if="item.farmer_type == 1">Paddy Farmer</span>
                    <span v-else-if="item.farmer_type == 2">Vegetable Farmer</span>
                    <span v-else>-</span>
                </td>
                <td>{{item.device_location}}</td>
                <td>{{item.device_lat}}</td>
                <td>{{item.device_long}}</td>
                <td>
                    <a @click="editData({data:item, id:item.id, modal:'fromModal'})" class="btn btn-outline-secondary action">
                        <i class='bx bxs-edit text-warning'></i>
                    </a>
                    <a @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})"  class="btn btn-outline-secondary action">
                        <i class='bx bxs-trash text-danger'></i>
                    </a>
                </td>
            </tr>
        </template>

        <fromModal @submit="submitForm({
            modal: 'fromModal',
            callback: function (retData) {
                Object.assign(formObject, {});
                getDataList();
            }
        })">

            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('device_id') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.device_id" v-validate="'required'" name="device_id" readonly class="form-control">
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('device_name') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.device_name" name="device_name" class="form-control">
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('farmer_type') }} :</strong></label>
                <div class="col-md-8">
                    <select class="form-control pointer" v-model="formObject.farmer_type">
                        <option value="">Select Type</option>
                        <option value="1">Paddy Farmer</option>
                        <option value="2">Vegetable Farmer</option>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('device_location') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.device_location" name="device_location" class="form-control">
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('device_lat') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.device_lat" name="device_lat" class="form-control">
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('device_long') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.device_long" name="device_long" class="form-control">
                </div>
            </div>

        </fromModal>
    </dataTable>
</template>
