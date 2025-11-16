import { URL } from "node:url";

/**
 * Map an app URL to its exported app URL
 * @param {URL|string} url - The URL to map
 * @returns {URL}
 */
export const asExportUrl = url => {
    const urlStr = url instanceof URL ? url.toString() : url;

    if (!import.meta.env?.EXPORT_BASE_URL) {
        return url instanceof URL ? url : new URL(url);
    }

    /**
     * @type {string}
     */
    const exportBaseUrl = import.meta.env?.EXPORT_BASE_URL;

    /**
     * @type {string}
     */
    const appBaseUrl = import.meta.env.APP_URL;

    const mappedUrlStr = urlStr.replace(appBaseUrl, exportBaseUrl);

    return new URL(mappedUrlStr, exportBaseUrl);
};


/**
 * @type {(exportRootUri?: string) => import("vite").Plugin}
 */
export const plugin = (exportBaseRootUri = process.env.EXPORT_BASE_URL) => ({
    name: "laravel-export",
    apply: "build",
    enforce: "pre",
    configEnvironment: {
        order: "pre",
        handler(name, config, env) {
            if (env.isSsrTargetWebworker) {
                return;
            }

            // If we have a non empty `exportBaseRootUri`, then we stringify that
            // otherwise we want null-checks to be valid as well as empty checks,
            // so we stringify null instead
            config.define.EXPORT_BASE_URL = JSON.stringify(exportBaseRootUri ? exportBaseRootUri : null);

            // We consider `APP_URL` to always be defined
            config.define.APP_URL = JSON.stringify(process.env.APP_URL);
        },
    },
});

export default plugin
