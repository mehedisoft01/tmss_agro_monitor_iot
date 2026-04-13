import {computed} from "vue";
import {useStore} from 'vuex';

export function appStore() {
    const store = useStore();

    const formFilter = computed({
        get() {
            return store.getters.formFilter;
        },
        set(value) {
            store.commit('formFilter', value);
        }
    });
    const dataList = computed({
        get() {
            return store.getters.dataList;
        },
        set(value) {
            store.commit('dataList', value);
        }
    });
    const allMenus = computed({
        get() {
            return store.getters.allMenus;
        },
        set(value) {
            store.commit('allMenus', value);
        }
    });
    const Permissions = computed({
        get() {
            return store.getters.Config.permissions;
        },
        set(value) {
            store.commit('Permissions', value);
        }
    });
    const formObject = computed({
        get: () => store.state.formObject,
        set: (val) => store.commit('setForm', val)
    });
    const assignStore = (stateName, resetObject = {}) => {
        store.commit(stateName, resetObject);
    };
    const formObjectField = (key, value) => {
        store.commit('formObjectField', { key, value: value });
    };

    const setState = (state, key, value) => {
        state.value = {
            ...state.value,
            [key]: value
        };
    };
    const useGetters = (...names) => {
        const result = {};
        names.forEach(name => {
            result[name] = computed(() => store.getters[name]);
        });
        return result;
    };

    return{
        formFilter,
        formObject,
        dataList,
        allMenus,
        Permissions,
        useGetters,
        setState,
        formObjectField,
        assignStore
    }
}
