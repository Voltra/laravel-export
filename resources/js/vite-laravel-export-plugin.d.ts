import type { URL } from "node:url";
import { Plugin } from "vite";

/// <reference types="vite/client" />


declare function asExportUrl(url: URL): URL;
declare function asExportUrl(url: string): URL;

declare function plugin(exportRootUri?: string): Plugin;

interface ImportMetaEnv extends Readonly<Record<string, string>> {
    readonly EXPORT_BASE_URL?: string;
    readonly APP_URL: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}


export {
    asExportUrl,
    plugin,
    plugin as default,
    ImportMeta,
    ImportMetaEnv,
}
