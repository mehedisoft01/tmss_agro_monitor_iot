<script setup>
    import {dataTable,fromModal,tableTop } from '@/components';

    import {ref, onMounted,watch} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
    const {_l,can,formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies,statusBadge,changeStatus, updateId} = {
        ...useBase(),
        ...useHttp(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };
    const { Config,appConfigs} = useGetters('Config','appConfigs' );

    const tableHeaders = ref(["#", "name", "email", "user_name", "create_at", "status", "actions"]);
    const {getDataList, httpReq} = useHttp();
    const settings = ref({});
    const isRole = (roleId) => {
        return Object.keys(settings.value).find(key => settings.value[key] === String(roleId));
    };

    onMounted(() => {
        getDataList();
        getDependency({dependency : ['roles','warehouses','division','district','upazila','salesman']});
        getDependency({
            dependency: {
                settings : ['division_manager_role', 'district_manager_role', 'financial_manager_role', 'salesman_role']
            },
            callback : (retData) => {
                settings.value = retData.settings;
            }
        });
    });

    const roleSubmit = async () => {
        try {
            const payload = {
                display_name: formObject.value.display_name || '',
                name: formObject.value.name || '',
            };

            const res = await httpReq({
                url: '/api/roles',
                method: 'post',
                data: payload,
                loader: true,
            });

            closeModal('roleModel');
            getDataList();
            getDependency({ dependency: ['roles'] });

            Object.keys(formObject.value).forEach(key => {
                formObject.value[key] = '';
            });

        } catch (error) {
            console.error('Modal Submit Error:', error);

            if (error.response?.data?.result) {
                console.error('Validation Errors:', error.response.data.result);
            }
        }
    };
    const warehouseSubmit = async () => {
        try {
            const payload = {
                warehouse_name: formObject.value.warehouse_name || '',
                warehouse_code: formObject.value.warehouse_code || '',
                contact_person: formObject.value.contact_person || '',
                phone: formObject.value.phone || '',
                email: formObject.value.email || '',
                division_id: formObject.value.division_id || '',
                district_id: formObject.value.district_id || '',
                upazila_id: formObject.value.upazila_id || '',
                area: formObject.value.area || '',
            };

            const res = await httpReq({
                url: '/api/warehouses',
                method: 'post',
                data: payload,
                loader: true,
            });

            closeModal('warehouseModal');
            getDataList();
            getDependency({ dependency: ['warehouses'] });

            Object.keys(formObject.value).forEach(key => {
                formObject.value[key] = '';
            });

        } catch (error) {
            console.error('Modal Submit Error:', error);

            if (error.response?.data?.result) {
                console.error('Validation Errors:', error.response.data.result);
            }
        }
    };

    const getNameEmail = () => {
        let salesmanId = Number(formObject.value.salesman_id); // ensure number

        let salesman = pageDependencies.value.salesman.find(
            each => Number(each.id) === salesmanId
        );

        if (salesman) {
            formObject.value.name = salesman.name;
            formObject.value.email = salesman.email;
        }
    };

</script>

<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultObject="{role_id:'',manager:'',upazila_id:'',division_id:'',district_id:'',salesman_id:''}"></tableTop>
        </template>
        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td>{{parseInt(dataList.from) + index}}</td>
                <td>
                    {{`${item.salesman_name}-${item.salesman_code} (${item.role_name}) `}}
                </td>
                <td>{{ item.email }}</td>
                <td>{{ item.username }}</td>
                <td>{{ item.created_at }}</td>
                <td>
                    <a @click="changeStatus({obj:item})" class="pointer" v-html="statusBadge(item.status)"></a>

                </td>
                <td>
                    <a v-if="can('users.update')" @click="editData({data:item, id:item.id, modal:'fromModal'})" class="btn btn-outline-secondary action">
                        <i class='bx bxs-edit text-warning'></i>
                    </a>
                    <a v-if="can('users.destroy')" @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})" class="btn btn-outline-secondary action">
                        <i class='bx bxs-trash text-danger'></i>
                    </a>
                </td>
            </tr>
        </template>

        <fromModal modal-size="modal-lg" @submit="submitForm({
            modal: 'fromModal',
            callback: function (retData) {
                Object.assign(formObject, {});
                getDataList();
            }
        })">
            <div class="row mb-2">
                <div class="col-md-4 d-flex align-items-center">
                    <strong>{{ _l('staff') }} :</strong>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white ms-2" style="width:26px; height:26px; cursor:pointer;" @click="openModal({ modalId: 'warehouseModal' })"><i class="bx bx-plus"></i></span>
                </div>
                <div class="col-md-8">
                    <select v-select2 @change="getNameEmail()" v-model="formObject.salesman_id" class="form-select" name="salesman_id"  :data-placeholder="_l('select_staff')">
                        <option value="">{{_l('select_staff')}}</option>
                        <template v-for="item in pageDependencies.salesman">
                            <option :value="item.id">{{item.name}}-{{item.salesman_code}} ({{item.designation_name}})</option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4 d-flex align-items-center">
                    <strong>Role :</strong>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white ms-2" style="width:26px; height:26px; cursor:pointer;" @click="openModal({ modalId: 'roleModel' })"><i class="bx bx-plus"></i></span>
                </div>
                <div class="col-md-8">
                    <select v-model="formObject.role_id" class="form-control">
                        <option value="">Select</option>
                        <template v-for="role in pageDependencies.roles">
                            <option :value="role.id">{{role.display_name}}</option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>Name : </strong></label>
                <div class="col-md-8">
                    <input readonly type="text" v-model="formObject.name" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>Email :</strong></label>
                <div class="col-md-8">
                    <input readonly type="text" v-model="formObject.email" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>Username : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.username" class="form-control"/>
                </div>
            </div>
            <div class="row mb-2">
                <label class="col-md-4"><strong>Password : </strong></label>
                <div class="col-md-8">
                    <input type="text" v-model="formObject.password" class="form-control"/>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4 d-flex align-items-center">
                    <strong>warehouse :</strong>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white ms-2" style="width:26px; height:26px; cursor:pointer;" @click="openModal({ modalId: 'warehouseModal' })"><i class="bx bx-plus"></i></span>
                </div>
                <div class="col-md-8">
                    <select v-select2 v-model="formObject.warehouse_id" class="form-select" name="warehouse_id"  :data-placeholder="'-- Select Warehouse --'">
                        <option value="">-- Select warehouse --</option>
                        <template v-for="item in pageDependencies.warehouses">
                            <option :value="item.id">{{item.warehouse_name}}</option>
                        </template>
                    </select>
                </div>
            </div>
            <template v-if="!formObject.warehouse_id">
                <div class="row mb-2" v-if="['division_manager_role','district_manager_role'].includes(isRole(formObject.role_id))">
                    <label class="form-label col-md-4"><strong>{{ _l('division') }}:</strong></label>
                    <div class="col-md-8">
                        <select v-select2 v-model="formObject.division_id" class="form-control" :data-placeholder="'select_division'">
                            <option value="">{{_l('select_division')}}</option>
                            <template v-for="item in pageDependencies.division">
                                <option :value="item.id">{{ item.division_name }}</option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="row mb-2" v-if="['district_manager_role'].includes(isRole(formObject.role_id))">
                    <label class="form-label col-md-4"><strong>{{ _l('district') }}:</strong></label>
                    <div class="col-md-8">
                        <select v-select2 v-model="formObject.district_id" class="form-control" :data-placeholder="'select_district'">
                            <option value="">{{_l('select_district')}}</option>
                            <template v-for="item in pageDependencies.district">
                                <option v-if="item.division_id === formObject.division_id" :value="item.id">{{ item.district_name }}</option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-4"><strong>Is SuperAdmin : </strong></label>
                    <div class="col-md-8">
                        <input type="checkbox" class="boxsize" v-model="formObject.is_superadmin" :true-value="1" :false-value="0">
                    </div>
                </div>
            </template>
        </fromModal>
    </dataTable>
    <fromModal modal-id="roleModel" @submit="roleSubmit">
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
    <fromModal modal-id="warehouseModal"  @submit="warehouseSubmit">
        <div class="row mb-2">
            <label class="col-md-4"><strong>{{_l('warehouse_name')}} : </strong></label>
            <div class="col-md-8">
                <input type="text" v-model="formObject.warehouse_name" class="form-control"/>
            </div>
        </div>
        <div class="row mb-2">
            <label class="col-md-4"><strong>{{_l('warehouse_code') }}: </strong></label>
            <div class="col-md-8">
                <input type="text" v-model="formObject.warehouse_code" class="form-control" />
            </div>
        </div>
        <div class="row mb-2">
            <label class="col-md-4"><strong>{{_l('contact_person')}} : </strong></label>
            <div class="col-md-8">
                <input type="text" v-model="formObject.contact_person" class="form-control" />
            </div>
        </div>
        <div class="row mb-2">
            <label class="col-md-4"><strong>{{_l('phone')}} : </strong></label>
            <div class="col-md-8">
                <input type="text" v-model="formObject.phone" class="form-control" />
            </div>
        </div>
        <div class="row mb-2">
            <label class="col-md-4"><strong>{{_l('email')}} : </strong></label>
            <div class="col-md-8">
                <input type="text" v-model="formObject.email" class="form-control"/>
            </div>
        </div>
        <div class="row mb-2">
            <label class="form-label col-md-4"><strong>{{ _l('division') }}:</strong></label>
            <div class="col-md-8">
                <select v-select2 v-model="formObject.division_id" class="form-control" :data-placeholder="'select_division'">
                    <option value="">{{_l('select_division')}}</option>
                    <template v-for="item in pageDependencies.division">
                        <option :value="item.id">{{ item.division_name }}</option>
                    </template>
                </select>
            </div>
        </div>

        <div class="row mb-2">
            <label class="form-label col-md-4"><strong>{{ _l('district') }}:</strong></label>
            <div class="col-md-8">
                <select v-select2 v-model="formObject.district_id" class="form-control" :data-placeholder="'select_district'">
                    <option value="">{{_l('select_district')}}</option>
                    <template v-for="item in pageDependencies.district">
                        <option v-if="item.division_id === formObject.division_id" :value="item.id">{{ item.district_name }}</option>
                    </template>
                </select>
            </div>
        </div>
        <div class="row mb-2">
            <label class="form-label col-md-4"><strong>{{ _l('upazila') }}:</strong></label>
            <div class="col-md-8">
                <select v-select2 v-model="formObject.upazila_id" class="form-control" :data-placeholder="'select_upazila'">
                    <option value="">{{_l('select_upazila')}}</option>
                    <template v-for="item in pageDependencies.upazila">
                        <option v-if="item.district_id === formObject.district_id" :value="item.id">{{ item.upazila_name }}</option>
                    </template>
                </select>
            </div>
        </div>
        <div class="row mb-2">
            <label class="col-md-4"><strong>{{_l('area')}} : </strong></label>
            <div class="col-md-8">
                <textarea type="text" v-model="formObject.area" class="form-control" />
            </div>
        </div>
    </fromModal>
</template>
<style scoped>
    .boxsize {
        width: 20px;
        height: 20px;
    }
</style>
