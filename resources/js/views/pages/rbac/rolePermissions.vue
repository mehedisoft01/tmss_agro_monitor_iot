<script setup>
    import {dataTable,fromModal,tableTop } from '@/components';

    import {ref, onMounted, computed} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency,} = {...useHttp()};
    const {formFilter, toaster, dataList, httpRequest, pageDependencies} = {
        ...useBase(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#",'Name','Permissions', ' ']);
    const {getDataList, httpReq,urlGenerate} = useHttp();

    formFilter.value.role_id = '';
    const reqData = ref({
        role_id: '',
        module_id: '',
        permissions: [],
        type: '',
        data_type: ''
    });

    const allModules = computed(() => dataList.value.all_modules || {});
    const modules = computed(() => dataList.value.modules || []);
    const permissions = computed(() => dataList.value.permissions || []);

function setAll(event) {
    reqData.value.role_id = formFilter.value.role_id;
    reqData.value.data_type = 'all';
    reqData.value.type = event.target.checked ? 'insert' : 'remove';

    httpReq({url: urlGenerate(), method: 'post',data: reqData.value,loader: true}).then(() => {
        resetReqData();
        getDataList({clearList:false});
    });
}

function selectModule(event, module) {
    if (!formFilter.value.role_id) {
        toaster('error', 'You must select a role first');
        return;
    }
    const checked = event.target.checked;
    reqData.value.role_id = formFilter.value.role_id;
    reqData.value.module_id = module.id;
    reqData.value.type = checked ? 'insert' : 'remove';
    reqData.value.permissions = module.permissions.map(p => p.id);

    httpReq({url: urlGenerate(), method: 'post',data: reqData.value,loader: true}).then(() => {
        resetReqData();
        getDataList({clearList:false});
    });
}

function selectPermissions(event, id) {
     if (!formFilter.value.role_id) {
        toaster('error', 'You must select a role first');
        return;
    }
    const checked = event.target.checked;
    reqData.value.role_id = formFilter.value.role_id;
    reqData.value.type = checked ? 'insert' : 'remove';
    reqData.value.permissions = [id];

    httpReq({url: urlGenerate(), method: 'post',data: reqData.value,loader: true}).then(() => {
        resetReqData();
        getDataList({clearList:false});
    });
}

function resetReqData() {
    reqData.value = { role_id: '', module_id: '', permissions: [], type: '', data_type: '' };
}


    onMounted(() => {
        getDataList();
        getDependency({dependency : ['roles']});
    });
</script>

<template>
  <dataTable :headings="tableHeaders" :setting="true">
    <template v-slot:tableTop>
      <tableTop :defaultObject="{}" :defaultAddButton="false">
        <template v-slot:filter>
          <div class="col-md-2">
            <select class="form-control pointer" v-model="formFilter.role_id" @change="getDataList">
              <option value="">Select Role</option>
                <template v-for="role in pageDependencies.roles">
                        <option :value="role.id">{{role.display_name}}</option>
                </template>
            </select>
          </div>
        </template>
      </tableTop>
    </template>
      <template v-slot:topRight>
        <button class="btn btn-info btn-flat">
            <label class="pointer">
              <input type="checkbox" @change="setAll"> Select All
            </label>
          </button>
    </template>

    <template v-slot:data>
      <template v-for="(module, index) in allModules.data" :key="module.id">
        <!-- Parent module row -->
        <tr class="parent">
          <td class="fw-medium">{{ index + 1 }}</td>
          <td>
            <label class="form-check-label" :for="'module-' + module.id">
              <input
                class="form-check-input"
                type="checkbox"
                :id="'module-' + module.id"
                :checked="modules.includes(module.id)"
                @change="selectModule($event, module)"
              >
              {{ module.name }}
            </label>
          </td>
          <td>
            <div class="row">
              <div class="col-md-3" v-for="perm in module.permissions" :key="perm.id">
                <div class="form-check form-check-outline form-check-secondary">
                  <label class="form-check-label" :for="'perm-' + perm.id">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :id="'perm-' + perm.id"
                      :checked="permissions.includes(perm.id)"
                      @change="selectPermissions($event, perm.id)"
                    >
                    {{ perm.name }}
                  </label>
                </div>
              </div>
            </div>
          </td>
          <td></td>
        </tr>

        <!-- Submenus -->
        <template v-for="(submenu, sIndex) in module.submenus" :key="submenu.id">
          <tr class="child">
            <td class="fw-medium">{{ index + 1 }}.{{ sIndex + 1 }}</td>
            <td>
              <label class="form-check-label" :for="'submenu-' + submenu.id">
                <input
                  class="form-check-input"
                  type="checkbox"
                  :id="'submenu-' + submenu.id"
                  :checked="modules.includes(submenu.id)"
                  @change="selectModule($event, submenu)"
                >
                {{ submenu.name }}
              </label>
            </td>
            <td>
              <div class="row">
                <div class="col-md-3" v-for="perm in submenu.permissions" :key="perm.id">
                  <div class="form-check form-check-outline form-check-secondary">
                    <label class="form-check-label" :for="'perm-' + perm.id">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        :id="'perm-' + perm.id"
                        :checked="permissions.includes(perm.id)"
                        @change="selectPermissions($event, perm.id)"
                      >
                      {{ perm.name }}
                    </label>
                  </div>
                </div>
              </div>
            </td>
            <td></td>
          </tr>

          <!-- Last level submenu -->
          <template v-for="(lastSub, lIndex) in submenu.submenus" :key="lastSub.id">
            <tr class="last_child">
              <td class="fw-medium">{{ index + 1 }}.{{ sIndex + 1 }}.{{ lIndex + 1 }}</td>
              <td>
                <label class="form-check-label" :for="'last-sub-' + lastSub.id">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    :id="'last-sub-' + lastSub.id"
                    :checked="modules.includes(lastSub.id)"
                    @change="selectModule($event, lastSub)"
                  >
                  {{ lastSub.name }}
                </label>
              </td>
              <td>
                <div class="row">
                  <div class="col-md-3" v-for="perm in lastSub.permissions" :key="perm.id">
                    <div class="form-check form-check-outline form-check-secondary">
                      <label class="form-check-label" :for="'perm-' + perm.id">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          :id="'perm-' + perm.id"
                          :checked="permissions.includes(perm.id)"
                          @change="selectPermissions($event, perm.id)"
                        >
                        {{ perm.name }}
                      </label>
                    </div>
                  </div>
                </div>
              </td>
              <td></td>
            </tr>
          </template>
        </template>
      </template>
    </template>
  </dataTable>
</template>

<style scoped>
label{
    cursor: pointer;
}
</style>


