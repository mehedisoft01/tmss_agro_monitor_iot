export function useInitials(routes) {
    const loadLocaleMessages  = async () => {
        const response = await fetch(`${baseUrl}/locale.json`);
        if (!response.ok) {
            throw new Error(`Failed to load ${locale} locale`);
        }
        return await response.json();
    };
    const addLocaleToJson  = async (item) => {
        return await fetch(`${baseUrl}/update.json?item=${item}`);
    };

    const loadBackendRoutes = async () => {
        const response = await fetch(`${baseUrl}/routes.json`);
        if (!response.ok) {
            throw new Error(`Failed to load routes`);
        }
        return await response.json();
    };

    const loanInitialJson = async () => {
        const response = await fetch(`${baseUrl}/load.json`);
        if (!response.ok) {
            throw new Error(`Failed to load ${locale} locale`);
        }
        return await response.json();
    };

    // Glob all Vue files in your views folder
    const modules = import.meta.glob('/resources/js/views/**/*.vue');

    const mapRoutes = (routes) => {
        return routes.map(route => {
            const newRoute = {
                path: route.path,
                name: route.name,
                icon: route.icon,
                meta: route.meta || {},
            };

            if (route.component) {
                const key = `/resources/js/${route.component}`;
                if (modules[key]) {
                    newRoute.component = modules[key];
                }
            }
            if (route.alias) {
                newRoute.alias = route.alias;
            }
            if (route.children && route.children.length > 0) {
                newRoute.children = mapRoutes(route.children);
            }
            return newRoute;
        });
    };


    return {
        loadLocaleMessages,
        loadBackendRoutes,
        loanInitialJson,
        mapRoutes,
        addLocaleToJson
    }
}
