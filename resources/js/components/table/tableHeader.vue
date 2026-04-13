<script setup>
    import {useBase} from "@/lib";
    const {_l, handleSelectAll} = useBase();

    const props = defineProps({
        headings: Array,
    });
    const headings = props.headings ?? [];

    const noPrintNames = ['action', 'actions','items'];
    function isNoPrintHeading(h) {
        return noPrintNames.includes(h.toString().trim().toLowerCase()) ? 'no-print' : '';
    }

</script>

<template>
    <template v-if="headings.length > 0">
        <template v-for="hading in headings">
            <template v-if="typeof hading === 'object' && hading.listObject !== undefined && hading.listObject.data !== undefined">
               <th class="checkbox">
                   <div class="d-flex align-items-center">
                       <div>
                           <input @change="handleSelectAll($event, hading.listObject.data)" class="form-check-input me-3 pointer" type="checkbox">
                       </div>
                       <div class="ms-2">
                           <h6 class="mb-0 font-14">{{hading.name}}</h6>
                       </div>
                   </div>
               </th>
            </template>
            <template v-else>
                <th :class="isNoPrintHeading(hading)">
                    <span v-if="typeof hading !== 'object'">{{_l(hading)}}</span>
                </th>
            </template>
        </template>
    </template>
</template>

<style scoped>

</style>
