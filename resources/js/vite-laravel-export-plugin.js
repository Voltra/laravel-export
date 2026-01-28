/**
 * Normalizes URLs for use in this package
 * @param {string} url
 * @returns {string}
 */
const normalizeUrl = url => url.endsWith("/") ? url.slice(0, -1) : url;

/**
 * Map an app URL to its exported app URL
 * @param {URL|string} url - The URL to map
 * @returns {URL}
 */
export const asExportUrl = url => {
    const urlStr = url instanceof URL ? url.toString() : url;

    if (!import.meta.env?.EXPORT_BASE_URL) {
        return url instanceof URL ? url : new URL(url, location.href);
    }

    /**
     * @type {string}
     */
    const exportBaseUrl = normalizeUrl(import.meta.env?.EXPORT_BASE_URL);

    /**
     * @type {string}
     */
    const appBaseUrl = normalizeUrl(import.meta.env.APP_URL);

    const mappedUrlStr = urlStr
        .replace(`${appBaseUrl}/`, `${exportBaseUrl}/`)
        .replace(appBaseUrl, exportBaseUrl);

    return new URL(mappedUrlStr, exportBaseUrl);
};


/**
 * vite-laravel-export-plugin
 * @type {(exportRootUri?: string) => import("vite").Plugin}
 * @param {string|undefined} [exportRootUri] - The export root URI to use (defaults to your `process.env.EXPORT_BASE_URL`)
 * @returns {import("vite").Plugin}
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

            config.define ??= {};

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
