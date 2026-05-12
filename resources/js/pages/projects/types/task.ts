import type { TaskStatus } from '@/shared/types/enums';

export interface CreateTaskPayload {
    project_id: number;
    name: string;
    description?: string | null;
    assignee?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    progress?: number;
    status?: TaskStatus;
    order?: number;
    dependency_ids?: number[];
}

export type UpdateTaskPayload = Partial<CreateTaskPayload>;
