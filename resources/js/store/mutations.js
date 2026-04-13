export const mutations = {
    modalTitle(state, title) {
        state.modalTitle = title;
    },
    formObject(state, object) {
        state.formObject = {...object};
    },
    resetForm(state, defaultObj = {}) {
        state.formObject = { ...defaultObj };
    },
    formObjectField(state, { key, value }) {
        state.formObject[key] = value;
    },
    formFilter(state, object) {
        state.formFilter = object;
    },
    formType(state, type) {
        state.formType = type;
    },
    dataList(state, data) {
        state.dataList = data;
    },
    updateId(state, id) {
        state.updateId = id;
    },
    Config(state, data) {
        state.Config = data;
    },
    allMenus(state, data) {
        state.allMenus = data;
    },
    localization(state, data) {
        state.localization = data;
    },
    layersConfig(state, data) {
        state.layersConfig = data;
    },
    Permissions(state, data) {
        state.Permissions = data;
    },
    httpRequest(state, data) {
        state.httpRequest = data;
    },
    pageDependencies(state, { key, value }) {
        state.pageDependencies[key] = value
    },
    currentPage(state, data) {
        state.currentPage = data;
    },

    currentPagination(state, data) {
        state.currentPagination = data;
    },
    detailsData(state, data) {
        state.detailsData = data;
    },
    uploadProgress(state, data) {
        state.uploadProgress = data;
    },
    useDynamicHead(state, head) {
        state.useDynamicHead = head;
    },
    authUser(state, head) {
        state.authUser = head;
    },
    previousDataData(state, head) {
        state.previousDataData = head;
    },
    appNotifications(state, data) {
        state.appNotifications = data;
    },
    appConfigs(state, data) {
        state.appConfigs = data;
    },
};
