import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/login.css",

                "resources/css/admin-dashboard.css",
                "resources/css/admin-houses.css",
                "resources/css/admin-record-house.css",
                "resources/css/admin-sensor-maintenance.css",
                "resources/css/admin-sensors.css",
                "resources/css/admin-shared.css",
                "resources/css/admin-workers.css",

                "resources/css/manager-biosecurity-logs.css",
                "resources/css/manager-dashboard.css",
                "resources/css/manager-houses.css",
                "resources/css/manager-inventory.css",
                "resources/css/manager-management.css",
                "resources/css/manager-record-house.css",
                "resources/css/manager-record-inventory.css",
                "resources/css/manager-reports.css",
                "resources/css/manager-sensor-maintenance.css",
                "resources/css/manager-sensors.css",
                "resources/css/manager-shared.css",
                "resources/css/manager-tasks.css",
                "resources/css/manager-workers.css",

                "resources/js/app.js",
                "resources/js/login.js",

                "resources/js/admin-dashboard.js",
                "resources/js/admin-houses.js",
                "resources/js/admin-record-house.js",
                "resources/js/admin-sensor-maintenance.js",
                "resources/js/admin-sensors.js",
                "resources/js/admin-workers.js",

                "resources/js/manager-biosecurity-logs.js",
                "resources/js/manager-dashboard.js",
                "resources/js/manager-houses.js",
                "resources/js/manager-inventory.js",
                "resources/js/manager-record-house.js",
                "resources/js/manager-record-inventory.js",
                "resources/js/manager-reports.js",
                "resources/js/manager-sensor-maintenance.js",
                "resources/js/manager-sensors.js",
                "resources/js/manager-tasks.js",
                "resources/js/manager-workers.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
