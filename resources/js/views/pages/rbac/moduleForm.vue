<script setup>
    import {appStore} from "@/lib";
    const { useGetters, formObjectField} = appStore();
    let {formObject} = useGetters('formObject');

    const props = defineProps({
        formObject: {type: Object, default: () => ({})},
        dependencies: {type: Object, default: () => ({})},
    });
    const pageDependencies = props.dependencies;

    const checkUncheck = ($event, permissions) => {
        let savPermissions = [...formObject.value.permissions];
        permissions.forEach((value, index) => {
            if ($event.target.checked && !savPermissions.includes(value)) {
                savPermissions.push(value);
            } else {
                let index = savPermissions.indexOf(value);
                if (index !== -1) {
                    savPermissions.splice(index, 1);
                }
            }
        });
        formObjectField('permissions', savPermissions);
    };
</script>
<template>
    <div class="row mb-2">
        <label class="col-md-4"><strong>Name :</strong></label>
        <div class="col-md-8">
            <input v-validate="'required'" v-model="formObject.name" class="form-control" />
        </div>
    </div>
    <div class="row mb-2">
        <label class="col-md-4"><strong>Link : </strong></label>
        <div class="col-md-8">
            <input v-validate="'required'" v-model="formObject.link" name="link" class="form-control" />
        </div>
    </div>
    <div class="row mb-2">
        <label class="col-md-4"><strong>Component : </strong></label>
        <div class="col-md-8">
            <select v-model="formObject.component" name="component" class="form-control">
                <option value="">Select</option>
                <option value="#">Parent Menu</option>
                <template v-for="role in pageDependencies.components">
                    <option :value="role">{{role}}</option>
                </template>
            </select>
        </div>
    </div>
    <div class="row mb-2">
        <label class="col-md-4"><strong>Icon : </strong></label>
        <div class="col-md-8">
            <select class="form-control" v-model="formObject.icon">
                <option value="">Select</option>
                <template v-for="role in pageDependencies.icons">
                    <option :value="role">{{role}}</option>
                </template>
            </select>
        </div>
    </div>
    <div class="row mb-2">
        <label class="col-md-4"><strong>Visibility : </strong></label>
        <div class="col-md-8">
            <select v-model="formObject.is_visible" class="form-control">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
    </div>

    <hr>
    <div class="row mb-2">
        <label class="col-md-4 pointer" for="allPermissions">
            <strong>Permission : </strong>
            <input @change="checkUncheck($event, pageDependencies.permissions)" class="form-check-input" type="checkbox" id="allPermissions" value="all">
        </label>
        <div class="col-md-8" v-if="formObject.permissions !== undefined">
            <div class="form-check form-check-inline" v-for="permission in pageDependencies.permissions">
                <input class="form-check-input" @change="checkUncheck($event, [permission])" type="checkbox" :checked="formObject.permissions.includes(permission)" :id="permission" :value="permission">
                <label class="form-check-label text-uppercase pointer" :for="permission">{{permission}}</label>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>