<script setup>
    import {dataTable,fromModal,tableTop } from '@/components';

    import {ref, onMounted} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
    const {_l,formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId} = {
        ...useBase(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#", "name", "email", "user_name", "phone", "opening_date", "action"]);
    const {getDataList, httpReq} = useHttp();

    onMounted(() => {
        getDataList();
        getDependency({dependency : ['roles']});
    });
</script>

<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultObject="{role_id:''}"></tableTop>
        </template>
        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td>{{index+1}}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.email }}</td>
                <td>{{ item.username }}</td>
                <td>{{ item.created_at }}</td>
                <td>
                    <a class="badge rounded-pill p-2 text-uppercase px-3" :class="parseInt(item.status) === 1 ? 'bg-success' : 'bg-warning'">
                        <i class='bx bxs-circle me-1'></i>
                        <span>Active</span>
                    </a>
                </td>
                <td>
                    <a @click="editData({data:item, id:item.id, modal:'fromModal'})" class="btn btn-outline-secondary action">
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
                <label class="col-md-4"><strong>{{_l('role')}} : </strong></label>
                <div class="col-md-8">
                    <select v-model="formObject.role_id" class="form-control">
                        <option value="">Select</option>
                        <template v-for="role in pageDependencies.roles">
                            <option :value="role.id">{{role.name}}</option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{_l('name')}} : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.name" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{_l('email')}} : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.email" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>{{_l('user_name')}} : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.username" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2" v-if="!updateId">
                <label class="col-md-4"><strong>{{_l('password')}} : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.password" class="form-control"/>
                </div>
            </div>
        </fromModal>
    </dataTable>

</template>
