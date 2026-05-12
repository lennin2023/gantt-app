export interface DashboardMetrics {
    total_projects: number;
    active_projects: number;
    completed_projects: number;
    overall_progress: number;
}

export interface DashboardProject {
    id: number;
    name: string;
    color: string;
    status_name: string;
    status_color: string;
    progress: number;
    total_tasks: number;
}

export interface DashboardStats {
    metrics: DashboardMetrics;
    projects: DashboardProject[];
}
