import {createStore} from 'vuex';

import {actions} from "./actions";
import {getters} from "./getters";
import {mutations} from "./mutations";
import {state} from "./state";


export const store = createStore({
    state,
    actions,
    mutations,
    getters,
});