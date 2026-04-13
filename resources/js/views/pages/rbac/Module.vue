<script setup>
    import {ref, reactive, onMounted} from 'vue'
    import {useStore} from 'vuex'
    import {useRouter} from 'vue-router'

    import {dataTable, tableTop, fromModal, pageTop} from '@/components'
    import moduleForm from "@/views/pages/rbac/moduleForm.vue";
    import {appStore, useHttp, useBase} from "@/lib";
    const store  = useStore();
    const router  = useRouter();

    const {_l, useGetters, getDataList,copiedItem,copyText, submitForm, editData, deleteRecord, getDependency,changeStatus, openModal, handleSelectAll, statusBadge, deleteAllRecords} = {
        ...appStore(),
        ...useHttp(),
        ...useBase()
    };
    const { httpRequest, dataList, pageDependencies } = useGetters('httpRequest', 'dataList', 'pageDependencies');

    const tableHeaders = reactive(['sl', {name: '', listObject: dataList}, 'display_name', 'name', 'status','visible', 'action']);
    const permissions = reactive(['directives.js', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'status']);

    onMounted(()=>{
        getDataList();
        getDependency({dependency : ['permissions', 'components', 'icons']});
    });

</script>
<template>
    <dataTable :headings="tableHeaders" :loader="true">
        <template v-slot:tableTop>
            <tableTop :defaultObject="{permissions:[]}">
                <div class="col-md-3">
                    <input type="text" class="form-control radius-30" placeholder="Search Order">
                </div>
            </tableTop>
        </template>
        <template v-slot:topRight v-if="dataList.data !== undefined">
            <a class="btn btn-sm btn-outline-danger radius-30 text-uppercase" @click="deleteAllRecords({dataObject:dataList.data})" v-if="dataList.data.some(each => parseInt(each.checked) === 1)">Delete All</a>
        </template>
        <template v-slot:data>
            <template v-for="(item, index) in dataList.data" :key="item.id">
                <tr>
                    <td>{{index+1}}</td>
                    <td class="checkbox">
                        <input :checked="item.checked" @change="handleSelectAll($event, [item])" class="form-check-input me-3 pointer" type="checkbox"/></td>
                    <td>{{ _l(item.name) }}</td>
<!--                    <td class="pointer" @click="copyText(item.name)">-->
<!--                        <span v-if="copiedItem === item.name" class="badge bg-success rounded-pill px-3" >Copied</span>-->
<!--                        <span v-else>{{ item.name }}</span>-->
<!--                    </td>-->
                    <td class="pointer"  >{{ item.name }}</td>
                    <td><a @click="changeStatus({obj:item})" class="pointer" v-html="statusBadge(item.status)"></a></td>
                    <td><a @click="changeStatus({obj:item, column:'is_visible'})" class="pointer" v-html="statusBadge(item.is_visible, 'visible', 'invisible')"></a></td>
                    <td>
                        <a @click="editData({id:item.id, modal:'fromModal'})" class="btn btn-outline-secondary action">
                            <i class='bx bxs-edit text-warning'></i>
                        </a>
                        <a @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})" class="btn btn-outline-secondary action">
                            <i class='bx bxs-trash text-danger'></i>
                        </a>
                    </td>
                </tr>
                <template v-for="(subItem, index2) in item.submenus" :key="item.id">
                    <tr >
                        <td>{{index+1}}.{{index2+1}} </td>
                        <td><input :checked="subItem.checked" @change="handleSelectAll($event, [subItem])" class="form-check-input me-3 pointer" type="checkbox"></td>
                        <td>{{ _l(subItem.name) }}</td>
                        <td>{{ subItem.name }}</td>
                        <td><a @click="changeStatus({obj:subItem})" class="pointer" v-html="statusBadge(subItem.status)"></a></td>
                        <td><a @click="changeStatus({obj:item})" class="pointer" v-html="statusBadge(item.is_visible, 'visible', 'invisible')"></a></td>
                        <td>
                            <a @click="editData({id:subItem.id, modal:'fromModal'})" class="btn btn-outline-secondary action">
                                <i class='bx bxs-edit text-warning'></i>
                            </a>
                            <a @click="deleteRecord({targetId:subItem.id, listObject:dataList.data})" class="btn btn-outline-secondary action">
                                <i class='bx bxs-trash text-danger'></i>
                            </a>
                        </td>
                    </tr>
                </template>
            </template>
        </template>

        <fromModal title="Module Form" modal-size="modal-xs" @submit="submitForm({
            modal: 'fromModal',
            callback: function (retData) {
                getDataList();
            }
        })">
            <moduleForm :dependencies="pageDependencies"></moduleForm>
        </fromModal>
    </dataTable>
</template>

<style scoped>

</style>