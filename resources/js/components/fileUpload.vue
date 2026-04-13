<script setup>
    import {defineProps} from 'vue';
    import {useBase, useHttp} from "../lib";
    const {clickFile, uploadFile, getImage} = {...useHttp(), ...useBase()};

    const props = defineProps({
        object : {type : Object, default : {}},
        column : {type : String, default: 'file'},
        height : {type : Number, default: 120}
    });

</script>
<template>
    <div class="drop_zone pointer" @click="clickFile(column + '_' + object.id)"
         :style="{ height: `${height}px`, backgroundImage: `url(${getImage(object[column], '../backend/images/upload.png')})` }">
        <input @change="uploadFile($event, {imageObject:object, dataModel:column})" type="file" :id="column + '_' + object.id" class="form-control file_upload" style="display: none;"/>
    </div>
</template>

<style scoped>

</style>