export const getters = {
    modalTitle(state) {
        return state.modalTitle;
    },
    formObject(state) {
        return state.formObject;
    },
    formFilter(state) {
        return state.formFilter;
    },
    formType(state) {
        return state.formType;
    },
    dataList(state) {
        return state.dataList;
    },
    updateId(state) {
        return state.updateId;
    },
    filter(state) {
        return state.filter;
    },
    httpRequest(state) {
        return state.httpRequest;
    },
    pageDependencies(state) {
        return state.pageDependencies;
    },
    Config(state) {
        return state.Config;
    },
    allMenus(state) {
        return state.allMenus;
    },
    localization(state) {
        return state.localization;
    },
    layersConfig(state) {
        return state.layersConfig;
    },
    Permissions(state) {
        return state.Permissions;
    },
    currentPagination(state) {
        return state.currentPagination;
    },

    Messages(state) {
        return state.Messages;
    },
    detailsData(state) {
        return state.detailsData;
    },
    uploadProgress(state) {
        return state.uploadProgress;
    },
    user(state) {
        return state.currentUser !== null && state.currentUser !== undefined ? state.currentUser : {};
    },
    useDynamicHead(state) {
        return state.useDynamicHead;
    },
    authUser(state) {
        return state.authUser;
    },
    previousDataData(state) {
        return state.previousDataData;
    },
    currentDate(state) {
        return state.currentDate;
    },
    currentDateTime(state) {
        return state.currentDateTime;
    },
    appNotifications(state) {
        return state.appNotifications;
    },
    appConfigs(state) {
        return state.appConfigs;
    },
};
