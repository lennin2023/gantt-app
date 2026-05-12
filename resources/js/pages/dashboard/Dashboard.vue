<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { API } from '@/api';
import type {
    DashboardMetrics,
    DashboardProject,
} from '@/pages/dashboard/types/dashboard';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const loading = ref(true);
const metrics = ref<DashboardMetrics>({
    total_projects: 0,
    active_projects: 0,
    completed_projects: 0,
    overall_progress: 0,
});
const projects = ref<DashboardProject[]>([]);

const loadStats = async (): Promise<void> => {
    try {
        const response = await API.dashboard.getStats();
        metrics.value = response.metrics;
        projects.value = response.projects;
    } catch (error) {
        console.error('Failed to load dashboard stats:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadStats);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <div v-if="loading" class="flex h-64 items-center justify-center">
            <div class="text-sm text-muted-foreground">Cargando...</div>
        </div>

        <template v-else>
            <!-- Métricas -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg bg-muted p-4">
                    <p class="mb-1 text-sm text-muted-foreground">Proyectos</p>
                    <p class="text-2xl font-medium">
                        {{ metrics.total_projects }}
                    </p>
                    <p class="text-xs text-muted-foreground">total</p>
                </div>
                <div class="rounded-lg bg-muted p-4">
                    <p class="mb-1 text-sm text-muted-foreground">Activos</p>
                    <p class="text-2xl font-medium">
                        {{ metrics.active_projects }}
                    </p>
                    <p class="text-xs text-muted-foreground">en curso</p>
                </div>
                <div class="rounded-lg bg-muted p-4">
                    <p class="mb-1 text-sm text-muted-foreground">
                        Completados
                    </p>
                    <p class="text-2xl font-medium">
                        {{ metrics.completed_projects }}
                    </p>
                    <p class="text-xs text-muted-foreground">finalizados</p>
                </div>
                <div class="rounded-lg bg-muted p-4">
                    <p class="mb-1 text-sm text-muted-foreground">Progreso</p>
                    <p class="text-2xl font-medium">
                        {{ metrics.overall_progress }}%
                    </p>
                    <p class="text-xs text-muted-foreground">
                        promedio general
                    </p>
                </div>
            </div>

            <!-- Tabla -->
            <div class="rounded-xl border">
                <div class="border-b px-5 py-4">
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Proyectos recientes
                    </p>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th
                                class="px-5 py-3 text-left text-xs font-normal text-muted-foreground"
                            >
                                Nombre
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-normal text-muted-foreground"
                            >
                                Estado
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-normal text-muted-foreground"
                            >
                                Progreso
                            </th>
                            <th
                                class="px-5 py-3 text-right text-xs font-normal text-muted-foreground"
                            >
                                Tareas
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="project in projects"
                            :key="project.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                                        :style="{ background: project.color }"
                                    />
                                    <span class="text-sm">{{
                                        project.name
                                    }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded px-2 py-1 text-xs"
                                    :style="{
                                        background: project.status_color + '22',
                                        color: project.status_color,
                                    }"
                                >
                                    {{ project.status_name }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full rounded-full"
                                            :style="{
                                                width: project.progress + '%',
                                                background: project.color,
                                            }"
                                        />
                                    </div>
                                    <span
                                        class="w-8 text-right text-xs text-muted-foreground"
                                    >
                                        {{ project.progress }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ project.total_tasks }}
                            </td>
                        </tr>
                        <tr v-if="projects.length === 0">
                            <td
                                colspan="4"
                                class="px-5 py-8 text-center text-sm text-muted-foreground"
                            >
                                No hay proyectos aún
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
