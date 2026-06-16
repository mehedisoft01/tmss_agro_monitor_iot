<script setup>
    import {dataTable,fromModal,tableTop} from '@/components';
     import axios from 'axios';
    import {ref, onMounted} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
    const {_l,can,formFilter, formObject, toaster,openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId,statusBadge,characterLimit,getImage,changeStatus,handleSelectAll,deleteAllRecords} = {
        ...useBase(),
        ...useHttp(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#", "display_name", "name", "device_id","product_name", "actions"]);
    const {getDataList, httpReq,urlGenerate} = useHttp();

    const loadingIndex = ref(null);
    const checkDeviceStatus = async (index, deviceId) => {
        loadingIndex.value = index;
        try {
            const res = await httpReq({
                method: 'get',
                url: `/api/device-status/${deviceId}`
            });
            // if (res.data.status == 2000) {
            //     toaster('success', res.data.message, 'Success');
            // }
        } catch (err) {
           toaster('error', err.message) ;

        } finally {
            loadingIndex.value = null;

        }
    };

    onMounted(() => {
        getDataList();
        getDependency({dependency : []});
    });
</script>


<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultAddButton="can('devices.store')" :defaultObject="{division_id:'',district_id:'',upazila_id:''}"></tableTop>
        </template>

        <template v-slot:data>
            <tr v-for="(item, index) in dataList">
                <td class="fw-medium">{{index+1}}</td>
                <td>{{item?.display_name}}</td>
                <td>{{item?.name}}</td>
                <td>{{item?.device_id}}</td>
                <td>{{item?.product_name}}</td>
<!--                <td>-->
<!--                    <a @click="changeStatus({obj:online})" class="pointer" v-html="statusBadge(item?.online)"></a>-->
<!--                </td>-->
                <td>
                    <a @click="editData({data:item, id:item.id, modal:'fromModal'})" v-if="can('devices.update')" class="btn btn-outline-secondary action">
                        <i class='bx bxs-edit text-warning'></i>
                    </a>
                    <a @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})" v-if="can('devices.destroy')" class="btn btn-outline-secondary action">
                        <i class='bx bxs-trash text-danger'></i>
                    </a>
                    <!-- <a class="btn btn-outline-secondary"  @click="checkDeviceStatus(index, item.device_id)"
                        :disabled="loadingIndex === index" >
                        <span v-if="loadingIndex === index"> <i class="fa fa-spinner fa-spin"></i> Checking... </span>
                        <span v-else>   Device Status</span>
                    </a> -->
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
                <label class="col-md-4"><strong>{{ _l('device_type') }} :</strong></label>
                <div class="col-md-8">
                    <select class="form-control pointer" v-model="formObject.device_category">
                        <option value="">Select Type</option>
                        <option value="1">Warehouse</option>
                        <option value="2">Soil</option>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('display_name') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.display_name" v-validate="'required'" name="display_name" class="form-control">
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{ _l('device_id') }} :</strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.device_id" v-validate="'required'" name="device_id" class="form-control">
                </div>
            </div>
        </fromModal>
    </dataTable>
</template>
