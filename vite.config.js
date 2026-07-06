import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
        VitePWA({
            registerType: "autoUpdate",
            injectRegister: "inline",
            workbox: {
                globDirectory: "public",
                globPatterns: [
                    "build/assets/*.{js,css,ico,png,svg,webp}",
                    "build/manifest.json",
                ],
                cleanupOutdatedCaches: true,
                clientsClaim: true,
                skipWaiting: true,
            },
            manifest: {
                name: "Movimento Canônico",
                short_name: "Movimento",
                description: "Movimento Canônico App",
                theme_color: "#6366f1",
                background_color: "#fafafa",
                display: "standalone",
                start_url: "/",
                icons: [
                    {
                        src: "icons/icon-192x192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "icons/icon-512x512.png", // ← obrigatório
                        sizes: "512x512",
                        type: "image/png",
                    },
                    {
                        src: "icons/icon-maskable-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                        purpose: "maskable", // ← para Android
                    },
                ],
            },
        }),
    ],
});
