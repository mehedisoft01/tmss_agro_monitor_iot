<script setup>
    import {dataTable,tableTop } from '@/components';

    import {ref, onMounted} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {statusBadge, dataList,changeStatus} = {
        ...useBase(),
        ...useHttp(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#",'Name','Status','Permitted Roles', ' ']);
    const {getDataList} = useHttp();

    onMounted(() => {
        getDataList();
    });
</script>

<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultObject="{}" :defaultAddButton="false"></tableTop>
        </template>
        <template v-slot:data>
              <tr v-for="(data, index) in dataList.data">
                    <td class="fw-medium">{{parseInt(dataList.from)+index}}</td>
                    <td>{{data.name}}</td>
                    <td>
                        <a @click="changeStatus({obj:data})" class="pointer" v-html="statusBadge(data.status)"></a>
                    </td>
                    <td>
                        <template v-for="(role, index) in data.role_modules">
                            <a class="btn btn-outline-danger">{{role.display_name}}</a>
                        </template>
                    </td>
                    <td></td>
                </tr>
        </template>
    </dataTable>

</template>
