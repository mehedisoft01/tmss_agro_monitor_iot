<script setup>
    import {dataTable,fromModal,tableTop } from '@/components';

    import {ref, onMounted} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
    const {formFilter, formObject, openModal, can, statusBadge, dataList, httpRequest, pageDependencies, updateId} = {
        ...useBase(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#","Display Name", "Name", "Status", "Actions"]);
    const {getDataList, httpReq,changeStatus} = useHttp();

    onMounted(() => {
        getDataList();
    });
</script>

<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultObject="{}"></tableTop>
        </template>
        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td>{{index+1}}</td>
                <td>{{ item.display_name }}</td>
                <td>{{ item.name }}</td>
                <td><a @click="changeStatus({obj:item})" class="pointer" v-html="statusBadge(item.status)"></a></td>
                <td>
                    <a @click="editData({data:item, id:item.id, modal:'fromModal'})" v-if="can('roles.update')" class="btn btn-outline-secondary action">
                        <i class='bx bxs-edit text-warning'></i>
                    </a>
                    <a @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})" class="btn btn-outline-secondary action">
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
                <label class="col-md-4"><strong>Display Name : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.display_name" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>name : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.name" class="form-control"/>
                </div>
            </div>
        </fromModal>
    </dataTable>

</template>
