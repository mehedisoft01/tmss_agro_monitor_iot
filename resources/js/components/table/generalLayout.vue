<script setup>
    import {tableHeader, tableTop, pageTop} from "@/components";
    import {pagination} from "@/plugins";

    import {appStore, useHttp} from "@/lib";

    const {useGetters, getDataList, dataList, httpRequest} = {
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters('httpRequest')
    };

    const props = defineProps({
        headings: Array,
        loader: {type:Boolean, default:true},
        defaultPagination: {type: Boolean, default: true},
        defaultObject: {type: Object, default: () => ({})},
    });

    const headings = props.headings ?? [];

    defineEmits(['page-change'])

</script>


<template>
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <slot name="pageTop"></slot>
        </div>
        <div class="card">
            <div class="card-body data-table">
               <slot></slot>
            </div>
        </div>

        <div class="page_loader" v-if="loader && httpRequest">
            <i class='bx bx-loader bx-spin text-warning'></i>
        </div>
    </div>
</div>
</template>
